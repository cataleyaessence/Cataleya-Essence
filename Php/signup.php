<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up · Cataleya Essence</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <!-- external CSS -->
    <link rel="stylesheet" href="../css/signup.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body>

    <div class="auth-container">
        <!-- Left Panel: Form -->
        <div class="auth-form" id="authForm">
            <div class="brand">
                <img src="../img/Rectangle 38 (1).png" alt="Cataleya Essence" />
                <div class="brand-text">
                    <span class="brand-name">Cataleya Essence</span>
                    <span class="brand-sub">of Beauty</span>
                </div>
            </div>

            <h1>Create Your Account</h1>
            <p class="subtitle">Your branding style is now live!</p>

            <div class="auth-tabs">
                <button onclick="window.location.href='signin.php'">Sign In</button>
                <button class="active">Sign Up</button>
            </div>

            <div id="authMessage" class="auth-message"></div>

            <form id="signupForm" novalidate>
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required />
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" required />
                </div>

                <!-- Password - now full width -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required />
                        <button type="button" class="toggle-password" data-target="password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password - below password, same style -->
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required />
                        <button type="button" class="toggle-password" data-target="confirm_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="terms-checkbox">
                    <input type="checkbox" id="terms" name="terms" required />
                    <label for="terms">
                        I agree to the <a href="terms.php" target="_blank" rel="noopener">Terms &amp; Conditions</a>.
                    </label>
                </div>

                <div class="terms-checkbox">
                    <input type="checkbox" id="privacy_policy" name="privacy_policy" required />
                    <label for="privacy_policy">
                        I agree to the <a href="PrivacyPolicy.php" target="_blank" rel="noopener">Privacy Policy</a>.
                    </label>
                </div>

                <button type="submit" class="btn-primary" id="signupBtn">Sign Up</button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="signin.php">Sign In</a>
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
    <!-- VERIFICATION CODE MODAL -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="verifyModal">
        <div class="modal-container">
            <div class="modal-card">
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

    <!-- TERMS & CONDITIONS MODAL -->
    <div class="modal-overlay" id="termsModal">
        <div class="modal-container modal-tall">
            <div class="modal-card">
                <button class="modal-close" id="termsModalClose">&times;</button>

                <div class="modal-brand">
                    <img src="../img/Rectangle 38 (1).png" alt="Cataleya Essence" />
                    <div class="brand-text">
                        <span class="brand-name">Cataleya Essence</span>
                        <span class="brand-sub">of Beauty</span>
                    </div>
                </div>

                <h2 class="verify-title">Confirm your agreement</h2>
                <p class="verify-subtitle">Create your account only after reviewing the full Terms and Privacy Policy.</p>

                <div class="terms-content" style="max-height: 320px; overflow-y: auto; margin-bottom: 24px; color: var(--text-muted); font-size: 14px; line-height: 1.7;">
                    <p>This is a short account-creation confirmation, not a duplicate copy of the Terms.</p>
                    <p>Please review the complete <a href="terms.php" target="_blank" rel="noopener">Terms &amp; Conditions</a> for booking, downpayment, rescheduling, cancellation, and rewards rules.</p>
                    <p>Read the <a href="PrivacyPolicy.php" target="_blank" rel="noopener">Privacy Policy</a> to understand how your account and booking information is handled.</p>
                </div>

                <div id="termsMessage" class="auth-message"></div>

                <button class="verify-continue" id="termsAcceptBtn">I Agree &amp; Continue</button>
                <button class="verify-continue" id="termsDeclineBtn" style="margin-top: 12px; background: var(--border-soft); color: var(--text-heading);">Decline</button>
            </div>
        </div>
    </div>

    <!-- external JavaScript -->
    <script src="../JS/signup.js"></script>
</body>
</html>
