<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

startSession();
logout();

header('Location: ../index.html');
exit;
