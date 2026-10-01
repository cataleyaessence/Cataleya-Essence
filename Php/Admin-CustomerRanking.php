<?php
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: signin.php');
    exit;
}

// Every customer with available reward points belongs in the ranking. Booking
// history is used only for the secondary ranking data, so a customer who has
// points but no eligible booking record is never omitted.
$customerRankingStmt = $pdo->query("
    SELECT
        u.id,
        u.full_name,
        COUNT(DISTINCT b.id) AS booking_count,
        COALESCE(SUM(CASE
            WHEN b.status IN ('confirmed', 'rescheduled', 'completed') THEN b.total_amount
            ELSE 0
        END), 0) AS lifetime_spend,
        r.points AS reward_points
    FROM users u
    INNER JOIN rewards r ON r.user_id = u.id
    LEFT JOIN bookings b ON b.user_id = u.id
    WHERE u.is_admin = 0
      AND r.points > 0
    GROUP BY u.id, u.full_name, r.points
    ORDER BY reward_points DESC, booking_count DESC, lifetime_spend DESC, u.full_name ASC
");
$customerRankings = $customerRankingStmt->fetchAll(PDO::FETCH_ASSOC);

$rankingTotals = $pdo->query("
    SELECT
        COUNT(DISTINCT r.user_id) AS ranked_customers,
        COUNT(DISTINCT b.id) AS total_bookings
    FROM rewards r
    INNER JOIN users u ON u.id = r.user_id AND u.is_admin = 0
    LEFT JOIN bookings b ON b.user_id = r.user_id
    WHERE r.points > 0
")->fetch(PDO::FETCH_ASSOC) ?: [];

$rankedCustomers = (int) ($rankingTotals['ranked_customers'] ?? 0);
$totalBookings = (int) ($rankingTotals['total_bookings'] ?? 0);
$topCustomerName = !empty($customerRankings) ? (string) $customerRankings[0]['full_name'] : 'No ranking yet';
$adminName = trim((string) ($_SESSION['full_name'] ?? 'Admin')) ?: 'Admin';
$adminInitial = strtoupper(substr($adminName, 0, 1)) ?: 'A';

// The browser polls this small response so changes to bookings or points are
// reflected without the admin having to refresh the whole page manually.
$rankingSnapshot = array_map(static function (array $customer): array {
    return [
        'id' => (int) $customer['id'],
        'bookings' => (int) $customer['booking_count'],
        'points' => (int) $customer['reward_points'],
        'spend' => (float) $customer['lifetime_spend'],
    ];
}, $customerRankings);
$rankingSnapshotSignature = hash('sha256', json_encode([
    'customers' => $rankingSnapshot,
    'rankedCustomers' => $rankedCustomers,
    'totalBookings' => $totalBookings,
]));

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'snapshot') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode([
        'success' => true,
        'signature' => $rankingSnapshotSignature,
    ]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Customer Ranking - Cataleya Essence</title>
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/Admin-Booking Analytics.css" />
    <link rel="stylesheet" href="../css/admin-sidebar.css" />
    <link rel="stylesheet" href="../css/Admin-CustomerRanking.css" />
</head>
<body class="ranking-page admin-page" data-ranking-snapshot="<?php echo htmlspecialchars($rankingSnapshotSignature, ENT_QUOTES, 'UTF-8'); ?>">
    <header class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo"><img src="../img/Rectangle 38 (1).png" class="logo-img" alt="Cataleya Essence of Beauty" /></div>
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

    <div class="layout">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a href="Admin-Sched.php" class="nav-link"><i class="fas fa-calendar-check"></i> Schedule</a>
                <a href="Admin-BookingStatus.php" class="nav-link"><i class="fas fa-check-circle"></i> Booking Status</a>
                <a href="Admin-Service.php" class="nav-link"><i class="fas fa-hand-sparkles"></i> Services</a>
                <a href="Admin-Staff.php" class="nav-link"><i class="fas fa-user-tie"></i> Staff</a>
                <a href="Admin-UserRecords.php" class="nav-link"><i class="fas fa-users"></i> User Records</a>
                <a href="Admin-Analytics.php" class="nav-link"><i class="fas fa-chart-pie"></i> Analytics Reports</a>
                <a href="Admin-CustomerRanking.php" class="nav-link active"><i class="fas fa-trophy"></i> Customer Ranking</a>
                <a href="Admin-ActivityLog.php" class="nav-link"><i class="fas fa-clipboard-list"></i> Activity Log</a>
                <a href="Admin-Settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-link logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <main class="main">
            <section class="page-header">
                <div>
                    <span class="page-eyebrow"><i class="fas fa-trophy"></i> Customer loyalty</span>
                    <h1 class="page-title">Customer Ranking</h1>
                    <p class="page-sub">Recognize every customer with available reward points as their loyalty grows.</p>
                    <p class="customer-ranking-live-status" id="customerRankingLiveStatus"><i class="fas fa-circle" aria-hidden="true"></i> Live updates are on</p>
                </div>
                <div class="analytics-period" aria-label="Ranking period">
                    <i class="fas fa-infinity"></i>
                    <div>
                        <span>Ranking period</span>
                        <strong>All time</strong>
                    </div>
                </div>
            </section>

            <section class="customer-ranking-summary" aria-label="Customer ranking summary">
                <article class="ranking-stat-card">
                    <span class="ranking-stat-icon ranking-stat-icon--pink"><i class="fas fa-users"></i></span>
                    <div><span>Ranked customers</span><strong><?php echo number_format($rankedCustomers); ?></strong></div>
                </article>
                <article class="ranking-stat-card">
                    <span class="ranking-stat-icon ranking-stat-icon--lilac"><i class="fas fa-calendar-check"></i></span>
                    <div><span>Total bookings</span><strong><?php echo number_format($totalBookings); ?></strong></div>
                </article>
                <article class="ranking-stat-card ranking-stat-card--leader">
                    <span class="ranking-stat-icon ranking-stat-icon--gold"><i class="fas fa-crown"></i></span>
                    <div><span>Current leader</span><strong><?php echo htmlspecialchars($topCustomerName, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                </article>
            </section>

            <section class="customer-ranking-card" aria-labelledby="customerRankingTitle">
                <div class="customer-ranking-card-head">
                    <div>
                        <h2 id="customerRankingTitle">Customer ranking</h2>
                        <p>All customers with reward points, ranked by points, bookings, then lifetime spending.</p>
                    </div>
                    <span class="ranking-limit"><i class="fas fa-list-ol"></i> <?php echo number_format($rankedCustomers); ?> customers</span>
                </div>

                <?php if (!empty($customerRankings)): ?>
                    <div class="customer-ranking-columns" aria-hidden="true">
                        <span>Rank</span><span>Customer</span><span>Bookings</span><span>Rewards</span><span>Lifetime spend</span>
                    </div>
                    <ol class="customer-ranking-list">
                        <?php foreach ($customerRankings as $index => $customer): ?>
                            <?php
                                $rank = $index + 1;
                                $bookingCount = (int) $customer['booking_count'];
                                $rewardPoints = (int) $customer['reward_points'];
                                $customerName = (string) $customer['full_name'];
                            ?>
                            <li class="customer-ranking-row">
                                <span class="customer-ranking-position customer-ranking-position--<?php echo $rank; ?>" aria-label="Rank <?php echo $rank; ?>"><?php echo $rank; ?></span>
                                <div class="customer-ranking-person">
                                    <span class="customer-ranking-avatar">
                                        <i class="fas fa-user" aria-hidden="true"></i>
                                        <span class="sr-only">Customer profile placeholder</span>
                                    </span>
                                    <strong><?php echo htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                                <span class="customer-ranking-services"><i class="fas fa-calendar-check" aria-hidden="true"></i> <?php echo number_format($bookingCount); ?> <span><?php echo $bookingCount === 1 ? 'booking' : 'bookings'; ?></span></span>
                                <span class="customer-ranking-points"><i class="fas fa-star" aria-hidden="true"></i> <?php echo number_format($rewardPoints); ?> pts</span>
                                <strong class="customer-ranking-spend">₱<?php echo number_format((float) $customer['lifetime_spend'], 2); ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <div class="ranking-empty-state">
                        <span><i class="fas fa-trophy"></i></span>
                        <h2>No customer ranking yet</h2>
                        <p>Rankings will appear here once a customer has reward points.</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="../JS/Admin-CustomerRanking.js"></script>
    <script src="../JS/admin-sidebar.js" defer></script>
</body>
</html>
