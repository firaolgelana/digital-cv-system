<?php
require_once __DIR__ . '/functions.php';

startSession();

/**
 * Require a logged-in user; redirect to login otherwise.
 *
 * @param string|string[] $allowedRoles  Pass a role name or array of role names.
 *                                       Empty = any authenticated user is allowed.
 */
function requireAuth(array|string $allowedRoles = []): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ../index.html');
        exit;
    }

    if (!empty($allowedRoles)) {
        $allowed = (array) $allowedRoles;
        if (!in_array($_SESSION['role'], $allowed, true)) {
            
            header('Location: ' . dashboardForRole($_SESSION['role']));
            exit;
        }
    }
}

/**
 * Returns the currently logged-in user's session data,
 * or null if nobody is logged in.
 */
function currentUser(): ?array {
    if (!empty($_SESSION['user_id'])) {
        return [
            'id'        => $_SESSION['user_id'],
            'full_name' => $_SESSION['full_name'],
            'email'     => $_SESSION['email'],
            'role'      => $_SESSION['role'],
        ];
    }
    return null;
}

/**
 * Destroy the current session (logout).
 */
function logout(): void {
    session_unset();
    session_destroy();
}
