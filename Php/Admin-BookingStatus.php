<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_activity.php';
require_once __DIR__ . '/../config/rewards.php';

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: signin.php');
    exit;
}

function parseBookingDateTime(string $bookingDate, string $bookingTime) {
    $dateTimeString = trim($bookingDate . ' ' . $bookingTime);
    if ($dateTimeString === '') {
        return false;
    }

    $formats = [
        'Y-m-d H:i',
        'Y-m-d H:i:s',
        'Y-m-d g:i A',
        'Y-m-d h:i A',
        'Y-m-d g:iA',
        'Y-m-d h:iA',
    ];

    foreach ($formats as $format) {
        $dateTime = DateTime::createFromFormat($format, $dateTimeString);
        if ($dateTime instanceof DateTime) {
            return $dateTime;
        }
    }

    try {
        return new DateTime($dateTimeString);
    } catch (Exception $e) {
        return false;
    }
}

// Handle admin status transitions for bookings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    $bookingId = (int)$_POST['booking_id'];
    $allowedTransitions = [
        'cancel_booking' => 'cancelled',
        'complete_booking' => 'completed',
    ];

    if ($bookingId > 0 && isset($allowedTransitions[$_POST['action']])) {
        $newStatus = $allowedTransitions[$_POST['action']];
        $wasUpdated = false;

        try {
            $pdo->beginTransaction();

            if ($_POST['action'] === 'complete_booking') {
                $checkStmt = $pdo->prepare("SELECT booking_date, booking_time, status FROM bookings WHERE id = ? AND status IN ('confirmed', 'rescheduled') FOR UPDATE");
                $checkStmt->execute([$bookingId]);
                $booking = $checkStmt->fetch();
                if ($booking) {
                    $scheduledDateTime = parseBookingDateTime($booking['booking_date'], $booking['booking_time']);
                    if ($scheduledDateTime === false || new DateTime() >= $scheduledDateTime) {
                        $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed', updated_at = NOW() WHERE id = ? AND status IN ('confirmed', 'rescheduled')");
                        $stmt->execute([$bookingId]);
                        $wasUpdated = $stmt->rowCount() > 0;
                        if ($wasUpdated) {
                            awardCompletedBookingRewards($pdo, $bookingId);
                        }
                    }
                }
            } else {
                // A completed booking cannot be cancelled. Active bookings have
                // no reward points under the completion-only rewards policy.
                $checkStmt = $pdo->prepare("SELECT user_id FROM bookings WHERE id = ? AND status IN ('confirmed', 'rescheduled') FOR UPDATE");
                $checkStmt->execute([$bookingId]);
                $booking = $checkStmt->fetch();
                if ($booking) {
                    removeBookingRewardPoints($pdo, $bookingId, (int) $booking['user_id']);
                    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND status IN ('confirmed', 'rescheduled')");
                    $stmt->execute([$bookingId]);
                    $wasUpdated = $stmt->rowCount() > 0;
                }
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Admin booking status update error: ' . $exception->getMessage());
        }

        if ($wasUpdated) {
            logAdminActivity($pdo, (int) $_SESSION['admin_id'], 'booking_status_updated', 'booking', $bookingId, 'Changed booking status to ' . $newStatus);
        }
    }

    header('Location: Admin-BookingStatus.php');
    exit();
}

$stmt = $pdo->prepare(
    "SELECT b.id, b.booking_date, b.booking_time, b.status, b.total_amount, b.notes, b.updated_at,
            u.full_name AS customer_name, u.email AS customer_email,
            s.name AS service_name, s.duration_minutes,
            st.full_name AS staff_name
     FROM bookings b
     LEFT JOIN users u ON b.user_id = u.id
     LEFT JOIN services s ON b.service_id = s.id
     LEFT JOIN staff st ON b.staff_id = st.id
     WHERE b.status IN ('confirmed', 'rescheduled', 'completed', 'cancelled')
     ORDER BY FIELD(b.status, 'confirmed', 'rescheduled', 'completed', 'cancelled'), b.booking_date ASC, b.booking_time ASC"
);
$stmt->execute();
$bookings = $stmt->fetchAll();
$bookingCount = count($bookings);

$statusCounts = [
    'confirmed' => 0,
    'rescheduled' => 0,
    'completed' => 0,
    'cancelled' => 0,
];
$statusStmt = $pdo->query("SELECT status, COUNT(*) AS count FROM bookings GROUP BY status");
foreach ($statusStmt->fetchAll() as $row) {
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = (int)$row['count'];
    }
}
$activeBookingCount = $statusCounts['confirmed'] + $statusCounts['rescheduled'];

// Lightweight state used by the admin page to detect a customer booking or a
// status change without repeatedly reloading the full booking list.
$bookingSnapshot = array_map(static function (array $booking): array {
    return [
        'id' => (int) $booking['id'],
        'date' => (string) $booking['booking_date'],
        'time' => (string) $booking['booking_time'],
        'status' => (string) $booking['status'],
        'updated_at' => (string) $booking['updated_at'],
    ];
}, $bookings);
$bookingSnapshotSignature = hash('sha256', json_encode($bookingSnapshot));

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'snapshot')) {
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode([
        'success' => true,
        'signature' => $bookingSnapshotSignature,
        'booking_count' => $bookingCount,
        'active_count' => $activeBookingCount,
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin · Booking Status</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../css/Admin-Serenity_Sched.css" />
    <link rel="stylesheet" href="../css/Admin-BookingStatus.css" />
    <link rel="stylesheet" href="../css/admin-sidebar.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body class="admin-page" data-booking-snapshot="<?php echo htmlspecialchars($bookingSnapshotSignature, ENT_QUOTES, 'UTF-8'); ?>">
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
                <span class="user-name">Admin</span>
                <span class="user-role">Manager</span>
            </div>
            <div class="user-avatar">A</div>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a href="Admin-Sched.php" class="nav-link"><i class="fas fa-calendar-check"></i> Schedule</a>
                <a href="Admin-BookingStatus.php" class="nav-link active"><i class="fas fa-check-circle"></i> Booking Status</a>
                <a href="Admin-Service.php" class="nav-link"><i class="fas fa-hand-sparkles"></i> Services</a>
                <a href="Admin-Staff.php" class="nav-link"><i class="fas fa-user-tie"></i> Staff</a>
                <a href="Admin-Analytics.php" class="nav-link"><i class="fas fa-chart-pie"></i> Analytics Reports</a>
                <a href="Admin-ActivityLog.php" class="nav-link"><i class="fas fa-clipboard-list"></i> Activity Log</a>
                <a href="Admin-Settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-link logout" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <main class="main">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Booking Status</h1>
                    <p class="page-sub">Bookings are confirmed automatically after the 50% downpayment.</p>
                </div>
            </div>

            <div class="booking-status-card">
                <div>
                    <h2>Active Bookings</h2>
                    <p class="page-sub">Confirmed appointments and customer reschedules with a recorded 50% downpayment.</p>
                </div>
                <div class="status-value"><?php echo $activeBookingCount; ?></div>
            </div>

            <div class="status-summary-grid">
                <div class="status-summary-card confirmed" data-status="confirmed">
                    <span class="summary-label">Confirmed</span>
                    <span class="summary-value"><?php echo $statusCounts['confirmed']; ?></span>
                </div>
                <div class="status-summary-card rescheduled" data-status="rescheduled">
                    <span class="summary-label">Rescheduled</span>
                    <span class="summary-value"><?php echo $statusCounts['rescheduled']; ?></span>
                </div>
                <div class="status-summary-card completed" data-status="completed">
                    <span class="summary-label">Completed</span>
                    <span class="summary-value"><?php echo $statusCounts['completed']; ?></span>
                </div>
                <div class="status-summary-card cancelled" data-status="cancelled">
                    <span class="summary-label">Canceled</span>
                    <span class="summary-value"><?php echo $statusCounts['cancelled']; ?></span>
                </div>
            </div>

            <div class="previous-bookings-card">
                <div class="section-header">
                    <div>
                        <h2>Bookings</h2>
                        <p>Manage confirmed, rescheduled, completed, and canceled appointments.</p>
                        <p class="booking-live-status" id="bookingLiveStatus"><i class="fas fa-circle-dot"></i> Live updates are on.</p>
                    </div>
                </div>
                <div class="booking-filter-bar">
                    <button type="button" class="filter-button active" data-status="all">All</button>
                    <button type="button" class="filter-button" data-status="confirmed">Confirmed</button>
                    <button type="button" class="filter-button" data-status="rescheduled">Rescheduled</button>
                    <button type="button" class="filter-button" data-status="completed">Completed</button>
                    <button type="button" class="filter-button" data-status="cancelled">Canceled</button>
                </div>
                <div class="previous-bookings-list">
                    <?php if ($bookingCount === 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-check"></i>
                            <p>No booking requests at the moment.</p>
                            <a href="Admin-Sched.php" class="btn-book-now">Return to schedule</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <?php
                                $statusClass = 'confirmed';
                                switch ($booking['status']) {
                                    case 'confirmed':
                                        $statusClass = 'confirmed';
                                        break;
                                    case 'rescheduled':
                                        $statusClass = 'rescheduled';
                                        break;
                                    case 'completed':
                                        $statusClass = 'completed';
                                        break;
                                    case 'cancelled':
                                        $statusClass = 'cancelled';
                                        break;
                                }
                                $statusLabel = ucfirst($booking['status']);
                            ?>
                            <div class="previous-booking-item" data-status="<?php echo $statusClass; ?>">
                                <div class="booking-title">
                                    <span class="booking-name"><?php echo htmlspecialchars($booking['customer_name'] ?: 'Guest'); ?></span>
                                    <span class="booking-status <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                </div>
                                <div class="booking-meta">
                                    <span><?php echo htmlspecialchars($booking['service_name'] ?: 'Service'); ?></span>
                                    <span><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> • <?php echo htmlspecialchars(substr($booking['booking_time'], 0, 5)); ?></span>
                                    <span>₱<?php echo number_format((float)$booking['total_amount'], 0); ?></span>
                                </div>
                                <div class="booking-details">
                                    <span><strong>User:</strong> <?php echo htmlspecialchars($booking['customer_name'] ?: 'Guest'); ?></span>
                                    <span><strong>Email:</strong> <?php echo htmlspecialchars($booking['customer_email'] ?: 'N/A'); ?></span>
                                    <span><strong>Therapist:</strong> <?php echo htmlspecialchars($booking['staff_name'] ?: 'Unassigned'); ?></span>
                                    <span><strong>Duration:</strong> <?php echo htmlspecialchars($booking['duration_minutes'] ? $booking['duration_minutes'] . ' mins' : 'Standard'); ?></span>
                                </div>
                                <?php if (!empty($booking['notes'])): ?>
                                    <div class="booking-who">Notes: <?php echo htmlspecialchars($booking['notes']); ?></div>
                                <?php endif; ?>
                                <div class="booking-actions">
                                    <?php if (in_array($booking['status'], ['confirmed', 'rescheduled'], true)): ?>
                                        <?php
                                            $scheduledDateTime = parseBookingDateTime($booking['booking_date'], $booking['booking_time']);
                                            $canComplete = $scheduledDateTime === false || new DateTime() >= $scheduledDateTime;
                                        ?>
                                        <form method="post" class="booking-actions-form">
                                            <input type="hidden" name="action" value="complete_booking" />
                                            <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>" />
                                            <button type="submit" class="booking-action-button complete" <?php echo $canComplete ? '' : 'disabled'; ?>><?php echo $canComplete ? 'Complete' : 'Complete (Not Yet)'; ?></button>
                                        </form>
                                        <?php if (!$canComplete): ?>
                                            <div class="booking-action-note">Can complete only at or after scheduled date/time.</div>
                                        <?php endif; ?>
                                        <form method="post" class="booking-actions-form">
                                            <input type="hidden" name="action" value="cancel_booking" />
                                            <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>" />
                                            <button type="submit" class="booking-action-button cancel">Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <div class="booking-actions-note">No actions available for completed or canceled bookings.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="no-bookings-message" hidden>No bookings match this status. Try another filter.</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../JS/Admin-BookingStatus.js"></script>
</body>
</html>
