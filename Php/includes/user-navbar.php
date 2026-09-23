<?php
/**
 * Shared customer navigation for public information pages.
 * It keeps the same Home links and signed-in profile menu everywhere.
 */
if (!isset($pdo)) {
    require_once __DIR__ . '/../../config/database.php';
}

$userNavbarActive = $userNavbarActive ?? '';
$userNavbarUser = null;
$userNavbarInitials = 'U';

if (!empty($_SESSION['user_id'])) {
    $userNavbarStatement = $pdo->prepare('SELECT full_name, profile_photo FROM users WHERE id = ?');
    $userNavbarStatement->execute([(int) $_SESSION['user_id']]);
    $userNavbarUser = $userNavbarStatement->fetch();

    if ($userNavbarUser) {
        $userNavbarNameParts = array_values(array_filter(explode(' ', trim((string) $userNavbarUser['full_name']))));
        if (count($userNavbarNameParts) >= 2) {
            $userNavbarInitials = strtoupper(substr($userNavbarNameParts[0], 0, 1) . substr($userNavbarNameParts[count($userNavbarNameParts) - 1], 0, 1));
        } elseif (count($userNavbarNameParts) === 1) {
            $userNavbarInitials = strtoupper(substr($userNavbarNameParts[0], 0, 2));
        }
    }
}
?>
<header class="navbar user-navbar">
    <a href="home.php" class="navbar__logo" aria-label="Cataleya Essence home">
        <img src="../img/Rectangle 38 (1).png" class="logo-img" alt="Cataleya Essence of Beauty" />
        <span class="logo-text">
            <span class="logo-name">Cataleya Essence</span>
            <span class="logo-sub">of Beauty</span>
        </span>
    </a>

    <nav class="navbar__links" id="nav-links" aria-label="Main navigation">
        <a href="home.php" class="nav-link<?php echo $userNavbarActive === 'home' ? ' active' : ''; ?>">Home</a>
        <a href="about.php" class="nav-link<?php echo $userNavbarActive === 'about' ? ' active' : ''; ?>">About Us</a>
        <a href="serv.php" class="nav-link<?php echo $userNavbarActive === 'services' ? ' active' : ''; ?>">Services</a>
        <a href="Rewards.php" class="nav-link<?php echo $userNavbarActive === 'rewards' ? ' active' : ''; ?>">Rewards</a>
    </nav>

    <?php if ($userNavbarUser): ?>
        <div class="profile-wrapper" id="profileWrapper">
            <div class="profile-avatar" id="profileAvatar" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-label="User menu">
                <?php if (!empty($userNavbarUser['profile_photo'])): ?>
                    <img src="<?php echo htmlspecialchars($userNavbarUser['profile_photo']); ?>" alt="Profile" class="avatar-image" />
                <?php else: ?>
                    <span class="avatar-initials"><?php echo htmlspecialchars($userNavbarInitials); ?></span>
                <?php endif; ?>
                <span class="status-dot" aria-hidden="true"></span>
            </div>
            <div class="profile-dropdown" id="profileDropdown" role="menu">
                <a href="profile.php" class="dropdown-item" role="menuitem"><i class="fas fa-user" aria-hidden="true"></i>Profile</a>
                <div class="dropdown-divider"></div>
                <a href="../auth/logout.php" class="dropdown-item logout" role="menuitem"><i class="fas fa-sign-out-alt" aria-hidden="true"></i>Log out</a>
            </div>
        </div>
    <?php else: ?>
        <a href="signin.php" class="btn-book">Sign in</a>
    <?php endif; ?>

    <button class="hamburger" id="hamburger" type="button" aria-label="Open menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
</header>
<div class="dropdown-overlay" id="dropdownOverlay"></div>
