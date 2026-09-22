<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $message = 'No account found with this email.';
            $messageType = 'error';
        } else {
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
                $message = 'Could not send email. Please try again.';
                $messageType = 'error';
            } else {
                // Store user_id in session for reset flow
                $_SESSION['reset_user_id'] = $user_id;
                $_SESSION['reset_email'] = $email;

                // Redirect to verification page
                header("Location: verify-code.php");
                exit();
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
    <title>Forgot Password · Cataleya Essence</title>
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

            <h1 style="text-align:center;">Forgot your password?</h1>
            <p class="subtitle" style="text-align:center;">No worries! Enter your email address and we'll send you a verification code to reset your password.</p>

            <div id="authMessage" class="auth-message <?php echo $messageType; ?>"><?php echo $message; ?></div>

            <form method="POST" id="forgotForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required />
                </div>
                <button type="submit" class="btn-primary">Send Verification Code</button>
            </form>

            <div class="auth-footer" style="margin-top:20px;">
                <a href="signin.php"><i class="fas fa-arrow-left"></i> Back to Sign In</a>
            </div>
        </div>
    </div>

</body>
</html>