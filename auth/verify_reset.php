<?php
// auth/verify_reset.php – called via auth.php?action=verify-reset
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

$user_id = $_SESSION['reset_user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Please request a new reset.']);
    exit;
}

// Fetch OTP
$stmt = $pdo->prepare("SELECT id, otp_code, expires_at FROM otp_codes WHERE user_id = ? AND purpose = 'reset' ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$otpRecord = $stmt->fetch();

if (!$otpRecord) {
    echo json_encode(['success' => false, 'error' => 'No reset code found. Please request a new one.']);
    exit;
}

// Check expiration
if (strtotime($otpRecord['expires_at']) < time()) {
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE id = ?");
    $stmt->execute([$otpRecord['id']]);
    echo json_encode(['success' => false, 'error' => 'Reset code expired.']);
    exit;
}

// Verify code
if ($otpRecord['otp_code'] !== $code) {
    echo json_encode(['success' => false, 'error' => 'Invalid reset code.']);
    exit;
}

// Mark as verified for reset process
$_SESSION['reset_verified'] = true;

// Delete used OTP
$stmt = $pdo->prepare("DELETE FROM otp_codes WHERE id = ?");
$stmt->execute([$otpRecord['id']]);

echo json_encode([
    'success' => true,
    'message' => 'Code verified. Please set a new password.',
    'redirect' => '../Php/set-password.php'
]);
?>