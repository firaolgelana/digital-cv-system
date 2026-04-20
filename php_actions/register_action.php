<?php
// ============================================================
//  Register Action
//  Accepts: POST (JSON body or form-encoded)
//  Returns: JSON { success, message, redirect? }
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
header('Content-Type: application/json');

// ── Only allow POST ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

// ── Read input (supports both JSON body and form-encoded) ────
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    $data = $_POST; // fallback to form-encoded
}

// ── Extract & sanitize fields ────────────────────────────────
$firstName   = clean($data['first_name']      ?? '');
$lastName    = clean($data['last_name']       ?? '');
$email       = strtolower(trim($data['email'] ?? ''));
$studentId   = clean($data['student_id']      ?? '');
$department  = clean($data['department']      ?? '');
$password    = $data['password']              ?? '';
$confirmPass = $data['confirm_password']      ?? '';

// ── Validation ───────────────────────────────────────────────
if (!$firstName || !$lastName) {
    jsonResponse(false, 'First name and last name are required.');
}
if (!isValidEmail($email)) {
    jsonResponse(false, 'Please enter a valid email address.');
}
if (!$studentId) {
    jsonResponse(false, 'Student ID is required.');
}
if (!$department) {
    jsonResponse(false, 'Please select your department.');
}
if (strlen($password) < 8) {
    jsonResponse(false, 'Password must be at least 8 characters long.');
}
if ($password !== $confirmPass) {
    jsonResponse(false, 'Passwords do not match.');
}

$fullName = $firstName . ' ' . $lastName;

// ── Database operations ──────────────────────────────────────
try {
    $pdo = getDB();

    // 1. Check email uniqueness
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'An account with this email already exists.');
    }

    // 2. Check student ID uniqueness
    $stmt = $pdo->prepare('SELECT id FROM students WHERE student_number = ? LIMIT 1');
    $stmt->execute([$studentId]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'This Student ID is already registered.');
    }

    // 3. Get the 'student' role ID
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'student' LIMIT 1");
    $stmt->execute();
    $role = $stmt->fetch();
    if (!$role) {
        jsonResponse(false, 'System error: default role not found. Please contact admin.');
    }
    $roleId = $role['id'];

    // 4. Resolve or insert the department
    $stmt = $pdo->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
    $stmt->execute([$department]);
    $dept = $stmt->fetch();

    if ($dept) {
        $deptId = $dept['id'];
    } else {
        $stmt = $pdo->prepare('INSERT INTO departments (name) VALUES (?)');
        $stmt->execute([$department]);
        $deptId = (int) $pdo->lastInsertId();
    }

    // 5. Insert into users
    $stmt = $pdo->prepare('
        INSERT INTO users (role_id, full_name, email, password_hash)
        VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([$roleId, $fullName, $email, hashPassword($password)]);
    $userId = (int) $pdo->lastInsertId();

    // 6. Insert into students
    $stmt = $pdo->prepare('
        INSERT INTO students (user_id, department_id, student_number)
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$userId, $deptId, $studentId]);

    // 7. Auto-login: set session
    $_SESSION['user_id']   = $userId;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email']     = $email;
    $_SESSION['role']      = 'student';

    jsonResponse(true, 'Account created successfully! Redirecting…', [
        'redirect' => '../student-dashboard.html',
    ]);

} catch (PDOException $e) {
    // Log in production; return a safe message
    error_log('Register error: ' . $e->getMessage());
    jsonResponse(false, 'A server error occurred. Please try again.');
}
