<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FAQs · Cataleya Essence</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/faq.css" />
    <link rel="stylesheet" href="../css/user-navbar.css" />
    <link rel="stylesheet" href="../css/user-footer.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body>
    <?php require __DIR__ . '/includes/user-navbar.php'; ?>

    <main class="faq-page">
        <section class="faq-hero">
            <p class="eyebrow"><i class="fas fa-circle-question" aria-hidden="true"></i> Help center</p>
            <h1>Frequently asked questions</h1>
            <p>Clear answers for booking, downpayments, rescheduling, cancellations, and rewards.</p>
        </section>

        <section class="faq-list" aria-label="Frequently asked questions">
            <details open>
                <summary>How do I book a service?<i class="fas fa-plus" aria-hidden="true"></i></summary>
                <p>Open <a href="serv.php">Services</a>, choose an available service, select a date and time, then complete the required downpayment. You can view the reservation afterward in <a href="bookings.php">My Bookings</a>.</p>
            </details>
            <details>
                <summary>Is my booking automatically confirmed?<i class="fas fa-plus" aria-hidden="true"></i></summary>
                <p>Yes. After the required 50% downpayment is recorded, the booking is automatically set to <strong>Confirmed</strong>. There is no pending status to wait for in My Bookings.</p>
            </details>
            <details>
                <summary>Can two customers book on the same day?<i class="fas fa-plus" aria-hidden="true"></i></summary>
                <p>Yes, as long as they select different available times. The system prevents active bookings that use the same date and time slot.</p>
            </details>
            <details>
                <summary>Can I reschedule a confirmed booking?<i class="fas fa-plus" aria-hidden="true"></i></summary>
                <p>Yes. Use the Reschedule action in My Bookings to select another available date and time. You can also change the service only when the replacement is in the same price range as the original service. A successful change sends an updated booking notice.</p>
            </details>
            <details>
                <summary>What happens if I cancel my booking?<i class="fas fa-plus" aria-hidden="true"></i></summary>
                <p>You will see a cancellation warning before confirming. The paid downpayment is non-refundable, and a cancelled booking will not earn rewards points.</p>
            </details>
            <details>
                <summary>When do I receive rewards points?<i class="fas fa-plus" aria-hidden="true"></i></summary>
                <p>Points and visits are added only after the service is marked <strong>Completed</strong>. Confirmed, rescheduled, and cancelled bookings do not earn points yet.</p>
            </details>
            <details>
                <summary>Where can I see my booking history and rewards?<i class="fas fa-plus" aria-hidden="true"></i></summary>
                <p>Use <a href="bookings.php">My Bookings</a> for current and past booking records, and visit <a href="Rewards.php">Rewards</a> to see your points, tier, and completed-service activity.</p>
            </details>
            <details>
                <summary>What if I need more help?<i class="fas fa-plus" aria-hidden="true"></i></summary>
                <p>Visit the <a href="contact.php">Contact page</a> or call <a href="tel:+639922353293">+63 992 235 3293</a>. Please include your booking details when asking about an existing appointment.</p>
            </details>
        </section>

        <aside class="faq-help-card">
            <div><i class="fas fa-headset" aria-hidden="true"></i><strong>Still need help?</strong><span>Our support page has our contact details and clinic location.</span></div>
            <a href="contact.php">Contact us <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </aside>
    </main>

    <?php require __DIR__ . '/includes/user-footer.php'; ?>
    <script src="../JS/user-navbar.js"></script>
</body>
</html>
