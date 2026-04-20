<?php
// ============================================================
//  Admin Action API
//  All endpoints require an active admin session.
//  Supports: GET ?action=get_stats|get_users
//            POST action=create_user|toggle_status|delete_user
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// ── Auth guard ───────────────────────────────────────────────
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    jsonResponse(false, 'Unauthorized.');
}

$method = $_SERVER['REQUEST_METHOD'];
$action = '';

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
} else {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;
    $action = $data['action'] ?? '';
}

$pdo = getDB();

// ============================================================
//  GET: get_stats
// ============================================================
if ($method === 'GET' && $action === 'get_stats') {
    $stats = [];

    // Total users (excluding admin)
    $s = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name != 'admin'");
    $stats['total_users'] = (int)$s->fetchColumn();

    // Total CVs
    $s = $pdo->query("SELECT COUNT(*) FROM cvs");
    $stats['total_cvs'] = (int)$s->fetchColumn();

    // Approved CVs (= active QR codes)
    $s = $pdo->query("SELECT COUNT(*) FROM qr_codes");
    $stats['active_qr'] = (int)$s->fetchColumn();

    // Total QR scans
    $s = $pdo->query("SELECT COUNT(*) FROM qr_access_logs");
    $stats['total_scans'] = (int)$s->fetchColumn();

    // CV status breakdown
    $s = $pdo->query("SELECT status, COUNT(*) AS cnt FROM cvs GROUP BY status");
    $cv_breakdown = [];
    foreach ($s->fetchAll() as $row) $cv_breakdown[$row['status']] = (int)$row['cnt'];
    $stats['cv_breakdown'] = $cv_breakdown;

    // Users by role
    $s = $pdo->query("SELECT r.name AS role, COUNT(*) AS cnt FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name != 'admin' GROUP BY r.name");
    $role_breakdown = [];
    foreach ($s->fetchAll() as $row) $role_breakdown[$row['role']] = (int)$row['cnt'];
    $stats['role_breakdown'] = $role_breakdown;

    jsonResponse(true, 'ok', ['stats' => $stats]);
}

// ============================================================
//  GET: get_users
// ============================================================
if ($method === 'GET' && $action === 'get_users') {
    $role   = $_GET['role']   ?? '';
    $search = $_GET['search'] ?? '';

    $where  = ["r.name != 'admin'"];
    $params = [];

    if ($role && $role !== 'all') {
        $where[]  = 'r.name = ?';
        $params[] = $role;
    }
    if ($search) {
        $where[]  = '(u.full_name LIKE ? OR u.email LIKE ?)';
        $like     = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $whereSQL = implode(' AND ', $where);

    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.email, u.phone, u.is_active, u.created_at,
               r.name AS role,
               d.name AS department
        FROM users u
        JOIN roles r ON r.id = u.role_id
        LEFT JOIN students st ON st.user_id = u.id
        LEFT JOIN departments d ON d.id = st.department_id
        WHERE {$whereSQL}
        ORDER BY u.created_at DESC
    ");
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    jsonResponse(true, 'ok', ['users' => $users]);
}

// ============================================================
//  POST: create_user
// ============================================================
if ($method === 'POST' && $action === 'create_user') {
    $fullName   = clean($data['full_name']  ?? '');
    $email      = strtolower(trim($data['email'] ?? ''));
    $password   = $data['password']         ?? '';
    $roleName   = clean($data['role']       ?? '');
    $phone      = clean($data['phone']      ?? '');
    $department = clean($data['department'] ?? '');

    // Validation
    if (!$fullName)              jsonResponse(false, 'Full name is required.');
    if (!isValidEmail($email))   jsonResponse(false, 'Invalid email address.');
    if (strlen($password) < 8)   jsonResponse(false, 'Password must be at least 8 characters.');

    $allowedRoles = ['supervisor', 'examiner', 'recruiter', 'student'];
    if (!in_array($roleName, $allowedRoles, true)) {
        jsonResponse(false, 'Invalid role selected.');
    }

    // Email uniqueness
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) jsonResponse(false, 'This email is already registered.');

    // Get role ID
    $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
    $stmt->execute([$roleName]);
    $role = $stmt->fetch();
    if (!$role) jsonResponse(false, 'Role not found.');

    try {
        $pdo->beginTransaction();

        // Insert user
        $stmt = $pdo->prepare('INSERT INTO users (role_id, full_name, email, password_hash, phone) VALUES (?,?,?,?,?)');
        $stmt->execute([$role['id'], $fullName, $email, hashPassword($password), $phone ?: null]);
        $userId = (int)$pdo->lastInsertId();

        // If student, also insert into students
        if ($roleName === 'student') {
            $deptId = null;
            if ($department) {
                $s2 = $pdo->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
                $s2->execute([$department]);
                $dept = $s2->fetch();
                if (!$dept) {
                    $s2 = $pdo->prepare('INSERT INTO departments (name) VALUES (?)');
                    $s2->execute([$department]);
                    $deptId = (int)$pdo->lastInsertId();
                } else {
                    $deptId = $dept['id'];
                }
            }
            $studentId = 'ADM-' . str_pad($userId, 6, '0', STR_PAD_LEFT);
            $s2 = $pdo->prepare('INSERT INTO students (user_id, department_id, student_number) VALUES (?,?,?)');
            $s2->execute([$userId, $deptId, $studentId]);
        }

        $pdo->commit();
        jsonResponse(true, "User '{$fullName}' created successfully.", ['user_id' => $userId]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Admin create_user error: ' . $e->getMessage());
        jsonResponse(false, 'Server error while creating user.');
    }
}

// ============================================================
//  POST: toggle_status
// ============================================================
if ($method === 'POST' && $action === 'toggle_status') {
    $userId = (int)($data['user_id'] ?? 0);
    if (!$userId) jsonResponse(false, 'Invalid user ID.');

    // Prevent admin from deactivating themselves
    if ($userId === (int)$_SESSION['user_id']) {
        jsonResponse(false, 'You cannot change your own account status.');
    }

    $stmt = $pdo->prepare('SELECT is_active FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) jsonResponse(false, 'User not found.');

    $newStatus = $user['is_active'] ? 0 : 1;
    $stmt = $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?');
    $stmt->execute([$newStatus, $userId]);

    $label = $newStatus ? 'activated' : 'deactivated';
    jsonResponse(true, "User {$label} successfully.", ['is_active' => $newStatus]);
}

// ============================================================
//  POST: delete_user
// ============================================================
if ($method === 'POST' && $action === 'delete_user') {
    $userId = (int)($data['user_id'] ?? 0);
    if (!$userId) jsonResponse(false, 'Invalid user ID.');

    if ($userId === (int)$_SESSION['user_id']) {
        jsonResponse(false, 'You cannot delete your own account.');
    }

    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role_id != (SELECT id FROM roles WHERE name = "admin")');
    $stmt->execute([$userId]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(false, 'User not found or cannot be deleted.');
    }

    jsonResponse(true, 'User deleted successfully.');
}

// ── Fallback ─────────────────────────────────────────────────
http_response_code(400);
jsonResponse(false, 'Unknown action.');
