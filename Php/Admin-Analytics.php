<?php
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: signin.php');
    exit;
}

// Auto-cancel overdue pending bookings while preserving booking history
$todayDate = date('Y-m-d');
$stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_date < ? AND status = 'pending'");
$stmt->execute([$todayDate]);

// ── METRICS ──
$currentMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));

// Monthly booking totals by booking date and status
$stmt = $pdo->prepare("SELECT
        COUNT(*) as total,
        SUM(status = 'confirmed') as confirmed,
        SUM(status = 'pending') as pending,
        SUM(status = 'cancelled') as cancelled,
        SUM(status = 'completed') as completed
    FROM bookings
    WHERE DATE_FORMAT(booking_date, '%Y-%m') = ?");
$stmt->execute([$currentMonth]);
$bookingStats = $stmt->fetch(PDO::FETCH_ASSOC);
$totalBookings = (int)($bookingStats['total'] ?? 0);
$confirmedBookings = (int)($bookingStats['confirmed'] ?? 0);
$pendingBookings = (int)($bookingStats['pending'] ?? 0);
$canceledBookings = (int)($bookingStats['cancelled'] ?? 0);
$completedBookings = (int)($bookingStats['completed'] ?? 0);

$stmt = $pdo->prepare("SELECT
        COUNT(*) as total,
        SUM(status = 'confirmed') as confirmed,
        SUM(status = 'pending') as pending,
        SUM(status = 'cancelled') as cancelled,
        SUM(status = 'completed') as completed
    FROM bookings
    WHERE DATE_FORMAT(booking_date, '%Y-%m') = ?");
$stmt->execute([$lastMonth]);
$lastMonthBookingStats = $stmt->fetch(PDO::FETCH_ASSOC);
$lastMonthBookings = (int)($lastMonthBookingStats['total'] ?? 0);
$lastMonthConfirmed = (int)($lastMonthBookingStats['confirmed'] ?? 0);
$lastMonthPending = (int)($lastMonthBookingStats['pending'] ?? 0);
$lastMonthCanceled = (int)($lastMonthBookingStats['cancelled'] ?? 0);
$lastMonthCompleted = (int)($lastMonthBookingStats['completed'] ?? 0);

// Total Revenue from downpayment payments or estimated downpayment if none exists
$stmt = $pdo->prepare("SELECT COALESCE(SUM(COALESCE(p.amount, b.total_amount * 0.5)), 0) as total FROM bookings b LEFT JOIN payments p ON p.booking_id = b.id AND p.status = 'completed' WHERE DATE_FORMAT(b.booking_date, '%Y-%m') = ?");
$stmt->execute([$currentMonth]);
$totalRevenue = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(COALESCE(p.amount, b.total_amount * 0.5)), 0) as total FROM bookings b LEFT JOIN payments p ON p.booking_id = b.id AND p.status = 'completed' WHERE DATE_FORMAT(b.booking_date, '%Y-%m') = ?");
$stmt->execute([$lastMonth]);
$lastMonthRevenue = $stmt->fetch()['total'];

$revenueChange = $lastMonthRevenue > 0 ? round((($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1) : 0;
$revenueChangeSign = $revenueChange > 0 ? '+' : ($revenueChange < 0 ? '' : '');

$bookingsChange = $lastMonthBookings > 0 ? round((($totalBookings - $lastMonthBookings) / $lastMonthBookings) * 100, 1) : 0;
$bookingsChangeSign = $bookingsChange > 0 ? '+' : ($bookingsChange < 0 ? '' : '');

$confirmedChange = $lastMonthConfirmed > 0 ? round((($confirmedBookings - $lastMonthConfirmed) / $lastMonthConfirmed) * 100, 1) : 0;
$confirmedChangeSign = $confirmedChange > 0 ? '+' : ($confirmedChange < 0 ? '' : '');

$pendingChange = $lastMonthPending > 0 ? round((($pendingBookings - $lastMonthPending) / $lastMonthPending) * 100, 1) : 0;
$pendingChangeSign = $pendingChange > 0 ? '+' : ($pendingChange < 0 ? '' : '');

$canceledChange = $lastMonthCanceled > 0 ? round((($canceledBookings - $lastMonthCanceled) / $lastMonthCanceled) * 100, 1) : 0;
$canceledChangeSign = $canceledChange > 0 ? '+' : ($canceledChange < 0 ? '' : '');

$completedChange = $lastMonthCompleted > 0 ? round((($completedBookings - $lastMonthCompleted) / $lastMonthCompleted) * 100, 1) : 0;
$completedChangeSign = $completedChange > 0 ? '+' : ($completedChange < 0 ? '' : '');

// New Customers
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt->execute([$currentMonth]);
$newCustomers = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt->execute([$lastMonth]);
$lastMonthCustomers = $stmt->fetch()['total'];
$customersChange = $lastMonthCustomers > 0 ? round((($newCustomers - $lastMonthCustomers) / $lastMonthCustomers) * 100, 1) : 0;
$customersChangeSign = $customersChange > 0 ? '+' : ($customersChange < 0 ? '' : '');

$avgBookingValue = $confirmedBookings > 0 ? round($totalRevenue / $confirmedBookings, 2) : 0;

$stmt = $pdo->prepare("SELECT COALESCE(AVG(COALESCE(p.amount, b.total_amount * 0.5)), 0) as avg FROM bookings b LEFT JOIN payments p ON p.booking_id = b.id AND p.status = 'completed' WHERE DATE_FORMAT(b.booking_date, '%Y-%m') = ?");
$stmt->execute([$lastMonth]);
$lastMonthAvg = $stmt->fetch()['avg'];
$avgChange = $lastMonthAvg > 0 ? round((($avgBookingValue - $lastMonthAvg) / $lastMonthAvg) * 100, 1) : 0;
$avgChangeSign = $avgChange > 0 ? '+' : ($avgChange < 0 ? '' : '');

// ── WEEKLY DATA (last 7 days) ──
$weeklyRevenue = [];
$weeklyBookings = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dateLabel = date('D', strtotime($date));
    
    // Revenue
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(COALESCE(p.amount, b.total_amount * 0.5)), 0) as total FROM bookings b LEFT JOIN payments p ON p.booking_id = b.id AND p.status = 'completed' WHERE DATE(b.booking_date) = ?");
    $stmt->execute([$date]);
    $weeklyRevenue[] = [
        'date' => $dateLabel,
        'value' => (float)$stmt->fetch()['total']
    ];
    
    // Bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE DATE(booking_date) = ?");
    $stmt->execute([$date]);
    $weeklyBookings[] = [
        'date' => $dateLabel,
        'count' => (int)$stmt->fetch()['total']
    ];
}

// ── MONTHLY DATA (last 12 months) ──
$monthlyRevenue = [];
$monthlyBookings = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M Y', strtotime("-$i months"));
    
    // Revenue
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(COALESCE(p.amount, b.total_amount * 0.5)), 0) as total FROM bookings b LEFT JOIN payments p ON p.booking_id = b.id AND p.status = 'completed' WHERE DATE_FORMAT(b.booking_date, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $monthlyRevenue[] = [
        'date' => $monthLabel,
        'value' => (float)$stmt->fetch()['total']
    ];
    
    // Bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE DATE_FORMAT(booking_date, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $monthlyBookings[] = [
        'date' => $monthLabel,
        'count' => (int)$stmt->fetch()['total']
    ];
}

// ── ANNUAL DATA (last 5 years) ──
$annualRevenue = [];
$annualBookings = [];
for ($i = 4; $i >= 0; $i--) {
    $year = date('Y', strtotime("-$i years"));
    
    // Revenue
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(COALESCE(p.amount, b.total_amount * 0.5)), 0) as total FROM bookings b LEFT JOIN payments p ON p.booking_id = b.id AND p.status = 'completed' WHERE YEAR(b.booking_date) = ?");
    $stmt->execute([$year]);
    $annualRevenue[] = [
        'date' => (string)$year,
        'value' => (float)$stmt->fetch()['total']
    ];
    
    // Bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE YEAR(booking_date) = ?");
    $stmt->execute([$year]);
    $annualBookings[] = [
        'date' => (string)$year,
        'count' => (int)$stmt->fetch()['total']
    ];
}

// ── BOOKING TREND (last 90 days) ──
$bookingTrend = [];
for ($i = 89; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE DATE(booking_date) = ?");
    $stmt->execute([$date]);
    $bookingTrend[] = [
        'date' => date('M d', strtotime($date)),
        'value' => (int)$stmt->fetch()['total']
    ];
}

// ── TOP STAFF BY USER BOOKINGS ──
$stmt = $pdo->prepare("
    SELECT s.id, s.full_name, s.title, s.rating, s.total_reviews, COUNT(b.id) as booking_count
    FROM staff s
    LEFT JOIN bookings b ON s.id = b.staff_id
      AND b.user_id IS NOT NULL
      AND b.status IN ('pending', 'confirmed', 'completed')
      AND DATE_FORMAT(b.booking_date, '%Y-%m') = ?
    GROUP BY s.id
    ORDER BY booking_count DESC, s.rating DESC
    LIMIT 5
");
$stmt->execute([$currentMonth]);
$staffData = $stmt->fetchAll();

// ── BOOKINGS BY SERVICE ──
$stmt = $pdo->prepare(" 
    SELECT s.name, COUNT(b.id) as booking_count
    FROM services s
    LEFT JOIN bookings b ON s.id = b.service_id AND b.status IN ('confirmed', 'completed')
    AND DATE_FORMAT(b.booking_date, '%Y-%m') = ?
    GROUP BY s.id
    ORDER BY booking_count DESC
");
$stmt->execute([$currentMonth]);
$serviceData = $stmt->fetchAll();

// Calculate percentages for services
$totalServiceBookings = array_sum(array_column($serviceData, 'booking_count'));
foreach ($serviceData as &$service) {
    $service['percentage'] = $totalServiceBookings > 0 ? round(($service['booking_count'] / $totalServiceBookings) * 100, 1) : 0;
}

// ── MONTHLY OVERVIEW (last 12 months) ──
$monthlyOverview = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE DATE_FORMAT(booking_date, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $monthlyOverview[] = [
        'month' => $monthLabel,
        'count' => (int)$stmt->fetch()['total']
    ];
}

// ── INVENTORY STATUS ──
$stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory WHERE quantity > low_stock_threshold");
$inStock = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory WHERE quantity > 0 AND quantity <= low_stock_threshold");
$lowStock = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory WHERE quantity = 0");
$critical = $stmt->fetch()['total'];

$totalInventory = $inStock + $lowStock + $critical;

// ── HANDLE ADD SERVICE (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_service') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $mainCategory = $_POST['main_category'] ?? 'Beauty Services';
    $subCategory = $_POST['sub_category'] ?? '';
    $price = $_POST['price'] ?? 0;
    $duration = $_POST['duration_minutes'] ?? 60;
    $imageUrl = $_POST['image_url'] ?? '';

    if ($name && $subCategory && $price > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO services (name, description, main_category, sub_category, price, duration_minutes, image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $mainCategory, $subCategory, $price, $duration, $imageUrl]);
        $success = true;
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Catalogya Essence of Beauty – Booking Analytics</title>
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" defer></script>
    <link rel="stylesheet" href="../css/Admin-Booking Analytics.css" />
    <link rel="stylesheet" href="../css/admin-sidebar.css" />
</head>
<body>

    <!-- ── TOP NAVBAR ── -->
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

         <!-- ── SIDEBAR ── -->
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a href="Admin-Sched.php" class="nav-link"><i class="fas fa-calendar-check"></i> Schedule</a>
                <a href="Admin-BookingStatus.php" class="nav-link"><i class="fas fa-check-circle"></i> Booking Status</a>
                <a href="Admin-Service.php" class="nav-link"><i class="fas fa-hand-sparkles"></i> Services</a>
                <a href="Admin-Staff.php" class="nav-link"><i class="fas fa-user-tie"></i> Staff</a>
                <a href="Admin-Analytics.php" class="nav-link active"><i class="fas fa-chart-pie"></i> Analytics Reports</a>
                <a href="Admin-ActivityLog.php" class="nav-link"><i class="fas fa-clipboard-list"></i> Activity Log</a>
                <a href="Admin-Settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-link logout" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- ── MAIN CONTENT ── -->
        <main class="main">

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Booking Analytics</h1>
                    <p class="page-sub">Monitoring sales performance and booking trends.</p>
                </div>
            </div>
            <!-- Metrics Row -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-label">Total Appointments</div>
                    <div class="metric-value"><?php echo $totalBookings; ?></div>
                    <span class="metric-change"><span class="<?php echo $bookingsChange > 0 ? 'up' : ($bookingsChange < 0 ? 'down' : 'neutral'); ?>"><?php echo $bookingsChangeSign . $bookingsChange; ?>%</span> vs last month</span>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Confirmed</div>
                    <div class="metric-value"><?php echo $confirmedBookings; ?></div>
                    <span class="metric-change"><span class="<?php echo $confirmedChange > 0 ? 'up' : ($confirmedChange < 0 ? 'down' : 'neutral'); ?>"><?php echo $confirmedChangeSign . $confirmedChange; ?>%</span> vs last month</span>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Pending</div>
                    <div class="metric-value"><?php echo $pendingBookings; ?></div>
                    <span class="metric-change"><span class="<?php echo $pendingChange > 0 ? 'up' : ($pendingChange < 0 ? 'down' : 'neutral'); ?>"><?php echo $pendingChangeSign . $pendingChange; ?>%</span> vs last month</span>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Cancelled</div>
                    <div class="metric-value"><?php echo $canceledBookings; ?></div>
                    <span class="metric-change"><span class="<?php echo $canceledChange > 0 ? 'up' : ($canceledChange < 0 ? 'down' : 'neutral'); ?>"><?php echo $canceledChangeSign . $canceledChange; ?>%</span> vs last month</span>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Downpayment Revenue</div>
                    <div class="metric-value">₱<?php echo number_format($totalRevenue, 0); ?></div>
                    <span class="metric-change"><span class="<?php echo $revenueChange > 0 ? 'up' : ($revenueChange < 0 ? 'down' : 'neutral'); ?>"><?php echo $revenueChangeSign . $revenueChange; ?>%</span> vs last month</span>
                </div>
            </div>

            <!-- Top Grid: Chart + Stats -->
            <div class="top-grid">

                <div class="chart-card">
                    <div class="chart-header">
                        <h2 class="chart-title">Booking Trend</h2>
                        <div class="period-tabs">
                            <button class="period-tab active" data-period="weekly">Weekly</button>
                            <button class="period-tab" data-period="monthly">Monthly</button>
                            <button class="period-tab" data-period="annual">Annual</button>
                        </div>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <div class="stats-col">
                    <div class="revenue-card">
                        <span class="revenue-arrow">↗</span>
                        <div class="revenue-amount">₱<?php echo number_format($totalRevenue, 0); ?></div>
                        <div class="revenue-label">Monthly Downpayment Revenue</div>
                        <span class="revenue-badge">
                            <span class="badge-pct"><?php echo $revenueChangeSign . $revenueChange; ?>%</span>
                            vs last month
                        </span>
                    </div>
                </div>
            </div>
            <!-- Staff + Services -->
            <div class="staff-services-grid">

                <div class="staff-card">
                    <h3 class="staff-card-title">Top Staff by Bookings</h3>
                    <div id="staffList"></div>
                </div>

                <div class="services-card">
                    <h3 class="services-card-title">Bookings by Service</h3>
                    <div class="services-chart-wrap">
                        <canvas id="servicesPieChart"></canvas>
                    </div>
                </div>

            </div>

            <!-- Overview Bar -->
            <div class="overview-card">
                <div class="overview-header">
                    <h3 class="overview-card-title">Bookings Overview</h3>
                    <div class="period-tabs">
                        <button class="period-tab active" data-period="daily">Daily</button>
                        <button class="period-tab" data-period="weekly">Weekly</button>
                        <button class="period-tab" data-period="monthly">Monthly</button>
                        <button class="period-tab" data-period="annual">Annual</button>
                    </div>
                </div>
                <div class="overview-chart-wrap">
                    <canvas id="overviewBarChart"></canvas>
                </div>
            </div>

        </main>
    </div>
    <script>
        window.analyticsData = {
            staffData: <?php echo json_encode($staffData); ?>,
            serviceData: <?php echo json_encode($serviceData); ?>,
            monthlyOverview: <?php echo json_encode($monthlyOverview); ?>,
            bookingTrend: <?php echo json_encode($bookingTrend); ?>,
            inventory: <?php echo json_encode([
                'inStock' => $inStock,
                'lowStock' => $lowStock,
                'critical' => $critical,
                'total' => $totalInventory
            ]); ?>,
            periods: {
                weekly: {
                    revenue: <?php echo json_encode($weeklyRevenue); ?>,
                    bookings: <?php echo json_encode($weeklyBookings); ?>
                },
                monthly: {
                    revenue: <?php echo json_encode($monthlyRevenue); ?>,
                    bookings: <?php echo json_encode($monthlyBookings); ?>
                },
                annual: {
                    revenue: <?php echo json_encode($annualRevenue); ?>,
                    bookings: <?php echo json_encode($annualBookings); ?>
                }
            }
        };
    </script>
    <script src="../JS/Admin-Booking Analytics.js" defer></script>
</body>
</html>
