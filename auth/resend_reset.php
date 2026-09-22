<?php
// auth/resend_reset.php – called via auth.php?action=resend-reset
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

header('Content-Type: application/json');

$user_id = $_SESSION['reset_user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Please request a new reset.']);
    exit;
}

// Get user email
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found.']);
    exit;
}

// Cooldown check
$stmt = $pdo->prepare("SELECT created_at FROM otp_codes WHERE user_id = ? AND purpose = 'reset' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$lastOtp = $stmt->fetch();
if ($lastOtp) {
    $lastTime = strtotime($lastOtp['created_at']);
    if (time() - $lastTime < 60) {
        echo json_encode(['success' => false, 'error' => 'Please wait 60 seconds before requesting a new code.']);
        exit;
    }
}
    
// Generate new OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Delete old OTP
$stmt = $pdo->prepare("DELETE FROM otp_codes WHERE user_id = ? AND purpose = 'reset'");
$stmt->execute([$user_id]);

// Insert new
$stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at) VALUES (?, ?, 'reset', ?)");
$stmt->execute([$user_id, $otp, $expires_at]);

// Send email
$mailSent = sendOTPEmail($user['email'], $otp, 'reset');

if (!$mailSent) {
    echo json_encode(['success' => false, 'error' => 'Could not send email. Please try again.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'A new reset code has been sent to your email.']);
?>