<?php





require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    $data = $_POST;
}

$email       = strtolower(trim((string)($data['email'] ?? '')));
$password    = (string)($data['password'] ?? '');
$role        = strtolower(trim((string)($data['role'] ?? '')));
$allowedRoles = ['student', 'supervisor', 'examiner', 'recruiter', 'admin'];


if (!isValidEmail($email)) {
    jsonResponse(false, 'Please enter a valid email address.');
}
if (empty($password)) {
    jsonResponse(false, 'Password is required.');
}
if (empty($role)) {
    jsonResponse(false, 'Please select your role.');
}
if (!in_array($role, $allowedRoles, true)) {
    jsonResponse(false, 'Please select a valid role.');
}


try {
    $pdo = getDB();

    
    $stmt = $pdo->prepare('
        SELECT u.id, u.full_name, u.email, u.password_hash, u.is_active, r.name AS role
        FROM   users u
        JOIN   roles r ON r.id = u.role_id
        WHERE  u.email = ?
        LIMIT  1
    ');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    
    if (!$user || !verifyPassword($password, $user['password_hash'])) {
        jsonResponse(false, 'Invalid email or password.');
    }

    
    if (!(int) $user['is_active']) {
        jsonResponse(false, 'Your account has been deactivated. Please contact the administrator.');
    }

    
    if ($user['role'] !== $role) {
        jsonResponse(false, 'The selected role does not match this account. Please choose the correct role.');
    }

    
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'];

    jsonResponse(true, 'Login successful! Redirecting…', [
        'redirect' => dashboardForRole($user['role']),
    ]);

} catch (PDOException $e) {
    error_log('Login error: ' . $e->getMessage());
    jsonResponse(false, 'A server error occurred. Please try again.');
}
