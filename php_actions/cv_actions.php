<?php
// ============================================================
//  Student CV Actions
//  GET  ?action=get_cv
//  POST action=save_draft|submit_cv
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireAuth(['student']);

$pdo = getDB();
ensureCvColumns($pdo);
ensureCvDocumentsTable($pdo);
ensureNotificationsTable($pdo);
ensureStudentColumns($pdo);

$method = $_SERVER['REQUEST_METHOD'];
$action = '';
$data = [];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'get_cv';
} else {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;
    $action = $data['action'] ?? '';
}

try {
    $student = getStudentContext($pdo, (int) $_SESSION['user_id']);

    if ($method === 'GET' && $action === 'get_cv') {
        $cv = getLatestCv($pdo, (int) $student['student_id']);
        jsonResponse(true, 'ok', [
            'cv' => $cv ? formatCvForFrontend($cv, $student) : null,
            'user' => formatUserForFrontend($student),
        ]);
    }

    if ($method === 'POST' && in_array($action, ['save_draft', 'submit_cv'], true)) {
        $isSubmit = $action === 'submit_cv';
        $payload = sanitizeCvPayload($data);

        if ($isSubmit) {
            validateSubmissionPayload($payload);
        }

        $pdo->beginTransaction();

        updateStudentProfile($pdo, (int) $_SESSION['user_id'], (int) $student['student_id'], $payload);
        $student = getStudentContext($pdo, (int) $_SESSION['user_id']);

        $existingCv = getLatestCv($pdo, (int) $student['student_id']);
        $dbStatus = $isSubmit ? 'pending' : 'draft';

        if ($existingCv) {
            $stmt = $pdo->prepare("
                UPDATE cvs
                SET title = ?, profession = ?, summary = ?, address = ?, linkedin = ?, portfolio = ?,
                    education_text = ?, experience_text = ?, technical_skills = ?, soft_skills = ?,
                    languages = ?, projects = ?, certifications = ?, status = ?,
                    submitted_at = CASE WHEN ? = 'pending' THEN CURRENT_TIMESTAMP ELSE NULL END,
                    reviewed_at = NULL, reviewer_id = NULL, review_note = NULL
                WHERE id = ?
            ");
            $stmt->execute([
                buildCvTitle($payload),
                nullable($payload['profession']),
                nullable($payload['summary']),
                nullable($payload['address']),
                nullable($payload['linkedin']),
                nullable($payload['portfolio']),
                nullable($payload['education']),
                nullable($payload['experience']),
                nullable($payload['technicalSkills']),
                nullable($payload['softSkills']),
                nullable($payload['languages']),
                nullable($payload['projects']),
                nullable($payload['certifications']),
                $dbStatus,
                $dbStatus,
                $existingCv['id'],
            ]);
            $cvId = (int) $existingCv['id'];
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO cvs (
                    student_id, title, profession, summary, address, linkedin, portfolio,
                    education_text, experience_text, technical_skills, soft_skills, languages,
                    projects, certifications, status, submitted_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $submittedAt = $dbStatus === 'pending' ? date('Y-m-d H:i:s') : null;
            $stmt->execute([
                $student['student_id'],
                buildCvTitle($payload),
                nullable($payload['profession']),
                nullable($payload['summary']),
                nullable($payload['address']),
                nullable($payload['linkedin']),
                nullable($payload['portfolio']),
                nullable($payload['education']),
                nullable($payload['experience']),
                nullable($payload['technicalSkills']),
                nullable($payload['softSkills']),
                nullable($payload['languages']),
                nullable($payload['projects']),
                nullable($payload['certifications']),
                $dbStatus,
                $submittedAt,
            ]);
            $cvId = (int) $pdo->lastInsertId();
        }

        $pdo->commit();

        // Handle file uploads if any
        if (!empty($_FILES['cv_docs'])) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $files = $_FILES['cv_docs'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $files['tmp_name'][$i];
                    $originalName = basename($files['name'][$i]);
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                    $newName = uniqid('cv_doc_', true) . '.' . $extension;
                    $savePath = $uploadDir . $newName;

                    if (move_uploaded_file($tmpName, $savePath)) {
                        $stmtDoc = $pdo->prepare("
                            INSERT INTO cv_documents (cv_id, original_name, stored_path, doc_type, mime_type, file_size_kb)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $fileSize = (int) (filesize($savePath) / 1024);
                        $mimeType = mime_content_type($savePath) ?: 'application/octet-stream';
                        $stmtDoc->execute([$cvId, $originalName, 'uploads/' . $newName, 'other', $mimeType, $fileSize]);
                    }
                }
            }
        }

        $savedCv = getCvById($pdo, $cvId);
        
        // Notify Supervisor if assigned
        if ($isSubmit) {
            $stmtSup = $pdo->prepare('SELECT supervisor_id FROM students WHERE id = ?');
            $stmtSup->execute([$student['student_id']]);
            $supId = $stmtSup->fetchColumn();
            if ($supId) {
                createNotification($pdo, (int)$supId, 'New CV Submission', 'Student ' . $student['full_name'] . ' has submitted a CV for review.');
            }
        }

        jsonResponse(true, $isSubmit ? 'CV submitted successfully.' : 'Draft saved successfully.', [
            'cv' => formatCvForFrontend($savedCv, $student),
            'user' => formatUserForFrontend($student),
        ]);
    }

    http_response_code(400);
    jsonResponse(false, 'Unknown action.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('CV action error: ' . $e->getMessage());
    jsonResponse(false, 'A server error occurred: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}

function ensureCvColumns(PDO $pdo): void {
    $pdo->exec("ALTER TABLE cvs MODIFY COLUMN status ENUM('draft','pending','approved','rejected','changes_requested') NOT NULL DEFAULT 'draft'");
    addColumnIfMissing($pdo, 'cvs', 'profession', 'VARCHAR(150) DEFAULT NULL AFTER title');
    addColumnIfMissing($pdo, 'cvs', 'address', 'VARCHAR(255) DEFAULT NULL AFTER summary');
    addColumnIfMissing($pdo, 'cvs', 'linkedin', 'VARCHAR(255) DEFAULT NULL AFTER address');
    addColumnIfMissing($pdo, 'cvs', 'portfolio', 'VARCHAR(255) DEFAULT NULL AFTER linkedin');
    addColumnIfMissing($pdo, 'cvs', 'education_text', 'TEXT DEFAULT NULL AFTER portfolio');
    addColumnIfMissing($pdo, 'cvs', 'experience_text', 'TEXT DEFAULT NULL AFTER education_text');
    addColumnIfMissing($pdo, 'cvs', 'technical_skills', 'TEXT DEFAULT NULL AFTER experience_text');
    addColumnIfMissing($pdo, 'cvs', 'soft_skills', 'TEXT DEFAULT NULL AFTER technical_skills');
    addColumnIfMissing($pdo, 'cvs', 'languages', 'TEXT DEFAULT NULL AFTER soft_skills');
    addColumnIfMissing($pdo, 'cvs', 'projects', 'TEXT DEFAULT NULL AFTER languages');
    addColumnIfMissing($pdo, 'cvs', 'certifications', 'TEXT DEFAULT NULL AFTER projects');
    addColumnIfMissing($pdo, 'cvs', 'reviewer_id', 'INT DEFAULT NULL AFTER status');
    addColumnIfMissing($pdo, 'cvs', 'review_note', 'TEXT DEFAULT NULL AFTER reviewer_id');
    addColumnIfMissing($pdo, 'cvs', 'reviewed_at', 'TIMESTAMP NULL DEFAULT NULL AFTER review_note');
    addColumnIfMissing($pdo, 'cvs', 'submitted_at', 'TIMESTAMP NULL DEFAULT NULL AFTER reviewed_at');
}

function ensureCvDocumentsTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cv_documents (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            cv_id INT UNSIGNED NOT NULL,
            doc_type VARCHAR(80) NOT NULL DEFAULT 'other',
            original_name VARCHAR(255) NOT NULL,
            stored_path VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) DEFAULT NULL,
            file_size_kb INT UNSIGNED DEFAULT NULL,
            uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_docs_cv_new FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

function ensureNotificationsTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

function getStudentContext(PDO $pdo, int $userId): array {
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

function getLatestCv(PDO $pdo, int $studentId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM cvs WHERE student_id = ? ORDER BY updated_at DESC, id DESC LIMIT 1");
    $stmt->execute([$studentId]);
    return $stmt->fetch() ?: null;
}

function getCvById(PDO $pdo, int $cvId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM cvs WHERE id = ? LIMIT 1");
    $stmt->execute([$cvId]);
    return $stmt->fetch() ?: null;
}

function sanitizeCvPayload(array $data): array {
    return [
        'fullName' => clean($data['fullName'] ?? ''),
        'profession' => clean($data['profession'] ?? ''),
        'email' => strtolower(trim($data['email'] ?? '')),
        'phone' => clean($data['phone'] ?? ''),
        'address' => clean($data['address'] ?? ''),
        'department' => clean($data['department'] ?? ''),
        'linkedin' => trim($data['linkedin'] ?? ''),
        'portfolio' => trim($data['portfolio'] ?? ''),
        'summary' => trim($data['summary'] ?? ''),
        'education' => trim($data['education'] ?? ''),
        'experience' => trim($data['experience'] ?? ''),
        'technicalSkills' => trim($data['technicalSkills'] ?? ''),
        'softSkills' => trim($data['softSkills'] ?? ''),
        'languages' => trim($data['languages'] ?? ''),
        'projects' => trim($data['projects'] ?? ''),
        'certifications' => trim($data['certifications'] ?? ''),
    ];
}

function validateSubmissionPayload(array $payload): void {
    $requiredFields = [
        'fullName', 'profession', 'email', 'phone', 'address', 'department',
        'summary', 'education', 'experience', 'technicalSkills', 'softSkills',
        'languages', 'projects', 'certifications',
    ];

    foreach ($requiredFields as $field) {
        if ($payload[$field] === '') {
            jsonResponse(false, 'Please complete every CV section before submitting.');
        }
    }

    if (!isValidEmail($payload['email'])) {
        jsonResponse(false, 'Please enter a valid email address.');
    }
}

function updateStudentProfile(PDO $pdo, int $userId, int $studentId, array $payload): void {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
    $stmt->execute([$payload['email'], $userId]);
    if ($payload['email'] && $stmt->fetch()) {
        jsonResponse(false, 'An account with this email already exists.');
    }

    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?');
    $stmt->execute([
        $payload['fullName'] ?: $_SESSION['full_name'],
        $payload['email'] ?: $_SESSION['email'],
        nullable($payload['phone']),
        $userId,
    ]);

    $deptId = null;
    if ($payload['department'] !== '') {
        $deptId = upsertDepartment($pdo, $payload['department']);
    }

    $stmt = $pdo->prepare('UPDATE students SET department_id = ? WHERE id = ?');
    $stmt->execute([$deptId, $studentId]);

    $_SESSION['full_name'] = $payload['fullName'] ?: $_SESSION['full_name'];
    $_SESSION['email'] = $payload['email'] ?: $_SESSION['email'];
}

function upsertDepartment(PDO $pdo, string $department): ?int {
    if ($department === '') {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
    $stmt->execute([$department]);
    $row = $stmt->fetch();
    if ($row) {
        return (int) $row['id'];
    }

    $stmt = $pdo->prepare('INSERT INTO departments (name) VALUES (?)');
    $stmt->execute([$department]);
    return (int) $pdo->lastInsertId();
}

function formatCvForFrontend(array $cv, array $student): array {
    global $pdo;
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
        'createdAt' => $cv['created_at'] ?? '',
        'updatedAt' => $cv['updated_at'] ?? '',
        'reviewNote' => $cv['review_note'] ?? '',
        'documents' => getCvDocuments($pdo, (int) $cv['id']),
        'activity' => buildActivityFeed($cv),
    ];
}

function getCvDocuments(PDO $pdo, int $cvId): array {
    $stmt = $pdo->prepare("
        SELECT original_name AS file_name, stored_path AS file_path, doc_type AS file_type 
        FROM cv_documents 
        WHERE cv_id = ?
    ");
    $stmt->execute([$cvId]);
    return $stmt->fetchAll();
}

function formatUserForFrontend(array $student): array {
    return [
        'full_name' => $student['full_name'],
        'email' => $student['email'],
        'role' => 'student',
        'department' => $student['department'] ?? '',
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

function buildActivityFeed(array $cv): array {
    $activity = [];

    if (!empty($cv['created_at'])) {
        $activity[] = ['label' => 'CV created', 'time' => $cv['created_at']];
    }
    if (!empty($cv['submitted_at'])) {
        $activity[] = ['label' => 'CV submitted for review', 'time' => $cv['submitted_at']];
    }
    if (!empty($cv['reviewed_at']) && !empty($cv['status'])) {
        $label = match ($cv['status']) {
            'approved' => 'CV approved',
            'rejected' => 'CV rejected',
            'changes_requested' => 'Changes requested by reviewer',
            default => 'CV reviewed',
        };
        $activity[] = ['label' => $label, 'time' => $cv['reviewed_at']];
    }
    if (!empty($cv['updated_at'])) {
        $activity[] = ['label' => 'CV updated', 'time' => $cv['updated_at']];
    }

    usort($activity, static fn(array $a, array $b): int => strcmp($b['time'], $a['time']));
    return $activity;
}

function buildCvTitle(array $payload): string {
    return $payload['profession'] !== '' ? $payload['profession'] . ' CV' : 'My CV';
}

function nullable(string $value): ?string {
    return $value === '' ? null : $value;
}

function ensureStudentColumns(PDO $pdo): void {
    addColumnIfMissing($pdo, 'students', 'supervisor_id', 'INT NULL DEFAULT NULL AFTER user_id');
    addColumnIfMissing($pdo, 'students', 'student_number', 'VARCHAR(50) NULL DEFAULT NULL AFTER supervisor_id');
}
