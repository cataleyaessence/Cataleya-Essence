<?php
require_once __DIR__ . '/../config/database.php';

$message = '';
$messageType = '';

// Check if reset is verified
if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
    $message = 'Unauthorized. Please verify your code first.';
    $messageType = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
        $messageType = 'error';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } else {
        $user_id = $_SESSION['reset_user_id'] ?? 0;
        if (!$user_id) {
            $message = 'Session expired. Please request a new reset.';
            $messageType = 'error';
        } else {
            // Update password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $user_id]);

            // Clear reset session
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_verified']);

            // Redirect to sign-in page
            header("Location: signin.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Set New Password · Cataleya Essence</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/style-auth.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body>

    <div class="auth-container">
        <!-- Left Panel: Form -->
        <div class="auth-form">
            <div class="brand">
                <img src="../img/Rectangle 38 (1).png" alt="Cataleya Essence" />
                <div class="brand-text">
                    <span class="brand-name">Cataleya Essence</span>
                    <span class="brand-sub">of Beauty</span>
                </div>
            </div>

            <h1>Set New Password</h1>
            <p class="subtitle">Create a new password for your account.</p>

            <!-- Message -->
            <div id="authMessage" class="auth-message <?php echo $messageType; ?>"><?php echo $message; ?></div>

            <!-- Form -->
            <form method="POST" id="setPasswordForm">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Min. 8 characters" required />
                        <button type="button" class="toggle-password" data-target="password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required />
                        <button type="button" class="toggle-password" data-target="confirm_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Update Password</button>
            </form>

            <div class="auth-footer">
                <a href="signin.php"><i class="fas fa-arrow-left"></i> Back to Sign In</a>
            </div>
        </div>

        <!-- Right Panel: Testimonial -->
        <div class="auth-testimonial">
            <h2>Elevate Your Spa Management Experience</h2>
            <p>Streamline appointments, track customer satisfaction, manage staff schedules, and grow your beauty business — all from one elegant dashboard.</p>

            <div class="stars">★★★★★</div>
            <p class="quote">"Cataleya's management system transformed how we run our clinic. Bookings are seamless and our clients love the experience."</p>
            <p class="author">Princess Neill Sta Maria</p>
            <p class="author-title">Spa Owner</p>
        </div>
    </div>

</body>
</html>