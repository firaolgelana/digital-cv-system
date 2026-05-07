<?php
/**
 * Notification Action API
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    jsonResponse(false, 'Unauthorized.');
}

$pdo = getDB();
$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT id, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
    $stmt->execute([$userId]);
    $notifs = $stmt->fetchAll();
    
    // Count unread
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    $unread = (int)$stmt->fetchColumn();

    jsonResponse(true, 'ok', ['notifications' => $notifs, 'unread_count' => $unread]);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;
$action = $data['action'] ?? '';

if ($action === 'mark_read') {
    $notifId = (int)($data['id'] ?? 0);
    if ($notifId) {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->execute([$notifId, $userId]);
    } else {
        // Mark all as read
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
    jsonResponse(true, 'Marked as read.');
}

if ($action === 'delete') {
    $notifId = (int)($data['id'] ?? 0);
    if ($notifId) {
        $stmt = $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
        $stmt->execute([$notifId, $userId]);
    } else {
        // Delete all
        $stmt = $pdo->prepare('DELETE FROM notifications WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
    jsonResponse(true, 'Deleted successfully.');
}

jsonResponse(false, 'Invalid action.');
