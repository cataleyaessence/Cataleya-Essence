<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT id, full_name, email, profile_photo, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: signin.php');
    exit;
}

$name_parts = array_values(array_filter(explode(' ', trim((string) $user['full_name']))));
if (count($name_parts) >= 2) {
    $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[count($name_parts) - 1], 0, 1));
} elseif (count($name_parts) === 1) {
    $initials = strtoupper(substr($name_parts[0], 0, 2));
} else {
    $initials = 'U';
}

// Fetch user's rewards data
$stmt = $pdo->prepare("SELECT points, tier, visits FROM rewards WHERE user_id = ?");
$stmt->execute([$user_id]);
$rewards = $stmt->fetch();

if (!$rewards) {
    // Create rewards record if it doesn't exist
    $stmt = $pdo->prepare("INSERT INTO rewards (user_id, points, tier, visits) VALUES (?, 0, 'bronze', 0)");
    $stmt->execute([$user_id]);
    $rewards = ['points' => 0, 'tier' => 'bronze', 'visits' => 0];
}

// Calculate progress to next tier
$current_points = $rewards['points'];
$current_tier = $rewards['tier'];
$next_tier_threshold = 0;
$progress_percent = 0;
$next_tier_name = '';

$tier_thresholds = [
    'bronze' => 50,
    'silver' => 100,
    'gold' => 150,
    'platinum' => 200
];

if ($current_tier === 'bronze') {
    $next_tier_threshold = $tier_thresholds['silver'];
    $next_tier_name = 'Silver';
    $progress_percent = min(($current_points / $next_tier_threshold) * 100, 100);
} elseif ($current_tier === 'silver') {
    $next_tier_threshold = $tier_thresholds['gold'];
    $next_tier_name = 'Gold';
    $progress_percent = min((($current_points - $tier_thresholds['silver']) / ($next_tier_threshold - $tier_thresholds['silver'])) * 100, 100);
} elseif ($current_tier === 'gold') {
    $next_tier_threshold = $tier_thresholds['platinum'];
    $next_tier_name = 'Platinum';
    $progress_percent = min((($current_points - $tier_thresholds['gold']) / ($next_tier_threshold - $tier_thresholds['gold'])) * 100, 100);
} elseif ($current_tier === 'platinum') {
    $progress_percent = 100;
    $next_tier_name = 'Max';
}

$points_to_next = $current_tier === 'platinum' ? 0 : $next_tier_threshold - $current_points;

$discount_rate = 5;
switch ($current_tier) {
    case 'silver':
        $discount_rate = 10;
        break;
    case 'gold':
        $discount_rate = 15;
        break;
    case 'platinum':
        $discount_rate = 20;
        break;
}

// Show every booking's reward outcome. Only completed services can earn points.
$stmt = $pdo->prepare("
    SELECT s.name AS service_name, b.booking_date, b.booking_time, b.status,
        s.price AS original_price, b.total_amount AS discounted_price,
        COALESCE(SUM(rt.points_earned - rt.points_redeemed), 0) AS points_earned
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    LEFT JOIN reward_transactions rt ON b.id = rt.booking_id
    WHERE b.user_id = ?
      AND b.status IN ('confirmed', 'rescheduled', 'completed', 'cancelled')
    GROUP BY b.id, s.name, b.booking_date, b.booking_time, b.status, s.price, b.total_amount
    ORDER BY b.booking_date DESC, b.booking_time DESC
");
$stmt->execute([$user_id]);
$recent_activity = $stmt->fetchAll();

function rewardZeroPointsReason(string $bookingStatus): string
{
    return match ($bookingStatus) {
        'confirmed' => 'No points yet — points are added after the service is completed.',
        'rescheduled' => 'No points yet — points are added after the rescheduled service is completed.',
        'cancelled' => 'No points earned — cancelled bookings are not eligible for rewards.',
        'completed' => 'No points recorded yet — please ask the spa team to confirm this completed service.',
        default => 'No points yet — only completed services earn rewards.',
    };
}

// Fetch Hall of Fame data
$stmt = $pdo->prepare("
    SELECT u.full_name, r.points, r.tier, r.visits
    FROM users u
    JOIN rewards r ON u.id = r.user_id
    ORDER BY r.points DESC
    LIMIT 8
");
$stmt->execute();
$hall_of_fame = $stmt->fetchAll();

// Get top 3 for podium display
$top3 = array_slice($hall_of_fame, 0, 3);
$remainder = array_slice($hall_of_fame, 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Member Dashboard · Cataleya Essence</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <!-- External CSS -->
  <link rel="stylesheet" href="../css/Landingpage.css" />
    <link rel="stylesheet" href="../css/Rewards.css" />
    <link rel="stylesheet" href="../css/user-footer.css" />
  <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body>

  <!-- ========== NAVBAR ========== -->
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
      <a href="Rewards.php" class="nav-link active">Rewards</a>
    </nav>

    <div class="profile-wrapper" id="profileWrapper">
      <div class="profile-avatar" id="profileAvatar" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-label="Open profile menu">
        <?php if (!empty($user['profile_photo'])): ?>
          <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile" class="avatar-image" />
        <?php else: ?>
          <span class="avatar-initials"><?php echo htmlspecialchars($initials); ?></span>
        <?php endif; ?>
        <span class="status-dot" aria-hidden="true"></span>
      </div>
      <div class="profile-dropdown" id="profileDropdown" role="menu">
        <div class="dropdown-header">Account</div>
        <a href="profile.php" class="dropdown-item" role="menuitem"><i class="fas fa-user"></i>My Profile</a>
        <div class="dropdown-divider"></div>
        <a href="../auth/logout.php" class="dropdown-item logout" role="menuitem"><i class="fas fa-sign-out-alt"></i>Logout</a>
      </div>
    </div>

    <button class="hamburger" id="hamburger" type="button" aria-label="Toggle navigation" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </header>
  <div class="dropdown-overlay" id="dropdownOverlay"></div>

  <!-- ========== MEMBERSHIP REWARDS ========== -->
  <section class="rewards" id="rewards">

    <!-- HEADER -->
    <div class="rewards__header">
      <h1 class="rewards__title">Member Dashboard</h1>
      <p class="rewards__subtitle">Your Rewards Status</p>
      <p class="rewards__desc">
        Track your points, see your tier progress, and view your recent activity.
      </p>
    </div>

    <!-- 4 TIER CARDS -->
    <div class="rewards__tiers">

      <div class="tier-card tier-card--bronze">
        <div class="tier-card__name">Bronze</div>
        <ul class="tier-card__benefits">
          <li>5% discount on all services</li>
          <li>Birthday bonus: 50 extra points</li>
          <li>Monthly newsletter with exclusive tips</li>
          <li>Access to member-only promotions</li>
        </ul>
      </div>

      <div class="tier-card tier-card--silver">
        <div class="tier-card__name">Silver</div>
        <ul class="tier-card__benefits">
          <li>10% discount on all services</li>
          <li>Priority booking access</li>
          <li>Complimentary aromatherapy add on</li>
          <li>Birthday bonus: 100 extra points</li>
          <li>2x points every 3rd visit</li>
        </ul>
      </div>

      <div class="tier-card tier-card--gold">
        <div class="tier-card__name">Gold</div>
        <ul class="tier-card__benefits">
          <li>15% discount on all services</li>
          <li>Free monthly 30-min facial</li>
          <li>Dedicated personal therapist</li>
          <li>Birthday bonus: 200 extra points</li>
          <li>Exclusive Gold member events</li>
          <li>Early access to new treatments</li>
        </ul>
      </div>

      <div class="tier-card tier-card--platinum">
        <div class="tier-card__name">Platinum</div>
        <ul class="tier-card__benefits">
          <li>20% discount on all services</li>
          <li>Monthly complimentary  treatment</li>
          <li>VIP lounge access</li>
          <li>Birthday bonus: 500 extra points</li>
          <li>Exclusive Platinum-only packages</li>
          <li>Dedicated concierge service</li>
          <li>Free product gift quarterly</li>
        </ul>
      </div>

    </div>

    <!-- DIVIDER -->
    <div class="rewards__divider"><span></span></div>

    <!-- ================================================================
    DASHBOARD – Your Rewards Status + Recent Activity
    ================================================================ -->
    <div class="rewards__dashboard">

      <!-- LEFT – Member Status Card -->
      <div class="status-card">
        <span class="status-card__label">Your Rewards Status</span>
        <h2 class="status-card__member-name"><?php echo htmlspecialchars($user['full_name']); ?></h2>
        <p class="status-card__member-since">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?> · <?php echo $rewards['visits']; ?> visits</p>

        <div class="status-card__points-row">
          <span class="status-card__points-number"><?php echo $rewards['points']; ?></span>
          <span class="status-card__points-label">Total Points</span>
        </div>

        <div class="status-card__tier-row">
          <span class="status-card__tier-name"><?php echo ucfirst($rewards['tier']); ?> Member</span>
          <?php if ($current_tier !== 'platinum'): ?>
          <span class="status-card__tier-next"><strong><?php echo $points_to_next; ?></strong> pts to <?php echo $next_tier_name; ?></span>
          <?php else: ?>
          <span class="status-card__tier-next">Max Tier Reached</span>
          <?php endif; ?>
        </div>
        <div class="status-card__discount-row">
          <span class="status-card__discount-label">Current discount</span>
          <span class="status-card__discount-value"><?php echo $discount_rate; ?>% off</span>
        </div>

        <!-- Progress bar -->
        <div class="status-card__progress-wrap">
          <div class="status-card__progress-track">
            <div class="status-card__progress-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
          </div>
          <div class="status-card__progress-label"><?php echo round($progress_percent); ?>% to <?php echo $next_tier_name; ?></div>
        </div>
      </div>

      <!-- RIGHT – Recent Activity -->
      <div class="activity-card">
        <h3 class="activity-card__title">Booking Rewards</h3>

        <?php if (!empty($recent_activity)): ?>
          <?php foreach ($recent_activity as $activity): ?>
            <?php
              $activityPoints = (int) $activity['points_earned'];
              $pointsClass = $activityPoints > 0
                ? 'activity-item__points--positive'
                : ($activityPoints < 0 ? 'activity-item__points--negative' : 'activity-item__points--zero');
              $zeroPointsReason = $activityPoints === 0
                ? rewardZeroPointsReason((string) $activity['status'])
                : null;
            ?>
            <div class="activity-item">
              <div class="activity-item__left">
                <span class="activity-item__name"><?php echo htmlspecialchars($activity['service_name']); ?></span>
                <span class="activity-item__date"><?php echo date('F j, Y \a\t H:i', strtotime($activity['booking_date'] . ' ' . $activity['booking_time'])); ?></span>
                <span class="activity-item__price">Original: ₱<?php echo number_format($activity['original_price'], 2); ?> • Discounted: ₱<?php echo number_format($activity['discounted_price'], 2); ?></span>
                <?php if ($zeroPointsReason): ?>
                  <span class="activity-item__reason"><?php echo htmlspecialchars($zeroPointsReason); ?></span>
                <?php endif; ?>
              </div>
              <span class="activity-item__points <?php echo $pointsClass; ?>"><?php echo $activityPoints; ?> pts</span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="activity-item__name">No booking rewards yet. Points are added after your service is completed.</p>
        <?php endif; ?>
      </div>

    </div>

  </section>
  <!-- ========== HALL OF FAME — NEW SECTION ========== -->
    <section class="hall-of-fame" id="hall-of-fame">

        <div class="hall-of-fame__header">
            <h2 class="hall-of-fame__title">Hall of Fame</h2>
            <p class="hall-of-fame__subtitle">Top Members Leaderboard</p>
            <p class="hall-of-fame__desc">
                Our most dedicated wellness enthusiasts. Could you be next on this list?
            </p>
        </div>

               <!-- Top 3 Members - Column Heights -->
        <div class="hall-of-fame__top3">

            <?php if (count($top3) >= 2): ?>
            <!-- Rank 2 (Silver) -->
            <div class="top-member top-member--rank2">
                <span class="top-member__rank">#2</span>
                <span class="top-member__name"><?php echo htmlspecialchars($top3[1]['full_name'] ?? ''); ?></span>
                <span class="top-member__points"><strong><?php echo number_format($top3[1]['points'] ?? 0, 2); ?></strong> pt</span>
            </div>
            <?php endif; ?>

            <?php if (count($top3) >= 1): ?>
            <!-- Rank 1 (Gold - tallest) -->
            <div class="top-member top-member--rank1">
                <span class="top-member__crown"></span>
                <span class="top-member__rank">#1</span>
                <span class="top-member__name"><?php echo htmlspecialchars($top3[0]['full_name'] ?? ''); ?></span>
                <span class="top-member__points"><strong><?php echo number_format($top3[0]['points'] ?? 0, 2); ?></strong> pt</span>
            </div>
            <?php endif; ?>

            <?php if (count($top3) >= 3): ?>
            <!-- Rank 3 (Bronze) -->
            <div class="top-member top-member--rank3">
                <span class="top-member__rank">#3</span>
                <span class="top-member__name"><?php echo htmlspecialchars($top3[2]['full_name'] ?? ''); ?></span>
                <span class="top-member__points"><strong><?php echo number_format($top3[2]['points'] ?? 0, 2); ?></strong> pt</span>
            </div>
            <?php endif; ?>

        </div>

        <!-- Table -->
        <div class="hall-of-fame__table-wrap">
            <table class="hall-of-fame__table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Members</th>
                        <th>HR</th>
                        <th>POINTS</th>
                        <th>VISITS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hall_of_fame as $index => $member): ?>
                    <tr>
                        <td class="rank-col"><?php echo $index + 1; ?></td>
                        <td class="member-col"><?php echo htmlspecialchars($member['full_name']); ?></td>
                        <td class="hr-col hr-<?php echo htmlspecialchars($member['tier']); ?>"><?php echo ucfirst(htmlspecialchars($member['tier'])); ?></td>
                        <td class="points-col"><?php echo number_format($member['points'], 2); ?> pts</td>
                        <td class="visits-col"><?php echo $member['visits'] ?? 0; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </section>

  <!-- ========== FOOTER ========== -->
  <footer class="footer">
    <div class="footer__main">
      <div class="footer__col footer__col--brand">
        <div class="footer__logo">
          <img src="../img/Rectangle 38 (1).png" class="footer__logo-img" alt="Cataleya Essence of Beauty" />
          <div class="footer__logo-text">
            <span class="footer__logo-name">Cataleya Essence</span>
            <span class="footer__logo-sub">of Beauty</span>
          </div>
        </div>
        <nav class="footer__nav">
          <a href="contact.php">Contact</a>
          <a href="faq.php">FAQs</a>
          <a href="terms.php">Terms &amp; Conditions</a>
          <a href="PrivacyPolicy.php">Privacy Policy</a>
        </nav>
      </div>
      <div class="footer__col footer__col--hours">
        <h4 class="footer__col-title">Opening Hours</h4>
        <ul class="footer__hours">
          <li><span class="day">Monday</span><span class="time">9:00 AM – 7:00 PM</span></li>
          <li><span class="day">Tuesday</span><span class="time">9:00 AM – 7:00 PM</span></li>
          <li><span class="day">Wednesday</span><span class="time">9:00 AM – 7:00 PM</span></li>
          <li><span class="day">Thursday</span><span class="time">9:00 AM – 7:00 PM</span></li>
          <li><span class="day">Friday</span><span class="time">9:00 AM – 7:00 PM</span></li>
          <li><span class="day">Saturday</span><span class="time">9:00 AM – 7:00 PM</span></li>
        </ul>
      </div>
      <div class="footer__col footer__col--contact">
        <h4 class="footer__col-title">Contact</h4>
        <ul class="footer__contact">
          <li>
            <svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
            <a href="https://www.facebook.com/CatelyaEssence" target="_blank">https://www.facebook.com/CatelyaEssence</a>
          </li>
          <li>
            <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
            <a href="#">@CatelyaEssence</a>
          </li>
          <li>
            <svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.116 1.528 5.845L.057 23.497a.5.5 0 0 0 .609.61l5.714-1.497A11.955 11.955 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.956 9.956 0 0 1-5.073-1.38l-.361-.214-3.742.981.998-3.648-.235-.374A9.956 9.956 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
            <a href="tel:+639922353293">+63 992 235 3293</a>
          </li>
          <li>
            <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 13 8 13s8-7.75 8-13a8 8 0 0 0-8-8z"/></svg>
            <span>Building J Malagpo St. San Vicente, Gapan City, Philippines, 3105</span>
          </li>
        </ul>
      </div>
    </div>
    <div class="footer__bottom">
      <p>&copy; 2026 Cataleya Essence of Beauty. All rights reserved.</p>
    </div>
  </footer>

  <!-- External JavaScript -->
  <script src="../JS/Rewards.js"></script>

</body>
</html>
