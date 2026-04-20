<?php
// ============================================================
//  Logout — destroys session and redirects to login page
// ============================================================
require_once __DIR__ . '/../includes/functions.php';

startSession();
logout();

header('Location: ../index.html');
exit;
