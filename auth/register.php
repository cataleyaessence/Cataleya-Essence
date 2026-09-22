<?php
// auth/register.php – called via auth.php?action=register
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = $_POST['password'] ?? '';
$confirm   = $_POST['confirm_password'] ?? '';
$terms     = isset($_POST['terms']) ? true : false;
$privacy   = isset($_POST['privacy_policy']) ? true : false;

$errors = [];

// Validate inputs
if (empty($full_name)) $errors[] = 'Full name is required.';
if (empty($email)) $errors[] = 'Email is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
if ($password !== $confirm) $errors[] = 'Passwords do not match.';
if (!$terms) $errors[] = 'You must agree to the Terms & Conditions.';
if (!$privacy) $errors[] = 'You must agree to the Privacy Policy.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Email already registered.']);
    exit;
}

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
$stmt->execute([$full_name, $email, $password_hash]);
$user_id = $pdo->lastInsertId();

// Generate OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Delete any existing OTP for this user (avoid duplicates)
$stmt = $pdo->prepare("DELETE FROM otp_codes WHERE user_id = ? AND purpose = 'signup'");
$stmt->execute([$user_id]);

// Store OTP
$stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at) VALUES (?, ?, 'signup', ?)");
$stmt->execute([$user_id, $otp, $expires_at]);

// Send email
$mailSent = sendOTPEmail($email, $otp, 'signup');

if (!$mailSent) {
    // Optionally delete user or handle error
    echo json_encode(['success' => false, 'error' => 'Could not send verification email. Please try again.']);
    exit;
}

// Store user_id in session for verification
$_SESSION['verify_user_id'] = $user_id;
$_SESSION['verify_email'] = $email;

echo json_encode([
    'success' => true,
    'message' => 'Account created! Please verify your email.',
    'redirect' => 'verify-code.html'  // your existing verification page
]);
?>