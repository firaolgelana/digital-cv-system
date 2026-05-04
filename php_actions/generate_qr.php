<?php
// ============================================================
//  QR Code and Public CV Actions
//  GET  ?action=get_qr
//  GET  ?action=get_public_cv&token=
//  POST action=generate_qr|track_copy|track_download
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

startSession();
header('Content-Type: application/json');

$pdo = getDB();
ensureQrColumns($pdo);

$method = $_SERVER['REQUEST_METHOD'];
$data = [];
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
}
$action = $method === 'GET'
    ? ($_GET['action'] ?? 'get_qr')
    : ($data['action'] ?? '');

try {
    if ($method === 'GET' && $action === 'get_public_cv') {
        $token = trim((string) ($_GET['token'] ?? ''));
        if ($token === '') {
            jsonResponse(false, 'Missing QR token.');
        }

        $publicCv = getPublicCvByToken($pdo, $token);
        if (!$publicCv) {
            http_response_code(404);
            jsonResponse(false, 'This CV link is invalid or unavailable.');
        }

        logQrAccess($pdo, $publicCv['qr_id']);

        jsonResponse(true, 'ok', [
            'student' => formatPublicStudent($publicCv),
            'meta' => [
                'token' => $token,
                'generatedAt' => $publicCv['generated_at'],
                'accessCount' => (int) $publicCv['access_count'],
            ],
        ]);
    }

    requireAuth(['student']);
    $student = getStudentQrContext($pdo, (int) $_SESSION['user_id']);

    if ($method === 'GET' && $action === 'get_qr') {
        $approvedCv = getLatestApprovedCv($pdo, (int) $student['student_id']);

        jsonResponse(true, 'ok', [
            'cv' => $approvedCv ? formatApprovedCv($approvedCv, $student) : null,
            'user' => formatStudentUser($student),
            'qr' => $approvedCv ? getStudentQrMeta($pdo, $approvedCv, $student) : null,
        ]);
    }

    if ($method === 'POST' && $action === 'generate_qr') {
        $approvedCv = getLatestApprovedCv($pdo, (int) $student['student_id']);
        if (!$approvedCv) {
            jsonResponse(false, 'Your CV must be approved before you can generate a QR code.');
        }

        $baseUrl = appBaseUrl();
        $token = generateQrToken();
        $shareUrl = buildPublicCvUrl($baseUrl, $token);
        $qrImageUrl = buildQrImageUrl($shareUrl);

        $stmt = $pdo->prepare("
            INSERT INTO qr_codes (cv_id, token, qr_image, generated_count, generated_at)
            VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                token = VALUES(token),
                qr_image = VALUES(qr_image),
                generated_count = generated_count + 1,
                generated_at = CURRENT_TIMESTAMP,
                expires_at = NULL
        ");
        $stmt->execute([(int) $approvedCv['id'], $token, $qrImageUrl]);

        $qrRow = getQrByCvId($pdo, (int) $approvedCv['id']);

        jsonResponse(true, 'QR code generated successfully.', [
            'cv' => formatApprovedCv($approvedCv, $student),
            'user' => formatStudentUser($student),
            'qr' => formatQrMeta($qrRow, $approvedCv, $student, $baseUrl),
        ]);
    }

    if ($method === 'POST' && in_array($action, ['track_copy', 'track_download'], true)) {
        $approvedCv = getLatestApprovedCv($pdo, (int) $student['student_id']);
        if (!$approvedCv) {
            jsonResponse(false, 'No approved CV is available for tracking.');
        }

        $qrRow = getQrByCvId($pdo, (int) $approvedCv['id']);
        if (!$qrRow) {
            jsonResponse(false, 'Generate a QR code first.');
        }

        $column = $action === 'track_copy' ? 'copied_count' : 'downloaded_count';
        $stmt = $pdo->prepare("UPDATE qr_codes SET {$column} = {$column} + 1 WHERE id = ?");
        $stmt->execute([(int) $qrRow['id']]);

        $qrRow = getQrByCvId($pdo, (int) $approvedCv['id']);
        jsonResponse(true, 'ok', [
            'qr' => formatQrMeta($qrRow, $approvedCv, $student, appBaseUrl()),
        ]);
    }

    http_response_code(400);
    jsonResponse(false, 'Unknown action.');
} catch (PDOException $e) {
    error_log('QR action error: ' . $e->getMessage());
    jsonResponse(false, 'A server error occurred. Please try again.');
}

function ensureQrColumns(PDO $pdo): void {
    addColumnIfMissing($pdo, 'qr_codes', 'generated_count', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER qr_image');
    addColumnIfMissing($pdo, 'qr_codes', 'copied_count', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER generated_count');
    addColumnIfMissing($pdo, 'qr_codes', 'downloaded_count', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER copied_count');
}

function getStudentQrContext(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT u.id AS user_id, u.full_name, u.email, u.phone,
               st.id AS student_id, d.name AS department
        FROM users u
        JOIN students st ON st.user_id = u.id
        LEFT JOIN departments d ON d.id = st.department_id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $student = $stmt->fetch();

    if (!$student) {
        http_response_code(404);
        jsonResponse(false, 'Student profile not found.');
    }

    return $student;
}

function getLatestApprovedCv(PDO $pdo, int $studentId): ?array {
    $stmt = $pdo->prepare("
        SELECT *
        FROM cvs
        WHERE student_id = ? AND status = 'approved'
        ORDER BY reviewed_at DESC, updated_at DESC, id DESC
        LIMIT 1
    ");
    $stmt->execute([$studentId]);
    return $stmt->fetch() ?: null;
}

function getQrByCvId(PDO $pdo, int $cvId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE cv_id = ? LIMIT 1");
    $stmt->execute([$cvId]);
    return $stmt->fetch() ?: null;
}

function getStudentQrMeta(PDO $pdo, array $cv, array $student): ?array {
    $qrRow = getQrByCvId($pdo, (int) $cv['id']);
    if (!$qrRow) {
        return null;
    }

    return formatQrMeta($qrRow, $cv, $student, appBaseUrl());
}

function formatQrMeta(array $qrRow, array $cv, array $student, string $baseUrl): array {
    return [
        'token' => $qrRow['token'],
        'shareUrl' => buildPublicCvUrl($baseUrl, $qrRow['token']),
        'qrImageUrl' => $qrRow['qr_image'] ?: buildQrImageUrl(buildPublicCvUrl($baseUrl, $qrRow['token'])),
        'generatedCount' => (int) ($qrRow['generated_count'] ?? 0),
        'copiedCount' => (int) ($qrRow['copied_count'] ?? 0),
        'downloadedCount' => (int) ($qrRow['downloaded_count'] ?? 0),
        'accessCount' => (int) ($qrRow['access_count'] ?? 0),
        'lastGeneratedAt' => $qrRow['generated_at'] ?? '',
        'publicTitle' => ($student['full_name'] ?: 'Student') . ' CV',
        'cvStatus' => frontendStatus((string) ($cv['status'] ?? 'draft')),
    ];
}

function buildPublicCvUrl(string $baseUrl, string $token): string {
    return rtrim($baseUrl, '/') . '/public-cv.html?token=' . rawurlencode($token);
}

function buildQrImageUrl(string $shareUrl): string {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($shareUrl);
}

function appBaseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = rtrim(str_replace('/php_actions/generate_qr.php', '', $scriptName), '/');
    return $scheme . '://' . $host . $basePath;
}

function generateQrToken(): string {
    return bin2hex(random_bytes(24));
}

function getPublicCvByToken(PDO $pdo, string $token): ?array {
    $stmt = $pdo->prepare("
        SELECT qr.id AS qr_id, qr.token, qr.generated_at, qr.access_count, qr.expires_at,
               cv.id AS cv_id, cv.profession, cv.summary, cv.address, cv.linkedin, cv.portfolio,
               cv.education_text, cv.experience_text, cv.technical_skills, cv.soft_skills,
               cv.languages, cv.projects, cv.certifications, cv.status, cv.updated_at,
               u.full_name, u.email, u.phone, d.name AS department
        FROM qr_codes qr
        JOIN cvs cv ON cv.id = qr.cv_id
        JOIN students st ON st.id = cv.student_id
        JOIN users u ON u.id = st.user_id
        LEFT JOIN departments d ON d.id = st.department_id
        WHERE qr.token = ?
          AND cv.status = 'approved'
          AND (qr.expires_at IS NULL OR qr.expires_at > NOW())
        LIMIT 1
    ");
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

function logQrAccess(PDO $pdo, int $qrId): void {
    $stmt = $pdo->prepare("
        INSERT INTO qr_access_logs (qr_id, accessed_by, ip_address, user_agent)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $qrId,
        isset($_SESSION['role']) && $_SESSION['role'] === 'recruiter' ? (int) $_SESSION['user_id'] : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
    ]);

    $stmt = $pdo->prepare("UPDATE qr_codes SET access_count = access_count + 1 WHERE id = ?");
    $stmt->execute([$qrId]);
}

function formatApprovedCv(array $cv, array $student): array {
    return [
        'id' => (int) $cv['id'],
        'fullName' => $student['full_name'],
        'profession' => $cv['profession'] ?? '',
        'email' => $student['email'],
        'phone' => $student['phone'] ?? '',
        'address' => $cv['address'] ?? '',
        'department' => $student['department'] ?? '',
        'linkedin' => $cv['linkedin'] ?? '',
        'portfolio' => $cv['portfolio'] ?? '',
        'summary' => $cv['summary'] ?? '',
        'education' => $cv['education_text'] ?? '',
        'experience' => $cv['experience_text'] ?? '',
        'technicalSkills' => $cv['technical_skills'] ?? '',
        'softSkills' => $cv['soft_skills'] ?? '',
        'languages' => $cv['languages'] ?? '',
        'projects' => $cv['projects'] ?? '',
        'certifications' => $cv['certifications'] ?? '',
        'status' => frontendStatus((string) $cv['status']),
        'updatedAt' => $cv['updated_at'] ?? '',
    ];
}

function formatStudentUser(array $student): array {
    return [
        'full_name' => $student['full_name'],
        'email' => $student['email'],
        'role' => 'student',
        'department' => $student['department'] ?? '',
    ];
}

function formatPublicStudent(array $row): array {
    return [
        'fullName' => $row['full_name'] ?? 'Student',
        'email' => $row['email'] ?? '',
        'profession' => $row['profession'] ?? '',
        'phone' => $row['phone'] ?? '',
        'address' => $row['address'] ?? '',
        'department' => $row['department'] ?? '',
        'linkedin' => $row['linkedin'] ?? '',
        'portfolio' => $row['portfolio'] ?? '',
        'summary' => $row['summary'] ?? '',
        'education' => $row['education_text'] ?? '',
        'experience' => $row['experience_text'] ?? '',
        'technicalSkills' => $row['technical_skills'] ?? '',
        'softSkills' => $row['soft_skills'] ?? '',
        'languages' => $row['languages'] ?? '',
        'projects' => $row['projects'] ?? '',
        'certifications' => $row['certifications'] ?? '',
        'status' => frontendStatus((string) ($row['status'] ?? 'approved')),
        'updatedAt' => $row['updated_at'] ?? '',
    ];
}

function frontendStatus(string $status): string {
    return match ($status) {
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'changes_requested' => 'Changes Requested',
        default => 'Draft',
    };
}
