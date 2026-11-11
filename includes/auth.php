<?php
/**
 * GROUPWARE - Authentication & Session Helper
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Enforce that the user is authenticated and possesses one of the allowed roles.
 *
 * @param array|string $allowedRoles Allowed roles ('admin', 'instructor', 'student')
 * @param string $redirect Path to redirect unauthorized users to
 */
function requireAuth($allowedRoles = [], string $redirect = '../index.php') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: $redirect");
        exit;
    }

    if (!empty($allowedRoles)) {
        $allowed = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
        if (!in_array($_SESSION['role'], $allowed, true)) {
            header("Location: $redirect");
            exit;
        }
    }
}

/**
 * Return human-readable label for a role
 */
function getRoleLabel(string $role): string {
    switch ($role) {
        case 'admin':
            return 'Workspace Admin';
        case 'instructor':
            return 'Project Lead';
        case 'student':
            return 'Team Member';
        default:
            return ucfirst($role);
    }
}
