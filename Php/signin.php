<?php
require_once __DIR__ . '/../config/database.php';


if (!empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_id'])) {
    header('Location: Admin-Sched.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign In · Cataleya Essence</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/signin.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head> 
<body>

    <div class="auth-container">
   
        <div class="auth-form" id="authForm">
            <div class="brand">
                <img src="../img/Rectangle 38 (1).png" alt="Cataleya Essence" />
                <div class="brand-text">
                    <span class="brand-name">Cataleya Essence</span>
                    <span class="brand-sub">of Beauty</span>
                </div>
            </div>

            <h1>Welcome Back</h1>
            <p class="subtitle">Sign in to access your dashboard</p>

            <div class="auth-tabs">
                <button class="active">Sign In</button>
                <button onclick="window.location.href='signup.php'">Sign Up</button>
            </div>

            <div id="authMessage" class="auth-message"></div>

            <form id="signinForm" novalidate>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required />
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required />
                        <button type="button" class="toggle-password" data-target="password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember">
                        <input type="checkbox" id="rememberMe" required />
                        <span>Remember me</span>
                    </label>
                    <a href="#" id="forgotPasswordLink">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-primary" id="signinBtn">Sign In</button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </div>
        </div>

        <!-- Right Panel: Image + Testimonial -->
        <div class="auth-testimonial">
            <div class="testimonial-content">
                <h2>Elevate Your Spa Management Experience</h2>
                <p>Streamline appointments, track customer satisfaction, manage staff schedules, and grow your beauty business — all from one elegant dashboard.</p>
                <div class="stars">★★★★★</div>
                <p class="quote">"Cataleya's management system transformed how we run our clinic. Bookings are seamless and our clients love the experience."</p>
                <p class="author">janella benito Tolentino</p>
                <p class="author-title">Spa Owner</p>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- FORGOT PASSWORD MODAL (step 1) -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="forgotModal">
        <div class="modal-container">
            <div class="modal-card">
                <button class="modal-close" id="modalClose">&times;</button>

                <div class="modal-brand">
                    <img src="../img/Rectangle 38 (1).png" alt="Cataleya Essence" />
                    <div class="brand-text">
                        <span class="brand-name">Cataleya Essence</span>
                        <span class="brand-sub">of Beauty</span>
                    </div>
                </div>

                <h2>Forgot your password?</h2>
                <p class="modal-subtitle">No worries! Enter your email address and we'll send you a verification code to reset your password.</p>

                <div id="modalMessage" class="auth-message"></div>

                <form id="forgotForm" novalidate>
                    <div class="form-group">
                        <label for="modalEmail">Email Address</label>
                        <input type="email" id="modalEmail" name="email" placeholder="you@example.com" required />
                    </div>
                    <button type="submit" class="btn-primary" id="forgotBtn">Send Verification Code</button>
                </form>

                <div class="modal-footer">
                    <a href="#" id="modalBackLink"><i class="fas fa-arrow-left"></i> Back to Sign In</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- VERIFICATION CODE MODAL (step 2) -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="verifyModal">
        <div class="modal-container">
            <div class="modal-card verify-card">
                <button class="modal-close" id="verifyModalClose">&times;</button>

                <div class="modal-brand">
                    <img src="../img/Rectangle 38 (1).png" alt="Cataleya Essence" />
                    <div class="brand-text">
                        <span class="brand-name">Cataleya Essence</span>
                        <span class="brand-sub">of Beauty</span>
                    </div>
                </div>

                <h2 class="verify-title">Enter verification code</h2>
                <p class="verify-subtitle">
                    We've sent a 6-digit code to <strong id="verifyEmailDisplay">you@example.com</strong>.<br />
                    Please enter it below.
                </p>

                <div id="verifyMessage" class="auth-message"></div>

                <div class="code-input-group" id="codeInputGroup">
                    <input type="text" maxlength="1" class="code-input" data-index="0" autofocus />
                    <input type="text" maxlength="1" class="code-input" data-index="1" />
                    <input type="text" maxlength="1" class="code-input" data-index="2" />
                    <input type="text" maxlength="1" class="code-input" data-index="3" />
                    <input type="text" maxlength="1" class="code-input" data-index="4" />
                    <input type="text" maxlength="1" class="code-input" data-index="5" />
                </div>
                <input type="hidden" id="verifyCodeHidden" />

                <button class="verify-continue" id="verifyContinueBtn">Continue</button>

                <div class="verify-footer">
                    <a href="#" id="changeEmailLink">Change email address</a>
                    <span class="divider">|</span>
                    <a href="#" id="resendCodeLink">Resend code</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SET NEW PASSWORD MODAL (step 3) -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="resetModal">
        <div class="modal-container">
            <div class="modal-card reset-card">
                <button class="modal-close" id="resetModalClose">&times;</button>

                <div class="modal-brand">
                    <img src="../img/Rectangle 38 (1).png" alt="Cataleya Essence" />
                    <div class="brand-text">
                        <span class="brand-name">Cataleya Essence</span>
                        <span class="brand-sub">of Beauty</span>
                    </div>
                </div>

                <h2 class="reset-title">Set new password</h2>
                <p class="reset-subtitle">Create a strong password for your account. Make sure it's at least 6 characters long.</p>

                <div id="resetMessage" class="auth-message"></div>

                <form id="resetForm" novalidate>
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="newPassword" name="new_password" placeholder="Enter your new password" required />
                            <button type="button" class="toggle-password" data-target="newPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm your new password" required />
                            <button type="button" class="toggle-password" data-target="confirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary" id="resetBtn">Reset Password</button>
                </form>

                <div class="modal-footer">
                    <a href="#" id="resetBackLink"><i class="fas fa-arrow-left"></i> Back to Sign In</a>
                </div>
            </div>
        </div>
    </div>

    <!-- external JavaScript -->
    <script src="../js/signin.js"></script>
</body>

</html>
