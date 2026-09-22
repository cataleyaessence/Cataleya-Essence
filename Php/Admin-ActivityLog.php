<?php
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: signin.php');
    exit;
}

$actionLabels = [
    'signed_in' => 'Signed in',
    'signed_out' => 'Signed out',
    'staff_added' => 'Staff added',
    'staff_fired' => 'Staff fired',
    'service_added' => 'Service added',
    'service_updated' => 'Service updated',
    'service_removed' => 'Service removed',
    'booking_created' => 'Booking created',
    'booking_status_updated' => 'Booking updated',
    'profile_updated' => 'Profile updated',
    'password_updated' => 'Password updated',
];
$selectedAction = trim((string) ($_GET['action'] ?? ''));
if ($selectedAction !== '' && !array_key_exists($selectedAction, $actionLabels)) {
    $selectedAction = '';
}

$where = '';
$params = [];
if ($selectedAction !== '') {
    $where = ' WHERE activity.action = ?';
    $params[] = $selectedAction;
}

$activityStmt = $pdo->prepare(
    'SELECT activity.id, activity.action, activity.entity_type, activity.entity_id, activity.details, activity.created_at,
            COALESCE(users.full_name, "Unknown administrator") AS admin_name
     FROM admin_activity_logs AS activity
     LEFT JOIN users ON users.id = activity.admin_id'
    . $where
    . ' ORDER BY activity.created_at DESC, activity.id DESC LIMIT 100'
);
$activityStmt->execute($params);
$activities = $activityStmt->fetchAll();

$todayCount = (int) $pdo->query('SELECT COUNT(*) FROM admin_activity_logs WHERE created_at >= CURDATE()')->fetchColumn();
$totalCount = (int) $pdo->query('SELECT COUNT(*) FROM admin_activity_logs')->fetchColumn();
$adminName = $_SESSION['full_name'] ?? 'Admin';
$adminInitial = strtoupper(substr(trim($adminName), 0, 1)) ?: 'A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Activity Log - Cataleya Essence</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
    <link rel="stylesheet" href="../css/admin-sidebar.css" />
    <link rel="stylesheet" href="../css/Admin-ActivityLog.css" />
</head>
<body>
    <header class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo"><img src="../img/Rectangle 38 (1).png" alt="Cataleya Essence of Beauty" /></div>
            <div class="navbar-brand-text"><span class="navbar-brand-name">Cataleya Essence</span><span class="navbar-brand-sub">Admin Panel</span></div>
        </div>
        <div class="navbar-user">
            <div class="user-info"><span class="user-name"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></span><span class="user-role">Manager</span></div>
            <div class="user-avatar"><?= htmlspecialchars($adminInitial, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a href="Admin-Sched.php" class="nav-link"><i class="fas fa-calendar-check"></i>Schedule</a>
                <a href="Admin-BookingStatus.php" class="nav-link"><i class="fas fa-check-circle"></i>Booking Status</a>
                <a href="Admin-Service.php" class="nav-link"><i class="fas fa-hand-sparkles"></i>Services</a>
                <a href="Admin-Staff.php" class="nav-link"><i class="fas fa-user-tie"></i>Staff</a>
                <a href="Admin-Analytics.php" class="nav-link"><i class="fas fa-chart-pie"></i>Analytics Reports</a>
                <a href="Admin-ActivityLog.php" class="nav-link active"><i class="fas fa-clipboard-list"></i>Activity Log</a>
                <a href="Admin-Settings.php" class="nav-link"><i class="fas fa-cog"></i>Settings</a>
            </nav>
            <div class="sidebar-footer"><a href="../auth/logout.php" class="nav-link logout"><i class="fas fa-sign-out-alt" aria-hidden="true"></i>Logout</a></div>
        </aside>

        <main class="main">
            <section class="page-header">
                <div>
                    <p class="eyebrow"><i class="fas fa-shield-halved"></i> Administrator audit trail</p>
                    <h1>Activity Log</h1>
                    <p>Review recent administrator actions across staff and service management.</p>
                </div>
            </section>

            <section class="activity-stats" aria-label="Activity summary">
                <article class="stat-card"><span class="stat-icon pink"><i class="fas fa-clock-rotate-left"></i></span><div><strong><?= $todayCount ?></strong><span>Actions today</span></div></article>
                <article class="stat-card"><span class="stat-icon lilac"><i class="fas fa-list-check"></i></span><div><strong><?= $totalCount ?></strong><span>Recorded actions</span></div></article>
            </section>

            <section class="log-card">
                <div class="log-card-head">
                    <div><h2>Recent Activity</h2><p>Showing the latest 100 actions.</p></div>
                    <form class="activity-filter" method="get">
                        <label for="activityAction">Show</label>
                        <select id="activityAction" name="action" onchange="this.form.submit()">
                            <option value="">All activity</option>
                            <?php foreach ($actionLabels as $action => $label): ?>
                                <option value="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>"<?= $selectedAction === $action ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($selectedAction !== ''): ?><a href="Admin-ActivityLog.php" class="clear-filter">Clear</a><?php endif; ?>
                    </form>
                </div>

                <?php if (!$activities): ?>
                    <div class="empty-log"><i class="fas fa-clipboard"></i><h3>No activity yet</h3><p>Administrator actions will appear here once they are performed.</p></div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Action</th><th>Details</th><th>Administrator</th><th>Date &amp; Time</th></tr></thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                    <?php $action = (string) $activity['action']; ?>
                                    <tr>
                                        <td><span class="action-badge action-<?= htmlspecialchars(str_replace('_', '-', $action), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($actionLabels[$action] ?? ucwords(str_replace('_', ' ', $action)), ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td><span class="detail-text"><?= htmlspecialchars((string) ($activity['details'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td><?= htmlspecialchars((string) $activity['admin_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="date-cell"><?= htmlspecialchars(date('M j, Y · g:i A', strtotime((string) $activity['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
