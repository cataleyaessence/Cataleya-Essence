<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_activity.php';
require_once __DIR__ . '/../config/rewards.php';

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: signin.php');
    exit;
}

$adminName = trim((string) ($_SESSION['full_name'] ?? 'Admin'));
$adminInitial = strtoupper(substr($adminName, 0, 1)) ?: 'A';

// Get current date or date from query parameter
$currentDate = date('Y-m-d');
if (!empty($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
    $currentDate = $_GET['date'];
}
// Monthly booking calendar. Past completed and cancelled appointments remain
// visible as records, while active bookings retain their reserved time slot.
$calendarMonth = trim((string) ($_GET['calendar_month'] ?? substr($currentDate, 0, 7)));
if (!preg_match('/^\d{4}-\d{2}$/', $calendarMonth)) {
    $calendarMonth = date('Y-m', strtotime($currentDate));
}
$calendarMonthStart = DateTimeImmutable::createFromFormat('!Y-m', $calendarMonth);
if (!$calendarMonthStart || $calendarMonthStart->format('Y-m') !== $calendarMonth) {
    $calendarMonthStart = new DateTimeImmutable('first day of this month');
    $calendarMonth = $calendarMonthStart->format('Y-m');
}
$calendarMonthEnd = $calendarMonthStart->modify('+1 month');
$previousCalendarMonth = $calendarMonthStart->modify('-1 month');
$nextCalendarMonth = $calendarMonthStart->modify('+1 month');
$previousCalendarUrl = 'Admin-Sched.php?' . http_build_query(['calendar_month' => $previousCalendarMonth->format('Y-m')]);
$nextCalendarUrl = 'Admin-Sched.php?' . http_build_query(['calendar_month' => $nextCalendarMonth->format('Y-m')]);
$calendarMonthLabel = $calendarMonthStart->format('F Y');

$bookingCalendarStmt = $pdo->prepare(
    "SELECT b.id, b.booking_date, b.booking_time, b.status,
            COALESCE(u.full_name, 'Customer') AS customer_name,
            COALESCE(s.name, 'Service') AS service_name
     FROM bookings AS b
     LEFT JOIN users AS u ON u.id = b.user_id
     LEFT JOIN services AS s ON s.id = b.service_id
     WHERE b.booking_date >= ? AND b.booking_date < ?
       AND b.status IN ('confirmed', 'rescheduled', 'completed', 'cancelled')
     ORDER BY b.booking_date ASC, b.booking_time ASC, b.id ASC"
);
$bookingCalendarStmt->execute([$calendarMonthStart->format('Y-m-d'), $calendarMonthEnd->format('Y-m-d')]);
$bookingCalendarByDate = [];
foreach ($bookingCalendarStmt->fetchAll() as $calendarBooking) {
    $bookingCalendarByDate[(string) $calendarBooking['booking_date']][] = $calendarBooking;
}
$calendarLeadingDays = (int) $calendarMonthStart->format('w');
$calendarDaysInMonth = (int) $calendarMonthStart->format('t');
$calendarCellCount = (int) (ceil(($calendarLeadingDays + $calendarDaysInMonth) / 7) * 7);

// Previous booking pagination
$previousBookingsLimit = 6;
$previousBookingsPage = max(0, (int)($_GET['previous_page'] ?? 0));
$previousBookingsOffset = $previousBookingsPage * $previousBookingsLimit;

// Fetch most recent previous bookings before the selected date
$stmt = $pdo->prepare("
    SELECT b.id, b.booking_date, b.booking_time, b.status, b.total_amount, b.notes,
           u.full_name as customer_name,
           s.name as service_name,
           st.full_name as staff_name,
           (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE booking_id = b.id AND status = 'completed') AS paid_amount
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN staff st ON b.staff_id = st.id
    WHERE b.booking_date < ?
    ORDER BY b.booking_date DESC, b.booking_time DESC
    LIMIT ?, ?
");
$stmt->bindValue(1, $currentDate, PDO::PARAM_STR);
$stmt->bindValue(2, $previousBookingsOffset, PDO::PARAM_INT);
$stmt->bindValue(3, $previousBookingsLimit + 1, PDO::PARAM_INT);
$stmt->execute();
$previousBookingsRaw = $stmt->fetchAll();
$hasMorePreviousBookings = count($previousBookingsRaw) > $previousBookingsLimit;
$previousBookings = array_slice($previousBookingsRaw, 0, $previousBookingsLimit);

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $bookingId = $_POST['booking_id'] ?? 0;
    $newStatus = $_POST['status'] ?? 'confirmed';
    if (!in_array($newStatus, ['confirmed', 'completed', 'cancelled'], true)) {
        $newStatus = 'confirmed';
    }

    $wasUpdated = false;
    try {
        $pdo->beginTransaction();

        if ($newStatus === 'completed') {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ? AND status IN ('confirmed', 'rescheduled')");
            $stmt->execute([$bookingId]);
            $wasUpdated = $stmt->rowCount() > 0;
            if ($wasUpdated) {
                awardCompletedBookingRewards($pdo, (int) $bookingId);
            }
        } elseif ($newStatus === 'cancelled') {
            $bookingStmt = $pdo->prepare("SELECT user_id FROM bookings WHERE id = ? AND status IN ('confirmed', 'rescheduled') FOR UPDATE");
            $bookingStmt->execute([$bookingId]);
            $booking = $bookingStmt->fetch();
            if ($booking) {
                removeBookingRewardPoints($pdo, (int) $bookingId, (int) $booking['user_id']);
                $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND status IN ('confirmed', 'rescheduled')");
                $stmt->execute([$bookingId]);
                $wasUpdated = $stmt->rowCount() > 0;
            }
        } else {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND status IN ('confirmed', 'rescheduled')");
            $stmt->execute([$bookingId]);
            $wasUpdated = $stmt->rowCount() > 0;
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Admin schedule status update error: ' . $exception->getMessage());
    }

    if ($wasUpdated) {
        logAdminActivity($pdo, (int) $_SESSION['admin_id'], 'booking_status_updated', 'booking', (int) $bookingId, 'Changed booking status to ' . $newStatus);
    }

    // Redirect to refresh data
    header("Location: Admin-Sched.php?date=" . $currentDate);
    exit();
}

// Handle new booking creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_booking') {
    $userId = $_POST['user_id'] ?? 0;
    $serviceId = $_POST['service_id'] ?? 0;
    $staffId = $_POST['staff_id'] ?? null;
    $bookingDate = $_POST['booking_date'] ?? $currentDate;
    $bookingTime = $_POST['booking_time'] ?? '';
    $totalAmount = $_POST['total_amount'] ?? 0;
    $notes = $_POST['notes'] ?? '';

    if ($userId && $serviceId && $bookingTime && $totalAmount > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, service_id, staff_id, booking_date, booking_time, total_amount, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')
        ");
        $stmt->execute([$userId, $serviceId, $staffId, $bookingDate, $bookingTime, $totalAmount, $notes]);
        logAdminActivity($pdo, (int) $_SESSION['admin_id'], 'booking_created', 'booking', (int) $pdo->lastInsertId(), 'Created booking for ' . $bookingDate);

        // Redirect to refresh data
        header("Location: Admin-Sched.php?date=" . $bookingDate);
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin · Cataleya Essence Schedule</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="../css/Admin-Serenity_Sched.css" />
    <link rel="stylesheet" href="../css/admin-sidebar.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body class="admin-page">
    <!-- ═══ NAVBAR ═══ -->
    <header class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">
                <img src="../img/Rectangle 38 (1).png" class="logo-img" alt="Cataleya Essence of Beauty" />
            </div>
            <div class="navbar-brand-text">
                <span class="navbar-brand-name">Cataleya Essence</span>
                <span class="navbar-brand-sub">Admin Panel</span>
            </div>
        </div>
        <div class="navbar-user">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="user-role">Manager</span>
            </div>
            <div class="user-avatar"><?php echo htmlspecialchars($adminInitial, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </header>

    <!-- ═══ LAYOUT ═══ -->
    <div class="layout">

        <!-- ═══ SIDEBAR ═══ -->
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a href="Admin-Sched.php" class="nav-link active"><i class="fas fa-calendar-check"></i> Schedule</a>
                <a href="Admin-BookingStatus.php" class="nav-link"><i class="fas fa-check-circle"></i> Booking Status</a>
                <a href="Admin-Service.php" class="nav-link"><i class="fas fa-hand-sparkles"></i> Services</a>
                <a href="Admin-Staff.php" class="nav-link"><i class="fas fa-user-tie"></i> Staff</a>
                <a href="Admin-Analytics.php" class="nav-link"><i class="fas fa-chart-pie"></i> Analytics Reports</a>
                <a href="Admin-ActivityLog.php" class="nav-link"><i class="fas fa-clipboard-list"></i> Activity Log</a>
                <a href="Admin-Settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-link logout" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- ═══ MAIN ═══ -->
        <main class="main">

            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title-group">
                    <p class="page-eyebrow"><i class="fas fa-calendar-days"></i> Appointment planner</p>
                    <h1 class="page-title">Schedule Overview</h1>
                    <p class="page-sub">Review each day’s appointments, availability, and booking history.</p>
                </div>
                <div class="legend">
                    <span class="legend-item">
                        <span class="legend-dot confirmed"></span> Confirmed
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot rescheduled"></span> Rescheduled
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot completed"></span> Completed
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot cancelled"></span> Cancelled
                    </span>
                </div>
            </div>

            <section class="booking-calendar-card" aria-labelledby="bookingCalendarTitle">
                <div class="booking-calendar-head">
                    <div>
                        <p class="calendar-kicker"><i class="fas fa-calendar-days"></i> Customer appointments</p>
                        <h2 id="bookingCalendarTitle">Booking Calendar</h2>
                        <p>Upcoming appointments and past booking records by day and time.</p>
                    </div>
                    <div class="calendar-month-controls" aria-label="Booking calendar month">
                        <a href="<?= htmlspecialchars($previousCalendarUrl, ENT_QUOTES, 'UTF-8') ?>" class="calendar-month-button" aria-label="Previous month"><i class="fas fa-chevron-left"></i></a>
                        <strong><?= htmlspecialchars($calendarMonthLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                        <a href="<?= htmlspecialchars($nextCalendarUrl, ENT_QUOTES, 'UTF-8') ?>" class="calendar-month-button" aria-label="Next month"><i class="fas fa-chevron-right"></i></a>
                    </div>
                </div>
                <div class="booking-calendar-legend" aria-label="Booking status legend">
                    <span class="booking-calendar-legend-label">Booking process:</span>
                    <span class="booking-calendar-legend-item confirmed"><i></i>Confirmed</span>
                    <span class="booking-calendar-legend-item rescheduled"><i></i>Rescheduled</span>
                    <span class="booking-calendar-legend-item completed"><i></i>Completed</span>
                    <span class="booking-calendar-legend-item cancelled"><i></i>Cancelled</span>
                </div>
                <div class="calendar-rule"><i class="fas fa-circle-check"></i> Completed and cancelled bookings remain here as records. Multiple appointments can be on the same day, but each active time slot is reserved for only one user.</div>

                <div class="booking-calendar-scroll">
                    <div class="booking-calendar-grid" role="grid" aria-label="<?= htmlspecialchars($calendarMonthLabel, ENT_QUOTES, 'UTF-8') ?> bookings">
                        <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday): ?>
                            <div class="booking-calendar-weekday" role="columnheader"><?= $weekday ?></div>
                        <?php endforeach; ?>
                        <?php for ($cellIndex = 0; $cellIndex < $calendarCellCount; $cellIndex++): ?>
                            <?php
                            $cellDate = $calendarMonthStart->modify(sprintf('%+d days', $cellIndex - $calendarLeadingDays));
                            $cellDateKey = $cellDate->format('Y-m-d');
                            $isCurrentCalendarMonth = $cellDate->format('Y-m') === $calendarMonth;
                            $dayBookings = $bookingCalendarByDate[$cellDateKey] ?? [];
                            ?>
                            <article class="booking-calendar-day<?= $isCurrentCalendarMonth ? '' : ' outside-month' ?><?= $cellDateKey === date('Y-m-d') ? ' today' : '' ?>" role="gridcell" aria-label="<?= htmlspecialchars($cellDate->format('F j, Y'), ENT_QUOTES, 'UTF-8') ?>">
                                <time class="booking-calendar-date" datetime="<?= $cellDateKey ?>"><?= $cellDate->format('j') ?></time>
                                <?php if ($isCurrentCalendarMonth && $dayBookings): ?>
                                    <div class="booking-calendar-slots">
                                        <?php foreach ($dayBookings as $dayBooking): ?>
                                            <?php
                                            $bookingTime = date('g:i A', strtotime((string) $dayBooking['booking_time']));
                                            $bookingStatus = (string) $dayBooking['status'];
                                            $bookingStatusLabel = match ($bookingStatus) {
                                                'rescheduled' => 'Rescheduled',
                                                'completed' => 'Completed',
                                                'cancelled' => 'Cancelled',
                                                default => 'Confirmed',
                                            };
                                            $bookingTitle = trim((string) $dayBooking['customer_name']) . ' — ' . trim((string) $dayBooking['service_name']);
                                            ?>
                                            <a href="Admin-Sched.php?date=<?= rawurlencode($cellDateKey) ?>&amp;calendar_month=<?= rawurlencode($calendarMonth) ?>" class="booking-calendar-slot <?= htmlspecialchars($bookingStatus, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($bookingTitle . ' at ' . $bookingTime, ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="booking-slot-time"><i class="far fa-clock"></i><?= htmlspecialchars($bookingTime, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="booking-slot-status"><?= htmlspecialchars($bookingStatusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="booking-slot-customer"><?= htmlspecialchars((string) $dayBooking['customer_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="booking-slot-service"><?= htmlspecialchars((string) $dayBooking['service_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($isCurrentCalendarMonth): ?>
                                    <span class="booking-calendar-free">No booking</span>
                                <?php endif; ?>
                            </article>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>

            <!-- Previous Bookings -->
            <div class="previous-bookings-card">
                <div class="section-header">
                    <div>
                        <h2>Previous Bookings</h2>
                        <p>Recent appointments before the selected date.</p>
                    </div>
                </div>
                <div class="previous-bookings-list" id="previousBookingsList">
                    <?php if (!empty($previousBookings)): ?>
                        <?php foreach ($previousBookings as $booking): ?>
                            <div class="previous-booking-item">
                                <div class="booking-title">
                                    <span class="booking-name"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                                    <span class="booking-status <?php echo htmlspecialchars($booking['status']); ?>"><?php echo ucfirst(htmlspecialchars($booking['status'])); ?></span>
                                </div>
                                <div class="booking-meta">
                                    <span><?php echo htmlspecialchars($booking['service_name']); ?></span>
                                    <span><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> • <?php echo htmlspecialchars(substr($booking['booking_time'], 0, 5)); ?></span>
                                    <span>₱<?php echo number_format((float)$booking['total_amount'], 0); ?></span>
                                </div>
                                <?php if ($booking['status'] === 'cancelled' || !empty($booking['staff_name'])): ?>
                                    <div class="booking-who">
                                        <?php if ($booking['status'] === 'cancelled'): ?>
                                            Cancelled booking for <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong><?php echo !empty($booking['staff_name']) ? ' • Therapist: ' . htmlspecialchars($booking['staff_name']) : ''; ?>
                                        <?php else: ?>
                                            Therapist: <?php echo htmlspecialchars($booking['staff_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-previous-bookings">No previous bookings found yet.</div>
                    <?php endif; ?>
                </div>
                <?php if ($hasMorePreviousBookings): ?>
                    <a class="load-more-btn" href="Admin-Sched.php?<?= htmlspecialchars(http_build_query([
                        'date' => $currentDate,
                        'calendar_month' => $calendarMonth,
                        'previous_page' => $previousBookingsPage + 1,
                    ]), ENT_QUOTES, 'UTF-8') ?>">Load more history</a>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- ═══ TOAST ═══ -->
</body>
</html>
