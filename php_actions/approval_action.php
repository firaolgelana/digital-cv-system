<?php
// ============================================================
//  Supervisor / Examiner Review Actions
//  GET  ?action=get_dashboard&status=pending&search=
//  POST action=review_cv
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireApiAuth(['supervisor', 'examiner']);

$pdo = getDB();

$method = $_SERVER['REQUEST_METHOD'];
$action = '';
$data = [];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'get_dashboard';
} else {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;
    $action = $data['action'] ?? '';
}

try {
    ensureReviewStatus($pdo);
    ensureQrSupport($pdo);

    if ($method === 'GET' && $action === 'get_dashboard') {
        $status = $_GET['status'] ?? 'pending';
        $search = trim($_GET['search'] ?? '');

        jsonResponse(true, 'ok', [
            'stats' => getReviewStats($pdo),
            'submissions' => getReviewList($pdo, $status, $search),
            'recent_reviewed' => getRecentReviewed($pdo),
        ]);
    }

    if ($method === 'POST' && $action === 'review_cv') {
        $cvId = (int) ($data['cv_id'] ?? 0);
        $decision = trim($data['decision'] ?? '');
        $note = trim($data['note'] ?? '');

        if (!$cvId) {
            jsonResponse(false, 'Invalid CV ID.');
        }

        $map = [
            'approve' => 'approved',
            'reject' => 'rejected',
            'request_changes' => 'changes_requested',
        ];
        if (!isset($map[$decision])) {
            jsonResponse(false, 'Invalid review action.');
        }

        $stmt = $pdo->prepare('SELECT id, status FROM cvs WHERE id = ? LIMIT 1');
        $stmt->execute([$cvId]);
        $cv = $stmt->fetch();
        if (!$cv) {
            jsonResponse(false, 'CV not found.');
        }

        $newStatus = $map[$decision];
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE cvs
            SET status = ?, reviewed_at = CURRENT_TIMESTAMP, reviewer_id = ?, review_note = ?
            WHERE id = ?
        ");
        $stmt->execute([$newStatus, (int) $_SESSION['user_id'], $note !== '' ? $note : null, $cvId]);

        if ($newStatus === 'approved') {
            ensureApprovedCvHasQr($pdo, $cvId);
        }

        $pdo->commit();

        $message = match ($decision) {
            'approve' => 'CV approved successfully.',
            'reject' => 'CV rejected successfully.',
            default => 'Changes requested successfully.',
        };

        jsonResponse(true, $message, [
            'stats' => getReviewStats($pdo),
            'submissions' => getReviewList($pdo, 'pending', ''),
            'recent_reviewed' => getRecentReviewed($pdo),
        ]);
    }

    http_response_code(400);
    jsonResponse(false, 'Unknown action.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Approval action error: ' . $e->getMessage());
    jsonResponse(false, 'A server error occurred. Please try again.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Approval action fatal: ' . $e->getMessage());
    jsonResponse(false, 'A server error occurred. Please try again.');
}

function ensureReviewStatus(PDO $pdo): void {
    $pdo->exec("ALTER TABLE cvs MODIFY COLUMN status ENUM('draft','pending','approved','rejected','changes_requested') NOT NULL DEFAULT 'draft'");
}

function ensureQrSupport(PDO $pdo): void {
    addColumnIfMissing($pdo, 'qr_codes', 'generated_count', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER qr_image');
    addColumnIfMissing($pdo, 'qr_codes', 'copied_count', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER generated_count');
    addColumnIfMissing($pdo, 'qr_codes', 'downloaded_count', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER copied_count');
}

function ensureApprovedCvHasQr(PDO $pdo, int $cvId): void {
    $stmt = $pdo->prepare('SELECT id FROM qr_codes WHERE cv_id = ? LIMIT 1');
    $stmt->execute([$cvId]);
    if ($stmt->fetch()) {
        return;
    }

    $token = bin2hex(random_bytes(24));
    $stmt = $pdo->prepare("
        INSERT INTO qr_codes (cv_id, token, generated_count)
        VALUES (?, ?, 1)
    ");
    $stmt->execute([$cvId, $token]);
}

function getReviewStats(PDO $pdo): array {
    $stats = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'changes_requested' => 0,
        'total_students' => 0,
    ];

    $rows = $pdo->query('SELECT status, COUNT(*) AS cnt FROM cvs GROUP BY status')->fetchAll();
    foreach ($rows as $row) {
        $stats[$row['status']] = (int) $row['cnt'];
    }

    $stats['total_students'] = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
    return $stats;
}

function getReviewList(PDO $pdo, string $status, string $search): array {
    $allowed = ['all', 'pending', 'approved', 'rejected', 'changes_requested'];
    if (!in_array($status, $allowed, true)) {
        $status = 'pending';
    }

    $where = [];
    $params = [];

    if ($status !== 'all') {
        $where[] = 'cv.status = ?';
        $params[] = $status;
    }

    if ($search !== '') {
        $where[] = '(u.full_name LIKE ? OR d.name LIKE ? OR st.student_number LIKE ? OR cv.profession LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("
        SELECT cv.id, cv.title, cv.profession, cv.summary, cv.address, cv.linkedin, cv.portfolio,
               cv.education_text, cv.experience_text, cv.technical_skills, cv.soft_skills,
               cv.languages, cv.projects, cv.certifications, cv.status, cv.submitted_at,
               cv.reviewed_at, cv.review_note, cv.created_at, cv.updated_at,
               u.full_name, u.email, u.phone, st.student_number, d.name AS department,
               (SELECT COUNT(*) FROM cv_documents doc WHERE doc.cv_id = cv.id) AS documents_count
        FROM cvs cv
        JOIN students st ON st.id = cv.student_id
        JOIN users u ON u.id = st.user_id
        LEFT JOIN departments d ON d.id = st.department_id
        {$whereSql}
        ORDER BY
            FIELD(cv.status, 'pending', 'changes_requested', 'approved', 'rejected', 'draft'),
            COALESCE(cv.submitted_at, cv.updated_at) DESC,
            cv.id DESC
    ");
    $stmt->execute($params);
    return array_map('formatSubmissionRow', $stmt->fetchAll());
}

function getRecentReviewed(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT cv.id, cv.status, cv.submitted_at, cv.reviewed_at, cv.review_note,
               u.full_name, st.student_number, d.name AS department
        FROM cvs cv
        JOIN students st ON st.id = cv.student_id
        JOIN users u ON u.id = st.user_id
        LEFT JOIN departments d ON d.id = st.department_id
        WHERE cv.status IN ('approved', 'rejected', 'changes_requested')
        ORDER BY cv.reviewed_at DESC, cv.id DESC
        LIMIT 10
    ");

    return array_map(static function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'full_name' => $row['full_name'],
            'student_number' => $row['student_number'],
            'department' => $row['department'] ?? 'No department',
            'status' => frontendReviewStatus($row['status']),
            'submitted_at' => $row['submitted_at'],
            'reviewed_at' => $row['reviewed_at'],
            'review_note' => $row['review_note'] ?? '',
        ];
    }, $stmt->fetchAll());
}

function formatSubmissionRow(array $row): array {
    $fields = [
        $row['full_name'] ?? '',
        $row['profession'] ?? '',
        $row['email'] ?? '',
        $row['phone'] ?? '',
        $row['address'] ?? '',
        $row['department'] ?? '',
        $row['summary'] ?? '',
        $row['education_text'] ?? '',
        $row['experience_text'] ?? '',
        $row['technical_skills'] ?? '',
        $row['soft_skills'] ?? '',
        $row['languages'] ?? '',
        $row['projects'] ?? '',
        $row['certifications'] ?? '',
    ];

    $completed = 0;
    foreach ($fields as $field) {
        if (trim((string) $field) !== '') {
            $completed++;
        }
    }

    return [
        'id' => (int) $row['id'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'phone' => $row['phone'] ?? '',
        'student_number' => $row['student_number'],
        'department' => $row['department'] ?? 'No department',
        'profession' => $row['profession'] ?? '',
        'summary' => $row['summary'] ?? '',
        'address' => $row['address'] ?? '',
        'linkedin' => $row['linkedin'] ?? '',
        'portfolio' => $row['portfolio'] ?? '',
        'education' => $row['education_text'] ?? '',
        'experience' => $row['experience_text'] ?? '',
        'technical_skills' => $row['technical_skills'] ?? '',
        'soft_skills' => $row['soft_skills'] ?? '',
        'languages' => $row['languages'] ?? '',
        'projects' => $row['projects'] ?? '',
        'certifications' => $row['certifications'] ?? '',
        'status' => frontendReviewStatus($row['status']),
        'submitted_at' => $row['submitted_at'],
        'reviewed_at' => $row['reviewed_at'],
        'review_note' => $row['review_note'] ?? '',
        'documents_count' => (int) $row['documents_count'],
        'completion' => (int) round(($completed / count($fields)) * 100),
        'completed_sections' => $completed,
        'total_sections' => count($fields),
    ];
}

function frontendReviewStatus(string $status): string {
    return match ($status) {
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'changes_requested' => 'Changes Requested',
        default => 'Draft',
    };
}
