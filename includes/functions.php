<?php
/**
 * Start session safely (only if not already started).
 */
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Send a JSON response and terminate.
 *
 * @param bool   $success
 * @param string $message
 * @param array  $data     Extra payload merged into the response
 */
function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $data));
    exit;
}

/**
 * Sanitize a plain string input.
 */
function clean(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate an email address.
 */
function isValidEmail(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Hash a plain-text password.
 */
function hashPassword(string $plain): string {
    return password_hash($plain, PASSWORD_BCRYPT);
}

/**
 * Verify a plain-text password against a stored hash.
 */
function verifyPassword(string $plain, string $hash): bool {
    return password_verify($plain, $hash);
}

/**
 * Return the dashboard URL for a given role name.
 */
function dashboardForRole(string $role): string {
    return match ($role) {
        'student'    => '../student-dashboard.php',
        'supervisor' => '../supervisor-dashboard.html',
        'examiner'   => '../supervisor-dashboard.html',
        'recruiter'  => '../recruiter-view.html',
        'admin'      => '../admin-dashboard.php',
        default      => '../index.html',
    };
}

/**
 * Require login to access the page.
 */
function requireLogin(): void {
    startSession();
    if (empty($_SESSION['user_id'])) {
        header('Location: index.html');
        exit;
    }
}
