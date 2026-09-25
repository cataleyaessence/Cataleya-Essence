<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if (empty($_SESSION['booking_csrf_token'])) {
    $_SESSION['booking_csrf_token'] = bin2hex(random_bytes(32));
}
$bookingCsrfToken = $_SESSION['booking_csrf_token'];

// Fetch all bookings for the user (we'll do client-side filtering)
$sql = "
    SELECT b.id, b.service_id, b.booking_date, b.booking_time, b.status, b.created_at, b.total_amount,
           s.name as service_name, s.image_url as service_image, s.price,
           st.full_name as stylist_name
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    LEFT JOIN staff st ON b.staff_id = st.id
    WHERE b.user_id = ?
      AND b.status IN ('confirmed', 'completed', 'cancelled', 'rescheduled')
    ORDER BY b.booking_date DESC, b.booking_time DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

$servicesStmt = $pdo->query(
    "SELECT id, name, price
     FROM services
     WHERE is_active = 1
     ORDER BY name"
);
$availableServices = $servicesStmt->fetchAll();

// Count bookings by status for the badge counts
$status_counts = [
    'all' => count($bookings),
    'confirmed' => 0,
    'rescheduled' => 0,
    'completed' => 0,
    'cancelled' => 0
];
foreach ($bookings as $b) {
    if (isset($status_counts[$b['status']])) {
        $status_counts[$b['status']]++;
    }
}

// Fetch user data for avatar
$stmt = $pdo->prepare("SELECT id, full_name, email, profile_photo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: signin.php');
    exit;
}

// Build initials for avatar
$name_parts = array_filter(explode(' ', trim($user['full_name'])));
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
    <title>My Bookings · Cataleya Essence</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/bookings.css" />
    <link rel="stylesheet" href="../css/user-footer.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body>

    <!-- ===== NAVBAR ===== -->
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

        <!-- Profile Avatar + Dropdown -->
        <div class="profile-wrapper" id="profileWrapper">
            <div class="profile-avatar" id="profileAvatar" role="button" aria-haspopup="true" aria-expanded="false">
                <?php if ($user['profile_photo']): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile" class="avatar-image" />
                <?php else: ?>
                    <span class="avatar-initials"><?php echo htmlspecialchars($initials); ?></span>
                <?php endif; ?>
                <span class="status-dot"></span>
            </div>
            <div class="profile-dropdown" id="profileDropdown" role="menu">
                <div class="dropdown-header">Now</div>
                <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> My Profile</a>
                <div class="dropdown-divider"></div>
                <a href="../auth/logout.php" class="dropdown-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </header>

    <div class="dropdown-overlay" id="dropdownOverlay"></div>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="profile-page">

        <!-- Page Header -->
        <div class="profile-header">
            <div>
                <h1>My Bookings</h1>
                <p>View and manage your appointments</p>
            </div>
        </div>

        <div class="profile-layout">

            <!-- Sidebar Navigation -->
            <aside class="profile-sidebar">
                <nav class="sidebar-nav">
                    <a href="profile.php" class="sidebar-link"><i class="fas fa-user"></i> Profile Overview</a>
                    <a href="bookings.php" class="sidebar-link active"><i class="fas fa-calendar-check"></i> My Bookings</a>
                    <a href="settings.php" class="sidebar-link"><i class="fas fa-cog"></i> Settings</a>
                    <a href="../auth/logout.php" class="sidebar-link sidebar-link--signout"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                </nav>
            </aside>

            <!-- Main Content Area -->
            <div class="profile-main">

                <!-- Filter Tabs -->
                <div class="filter-tabs" id="filterTabs" role="toolbar" aria-label="Filter bookings by status">
                    <button type="button" class="filter-tab active" data-status="all" aria-pressed="true">
                        All <span class="count-badge"><?php echo $status_counts['all']; ?></span>
                    </button>
                    <button type="button" class="filter-tab" data-status="confirmed" aria-pressed="false">
                        <i class="fas fa-check-circle"></i> Confirmed <span class="count-badge"><?php echo $status_counts['confirmed']; ?></span>
                    </button>
                    <button type="button" class="filter-tab" data-status="rescheduled" aria-pressed="false">
                        <i class="fas fa-calendar-pen"></i> Rescheduled <span class="count-badge"><?php echo $status_counts['rescheduled']; ?></span>
                    </button>
                    <button type="button" class="filter-tab" data-status="completed" aria-pressed="false">
                        <i class="fas fa-check-double"></i> Completed <span class="count-badge"><?php echo $status_counts['completed']; ?></span>
                    </button>
                    <button type="button" class="filter-tab" data-status="cancelled" aria-pressed="false">
                        <i class="fas fa-times-circle"></i> Cancelled <span class="count-badge"><?php echo $status_counts['cancelled']; ?></span>
                    </button>
                </div>

                <!-- Bookings List -->
                <div class="bookings-container" id="bookingsContainer">
                    <?php if (count($bookings) === 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-plus"></i>
                            <p>You have no bookings yet.</p>
                            <a href="serv.php" class="btn-book-now">Book a Service</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-card" data-status="<?php echo htmlspecialchars($booking['status']); ?>" data-booking-id="<?php echo $booking['id']; ?>">
                                <div class="booking-icon-wrap">
                                    <?php if ($booking['service_image']): ?>
                                        <img src="<?php echo htmlspecialchars($booking['service_image']); ?>" alt="<?php echo htmlspecialchars($booking['service_name']); ?>" />
                                    <?php else: ?>
                                        <i class="fas fa-spa"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="booking-body">
                                    <span class="service-name"><?php echo htmlspecialchars($booking['service_name']); ?></span>
                                    <div class="booking-meta">
                                        <span><i class="fas fa-calendar-day"></i> <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></span>
                                        <span><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($booking['booking_time'])); ?></span>
                                        <?php if ($booking['stylist_name']): ?>
                                            <span><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($booking['stylist_name']); ?></span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-tag"></i> ₱<?php echo number_format($booking['total_amount'] ?: $booking['price'], 2); ?></span>
                                    </div>
                                </div>
                                <div class="booking-actions">
                                    <span class="status-badge <?php echo getStatusBadgeClass($booking['status']); ?>">
                                        <i class="fas <?php echo getStatusIcon($booking['status']); ?>"></i>
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                    <?php if ($booking['status'] === 'confirmed'): ?>
                                        <button type="button" class="btn-reschedule-booking" data-booking-id="<?php echo (int) $booking['id']; ?>" data-booking-date="<?php echo htmlspecialchars($booking['booking_date']); ?>" data-booking-time="<?php echo htmlspecialchars($booking['booking_time']); ?>" data-service-id="<?php echo (int) $booking['service_id']; ?>" data-service-price="<?php echo htmlspecialchars(number_format((float) $booking['price'], 2, '.', '')); ?>">
                                            <i class="fas fa-calendar-pen"></i> Reschedule
                                        </button>
                                    <?php endif; ?>
                                    <?php if (in_array($booking['status'], ['confirmed', 'rescheduled'], true)): ?>
                                        <button type="button" class="btn-cancel-booking" data-booking-id="<?php echo (int) $booking['id']; ?>">
                                            <i class="fas fa-ban"></i> Cancel
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="filter-empty-state" id="filterEmptyState" hidden role="status" aria-live="polite">
                        <i class="fas fa-calendar-xmark" aria-hidden="true"></i>
                        <p>No bookings match this filter.</p>
                    </div>
                </div>

            </div> <!-- /profile-main -->
        </div> <!-- /profile-layout -->
    </main>

    <?php require __DIR__ . '/includes/user-footer.php'; ?>

    <!-- ===== BOOKING DETAILS MODAL ===== -->
    <div class="modal-overlay" id="bookingModal">
        <div class="modal-container">
            <div class="modal-card booking-details-card">
                <button class="modal-close" id="bookingModalClose">&times;</button>
                
                <div class="modal-header">
                    <p class="modal-kicker"><i class="fas fa-calendar-heart"></i> Appointment overview</p>
                    <h2>Booking Details</h2>
                </div>
                
                <div class="booking-details-content" id="bookingDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
                
                <div class="modal-footer">
                    <button class="btn-close-modal" id="closeBookingModal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== RESCHEDULE BOOKING MODAL ===== -->
    <div class="modal-overlay" id="rescheduleModal" aria-hidden="true">
        <div class="modal-container">
            <div class="modal-card reschedule-modal-card" role="dialog" aria-modal="true" aria-labelledby="rescheduleModalTitle">
                <button type="button" class="modal-close" id="rescheduleModalClose" aria-label="Close reschedule form">&times;</button>
                <div class="modal-header">
                    <h2 id="rescheduleModalTitle">Reschedule Booking</h2>
                </div>
                <form id="rescheduleForm" class="reschedule-form">
                    <input type="hidden" id="rescheduleBookingId" name="booking_id" />
                    <div class="reschedule-field">
                        <label for="rescheduleService">Service</label>
                        <select id="rescheduleService" name="service_id" required>
                            <option value="">Choose a service</option>
                        </select>
                    </div>
                    <div class="reschedule-field">
                        <label for="rescheduleDate">New date</label>
                        <input type="date" id="rescheduleDate" name="booking_date" required />
                    </div>
                    <div class="reschedule-field">
                        <label for="rescheduleTime">Available time</label>
                        <select id="rescheduleTime" name="booking_time" required disabled>
                            <option value="">Choose a date first</option>
                        </select>
                    </div>
                    <p class="reschedule-policy-note"><i class="fas fa-circle-info"></i> You may switch only to a service with the same original price. Your completed downpayment remains applied.</p>
                    <p class="reschedule-message" id="rescheduleMessage" role="status" aria-live="polite"></p>
                    <div class="modal-footer reschedule-footer">
                        <button type="button" class="btn-secondary-modal" id="cancelReschedule">Cancel</button>
                        <button type="submit" class="btn-confirm-reschedule" id="confirmReschedule">Confirm Reschedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== CANCEL BOOKING CONFIRMATION ===== -->
    <div class="modal-overlay" id="cancelBookingModal" aria-hidden="true">
        <div class="modal-container">
            <div class="modal-card cancel-booking-modal" role="dialog" aria-modal="true" aria-labelledby="cancelBookingModalTitle">
                <button type="button" class="modal-close" id="cancelBookingModalClose" aria-label="Close cancellation confirmation">&times;</button>
                <div class="modal-header">
                    <p class="modal-kicker warning"><i class="fas fa-triangle-exclamation"></i> Cancellation warning</p>
                    <h2 id="cancelBookingModalTitle">Cancel this booking?</h2>
                </div>
                <div class="cancel-booking-content">
                    <p>Are you sure you want to cancel this booking?</p>
                    <div class="cancel-policy-warning">
                        <i class="fas fa-circle-exclamation"></i>
                        <div>
                            <strong>No refund for the downpayment</strong>
                            <span>Your completed 50% downpayment is non-refundable, and this cancelled booking will not earn rewards points.</span>
                        </div>
                    </div>
                    <p class="cancel-message" id="cancelBookingMessage" role="status" aria-live="polite"></p>
                </div>
                <div class="modal-footer cancel-booking-footer">
                    <button type="button" class="btn-secondary-modal" id="keepBooking">Keep Booking</button>
                    <button type="button" class="btn-confirm-cancel" id="confirmCancelBooking">Yes, Cancel Booking</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== JAVASCRIPT ===== -->
    <script>window.bookingPageConfig = { csrfToken: <?php echo json_encode($bookingCsrfToken); ?>, services: <?php echo json_encode($availableServices); ?> };</script>
    <script src="../JS/bookings.js"></script>

</body>
</html>

<?php
// Helper functions (placed here for clarity, can also be in a separate file)
function getStatusBadgeClass(string $status): string {
    return match ($status) {
        'confirmed' => 'status-confirmed',
        'rescheduled' => 'status-rescheduled',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled',
        default     => 'status-default',
    };
}

function getStatusIcon(string $status): string {
    return match ($status) {
        'confirmed' => 'fa-check-circle',
        'rescheduled' => 'fa-calendar-pen',
        'completed' => 'fa-check-double',
        'cancelled' => 'fa-times-circle',
        default     => 'fa-circle',
    };
}
?>
