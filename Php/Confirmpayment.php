<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data from database
$stmt = $pdo->prepare("SELECT id, full_name, email, phone, profile_photo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: signin.php');
    exit;
}

$full_name = $user['full_name'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';

// Build initials for the profile avatar
$name_parts = array_filter(explode(' ', trim($full_name)));
if (count($name_parts) >= 2) {
    $initials = strtoupper(substr(reset($name_parts), 0, 1) . substr(end($name_parts), 0, 1));
} elseif (count($name_parts) === 1) {
    $initials = strtoupper(substr(reset($name_parts), 0, 2));
} else {
    $initials = 'U';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Confirm Payment · Cataleya Essence</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/Confirmpayment.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body>

    <header class="navbar">
        <div class="navbar__logo">
            <img src="../img/Rectangle 38 (1).png" class="logo-img" alt="Cataleya Essence of Beauty" />
            <div class="logo-text">
                <span class="logo-name">Cataleya Essence</span>
                <span class="logo-sub">of Beauty</span>
            </div>
        </div>
        <nav class="navbar__links" id="nav-links">
            <a href="home.php" class="nav-link">Home</a>
            <a href="about.php" class="nav-link">About Us</a>
            <a href="serv.php" class="nav-link">Services</a>
            <a href="Rewards.php" class="nav-link">Rewards</a>
        </nav>

        <!-- ====== PROFILE AVATAR + DROPDOWN ====== -->
        <div class="profile-wrapper" id="profileWrapper">
            <div class="profile-avatar" id="profileAvatar" role="button" aria-haspopup="true" aria-expanded="false" aria-label="User menu">
                <?php if ($user['profile_photo']): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile" class="avatar-image" />
                <?php else: ?>
                    <span class="avatar-initials"><?php echo htmlspecialchars($initials); ?></span>
                <?php endif; ?>
                <span class="status-dot"></span>
            </div>
            <div class="profile-dropdown" id="profileDropdown" role="menu">
                <a href="profile.php" class="dropdown-item" role="menuitem">
                    <i class="fas fa-user"></i> Profile
                </a>
                <div class="dropdown-divider"></div>
                <a href="../auth/logout.php" class="dropdown-item logout" role="menuitem">
                    <i class="fas fa-sign-out-alt"></i> Log out
                </a>
            </div>
        </div>

        <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </header>

    <!-- dropdown overlay (click outside to close) -->
    <div class="dropdown-overlay" id="dropdownOverlay"></div>

    <main class="payment-page">
        <a href="Therapist%20selection.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Therapist</a>

        <div class="payment-hero">
            <h1>Confirm Payment</h1>
            <p>Please review your booking details and provide your information to complete the appointment.</p>
        </div>

        <!-- ============================================================ -->
        <!-- STEP PROGRESS INDICATOR                                      -->
        <!-- ============================================================ -->
        <div class="steps">
            <div class="step active" id="step1Indicator">
                <div class="step-circle">1</div>
                <div class="step-label">Your Info</div>
            </div>
            <div class="step-line" id="stepLine1"></div>
            <div class="step" id="step2Indicator">
                <div class="step-circle">2</div>
                <div class="step-label">Payment</div>
            </div>
            <div class="step-line" id="stepLine2"></div>
            <div class="step" id="step3Indicator">
                <div class="step-circle">3</div>
                <div class="step-label">Confirmation</div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- STEP 1: Information Form                                     -->
        <!-- ============================================================ -->
        <div class="form-wrapper" id="step1">

            <div class="form-card">
                <h2>Your Information</h2>
                <p class="subtitle">Provide your details to complete the transaction.</p>

                <form id="paymentForm" novalidate>
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" placeholder="Enter your full name" value="<?php echo htmlspecialchars($full_name); ?>" required />
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" placeholder="Enter your email address" value="<?php echo htmlspecialchars($email); ?>" required />
                    </div>

                    <div class="form-group">
                        <label for="phone">Contact Number</label>
                        <input type="tel" id="phone" placeholder="Enter your contact number" value="<?php echo htmlspecialchars($phone); ?>" required />
                    </div>

                    <div class="form-group">
                        <label for="requests">Special Requests <span style="font-weight:400;color:var(--text-muted);">(Optional)</span></label>
                        <textarea id="requests" placeholder="Let us know if you have any special requests or notes..."></textarea>
                    </div>
                </form>

                <button class="btn-next" id="nextBtn">
                    Proceed to Downpayment <i class="fas fa-arrow-right" style="margin-left:8px;"></i>
                </button>
            </div>

        </div>

        <!-- ============================================================ -->
        <!-- STEP 2: GCash Payment                                       -->
        <!-- ============================================================ -->
        <div class="payment-grid" id="step2" style="display:none;">

            <div class="payment-card">

                <!-- Header -->
                <div class="payment-header">
                    <div class="payment-method-icon">
                        <img src="../img/2206208.png" alt="GCash" class="gcash-logo" />
                    </div>
                    <div>
                        <h2>Pay with GCash</h2>
                        <p class="payment-subtitle">Scan the QR code below to securely pay for your booking.</p>
                    </div>
                </div>

                <!-- QR Code + Instructions -->
                <div class="qr-container">
                    <div class="qr-code">
                        <img src="../img/qr.png" alt="GCash QR Code" id="qrImage" />
                    </div>
                    <div class="qr-instructions">
                        <span class="qr-step"><i class="fas fa-qrcode"></i> Tap QR and scan to pay</span>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="payment-details">
                    <h3>PAYMENT DETAILS</h3>
                    <div class="detail-row">
                        <span class="detail-label">Merchant Name</span>
                        <span class="detail-value">Cataleya Essence of Beauty</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Service Price</span>
                        <span class="detail-value" id="servicePrice">₱1,799.00</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Downpayment (50%)</span>
                        <span class="detail-value amount-highlight" id="paymentAmount">₱899.50</span>
                    </div>
                </div>

                <!-- Secure Payment Note -->
                <div class="secure-note">
                    <i class="fas fa-lock"></i> Secure payments powered by GCash<br />
                    <span>Your payment is safe and encrypted.</span>
                </div>

                <!-- Actions -->
                <div class="payment-actions">
                    <button class="btn-back" id="backBtn">
                        <i class="fas fa-arrow-left" style="margin-right:8px;"></i> Back
                    </button>
                    <button class="btn-verify" id="verifyBtn">
                        <i class="fas fa-check-circle" style="margin-right:8px;"></i> Verify Payment
                    </button>
                </div>

            </div>
        </div>

        <!-- ============================================================ -->
        <!-- STEP 3: Confirmation (Review & Confirm)                     -->
        <!-- ============================================================ -->
        <div class="review-grid" id="step3" style="display:none;">

            <div class="review-card">

                <!-- Header -->
                <div class="review-header">
                    <div class="review-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h2>Review Your Booking</h2>
                        <p class="review-subtitle">Please double-check your details before confirming.</p>
                    </div>
                </div>

                <!-- Booking Details -->
                <div class="review-details">
                    <h3>BOOKING DETAILS</h3>
                    <div class="detail-group">
                        <span class="detail-label">Service</span>
                        <span class="detail-value" id="revService">Eyelash Enhancement</span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">When</span>
                        <span class="detail-value"><span id="revDate">Mar 28, 2026</span> at <span id="revTime">5:00 PM</span></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Therapist</span>
                        <span class="detail-value" id="revTherapist">Jhacel</span>
                    </div>
                </div>

                <!-- Your Info -->
                <div class="review-details">
                    <h3>YOUR INFORMATION</h3>
                    <div class="detail-group">
                        <span class="detail-label">Name</span>
                        <span class="detail-value" id="revName">-</span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Email</span>
                        <span class="detail-value" id="revEmail">-</span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value" id="revPhone">-</span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Special Requests</span>
                        <span class="detail-value" id="revRequests">None</span>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div class="payment-details">
                    <h3>PAYMENT SUMMARY</h3>
                    <div class="detail-row">
                        <span class="detail-label">Service Price</span>
                        <span class="detail-value" id="revServicePrice">₱1,799.00</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Downpayment (Paid)</span>
                        <span class="detail-value amount-highlight" id="revDownpayment">₱899.50</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Balance on Appointment Day</span>
                        <span class="detail-value" id="revBalance">₱899.50</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="payment-actions">
                    <button class="btn-back" id="reviewBackBtn">
                        <i class="fas fa-arrow-left" style="margin-right:8px;"></i> Back
                    </button>
                    <button class="btn-verify" id="confirmBookingBtn">
                        <i class="fas fa-check-circle" style="margin-right:8px;"></i> Confirm Booking
                    </button>
                </div>

            </div>
        </div>

    </main>

    <!-- ========== FOOTER ========== -->
    <footer class="footer">
        <div class="footer__main">
            <div class="footer__col footer__col--brand">
                <div class="footer__logo">
                    <img src="../img/Rectangle 38 (1).png" class="footer__logo-img" alt="Cataleya Essence of Beauty" />
                    <div class="footer__logo-text">
                        <span class="footer__logo-name">Cataleya Essence</span>
                        <span class="footer__logo-sub">of Beauty</span>
                    </div>
                </div>
                <nav class="footer__nav">
                    <a href="contact.php">Contact</a>
                    <a href="terms.php">Terms and Condition</a>
                    <a href="PrivacyPolicy.php">Privacy Policy</a>
                </nav>
            </div>
            <div class="footer__col footer__col--hours">
                <h4 class="footer__col-title">Opening Hours</h4>
                <ul class="footer__hours">
                    <li><span class="day">Monday</span><span class="time">9:00 AM – 7:00 PM</span></li>
                    <li><span class="day">Tuesday</span><span class="time">9:00 AM – 7:00 PM</span></li>
                    <li><span class="day">Wednesday</span><span class="time">9:00 AM – 7:00 PM</span></li>
                    <li><span class="day">Thursday</span><span class="time">9:00 AM – 7:00 PM</span></li>
                    <li><span class="day">Friday</span><span class="time">9:00 AM – 7:00 PM</span></li>
                    <li><span class="day">Saturday</span><span class="time">9:00 AM – 7:00 PM</span></li>
                </ul>
            </div>
            <div class="footer__col footer__col--contact">
                <h4 class="footer__col-title">Contact</h4>
                <ul class="footer__contact">
                    <li>
                        <svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" /></svg>
                        <a href="https://www.facebook.com/CatelyaEssence" target="_blank">https://www.facebook.com/CatelyaEssence</a>
                    </li>
                    <li>
                        <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" /></svg>
                        <a href="#">@CatelyaEssence</a>
                    </li>
                    <li>
                        <svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" /><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.116 1.528 5.845L.057 23.497a.5.5 0 0 0 .609.61l5.714-1.497A11.955 11.955 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.956 9.956 0 0 1-5.073-1.38l-.361-.214-3.742.981.998-3.648-.235-.374A9.956 9.956 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z" /></svg>
                        <a href="tel:+639922353293">+63 992 235 3293</a>
                    </li>
                    <li>
                        <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3" /><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 13 8 13s8-7.75 8-13a8 8 0 0 0-8-8z" /></svg>
                        <span>Building J Malagpo St. San Vicente, Gapan City, Philippines, 3105</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="footer__bottom">
            <p>&copy; 2026 Cataleya Essence of Beauty. All rights reserved.</p>
        </div>
    </footer>

    <!-- ========== SCRIPTS ========== -->
    <script src="../JS/Confirmpayment.js"></script>

</body>
</html>
