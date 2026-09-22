<?php
// auth/verify_signup.php – called via auth.php?action=verify
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$code = trim($_POST['code'] ?? '');
if (strlen($code) !== 6 || !ctype_digit($code)) {
    echo json_encode(['success' => false, 'error' => 'Invalid verification code.']);
    exit;
}

// Get user_id from session
$user_id = $_SESSION['verify_user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Please register again.']);
    exit;
}

// Fetch OTP record
$stmt = $pdo->prepare("SELECT id, otp_code, expires_at FROM otp_codes WHERE user_id = ? AND purpose = 'signup' ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$otpRecord = $stmt->fetch();

if (!$otpRecord) {
    echo json_encode(['success' => false, 'error' => 'No OTP found. Please request a new one.']);
    exit;
}

// Check expiration
if (strtotime($otpRecord['expires_at']) < time()) {
    // Delete expired OTP
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE id = ?");
    $stmt->execute([$otpRecord['id']]);
    echo json_encode(['success' => false, 'error' => 'Verification code expired.']);
    exit;
}

// Verify code
if ($otpRecord['otp_code'] !== $code) {
    echo json_encode(['success' => false, 'error' => 'Invalid verification code.']);
    exit;
}

// Mark user as verified
$stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
$stmt->execute([$user_id]);

// Delete used OTP
$stmt = $pdo->prepare("DELETE FROM otp_codes WHERE id = ?");
$stmt->execute([$otpRecord['id']]);

// Clear session
unset($_SESSION['verify_user_id']);
unset($_SESSION['verify_email']);

echo json_encode([
    'success' => true,
    'message' => 'Email verified successfully!',
    'redirect' => '../Php/home.php'
]);
?>