<?php
require_once __DIR__ . '/../config/database.php';

$message = '';
$messageType = '';

// Check if user is in reset flow
if (!isset($_SESSION['reset_user_id'])) {
    $message = 'Session expired. Please request a new reset.';
    $messageType = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = trim($_POST['code']);

    if (strlen($code) !== 6 || !ctype_digit($code)) {
        $message = 'Invalid verification code format.';
        $messageType = 'error';
    } else {
        $user_id = $_SESSION['reset_user_id'] ?? 0;
        if (!$user_id) {
            $message = 'Session expired. Please request a new reset.';
            $messageType = 'error';
        } else {
            // Fetch OTP
            $stmt = $pdo->prepare("SELECT id, otp_code, expires_at FROM otp_codes WHERE user_id = ? AND purpose = 'reset' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $otpRecord = $stmt->fetch();

            if (!$otpRecord) {
                $message = 'No reset code found. Please request a new one.';
                $messageType = 'error';
            } else {
                // Check expiration
                if (strtotime($otpRecord['expires_at']) < time()) {
                    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE id = ?");
                    $stmt->execute([$otpRecord['id']]);
                    $message = 'Reset code expired. Please request a new one.';
                    $messageType = 'error';
                } else {
                    // Verify code
                    if ($otpRecord['otp_code'] !== $code) {
                        $message = 'Invalid reset code.';
                        $messageType = 'error';
                    } else {
                        // Mark as verified for reset process
                        $_SESSION['reset_verified'] = true;

                        // Delete used OTP
                        $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE id = ?");
                        $stmt->execute([$otpRecord['id']]);

                        // Redirect to set password page
                        header("Location: set-password.php");
                        exit();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verify Code · Cataleya Essence</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/style-auth.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body>

    <div class="auth-container" style="grid-template-columns:1fr; background: rgba(0,0,0,0.5); backdrop-filter:blur(8px);">
        <div class="auth-form" style="max-width:480px; margin:auto; height:auto; padding:40px; background:white; border-radius:24px; box-shadow:0 24px 64px rgba(0,0,0,0.2);">
            <div class="brand" style="justify-content:center;">
                <div class="brand-icon"><i class="fas fa-spa"></i></div>
                <div class="brand-text">
                    <span class="brand-name">Cataleya Essence</span>
                    <span class="brand-sub">of Beauty</span>
                </div>
            </div>

            <h1 style="text-align:center;">Enter Verification Code</h1>
            <p class="subtitle" style="text-align:center;">We've sent a 6-digit code to your email. Enter it below to continue.</p>

            <div id="authMessage" class="auth-message <?php echo $messageType; ?>"><?php echo $message; ?></div>

            <form method="POST" id="verifyForm">
                <div class="form-group">
                    <label for="code">Verification Code</label>
                    <input type="text" id="code" name="code" placeholder="123456" maxlength="6" pattern="[0-9]{6}" required style="letter-spacing: 8px; text-align: center; font-size: 24px; font-weight: 600;" />
                </div>
                <button type="submit" class="btn-primary">Verify Code</button>
            </form>

            <div class="auth-footer" style="margin-top:20px;">
                <a href="forgot-password.php">Request new code</a>
            </div>
        </div>
    </div>

</body>
</html>
