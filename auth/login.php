<?php
// auth/login.php – called via auth.php?action=signin
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_activity.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']) ? true : false;

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Email and password are required.']);
    exit;
}

// Fetch the account from the shared users table. `is_admin` is the role key
// that determines which dashboard the authenticated user can access.
$stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, is_verified, is_admin FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);
    exit;
}

// Check if verified
if (!$user['is_verified']) {
    // Store user_id for verification resend
    $_SESSION['verify_user_id'] = $user['id'];
    $_SESSION['verify_email'] = $user['email'];
    echo json_encode([
        'success' => false,
        'error' => 'Account not verified. Please verify your email.',
        'redirect' => 'verify-code.html'
    ]);
    exit;
}

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);
    exit;
}

// Do not create another "Signed in" audit record when the same administrator
// submits the form while their authenticated session is still active.
$alreadySignedInAsThisAdmin = !empty($_SESSION['admin_logged_in'])
    && (int) ($_SESSION['admin_id'] ?? 0) === (int) $user['id'];

// Start a fresh authenticated session.
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email'] = $user['email'];

$isAdmin = (int) ($user['is_admin'] ?? 0) === 1;

if ($isAdmin) {
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_email'] = $user['email'];
    if (!$alreadySignedInAsThisAdmin) {
        logAdminActivity($pdo, (int) $user['id'], 'signed_in', 'account', (int) $user['id'], 'Administrator signed in');
    }
    $redirect = '../Php/Admin-Sched.php';
} else {
    // Do not retain admin privileges if a different account signs in later
    // within the same browser session.
    unset($_SESSION['is_admin'], $_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_email']);
    $redirect = '../Php/home.php';
}

// Remember me (optional)
if ($remember) {
    // You can set a cookie with a token if desired.
}

echo json_encode([
    'success' => true,
    'message' => $isAdmin ? 'Welcome back, Administrator!' : 'Welcome back!',
    'redirect' => $redirect
]);
?>
