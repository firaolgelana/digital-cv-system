<?php
// ============================================================
//  Shared Helper Functions
// ============================================================

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
        'student'    => '../student-dashboard.html',
        'supervisor' => '../supervisor-dashboard.php',
        'examiner'   => '../supervisor-dashboard.php',
        'recruiter'  => '../recruiter-view.html',
        'admin'      => '../admin-dashboard.php',
        default      => '../index.html',
    };
}

/**
 * Ensure the default roles exist.
 */
function ensureDefaultRoles(PDO $pdo): void {
    $roles = ['student', 'supervisor', 'examiner', 'recruiter', 'admin'];
    $stmt = $pdo->prepare('INSERT IGNORE INTO roles (name) VALUES (?)');

    foreach ($roles as $role) {
        $stmt->execute([$role]);
    }
}

/**
 * Ensure the default admin account exists with the expected credentials.
 */
function ensureDefaultAdminAccount(PDO $pdo): void {
    ensureDefaultRoles($pdo);

    $adminEmail = 'gelanafiraol@gmail.com';
    $adminPassword = 'firaolgelana';
    $adminName = 'Firaol Gelana';

    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'admin' LIMIT 1");
    $stmt->execute();
    $role = $stmt->fetch();

    if (!$role) {
        return;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$adminEmail]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare('UPDATE users SET role_id = ?, full_name = ?, password_hash = ?, is_active = 1 WHERE id = ?');
        $stmt->execute([(int) $role['id'], $adminName, hashPassword($adminPassword), (int) $user['id']]);
        return;
    }

    $stmt = $pdo->prepare('
        INSERT INTO users (role_id, full_name, email, password_hash, is_active)
        VALUES (?, ?, ?, ?, 1)
    ');
    $stmt->execute([(int) $role['id'], $adminName, $adminEmail, hashPassword($adminPassword)]);
}

/**
 * Check whether a table column exists in the current database.
 */
function dbColumnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Add a column only when it is missing.
 */
function addColumnIfMissing(PDO $pdo, string $table, string $column, string $definition): void {
    if (dbColumnExists($pdo, $table, $column)) {
        return;
    }

    $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
}
