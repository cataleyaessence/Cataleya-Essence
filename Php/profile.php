<?php
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT id, full_name, email, phone, address_line1, address_line2, city, province, postal_code, created_at, profile_photo FROM users WHERE id = ?");
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

// Calculate stats
// Total bookings
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_bookings = $stmt->fetch()['total'];

// Upcoming appointments are confirmed after the required downpayment.
$stmt = $pdo->prepare("SELECT COUNT(*) as upcoming FROM bookings WHERE user_id = ? AND status IN ('confirmed', 'rescheduled') AND booking_date >= CURDATE()");
$stmt->execute([$user_id]);
$upcoming_appointments = $stmt->fetch()['upcoming'];

// Services completed
$stmt = $pdo->prepare("SELECT COUNT(*) as completed FROM bookings WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$services_completed = $stmt->fetch()['completed'];

// Format member since date
$member_since = date('F Y', strtotime($user['created_at']));

// Build full address
$address_parts = array_filter([$user['address_line1'], $user['city'], $user['province'], $user['postal_code']]);
$full_address = implode(', ', $address_parts);

// Fetch recent bookings
$stmt = $pdo->prepare("
    SELECT b.id, b.booking_date, b.booking_time, b.status,
           s.name as service_name, s.image_url as service_image
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC, b.booking_time DESC
    LIMIT 3
");
$stmt->execute([$user_id]);
$recent_bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Profile · Cataleya Essence</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/profile.css" />
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
                <a href="profile.php" class="dropdown-item active"><i class="fas fa-user"></i> My Profile</a>
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

        <!-- Profile Header -->
        <div class="profile-header">
            <div>
                <h1>My Profile</h1>
                <p>Manage your information and preferences</p>
            </div>
        </div>

        <div class="profile-layout">

            <!-- Sidebar Navigation -->
            <aside class="profile-sidebar">
                <nav class="sidebar-nav">
                    <a href="profile.php" class="sidebar-link active"><i class="fas fa-user"></i> Profile Overview</a>
                    <a href="bookings.php" class="sidebar-link"><i class="fas fa-calendar-check"></i> My Bookings</a>
                    <a href="settings.php" class="sidebar-link"><i class="fas fa-cog"></i> Settings</a>
                    <a href="../auth/logout.php" class="sidebar-link sidebar-link--signout"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                </nav>
            </aside>

            <div class="profile-main">

        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-avatar-large">
                <?php if ($user['profile_photo']): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile" />
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <div class="profile-name-row">
                    <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                </div>
                <div class="profile-details">
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></p>
                    <p><i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($full_address ?: 'Not provided'); ?></p>
                    <p class="member-since"><i class="fas fa-calendar-alt"></i> Member since <?php echo htmlspecialchars($member_since); ?></p>
                </div>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card"><div class="stat-number"><?php echo $total_bookings; ?></div><div class="stat-label">Total Bookings</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $upcoming_appointments; ?></div><div class="stat-label">Upcoming Appointments</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $services_completed; ?></div><div class="stat-label">Services Completed</div></div>
        </div>

        <!-- Two‑column layout -->
        <div class="profile-content-grid">

            <!-- Recent Bookings -->
            <section class="recent-bookings">
                <div class="section-header">
                    <h3>Recent Bookings</h3>
                    <a href="bookings.php" class="view-all">View All Bookings <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="booking-list">
                    <?php if (count($recent_bookings) > 0): ?>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <div class="booking-item">
                                <div class="booking-icon">
                                    <?php if ($booking['service_image']): ?>
                                        <img src="<?php echo htmlspecialchars($booking['service_image']); ?>" alt="<?php echo htmlspecialchars($booking['service_name']); ?>" class="booking-img" />
                                    <?php else: ?>
                                        <i class="fas fa-spa"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="booking-info">
                                    <span class="booking-name"><?php echo htmlspecialchars($booking['service_name']); ?></span>
                                    <span class="booking-date"><?php echo date('F j, Y', strtotime($booking['booking_date'])); ?> • <?php echo date('g:i A', strtotime($booking['booking_time'])); ?></span>
                                </div>
                                <span class="booking-status <?php echo strtolower($booking['status']); ?>"><?php echo ucfirst($booking['status']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="booking-item">
                            <div class="booking-info">
                                <span class="booking-name">No bookings yet</span>
                                <span class="booking-date">Book your first service to see it here</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            </div>
        </div>
    </main>

    <script src="../JS/profile.js"></script>

</body>
</html>
