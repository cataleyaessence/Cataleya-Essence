<?php
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: signin.php');
    exit;
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
}

$where = ['u.is_admin = 0'];
$params = [];
if ($search !== '') {
    $where[] = '(u.full_name LIKE ? OR u.email LIKE ? OR COALESCE(u.phone, \'\') LIKE ?)';
    $likeSearch = '%' . $search . '%';
    array_push($params, $likeSearch, $likeSearch, $likeSearch);
}
$whereSql = implode(' AND ', $where);
$recordsStatement = $pdo->prepare(
    "SELECT
        u.id,
        u.full_name,
        u.email,
        u.phone,
        u.created_at,
        COUNT(DISTINCT b.id) AS booking_count,
        MAX(b.booking_date) AS latest_booking_date,
        COALESCE(r.points, 0) AS reward_points
     FROM users u
     LEFT JOIN bookings b ON b.user_id = u.id
     LEFT JOIN rewards r ON r.user_id = u.id
     WHERE {$whereSql}
     GROUP BY u.id, u.full_name, u.email, u.phone, u.created_at, r.points
     ORDER BY u.created_at DESC, u.full_name ASC"
);
$recordsStatement->execute($params);
$userRecords = $recordsStatement->fetchAll(PDO::FETCH_ASSOC);

$customerTotals = $pdo->query(
    "SELECT
        COUNT(*) AS total_customers
     FROM users
     WHERE is_admin = 0"
)->fetch(PDO::FETCH_ASSOC) ?: [];
$customersWithBookings = (int) $pdo->query(
    "SELECT COUNT(DISTINCT b.user_id)
     FROM bookings b
     INNER JOIN users u ON u.id = b.user_id
     WHERE u.is_admin = 0"
)->fetchColumn();
$rewardPointsTotal = (int) $pdo->query(
    "SELECT COALESCE(SUM(r.points), 0)
     FROM rewards r
     INNER JOIN users u ON u.id = r.user_id
     WHERE u.is_admin = 0"
)->fetchColumn();

$totalCustomers = (int) ($customerTotals['total_customers'] ?? 0);
$adminName = trim((string) ($_SESSION['full_name'] ?? 'Admin')) ?: 'Admin';
$adminInitial = strtoupper(substr($adminName, 0, 1)) ?: 'A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Records - Cataleya Essence</title>
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/Admin-Booking Analytics.css" />
    <link rel="stylesheet" href="../css/admin-sidebar.css" />
    <link rel="stylesheet" href="../css/Admin-UserRecords.css" />
</head>
<body class="user-records-page admin-page">
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
                <a href="Admin-UserRecords.php" class="nav-link active"><i class="fas fa-users"></i> User Records</a>
                <a href="Admin-Analytics.php" class="nav-link"><i class="fas fa-chart-pie"></i> Analytics Reports</a>
                <a href="Admin-CustomerRanking.php" class="nav-link"><i class="fas fa-trophy"></i> Customer Ranking</a>
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
                    <span class="page-eyebrow"><i class="fas fa-address-book"></i> Customer directory</span>
                    <h1 class="page-title">User Records</h1>
                    <p class="page-sub">Review registered customer accounts, booking history, and reward points.</p>
                </div>
                <div class="records-count" aria-label="Records displayed">
                    <i class="fas fa-list"></i>
                    <div><span>Displayed records</span><strong><?php echo number_format(count($userRecords)); ?></strong></div>
                </div>
            </section>

            <section class="records-summary" aria-label="User record summary">
                <article class="records-stat-card records-stat-card--rose"><span><i class="fas fa-users"></i></span><div><small>Total customers</small><strong><?php echo number_format($totalCustomers); ?></strong></div></article>
                <article class="records-stat-card records-stat-card--purple"><span><i class="fas fa-calendar-check"></i></span><div><small>Customers with bookings</small><strong><?php echo number_format($customersWithBookings); ?></strong></div></article>
                <article class="records-stat-card records-stat-card--gold"><span><i class="fas fa-star"></i></span><div><small>Reward points</small><strong><?php echo number_format($rewardPointsTotal); ?></strong></div></article>
            </section>

            <section class="records-card" aria-labelledby="recordsTitle">
                <div class="records-card-head">
                    <div>
                        <h2 id="recordsTitle">Customer accounts</h2>
                        <p>Search by name, email, or mobile number.</p>
                    </div>
                    <form id="recordsSearchForm" class="records-search" method="get" action="Admin-UserRecords.php" role="search">
                        <label class="sr-only" for="recordSearch">Search user records</label>
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input id="recordSearch" name="q" type="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search users..." autocomplete="off" />
                        <button type="submit">Search</button>
                    </form>
                </div>

                <?php if (!empty($userRecords)): ?>
                    <div class="records-table-wrap">
                        <table class="records-table">
                            <thead>
                                <tr><th>Customer</th><th>Contact</th><th>Bookings</th><th>Reward points</th><th>Latest booking</th><th>Registered</th></tr>
                            </thead>
                            <tbody id="recordsTableBody">
                                <?php foreach ($userRecords as $record): ?>
                                    <?php
                                        $recordName = (string) $record['full_name'];
                                        $recordSearchText = trim($recordName . ' ' . (string) $record['email'] . ' ' . (string) $record['phone']);
                                    ?>
                                    <tr data-record-search="<?php echo htmlspecialchars($recordSearchText, ENT_QUOTES, 'UTF-8'); ?>">
                                        <td><div class="record-customer"><span class="record-avatar"><i class="fas fa-user" aria-hidden="true"></i></span><strong><?php echo htmlspecialchars($recordName, ENT_QUOTES, 'UTF-8'); ?></strong></div></td>
                                        <td><div class="record-contact"><a href="mailto:<?php echo htmlspecialchars((string) $record['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $record['email'], ENT_QUOTES, 'UTF-8'); ?></a><?php if (!empty($record['phone'])): ?><span><?php echo htmlspecialchars((string) $record['phone'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></div></td>
                                        <td><span class="record-bookings"><i class="fas fa-calendar-check" aria-hidden="true"></i> <?php echo number_format((int) $record['booking_count']); ?></span></td>
                                        <td><span class="record-points"><i class="fas fa-star" aria-hidden="true"></i> <?php echo number_format((int) $record['reward_points']); ?> pts</span></td>
                                        <td><?php echo !empty($record['latest_booking_date']) ? htmlspecialchars(date('M j, Y', strtotime((string) $record['latest_booking_date'])), ENT_QUOTES, 'UTF-8') : '<span class="record-empty">No bookings</span>'; ?></td>
                                        <td><?php echo htmlspecialchars(date('M j, Y', strtotime((string) $record['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="clientSearchEmpty" class="records-client-empty" role="status" aria-live="polite" hidden>No matching user records.</div>
                <?php else: ?>
                    <div class="records-empty"><span><i class="fas fa-users"></i></span><h2>No user records found</h2><p>Try another search term.</p></div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="../JS/Admin-UserRecords.js"></script>
    <script src="../JS/admin-sidebar.js" defer></script>
</body>
</html>
