<?php
// auth/forgot_password.php – called via auth.php?action=forgot
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'No account found with this email.']);
    exit;
}

$user_id = $user['id'];

// Generate OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Delete old reset OTP
$stmt = $pdo->prepare("DELETE FROM otp_codes WHERE user_id = ? AND purpose = 'reset'");
$stmt->execute([$user_id]);

// Store new reset OTP
$stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at) VALUES (?, ?, 'reset', ?)");
$stmt->execute([$user_id, $otp, $expires_at]);

// Send email
$mailSent = sendOTPEmail($email, $otp, 'reset');

if (!$mailSent) {
    echo json_encode(['success' => false, 'error' => 'Could not send email. Please try again.']);
    exit;
}

// Store user_id in session for reset flow
$_SESSION['reset_user_id'] = $user_id;
$_SESSION['reset_email'] = $email;

echo json_encode([
    'success' => true,
    'message' => 'A verification code has been sent to your email.',
    'redirect' => '../Php/verify-code.php'
]);
?>