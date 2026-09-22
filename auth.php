<?php
// auth.php – main entry point for all AJAX actions
require_once __DIR__ . '/config/database.php'; // starts session

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        require_once __DIR__ . '/auth/register.php';
        break;
    case 'verify':
        require_once __DIR__ . '/auth/verify_signup.php';
        break;
    case 'resend':
        require_once __DIR__ . '/auth/resend_signup.php';
        break;
    case 'signin':
        require_once __DIR__ . '/auth/login.php';
        break;
    case 'forgot':
        require_once __DIR__ . '/auth/forgot_password.php';
        break;
    case 'verify-reset':
        require_once __DIR__ . '/auth/verify_reset.php';
        break;
    case 'resend-reset':
        require_once __DIR__ . '/auth/resend_reset.php';
        break;
    case 'set-password':
        require_once __DIR__ . '/auth/set_new_password.php';
        break;
    case 'logout':
        require_once __DIR__ . '/auth/logout.php';
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
        break;
}
?>