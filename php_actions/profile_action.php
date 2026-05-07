<?php
/**
 * Profile Action API
 * Handles GET (fetch profile) and POST (update info, password, avatar)
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Session check
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    jsonResponse(false, 'Unauthorized.');
}

$pdo = getDB();
$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
//  GET: Fetch profile data
// ============================================================
if ($method === 'GET') {
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.email, u.phone, r.name as role
        FROM users u
        JOIN roles r ON r.id = u.role_id
        WHERE u.id = ? LIMIT 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) jsonResponse(false, 'User not found.');

    jsonResponse(true, 'ok', ['user' => $user]);
}

// ============================================================
//  POST: Actions
// ============================================================
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;
$action = $data['action'] ?? '';

if ($action === 'update_info') {
    $fullName = clean($data['full_name'] ?? '');
    $phone    = clean($data['phone'] ?? '');

    if (!$fullName) jsonResponse(false, 'Full name is required.');

    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
    if ($stmt->execute([$fullName, $phone, $userId])) {
        $_SESSION['full_name'] = $fullName;
        createNotification($pdo, $userId, 'Profile Updated', 'Your personal information has been successfully updated.');
        jsonResponse(true, 'Profile updated successfully.');
    } else {
        jsonResponse(false, 'Failed to update profile.');
    }
}

if ($action === 'update_password') {
    $current = $data['current_password'] ?? '';
    $new     = $data['new_password'] ?? '';
    $confirm = $data['confirm_password'] ?? '';

    if (strlen($new) < 8) jsonResponse(false, 'New password must be at least 8 characters.');
    if ($new !== $confirm) jsonResponse(false, 'Passwords do not match.');

    // Verify current
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!verifyPassword($current, $user['password_hash'])) {
        jsonResponse(false, 'Incorrect current password.');
    }

    $hash = hashPassword($new);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    if ($stmt->execute([$hash, $userId])) {
        createNotification($pdo, $userId, 'Password Changed', 'Your account password was updated successfully.');
        jsonResponse(true, 'Password updated successfully.');
    } else {
        jsonResponse(false, 'Failed to update password.');
    }
}

jsonResponse(false, 'Invalid action.');
