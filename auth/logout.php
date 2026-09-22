<?php
// auth/logout.php – called via auth.php?action=logout
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_activity.php';

// Keep the administrator ID before clearing the session so a real sign-out
// is included in the audit trail. Guests and regular users do not create an
// administrator activity entry.
if (!empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_id'])) {
    $adminId = (int) $_SESSION['admin_id'];
    logAdminActivity($pdo, $adminId, 'signed_out', 'account', $adminId, 'Administrator signed out');
}

// Clear all session data
$_SESSION = [];

if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Remove the session cookie if it exists
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

header('Location: ../Php/signin.php');
exit;
?>
