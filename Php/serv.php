<?php
require_once __DIR__ . '/../config/database.php';

// Service edits from the admin page must be fetched again when this catalog reloads.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data from database
$stmt = $pdo->prepare("SELECT id, full_name, email, profile_photo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: signin.php');
    exit;
}

$full_name = $user['full_name'];

// Build initials for the profile avatar
$name_parts = array_filter(explode(' ', trim($full_name)));
if (count($name_parts) >= 2) {
    $initials = strtoupper(substr(reset($name_parts), 0, 1) . substr(end($name_parts), 0, 1));
} elseif (count($name_parts) === 1) {
    $initials = strtoupper(substr(reset($name_parts), 0, 2));
} else {
    $initials = 'U';
}

$stmt = $pdo->prepare("SELECT tier FROM rewards WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$reward = $stmt->fetch();
$discount_rate = 5;
if ($reward && !empty($reward['tier'])) {
    switch ($reward['tier']) {
        case 'silver':
            $discount_rate = 10;
            break;
        case 'gold':
            $discount_rate = 15;
            break;
        case 'platinum':
            $discount_rate = 20;
            break;
        default:
            $discount_rate = 5;
            break;
    }
}

// The customer catalog uses the same active services managed in Admin-Service.php.
$serviceStmt = $pdo->query(
    'SELECT id, name, description, main_category AS category, sub_category AS subCategory,
            price, duration_minutes AS durationMinutes, image_url AS image
     FROM services
     WHERE is_active = 1
     ORDER BY main_category, sub_category, name'
);
$catalogServices = $serviceStmt->fetchAll();

function formatCatalogDuration(?int $durationMinutes): string
{
    $totalMinutes = (int) $durationMinutes;
    if ($totalMinutes < 1) {
        return 'Duration not specified';
    }

    $hours = intdiv($totalMinutes, 60);
    $minutes = $totalMinutes % 60;
    $parts = [];

    if ($hours > 0) {
        $parts[] = $hours . ' hr' . ($hours === 1 ? '' : 's');
    }
    if ($minutes > 0) {
        $parts[] = $minutes . ' min';
    }

    return implode(' ', $parts);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Price List · Cataleya Essence</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/serv.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body data-discount-rate="<?php echo $discount_rate; ?>">

    <!-- ========== NAVBAR ========== -->
    <header class="navbar">
        <div class="navbar__logo">
            <img src="../img/Rectangle 38 (1).png" class="logo-img" alt="Cataleya Essence of Beauty">
            <div class="logo-text">
                <span class="logo-name">Cataleya Essence</span>
                <span class="logo-sub">of Beauty</span>
            </div>
        </div>
        <nav class="navbar__links" id="nav-links">
            <a href="home.php" class="nav-link">Home</a>
            <a href="about.php" class="nav-link">About Us</a>
            <a href="serv.php" class="nav-link active">Services</a>
            <a href="Rewards.php" class="nav-link">Rewards</a>
        </nav>

        <!-- ====== PROFILE AVATAR + DROPDOWN ====== -->
        <div class="profile-wrapper" id="profileWrapper">
            <div class="profile-avatar" id="profileAvatar" role="button" aria-haspopup="true" aria-expanded="false" aria-label="User menu">
                <?php if ($user['profile_photo']): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile" class="avatar-image" />
                <?php else: ?>
                    <span class="avatar-initials"><?php echo htmlspecialchars($initials); ?></span>
                <?php endif; ?>
                <span class="status-dot"></span>
            </div>
            <div class="profile-dropdown" id="profileDropdown" role="menu">
                <a href="profile.php" class="dropdown-item" role="menuitem">
                    <i class="fas fa-user"></i> Profile
                </a>
                <div class="dropdown-divider"></div>
                <a href="../auth/logout.php" class="dropdown-item logout" role="menuitem">
                    <i class="fas fa-sign-out-alt"></i> Log out
                </a>
            </div>
        </div>

        <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </header>

    <!-- dropdown overlay (click outside to close) -->
    <div class="dropdown-overlay" id="dropdownOverlay"></div>

    <!-- ========== MAIN CONTENT ========== -->
    <main class="services-page">
        <div class="page-header">
            <div>
                <h1>Price List <span>·</span> Cataleya</h1>
                <p>All beauty, spa &amp; wellness services at a glance</p>
            </div>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search services…" />
            </div>
        </div>

        <!-- Main tabs: All, Beauty Services, Spa Massage -->
        <div class="main-tabs" id="mainTabs">
            <button class="active" data-main="all">All</button>
            <button data-main="Beauty Services">Beauty Services</button>
            <button data-main="Spa Massage">Spa Massage</button>
        </div>

        <!-- Sub-category tabs -->
        <div class="sub-tabs" id="subTabs">
            <span class="empty-hint" id="subTabsHint">All services — use search or switch to a category</span>
            <button data-main-group="Beauty Services" data-sub="all" class="active" style="display:none;">All</button>
            <button data-main-group="Beauty Services" data-sub="Facial Services" style="display:none;">Facial Services</button>
            <button data-main-group="Beauty Services" data-sub="RF + Lipo Cav" style="display:none;">RF + Lipo Cav</button>
            <button data-main-group="Beauty Services" data-sub="EyeLash Enhancement" style="display:none;">EyeLash Enhancement</button>
            <button data-main-group="Beauty Services" data-sub="Hair Laser Removal" style="display:none;">Hair Laser Removal</button>
            <button data-main-group="Beauty Services" data-sub="Laser Whitening" style="display:none;">Laser Whitening</button>
            <button data-main-group="Beauty Services" data-sub="Mesolipo" style="display:none;">Mesolipo</button>
            <button data-main-group="Beauty Services" data-sub="Gluta Whitening" style="display:none;">Gluta Whitening</button>
            <button data-main-group="Beauty Services" data-sub="Semi-Permanent Make Up" style="display:none;">Semi-Permanent Make Up</button>
            <button data-main-group="Beauty Services" data-sub="Waxing" style="display:none;">Waxing</button>
            <button data-main-group="Beauty Services" data-sub="HIFU Ultheraphy" style="display:none;">HIFU Ultheraphy</button>
            <button data-main-group="Beauty Services" data-sub="Nail Services" style="display:none;">Nail Services</button>
            <button data-main-group="Spa Massage" data-sub="all" class="active" style="display:none;">All</button>
            <button data-main-group="Spa Massage" data-sub="Body Care" style="display:none;">Body Care</button>
            <button data-main-group="Spa Massage" data-sub="Traditional Body Care" style="display:none;">Traditional Body Care</button>
            <button data-main-group="Spa Massage" data-sub="Body Skin Treatment" style="display:none;">Body Skin Treatment</button>
        </div>

        <!-- Services grid -->
        <div class="services-grid" id="servicesGrid">
            <?php foreach ($catalogServices as $service): ?>
                <div class="service-card"
                     data-id="<?= (int) $service['id'] ?>"
                     data-main="<?= htmlspecialchars($service['category'], ENT_QUOTES, 'UTF-8') ?>"
                     data-sub="<?= htmlspecialchars($service['subCategory'], ENT_QUOTES, 'UTF-8') ?>"
                     data-name="<?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>"
                     data-price="<?= htmlspecialchars((string) $service['price'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="badge"><?= htmlspecialchars($service['subCategory'], ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="icon">
                        <?php if (!empty($service['image'])): ?>
                            <img src="<?= htmlspecialchars($service['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>" class="service-icon-img" />
                        <?php else: ?>
                            <i class="fas fa-spa" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                    <h3><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <?php if (!empty($service['description'])): ?>
                        <p class="service-description"><?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <div class="meta">
                        <span class="price">₱<?= number_format((float) $service['price'], 2) ?></span>
                        <span class="duration"><i class="fas fa-clock"></i> <?= htmlspecialchars(formatCatalogDuration($service['durationMinutes']), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <button class="book-btn" type="button" data-id="<?= (int) $service['id'] ?>" data-name="<?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>" data-price="<?= htmlspecialchars((string) $service['price'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas fa-calendar-plus"></i> Book Now
                    </button>
                </div>
            <?php endforeach; ?>
            <div id="legacyServiceCards" hidden>
            <div class="service-card" data-main="Beauty Services" data-sub="Facial Services" data-name="Signature Facial">
                <span class="badge">Facial Services</span>
                <div class="icon">
                    <img src="../img/Signature facial.png" alt="Signature Facial" class="service-icon-img" />
                </div>
                <h3>Signature Facial</h3>
                <div class="meta">
                    <span class="price">₱599</span>
                </div>
                <button class="book-btn" data-name="Signature Facial" data-price="599">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Facial Services" data-name="Acne Clear Facial">
                <span class="badge">Facial Services</span>
                <div class="icon">
                    <img src="../img/acne clear facial.png" alt="Acne Clear Facial" class="service-icon-img" />
                </div>
                <h3>Acne Clear Facial</h3>
                <div class="meta">
                    <span class="price">₱599</span>
                </div>
                <button class="book-btn" data-name="Acne Clear Facial" data-price="599">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Facial Services" data-name="Classic Facial">
                <span class="badge">Facial Services</span>
                <div class="icon">
                    <img src="../img/classic facial.png" alt="Classic Facial" class="service-icon-img" />
                </div>
                <h3>Classic Facial</h3>
                <div class="meta">
                    <span class="price">₱350</span>
                </div>
                <button class="book-btn" data-name="Classic Facial" data-price="350">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Facial Services" data-name="Hydra Facial">
                <span class="badge">Facial Services</span>
                <div class="icon">
                    <img src="../img/hydra facial.png" alt="Hydra Facial" class="service-icon-img" />
                </div>
                <h3>Hydra Facial</h3>
                <div class="meta">
                    <span class="price">₱799</span>
                </div>
                <button class="book-btn" data-name="Hydra Facial" data-price="799">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Facial Services" data-name="Diamond Peel">
                <span class="badge">Facial Services</span>
                <div class="icon">
                    <img src="../img/diamond feal.png" alt="Diamond Peel" class="service-icon-img" />
                </div>
                <h3>Diamond Peel</h3>
                <div class="meta">
                    <span class="price">₱249</span>
                </div>
                <button class="book-btn" data-name="Diamond Peel" data-price="249">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Facial Services" data-name="PDT Light Therapy">
                <span class="badge">Facial Services</span>
                <div class="icon">
                    <img src="../img/pdt light.png" alt="PDT Light Therapy" class="service-icon-img" />
                </div>
                <h3>PDT Light Therapy</h3>
                <div class="meta">
                    <span class="price">₱249</span>
                </div>
                <button class="book-btn" data-name="PDT Light Therapy" data-price="249">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Facial Services" data-name="Pimple Injection">
                <span class="badge">Facial Services</span>
                <div class="icon">
                    <img src="../img/pimple injection.png" alt="Pimple Injection" class="service-icon-img" />
                </div>
                <h3>Pimple Injection</h3>
                <div class="meta">
                    <span class="price">₱199</span>
                </div>
                <button class="book-btn" data-name="Pimple Injection" data-price="199">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Facial Services" data-name="BB Glow">
                <span class="badge">Facial Services</span>
                <div class="icon">
                    <img src="../img/bb glow.png" alt="BB Glow" class="service-icon-img" />
                </div>
                <h3>BB Glow</h3>
                <div class="meta">
                    <span class="price">₱799</span>
                </div>
                <button class="book-btn" data-name="BB Glow" data-price="799">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Facial Services" data-name="Warts Removal (Per Area)">
                <span class="badge">Facial Services</span>
                <div class="icon">
                    <img src="../img/warts removal.png" alt="Warts Removal (Per Area)" class="service-icon-img" />
                </div>
                <h3>Warts Removal (Per Area)</h3>
                <div class="meta">
                    <span class="price">₱1499</span>
                </div>
                <button class="book-btn" data-name="Warts Removal (Per Area)" data-price="1499">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Facial Services" data-name="Vajacial">
                <span class="badge">Facial Services</span>
                <div class="icon">
                    <img src="../img/vajacial.png" alt="Vajacial" class="service-icon-img" />
                </div>
                <h3>Vajacial</h3>
                <div class="meta">
                    <span class="price">₱999</span>
                </div>
                <button class="book-btn" data-name="Vajacial" data-price="999">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="RF + Lipo Cav" data-name="RF Face">
                <span class="badge">RF + Lipo Cav</span>
                <div class="icon">
                    <img src="../img/rf face (1).png" alt="RF Face" class="service-icon-img" />
                </div>
                <h3>RF Face</h3>
                <div class="meta">
                    <span class="price">₱299</span>
                </div>
                <button class="book-btn" data-name="RF Face" data-price="299">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="RF + Lipo Cav" data-name="RF Arms">
                <span class="badge">RF + Lipo Cav</span>
                <div class="icon">
                    <img src="../img/rf arms.png" alt="RF Arms" class="service-icon-img" />
                </div>
                <h3>RF Arms</h3>
                <div class="meta">
                    <span class="price">₱499</span>
                </div>
                <button class="book-btn" data-name="RF Arms" data-price="499">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="RF + Lipo Cav" data-name="RF Belly">
                <span class="badge">RF + Lipo Cav</span>
                <div class="icon">
                    <img src="../img/rf belly.png" alt="RF Belly" class="service-icon-img" />
                </div>
                <h3>RF Belly</h3>
                <div class="meta">
                    <span class="price">₱599</span>
                </div>
                <button class="book-btn" data-name="RF Belly" data-price="599">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="RF + Lipo Cav" data-name="RF Legs">
                <span class="badge">RF + Lipo Cav</span>
                <div class="icon">
                    <img src="../img/rf legs.png" alt="RF Legs" class="service-icon-img" />
                </div>
                <h3>RF Legs</h3>
                <div class="meta">
                    <span class="price">₱699</span>
                </div>
                <button class="book-btn" data-name="RF Legs" data-price="699">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="RF + Lipo Cav" data-name="RF Whole Body">
                <span class="badge">RF + Lipo Cav</span>
                <div class="icon">
                    <img src="../img/rf whole.png" alt="RF Whole Body" class="service-icon-img" />
                </div>
                <h3>RF Whole Body</h3>
                <div class="meta">
                    <span class="price">₱1999</span>
                </div>
                <button class="book-btn" data-name="RF Whole Body" data-price="1999">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="EyeLash Enhancement" data-name="Natural Look">
                <span class="badge">EyeLash Enhancement</span>
                <div class="icon">
                    <img src="../img/natural look.png" alt="Natural Look" class="service-icon-img" />
                </div>
                <h3>Natural Look</h3>
                <div class="meta">
                    <span class="price">₱350</span>
                </div>
                <button class="book-btn" data-name="Natural Look" data-price="350">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="EyeLash Enhancement" data-name="Volume Look">
                <span class="badge">EyeLash Enhancement</span>
                <div class="icon">
                    <img src="../img/volume look.png" alt="Volume Look" class="service-icon-img" />
                </div>
                <h3>Volume Look</h3>
                <div class="meta">
                    <span class="price">₱450</span>
                </div>
                <button class="book-btn" data-name="Volume Look" data-price="450">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="EyeLash Enhancement" data-name="Cat Eye Look">
                <span class="badge">EyeLash Enhancement</span>
                <div class="icon">
                    <img src="../img/cat eye look.png" alt="Cat Eye Look" class="service-icon-img" />
                </div>
                <h3>Cat Eye Look</h3>
                <div class="meta">
                    <span class="price">₱550</span>
                </div>
                <button class="book-btn" data-name="Cat Eye Look" data-price="550">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="EyeLash Enhancement" data-name="Wispy Look">
                <span class="badge">EyeLash Enhancement</span>
                <div class="icon">
                    <img src="../img/wispy look.png" alt="Wispy Look" class="service-icon-img" />
                </div>
                <h3>Wispy Look</h3>
                <div class="meta">
                    <span class="price">₱800</span>
                </div>
                <button class="book-btn" data-name="Wispy Look" data-price="800">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="EyeLash Enhancement" data-name="Keratin lash Lift">
                <span class="badge">EyeLash Enhancement</span>
                <div class="icon">
                    <img src="../img/keratin lash.png" alt="Keratin lash Lift" class="service-icon-img" />
                </div>
                <h3>Keratin lash Lift</h3>
                <div class="meta">
                    <span class="price">₱800</span>
                </div>
                <button class="book-btn" data-name="Keratin lash Lift" data-price="800">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="EyeLash Enhancement" data-name="Last Lift/ Mascara">
                <span class="badge">EyeLash Enhancement</span>
                <div class="icon">
                    <img src="../img/last lift.png" alt="Last Lift/ Mascara" class="service-icon-img" />
                </div>
                <h3>Last Lift/ Mascara</h3>
                <div class="meta">
                    <span class="price">₱350</span>
                </div>
                <button class="book-btn" data-name="Last Lift/ Mascara" data-price="350">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Hair Laser Removal" data-name="Upper up">
                <span class="badge">Hair Laser Removal</span>
                <div class="icon">
                    <img src="../img/removal upper up.png" alt="Upper up" class="service-icon-img" />
                </div>
                <h3>Upper up</h3>
                <div class="meta">
                    <span class="price">₱199</span>
                </div>
                <button class="book-btn" data-name="Upper up" data-price="199">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Hair Laser Removal" data-name="Face">
                <span class="badge">Hair Laser Removal</span>
                <div class="icon">
                    <img src="../img/removal face.png" alt="Face" class="service-icon-img" />
                </div>
                <h3>Face</h3>
                <div class="meta">
                    <span class="price">₱299</span>
                </div>
                <button class="book-btn" data-name="Face" data-price="299">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Hair Laser Removal" data-name="Underarm">
                <span class="badge">Hair Laser Removal</span>
                <div class="icon">
                    <img src="../img/removal arms.png" alt="Underarm" class="service-icon-img" />
                </div>
                <h3>Underarm</h3>
                <div class="meta">
                    <span class="price">₱499</span>
                </div>
                <button class="book-btn" data-name="Underarm" data-price="499">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Hair Laser Removal" data-name="Arms">
                <span class="badge">Hair Laser Removal</span>
                <div class="icon">
                    <img src="../img/removal arms.png" alt="Arms" class="service-icon-img" />
                </div>
                <h3>Arms</h3>
                <div class="meta">
                    <span class="price">₱799</span>
                </div>
                <button class="book-btn" data-name="Arms" data-price="799">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Hair Laser Removal" data-name="Legs">
                <span class="badge">Hair Laser Removal</span>
                <div class="icon">
                    <img src="../img/removal legs.png" alt="Legs" class="service-icon-img" />
                </div>
                <h3>Legs</h3>
                <div class="meta">
                    <span class="price">₱899</span>
                </div>
                <button class="book-btn" data-name="Legs" data-price="899">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Hair Laser Removal" data-name="Chest">
                <span class="badge">Hair Laser Removal</span>
                <div class="icon">
                    <img src="../img/removal chest.png" alt="Chest" class="service-icon-img" />
                </div>
                <h3>Chest</h3>
                <div class="meta">
                    <span class="price">₱499</span>
                </div>
                <button class="book-btn" data-name="Chest" data-price="499">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Hair Laser Removal" data-name="Brazilian">
                <span class="badge">Hair Laser Removal</span>
                <div class="icon">
                    <img src="../img/removal brazilian.png" alt="Brazilian" class="service-icon-img" />
                </div>
                <h3>Brazilian</h3>
                <div class="meta">
                    <span class="price">₱799</span>
                </div>
                <button class="book-btn" data-name="Brazilian" data-price="799">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Hair Laser Removal" data-name="Whole Body">
                <span class="badge">Hair Laser Removal</span>
                <div class="icon">
                    <img src="../img/removal whole body.png" alt="Whole Body" class="service-icon-img" />
                </div>
                <h3>Whole Body</h3>
                <div class="meta">
                    <span class="price">₱2500</span>
                </div>
                <button class="book-btn" data-name="Whole Body" data-price="2500">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Laser Whitening" data-name="Black Doll Carbon">
                <span class="badge">Laser Whitening</span>
                <div class="icon">
                    <img src="../img/back doll.png" alt="Black Doll Carbon" class="service-icon-img" />
                </div>
                <h3>Black Doll Carbon</h3>
                <div class="meta">
                    <span class="price">₱799</span>
                </div>
                <button class="book-btn" data-name="Black Doll Carbon" data-price="799">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Laser Whitening" data-name="Underarm Laser">
                <span class="badge">Laser Whitening</span>
                <div class="icon">
                    <img src="../img/underarm laser.png" alt="Underarm Laser" class="service-icon-img" />
                </div>
                <h3>Underarm Laser</h3>
                <div class="meta">
                    <span class="price">₱499</span>
                </div>
                <button class="book-btn" data-name="Underarm Laser" data-price="499">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Laser Whitening" data-name="Elbow Whitening">
                <span class="badge">Laser Whitening</span>
                <div class="icon">
                    <img src="../img/elbow whitenming.png" alt="Elbow Whitening" class="service-icon-img" />
                </div>
                <h3>Elbow Whitening</h3>
                <div class="meta">
                    <span class="price">₱499</span>
                </div>
                <button class="book-btn" data-name="Elbow Whitening" data-price="499">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Laser Whitening" data-name="Knee Whitening">
                <span class="badge">Laser Whitening</span>
                <div class="icon">
                    <img src="../img/knee whitening.png" alt="Knee Whitening" class="service-icon-img" />
                </div>
                <h3>Knee Whitening</h3>
                <div class="meta">
                    <span class="price">₱599</span>
                </div>
                <button class="book-btn" data-name="Knee Whitening" data-price="599">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Laser Whitening" data-name="Melasma">
                <span class="badge">Laser Whitening</span>
                <div class="icon">
                    <img src="../img/melasma.png" alt="Melasma" class="service-icon-img" />
                </div>
                <h3>Melasma</h3>
                <div class="meta">
                    <span class="price">₱799</span>
                </div>
                <button class="book-btn" data-name="Melasma" data-price="799">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Laser Whitening" data-name="Tattoo Removal">
                <span class="badge">Laser Whitening</span>
                <div class="icon">
                    <img src="../img/tattoo removal.png" alt="Tattoo Removal" class="service-icon-img" />
                </div>
                <h3>Tattoo Removal</h3>
                <div class="meta">
                    <span class="price">₱2500</span>
                </div>
                <button class="book-btn" data-name="Tattoo Removal" data-price="2500">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Mesolipo" data-name="Double Chin">
                <span class="badge">Mesolipo</span>
                <div class="icon">
                    <img src="../img/double chin.png" alt="Double Chin" class="service-icon-img" />
                </div>
                <h3>Double Chin</h3>
                <div class="meta">
                    <span class="price">₱999</span>
                </div>
                <button class="book-btn" data-name="Double Chin" data-price="999">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Mesolipo" data-name="Arms">
                <span class="badge">Mesolipo</span>
                <div class="icon">
                    <img src="../img/arms gluta.png" alt="Arms" class="service-icon-img" />
                </div>
                <h3>Arms</h3>
                <div class="meta">
                    <span class="price">₱2000</span>
                </div>
                <button class="book-btn" data-name="Arms" data-price="2000">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Mesolipo" data-name="Tummy">
                <span class="badge">Mesolipo</span>
                <div class="icon">
                    <img src="../img/tummy.png" alt="Tummy" class="service-icon-img" />
                </div>
                <h3>Tummy</h3>
                <div class="meta">
                    <span class="price">₱1499</span>
                </div>
                <button class="book-btn" data-name="Tummy" data-price="1499">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Mesolipo" data-name="Thigh">
                <span class="badge">Mesolipo</span>
                <div class="icon">
                    <img src="../img/thigh.png" alt="Thigh" class="service-icon-img" />
                </div>
                <h3>Thigh</h3>
                <div class="meta">
                    <span class="price">₱2000</span>
                </div>
                <button class="book-btn" data-name="Thigh" data-price="2000">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Gluta Whitening" data-name="Ultra Whitening Drip / Session">
                <span class="badge">Gluta Whitening</span>
                <div class="icon">
                    <img src="../img/ultra whitening.png" alt="Ultra Whitening Drip / Session" class="service-icon-img" />
                </div>
                <h3>Ultra Whitening Drip / Session</h3>
                <div class="meta">
                    <span class="price">₱1999</span>
                </div>
                <button class="book-btn" data-name="Ultra Whitening Drip / Session" data-price="1999">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Gluta Whitening" data-name="Celebrity Drip">
                <span class="badge">Gluta Whitening</span>
                <div class="icon">
                    <img src="../img/celebrity drip.png" alt="Celebrity Drip" class="service-icon-img" />
                </div>
                <h3>Celebrity Drip</h3>
                <div class="meta">
                    <span class="price">₱1799</span>
                </div>
                <button class="book-btn" data-name="Celebrity Drip" data-price="1799">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Gluta Whitening" data-name="Korean Whitening Drip">
                <span class="badge">Gluta Whitening</span>
                <div class="icon">
                    <img src="../img/korean whitening.png" alt="Korean Whitening Drip" class="service-icon-img" />
                </div>
                <h3>Korean Whitening Drip</h3>
                <div class="meta">
                    <span class="price">₱999</span>
                </div>
                <button class="book-btn" data-name="Korean Whitening Drip" data-price="999">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Gluta Whitening" data-name="Gluta IV Push">
                <span class="badge">Gluta Whitening</span>
                <div class="icon">
                    <img src="../img/gluta iv.png" alt="Gluta IV Push" class="service-icon-img" />
                </div>
                <h3>Gluta IV Push</h3>
                <div class="meta">
                    <span class="price">₱550</span>
                </div>
                <button class="book-btn" data-name="Gluta IV Push" data-price="550">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Gluta Whitening" data-name="Collagen">
                <span class="badge">Gluta Whitening</span>
                <div class="icon">
                    <img src="../img/colagen.png" alt="Collagen" class="service-icon-img" />
                </div>
                <h3>Collagen</h3>
                <div class="meta">
                    <span class="price">₱499</span>
                </div>
                <button class="book-btn" data-name="Collagen" data-price="499">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Gluta Whitening" data-name="Slimming">
                <span class="badge">Gluta Whitening</span>
                <div class="icon">
                    <img src="../img/slimming.png" alt="Slimming" class="service-icon-img" />
                </div>
                <h3>Slimming</h3>
                <div class="meta">
                    <span class="price">₱499</span>
                </div>
                <button class="book-btn" data-name="Slimming" data-price="499">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Gluta Whitening" data-name="Vitamin C">
                <span class="badge">Gluta Whitening</span>
                <div class="icon">
                    <img src="../img/vitamin c.png" alt="Vitamin C" class="service-icon-img" />
                </div>
                <h3>Vitamin C</h3>
                <div class="meta">
                    <span class="price">₱250</span>
                </div>
                <button class="book-btn" data-name="Vitamin C" data-price="250">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Semi-Permanent Make Up" data-name="Microblading">
                <span class="badge">Semi-Permanent Make Up</span>
                <div class="icon">
                    <img src="../img/microblading.png" alt="Microblading" class="service-icon-img" />
                </div>
                <h3>Microblading</h3>
                <div class="meta">
                    <span class="price">₱2999</span>
                </div>
                <button class="book-btn" data-name="Microblading" data-price="2999">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Semi-Permanent Make Up" data-name="Microshading">
                <span class="badge">Semi-Permanent Make Up</span>
                <div class="icon">
                    <img src="../img/microshading.png" alt="Microshading" class="service-icon-img" />
                </div>
                <h3>Microshading</h3>
                <div class="meta">
                    <span class="price">₱2999</span>
                </div>
                <button class="book-btn" data-name="Microshading" data-price="2999">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Semi-Permanent Make Up" data-name="Obrebrows">
                <span class="badge">Semi-Permanent Make Up</span>
                <div class="icon">
                    <img src="../img/obrebrows.png" alt="Obrebrows" class="service-icon-img" />
                </div>
                <h3>Obrebrows</h3>
                <div class="meta">
                    <span class="price">₱2999</span>
                </div>
                <button class="book-btn" data-name="Obrebrows" data-price="2999">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Waxing" data-name="Face">
                <span class="badge">Waxing</span>
                <div class="icon">
                    <img src="../img/face.png" alt="Face" class="service-icon-img" />
                </div>
                <h3>Face</h3>
                <div class="meta">
                    <span class="price">₱150</span>
                </div>
                <button class="book-btn" data-name="Face" data-price="150">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Waxing" data-name="Underarm">
                <span class="badge">Waxing</span>
                <div class="icon">
                    <img src="../img/underarm.png" alt="Underarm" class="service-icon-img" />
                </div>
                <h3>Underarm</h3>
                <div class="meta">
                    <span class="price">₱199</span>
                </div>
                <button class="book-btn" data-name="Underarm" data-price="199">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Waxing" data-name="Arm">
                <span class="badge">Waxing</span>
                <div class="icon">
                    <img src="../img/arm.png" alt="Arm" class="service-icon-img" />
                </div>
                <h3>Arm</h3>
                <div class="meta">
                    <span class="price">₱250</span>
                </div>
                <button class="book-btn" data-name="Arm" data-price="250">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Waxing" data-name="Legs">
                <span class="badge">Waxing</span>
                <div class="icon">
                    <img src="../img/legs.png" alt="Legs" class="service-icon-img" />
                </div>
                <h3>Legs</h3>
                <div class="meta">
                    <span class="price">₱350</span>
                </div>
                <button class="book-btn" data-name="Legs" data-price="350">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Waxing" data-name="Chest">
                <span class="badge">Waxing</span>
                <div class="icon">
                    <img src="../img/chest.png" alt="Chest" class="service-icon-img" />
                </div>
                <h3>Chest</h3>
                <div class="meta">
                    <span class="price">₱300</span>
                </div>
                <button class="book-btn" data-name="Chest" data-price="300">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Waxing" data-name="Brazilian">
                <span class="badge">Waxing</span>
                <div class="icon">
                    <img src="../img/brazilian.png" alt="Brazilian" class="service-icon-img" />
                </div>
                <h3>Brazilian</h3>
                <div class="meta">
                    <span class="price">₱999</span>
                </div>
                <button class="book-btn" data-name="Brazilian" data-price="999">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Waxing" data-name="Whole Body">
                <span class="badge">Waxing</span>
                <div class="icon">
                    <img src="../img/whole body.png" alt="Whole Body" class="service-icon-img" />
                </div>
                <h3>Whole Body</h3>
                <div class="meta">
                    <span class="price">₱1399</span>
                </div>
                <button class="book-btn" data-name="Whole Body" data-price="1399">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="HIFU Ultheraphy" data-name="Face HIFU">
                <span class="badge">HIFU Ultheraphy</span>
                <div class="icon">
                    <img src="../img/face Hifu.png" alt="Face HIFU" class="service-icon-img" />
                </div>
                <h3>Face HIFU</h3>
                <div class="meta">
                    <span class="price">₱3000</span>
                </div>
                <button class="book-btn" data-name="Face HIFU" data-price="3000">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="HIFU Ultheraphy" data-name="Arms HIFU">
                <span class="badge">HIFU Ultheraphy</span>
                <div class="icon">
                    <img src="../img/arms HIFU.png" alt="Arms HIFU" class="service-icon-img" />
                </div>
                <h3>Arms HIFU</h3>
                <div class="meta">
                    <span class="price">₱4000</span>
                </div>
                <button class="book-btn" data-name="Arms HIFU" data-price="4000">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="HIFU Ultheraphy" data-name="Belly HIFU">
                <span class="badge">HIFU Ultheraphy</span>
                <div class="icon">
                    <img src="../img/belly HIFU.png" alt="Belly HIFU" class="service-icon-img" />
                </div>
                <h3>Belly HIFU</h3>
                <div class="meta">
                    <span class="price">₱4500</span>
                </div>
                <button class="book-btn" data-name="Belly HIFU" data-price="4500">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="HIFU Ultheraphy" data-name="Leg HIFU">
                <span class="badge">HIFU Ultheraphy</span>
                <div class="icon">
                    <img src="../img/Leg HIFU.png" alt="Leg HIFU" class="service-icon-img" />
                </div>
                <h3>Leg HIFU</h3>
                <div class="meta">
                    <span class="price">₱6000</span>
                </div>
                <button class="book-btn" data-name="Leg HIFU" data-price="6000">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Nail Services" data-name="Reg Manicure">
                <span class="badge">Nail Services</span>
                <div class="icon">
                    <img src="../img/reg manicure.png" alt="Reg Manicure" class="service-icon-img" />
                </div>
                <h3>Reg Manicure</h3>
                <div class="meta">
                    <span class="price">₱120</span>
                </div>
                <button class="book-btn" data-name="Reg Manicure" data-price="120">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Nail Services" data-name="Reg Pedicure">
                <span class="badge">Nail Services</span>
                <div class="icon">
                    <img src="../img/reg pedicure.png" alt="Reg Pedicure" class="service-icon-img" />
                </div>
                <h3>Reg Pedicure</h3>
                <div class="meta">
                    <span class="price">₱149</span>
                </div>
                <button class="book-btn" data-name="Reg Pedicure" data-price="149">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Nail Services" data-name="Gel Manicure">
                <span class="badge">Nail Services</span>
                <div class="icon">
                    <img src="../img/gel manicure.png" alt="Gel Manicure" class="service-icon-img" />
                </div>
                <h3>Gel Manicure</h3>
                <div class="meta">
                    <span class="price">₱299</span>
                </div>
                <button class="book-btn" data-name="Gel Manicure" data-price="299">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Nail Services" data-name="Gel Removal">
                <span class="badge">Nail Services</span>
                <div class="icon">
                    <img src="../img/gel removal.png" alt="Gel Removal" class="service-icon-img" />
                </div>
                <h3>Gel Removal</h3>
                <div class="meta">
                    <span class="price">₱349</span>
                </div>
                <button class="book-btn" data-name="Gel Removal" data-price="349">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Nail Services" data-name="Nail Extension">
                <span class="badge">Nail Services</span>
                <div class="icon">
                    <img src="../img/nail extension.png" alt="Nail Extension" class="service-icon-img" />
                </div>
                <h3>Nail Extension</h3>
                <div class="meta">
                    <span class="price">₱899</span>
                </div>
                <button class="book-btn" data-name="Nail Extension" data-price="899">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Nail Services" data-name="Poly Gel">
                <span class="badge">Nail Services</span>
                <div class="icon">
                    <img src="../img/poly gel.png" alt="Poly Gel" class="service-icon-img" />
                </div>
                <h3>Poly Gel</h3>
                <div class="meta">
                    <span class="price">₱899</span>
                </div>
                <button class="book-btn" data-name="Poly Gel" data-price="899">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Nail Services" data-name="Foot Spa">
                <span class="badge">Nail Services</span>
                <div class="icon">
                    <img src="../img/foot spa.png" alt="Foot Spa" class="service-icon-img" />
                </div>
                <h3>Foot Spa</h3>
                <div class="meta">
                    <span class="price">₱349</span>
                </div>
                <button class="book-btn" data-name="Foot Spa" data-price="349">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Beauty Services" data-sub="Nail Services" data-name="Luxury Foot Spa w/ Pedicure">
                <span class="badge">Nail Services</span>
                <div class="icon">
                    <img src="../img/luxury foot spa.png" alt="Luxury Foot Spa w/ Pedicure" class="service-icon-img" />
                </div>
                <h3>Luxury Foot Spa w/ Pedicure</h3>
                <div class="meta">
                    <span class="price">₱499</span>
                </div>
                <button class="book-btn" data-name="Luxury Foot Spa w/ Pedicure" data-price="499">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Body Care" data-name="Regular Massage">
                <span class="badge">Body Care</span>
                <div class="icon">
                    <img src="../img/regular massage.png" alt="Regular Massage" class="service-icon-img" />
                </div>
                <h3>Regular Massage</h3>
                <div class="meta">
                    <span class="price">₱350</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr</span>
                </div>
                <button class="book-btn" data-name="Regular Massage" data-price="350">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Body Care" data-name="Swedish Massage">
                <span class="badge">Body Care</span>
                <div class="icon">
                    <img src="../img/swedish massage.png" alt="Swedish Massage" class="service-icon-img" />
                </div>
                <h3>Swedish Massage</h3>
                <div class="meta">
                    <span class="price">₱350</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr</span>
                </div>
                <button class="book-btn" data-name="Swedish Massage" data-price="350">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Body Care" data-name="Shiatsu Massage">
                <span class="badge">Body Care</span>
                <div class="icon">
                    <img src="../img/siatsu massage.png" alt="Shiatsu Massage" class="service-icon-img" />
                </div>
                <h3>Shiatsu Massage</h3>
                <div class="meta">
                    <span class="price">₱350</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr</span>
                </div>
                <button class="book-btn" data-name="Shiatsu Massage" data-price="350">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Body Care" data-name="Foot and Back Massage">
                <span class="badge">Body Care</span>
                <div class="icon">
                    <img src="../img/foot and black massage.png" alt="Foot and Back Massage" class="service-icon-img" />
                </div>
                <h3>Foot and Back Massage</h3>
                <div class="meta">
                    <span class="price">₱350</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr</span>
                </div>
                <button class="book-btn" data-name="Foot and Back Massage" data-price="350">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Body Care" data-name="Sports Massage">
                <span class="badge">Body Care</span>
                <div class="icon">
                    <img src="../img/sport massage.png" alt="Sports Massage" class="service-icon-img" />
                </div>
                <h3>Sports Massage</h3>
                <div class="meta">
                    <span class="price">₱400</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr</span>
                </div>
                <button class="book-btn" data-name="Sports Massage" data-price="400">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Body Care" data-name="Thai Massage">
                <span class="badge">Body Care</span>
                <div class="icon">
                    <img src="../img/thai massage.png" alt="Thai Massage" class="service-icon-img" />
                </div>
                <h3>Thai Massage</h3>
                <div class="meta">
                    <span class="price">₱350</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr</span>
                </div>
                <button class="book-btn" data-name="Thai Massage" data-price="350">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Body Care" data-name="Aromatherapy Massage">
                <span class="badge">Body Care</span>
                <div class="icon">
                    <img src="../img/aromatherapy massage.png" alt="Aromatherapy Massage" class="service-icon-img" />
                </div>
                <h3>Aromatherapy Massage</h3>
                <div class="meta">
                    <span class="price">₱500</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr</span>
                </div>
                <button class="book-btn" data-name="Aromatherapy Massage" data-price="500">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Body Care" data-name="Twin Massage">
                <span class="badge">Body Care</span>
                <div class="icon">
                    <img src="../img/twin massage.png" alt="Twin Massage" class="service-icon-img" />
                </div>
                <h3>Twin Massage</h3>
                <div class="meta">
                    <span class="price">₱700</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr 20 min</span>
                </div>
                <button class="book-btn" data-name="Twin Massage" data-price="700">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Body Care" data-name="Signatured Massage">
                <span class="badge">Body Care</span>
                <div class="icon">
                    <img src="../img/signature massage.png" alt="Signatured Massage" class="service-icon-img" />
                </div>
                <h3>Signatured Massage</h3>
                <div class="meta">
                    <span class="price">₱500</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr 15 min</span>
                </div>
                <button class="book-btn" data-name="Signatured Massage" data-price="500">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Traditional Body Care" data-name="Hot Stone">
                <span class="badge">Traditional Body Care</span>
                <div class="icon">
                    <img src="../img/Hot Stone.png" alt="Hot Stone" class="service-icon-img" />
                </div>
                <h3>Hot Stone</h3>
                <div class="meta">
                    <span class="price">₱600</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr 30 min</span>
                </div>
                <button class="book-btn" data-name="Hot Stone" data-price="600">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Traditional Body Care" data-name="Hilot">
                <span class="badge">Traditional Body Care</span>
                <div class="icon">
                    <img src="../img/Hilot.png" alt="Hilot" class="service-icon-img" />
                </div>
                <h3>Hilot</h3>
                <div class="meta">
                    <span class="price">₱400</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr 15 min</span>
                </div>
                <button class="book-btn" data-name="Hilot" data-price="400">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Traditional Body Care" data-name="Ventosa">
                <span class="badge">Traditional Body Care</span>
                <div class="icon">
                    <img src="../img/Ventosa.png" alt="Ventosa" class="service-icon-img" />
                </div>
                <h3>Ventosa</h3>
                <div class="meta">
                    <span class="price">₱750</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr 30 min</span>
                </div>
                <button class="book-btn" data-name="Ventosa" data-price="750">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Traditional Body Care" data-name="Ear Candling">
                <span class="badge">Traditional Body Care</span>
                <div class="icon">
                    <img src="../img/Ear Candling.png" alt="Ear Candling" class="service-icon-img" />
                </div>
                <h3>Ear Candling</h3>
                <div class="meta">
                    <span class="price">₱150</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr</span>
                </div>
                <button class="book-btn" data-name="Ear Candling" data-price="150">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Traditional Body Care" data-name="Foot Reflexology">
                <span class="badge">Traditional Body Care</span>
                <div class="icon">
                    <img src="../img/foot reflexology.png" alt="Foot Reflexology" class="service-icon-img" />
                </div>
                <h3>Foot Reflexology</h3>
                <div class="meta">
                    <span class="price">₱400</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr & 15 min</span>
                </div>
                <button class="book-btn" data-name="Foot Reflexology" data-price="400">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Traditional Body Care" data-name="Hand Massage">
                <span class="badge">Traditional Body Care</span>
                <div class="icon">
                    <img src="../img/Ventosa 2.png" alt="Hand Massage" class="service-icon-img" />
                </div>
                <h3>Ventosa</h3>
                <div class="meta">
                    <span class="price">₱750</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr & 30 min</span>
                </div>
                <button class="book-btn" data-name="Ventosa" data-price="750">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Traditional Body Care" data-name="Head Massage">
                <span class="badge">Traditional Body Care</span>
                <div class="icon">
                    <img src="../img/Hilot 2.png" alt="Head Massage" class="service-icon-img" />
                </div>
                <h3>Hilot</h3>
                <div class="meta">
                    <span class="price">₱400</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr & 15 min</span>
                </div>
                <button class="book-btn" data-name="Hilot" data-price="400">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Traditional Body Care" data-name="Back Massage">
                <span class="badge">Traditional Body Care</span>
                <div class="icon">
                    <img src="../img/ventosa 3.png" alt="Back Massage" class="service-icon-img" />
                </div>
                <h3>Ventosa</h3>
                <div class="meta">
                    <span class="price">₱750</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr & 30 min</span>
                </div>
                <button class="book-btn" data-name="Ventosa" data-price="750">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Body Skin Treatment" data-name="Oriental (Green Tea Scrub)">
                <span class="badge">Body Skin Treatment</span>
                <div class="icon">
                    <img src="../img/Oriental.png" alt="Oriental (Green Tea Scrub)" class="service-icon-img" />
                </div>
                <h3>Oriental (Green Tea Scrub)</h3>
                <div class="meta">
                    <span class="price">₱600</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr 45 min</span>
                </div>
                <button class="book-btn" data-name="Oriental (Green Tea Scrub)" data-price="600">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <div class="service-card" data-main="Spa Massage" data-sub="Body Skin Treatment" data-name="Whitening Body Scrub">
                <span class="badge">Body Skin Treatment</span>
                <div class="icon">
                    <img src="../img/whitening body scrub.png" alt="Whitening Body Scrub" class="service-icon-img" />
                </div>
                <h3>Whitening Body Scrub</h3>
                <div class="meta">
                    <span class="price">₱400</span>
                    <span class="duration"><i class="fas fa-clock"></i> 1 hr 45 min</span>
                </div>
                <button class="book-btn" data-name="Whitening Body Scrub" data-price="400">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            </div>
            <div class="no-results" id="noResults" style="display:none;">No services found.</div>
        </div>
    </main>

    <!-- ========== FOOTER ========== -->
    <footer class="footer">
        <div class="footer__main">
            <div class="footer__col footer__col--brand">
                <div class="footer__logo">
                    <img src="../img/Rectangle 38 (1).png" class="footer__logo-img" alt="Cataleya Essence of Beauty">
                    <div class="footer__logo-text">
                        <span class="footer__logo-name">Cataleya Essence</span>
                        <span class="footer__logo-sub">of Beauty</span>
                    </div>
                </div>
                <nav class="footer__nav">
                    <a href="contact.php">Contact</a>
                    <a href="terms.php">Terms and Condition</a>
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

    <!-- ========== SCRIPTS ========== -->
    <script>
        // All service data now lives directly in the HTML as <div class="service-card">
        // elements with data-main / data-sub / data-name attributes, and the sub-category
        // buttons live in #subTabs with data-main-group / data-sub attributes.
        // This script only shows/hides what's already in the DOM — it no longer builds it.

        // ─── STATE ──────────────────────────────────────────────────
        let currentMain = 'all';
        let currentSub = 'all';
        let searchTerm = '';
        const userDiscountRate = parseInt(document.body.dataset.discountRate || '5', 10);

        function formatCurrency(amount) {
            return '₱' + Number(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function applyDiscount(price) {
            return Number((price * (100 - userDiscountRate) / 100).toFixed(2));
        }

        function renderDiscountedPrices() {
            document.querySelectorAll('.service-card').forEach(card => {
                const priceEl = card.querySelector('.meta .price');
                if (!priceEl) return;

                const original = parseFloat(card.dataset.price || priceEl.textContent.replace(/[₱,]/g, ''));
                if (!Number.isFinite(original)) return;

                const discounted = applyDiscount(original);
                card.dataset.discountedPrice = discounted.toFixed(2);

                if (discounted === original) {
                    priceEl.textContent = `₱${original.toFixed(2)}`;
                } else {
                    priceEl.innerHTML = `
                        <span class="service-price-original">₱${original.toFixed(2)}</span>
                        <span class="service-price-current">${formatCurrency(discounted)}</span>
                        <span class="service-price-note">${userDiscountRate}% member price</span>
                    `;
                }
            });
        }

        // ─── SHOW/HIDE SUB-CATEGORY TABS ───────────────────────────
        function updateSubTabs() {
            const hint = document.getElementById('subTabsHint');
            hint.style.display = currentMain === 'all' ? '' : 'none';

            document.querySelectorAll('#subTabs button[data-main-group]').forEach(btn => {
                const belongsToCurrentMain = btn.dataset.mainGroup === currentMain;
                btn.style.display = belongsToCurrentMain ? '' : 'none';
                btn.classList.toggle('active', belongsToCurrentMain && btn.dataset.sub === currentSub);
            });
        }

        // ─── SHOW/HIDE SERVICE CARDS ────────────────────────────────
        function updateCards() {
            const term = searchTerm.trim().toLowerCase();
            let anyVisible = false;

            document.querySelectorAll('.service-card').forEach(card => {
                const main = card.dataset.main;
                const sub = card.dataset.sub;
                const name = card.dataset.name.toLowerCase();

                let show = true;
                if (currentMain !== 'all' && main !== currentMain) show = false;
                if (currentSub !== 'all' && sub !== currentSub) show = false;
                if (term && !(name.includes(term) || sub.toLowerCase().includes(term))) show = false;

                card.style.display = show ? '' : 'none';
                if (show) anyVisible = true;
            });

            document.getElementById('noResults').style.display = anyVisible ? 'none' : '';
        }

        // ─── HANDLE MAIN TAB CLICK ──────────────────────────────────
        function switchMain(main) {
            currentMain = main;
            currentSub = 'all';
            // Don't reset searchTerm — keep it as is

            document.querySelectorAll('.main-tabs button').forEach(b => {
                b.classList.toggle('active', b.dataset.main === main);
            });

            updateSubTabs();
            updateCards();
        }

        // ─── EVENT LISTENERS: MAIN TABS ─────────────────────────────
        document.querySelectorAll('.main-tabs button').forEach(btn => {
            btn.addEventListener('click', () => {
                switchMain(btn.dataset.main);
            });
        });

        // ─── EVENT LISTENERS: SUB TABS (event delegation) ───────────
        document.getElementById('subTabs').addEventListener('click', function(e) {
            const btn = e.target.closest('button[data-sub]');
            if (!btn) return;
            currentSub = btn.dataset.sub;
            updateSubTabs();
            updateCards();
        });

        // ─── EVENT LISTENERS: BOOK BUTTON (event delegation) ────────
        document.getElementById('servicesGrid').addEventListener('click', function(e) {
            const btn = e.target.closest('.book-btn');
            if (!btn) return;

            const card = btn.closest('.service-card');
            const serviceId = card.dataset.id || '';
            const serviceName = card.dataset.name || btn.dataset.name;
            const servicePrice = card.dataset.price || btn.dataset.price;
            const serviceCategory = card.dataset.main || 'Beauty Services';

            console.log('Book button clicked - Service:', serviceName);
            console.log('Book button clicked - Category:', serviceCategory);
            console.log('Book button clicked - Card data-main:', card.dataset.main);

            sessionStorage.setItem('selectedService', JSON.stringify({
                id: serviceId,
                name: serviceName,
                price: servicePrice,
                category: serviceCategory
            }));

            // Redirect to calendar selection
            window.location.href = 'Calendar%20service.php';
        });

        // ─── SEARCH ───────────────────────────────────────────────────
        document.getElementById('searchInput').addEventListener('input', function() {
            searchTerm = this.value;
            updateCards();
        });

        // ─── HAMBURGER MENU ──────────────────────────────────────────
        const hamburger = document.getElementById('hamburger');
        const navLinks = document.getElementById('nav-links');

        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            navLinks.classList.toggle('open');
            const isOpen = navLinks.classList.contains('open');
            this.setAttribute('aria-expanded', isOpen);
        });

        // ─── PROFILE DROPDOWN TOGGLE ──────────────────────────────────
        const profileWrapper = document.getElementById('profileWrapper');
        const profileAvatar = document.getElementById('profileAvatar');
        const profileDropdown = document.getElementById('profileDropdown');
        const dropdownOverlay = document.getElementById('dropdownOverlay');

        if (profileAvatar && profileDropdown) {
            function toggleDropdown(forceState) {
                const isOpen = typeof forceState === 'boolean' ? forceState : !profileDropdown.classList.contains('open');
                profileDropdown.classList.toggle('open', isOpen);
                profileAvatar.setAttribute('aria-expanded', isOpen);
                dropdownOverlay.classList.toggle('active', isOpen);
            }

            profileAvatar.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleDropdown();
            });

            // Close dropdown when clicking outside
            dropdownOverlay.addEventListener('click', function() {
                toggleDropdown(false);
            });

            document.addEventListener('click', function(e) {
                if (profileWrapper && !profileWrapper.contains(e.target) && profileDropdown.classList.contains('open')) {
                    toggleDropdown(false);
                }
            });

            profileDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }

        // ─── INIT ──────────────────────────────────────────────────────
        updateSubTabs();
        renderDiscountedPrices();
        updateCards();
    </script>

    <script>
        // Render only the active database services so admin changes immediately appear here.
        (() => {
            const catalogServices = <?= json_encode($catalogServices, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const servicesGrid = document.getElementById('servicesGrid');

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#039;',
                '"': '&quot;'
            })[character]);

            const formatDuration = (durationMinutes) => {
                const totalMinutes = Number(durationMinutes) || 0;
                const hours = Math.floor(totalMinutes / 60);
                const minutes = totalMinutes % 60;
                const parts = [];

                if (hours > 0) parts.push(`${hours} hr${hours === 1 ? '' : 's'}`);
                if (minutes > 0) parts.push(`${minutes} min`);

                return parts.join(' ') || 'Duration not specified';
            };

            servicesGrid.innerHTML = catalogServices.map((service) => {
                const image = service.image
                    ? `<img src="${escapeHtml(service.image)}" alt="${escapeHtml(service.name)}" class="service-icon-img" />`
                    : '<i class="fas fa-spa" aria-hidden="true"></i>';
                const description = service.description
                    ? `<p class="service-description">${escapeHtml(service.description)}</p>`
                    : '';

                return `
                    <div class="service-card"
                         data-id="${Number(service.id)}"
                         data-main="${escapeHtml(service.category)}"
                         data-sub="${escapeHtml(service.subCategory)}"
                         data-name="${escapeHtml(service.name)}"
                         data-price="${Number(service.price)}">
                        <span class="badge">${escapeHtml(service.subCategory)}</span>
                        <div class="icon">${image}</div>
                        <h3>${escapeHtml(service.name)}</h3>
                        ${description}
                        <div class="meta">
                            <span class="price">₱${Number(service.price).toFixed(2)}</span>
                            <span class="duration"><i class="fas fa-clock"></i> ${formatDuration(service.durationMinutes)}</span>
                        </div>
                        <button class="book-btn" type="button" data-id="${Number(service.id)}" data-name="${escapeHtml(service.name)}" data-price="${Number(service.price)}">
                            <i class="fas fa-calendar-plus"></i> Book Now
                        </button>
                    </div>
                `;
            }).join('') + '<div class="no-results" id="noResults" style="display:none;">No services found.</div>';

            renderDiscountedPrices();
            updateCards();
        })();
    </script>

</body>
</html>
