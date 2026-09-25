<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Terms &amp; Conditions · Cataleya Essence</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/terms.css" />
    <link rel="stylesheet" href="../css/user-navbar.css" />
    <link rel="stylesheet" href="../css/user-footer.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body>
    <?php require __DIR__ . '/includes/user-navbar.php'; ?>

    <main class="terms-page">
        <article class="terms-card">
            <header class="terms-card-header">
                <span class="header-icon"><i class="fas fa-file-contract" aria-hidden="true"></i></span>
                <h1>Terms &amp; Conditions</h1>
                <p class="subtitle">The booking and account rules for Cataleya Essence of Beauty.</p>
                <div class="brand-badge"><i class="fas fa-spa" aria-hidden="true"></i> Cataleya Essence of Beauty</div>
            </header>

            <div class="terms-card-body">
                <div class="terms-content">
                    <h2>1. Using your account</h2>
                    <p>Keep your account details accurate and do not share your password. You are responsible for bookings made through your account. We may contact you using the email address saved in your profile for booking updates.</p>

                    <h2>2. Booking a service</h2>
                    <p>Choose an active service, a date, and an available time before continuing to payment. The system blocks two active bookings at the same date and time, so another customer may still reserve a slot while you are choosing it.</p>
                    <div class="highlight-box"><p><strong>Booking confirmation:</strong> a reservation is automatically marked <strong>Confirmed</strong> once its required 50% downpayment is successfully recorded.</p></div>

                    <h2>3. Downpayment, price, and attendance</h2>
                    <ul>
                        <li>The required downpayment is 50% of the service total unless the booking screen shows a different amount.</li>
                        <li>Service prices and availability are the values shown in the current service catalog at the time you book.</li>
                        <li>Please review the selected service, date, and time before payment, and arrive on time for your appointment.</li>
                    </ul>

                    <h2>4. Rescheduling a confirmed booking</h2>
                    <p>Confirmed and previously rescheduled bookings can be rescheduled from <strong>My Bookings</strong>, subject to an available future schedule.</p>
                    <ul>
                        <li>You may choose another available date and time.</li>
                        <li>If changing service, the replacement service must be in the same price range as the original booking.</li>
                        <li>The system sends an updated booking notice after a successful reschedule.</li>
                    </ul>

                    <h2>5. Cancelling a booking</h2>
                    <p>You can cancel an eligible booking from <strong>My Bookings</strong>. The confirmation screen will remind you of the applicable rule before cancellation is completed.</p>
                    <div class="highlight-box"><p><strong>Cancellation rule:</strong> the recorded downpayment is non-refundable. A cancelled booking does not earn rewards points.</p></div>

                    <h2>6. Rewards</h2>
                    <p>Points and visit credit are granted only after the service has been marked <strong>Completed</strong>. Confirmed, rescheduled, and cancelled bookings do not earn points. Your current points and tier are shown on the Rewards page.</p>

                    <h2>7. Service and schedule changes</h2>
                    <p>Management may update service information, operating availability, or schedules when needed. Existing booking records remain visible in your account, including past and cancelled appointments.</p>

                    <h2>8. Fair and safe use</h2>
                    <p>Do not create false bookings, attempt to bypass schedule limits, use another person's account, or interfere with the website. We may restrict access when an account is used fraudulently or disruptively.</p>

                    <h2>9. Privacy and updates</h2>
                    <p>Your personal and booking information is handled according to our <a href="PrivacyPolicy.php">Privacy Policy</a>. We may revise these Terms when system rules change; the version displayed on this page applies to continued use of the website.</p>

                    <div class="last-updated">
                        <p><strong>Last updated:</strong> September 23, 2026</p>
                        <p>Questions about these rules? Visit the <a href="faq.php">FAQs</a> or <a href="contact.php">contact us</a>.</p>
                    </div>
                </div>
            </div>

            <footer class="terms-card-footer">
                <?php if (!$userNavbarUser): ?>
                <a class="btn-primary" href="signup.php"><i class="fas fa-user-plus" aria-hidden="true"></i> Create an account</a>
                <?php endif; ?>
                <a class="btn-secondary" href="faq.php"><i class="fas fa-circle-question" aria-hidden="true"></i> View FAQs</a>
            </footer>
        </article>
    </main>

    <?php require __DIR__ . '/includes/user-footer.php'; ?>
    <script src="../JS/user-navbar.js"></script>
</body>
</html>
