<?php
// ============================================================
//  Check Auth API
//  Returns JSON status of current session.
// ============================================================
require_once __DIR__ . '/../includes/auth.php';

// startSession() is already called inside auth.php if needed, 
// but requireAuth calls it too. includes/auth.php line 7 does startSession().

$user = currentUser();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: application/json');

if ($user) {
    echo json_encode([
        'success' => true,
        'message' => 'Authenticated',
        'user'    => $user
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthenticated'
    ]);
}
exit;
