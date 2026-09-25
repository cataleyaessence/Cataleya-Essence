<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_activity.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: signin.php');
    exit;
}

$adminId = $_SESSION['admin_id'];
$errorMessage = '';
$successMessage = '';

function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

try {
    $stmt = $pdo->prepare('SELECT id, full_name, email, is_verified, created_at FROM users WHERE id = ? AND is_admin = 1');
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    if (!$admin) {
        header('Location: signin.php');
        exit;
    }
} catch (PDOException $e) {
    die('Unable to load admin account: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $newName = trim($_POST['new_name'] ?? '');
        $newEmail = trim($_POST['new_email'] ?? '');
        $confirmEmail = trim($_POST['confirm_email'] ?? '');

        if ($newName === '') {
            $errorMessage = 'Full name cannot be empty.';
        } elseif ($newEmail === '' || !isValidEmail($newEmail)) {
            $errorMessage = 'Please enter a valid email address.';
        } elseif ($newEmail !== $confirmEmail) {
            $errorMessage = 'Email addresses do not match.';
        } else {
            try {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                $stmt->execute([$newEmail, $adminId]);
                if ($stmt->fetch()) {
                    $errorMessage = 'This email is already registered.';
                } else {
                    $update = $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE id = ?');
                    $update->execute([$newName, $newEmail, $adminId]);
                    $successMessage = 'Profile updated successfully.';
                    $admin['full_name'] = $newName;
                    $admin['email'] = $newEmail;
                    $_SESSION['admin_email'] = $newEmail;
                    $_SESSION['full_name'] = $newName;
                    logAdminActivity($pdo, (int) $adminId, 'profile_updated', 'account', (int) $adminId, 'Updated administrator profile');
                }
            } catch (PDOException $e) {
                $errorMessage = 'Unable to update profile. Please try again.';
            }
        }
    }

    if ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $errorMessage = 'All password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'New passwords do not match.';
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = 'New password must be at least 6 characters long.';
        } else {
            try {
                $passStmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
                $passStmt->execute([$adminId]);
                $row = $passStmt->fetch();

                if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
                    $errorMessage = 'Current password is incorrect.';
                } else {
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $update = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $update->execute([$newHash, $adminId]);
                    $successMessage = 'Password updated successfully.';
                    logAdminActivity($pdo, (int) $adminId, 'password_updated', 'account', (int) $adminId, 'Updated administrator password');
                }
            } catch (PDOException $e) {
                $errorMessage = 'Unable to update password. Please try again.';
            }
        }
    }
}

$adminDisplayName = trim((string) ($admin['full_name'] ?? 'Admin'));
$adminInitial = strtoupper(substr($adminDisplayName, 0, 1)) ?: 'A';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cataleya Essence of Beauty – Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/Admin-Settings.css" />
    <link rel="stylesheet" href="../css/admin-sidebar.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body class="admin-page">

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
                <span class="user-name"><?=htmlspecialchars($adminDisplayName, ENT_QUOTES, 'UTF-8')?></span>
                <span class="user-role">Manager</span>
            </div>
            <div class="user-avatar"><?=htmlspecialchars($adminInitial, ENT_QUOTES, 'UTF-8')?></div>
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
                <a href="Admin-Analytics.php" class="nav-link"><i class="fas fa-chart-pie"></i> Analytics Reports</a>
                <a href="Admin-ActivityLog.php" class="nav-link"><i class="fas fa-clipboard-list"></i> Activity Log</a>
                <a href="Admin-Settings.php" class="nav-link active"><i class="fas fa-cog"></i> Settings</a>
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
            <div class="page-header settings-page-header">
                <div>
                    <p class="page-eyebrow"><i class="fas fa-sliders"></i> Account workspace</p>
                    <h1 class="page-title">
                        <i class="fas fa-cog settings-icon"></i> Settings
                    </h1>
                    <p class="page-sub">Keep your administrator profile, email address, and password up to date.</p>
                </div>
            </div>
            <?php if ($errorMessage): ?>
                <div class="settings-alert settings-alert-error" role="alert"><i class="fas fa-circle-exclamation"></i><?=htmlspecialchars($errorMessage)?></div>
            <?php elseif ($successMessage): ?>
                <div class="settings-alert settings-alert-success" role="status"><i class="fas fa-circle-check"></i><?=htmlspecialchars($successMessage)?></div>
            <?php endif; ?>

            <!-- ── SETTINGS GRID (2 columns, 2 rows – all cards same size) ── -->
            <div class="settings-grid">

                <!-- Account Information -->
                <div class="settings-card account-card">
                    <h2 class="card-title">
                        <i class="fas fa-user-circle"></i> Account Information
                    </h2>
                    <div class="account-fields">
                        <div class="field-row">
                            <span class="field-label">Full Name</span>
                            <span class="field-value" id="fullName"><?=htmlspecialchars($admin['full_name'])?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Email Address</span>
                            <span class="field-value" id="emailAddress"><?=htmlspecialchars($admin['email'])?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Role</span>
                            <span class="field-value" id="userRole">Administrator</span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Account Created</span>
                            <span class="field-value" id="accountCreated"><?=date('F j, Y', strtotime($admin['created_at']))?></span>
                        </div>
                    </div>
                </div>

                <!-- Change Email (same size as cards above) -->
                <div class="settings-card email-card">
                    <h2 class="card-title">
                        <i class="fas fa-envelope"></i> Change Email
                    </h2>
                    <p class="card-sub">Update your email address.</p>
                    <div class="email-fields">
                        <form method="post" action="Admin-Settings.php">
                            <input type="hidden" name="action" value="update_profile" />
                            <div class="email-field">
                                <label class="email-label">Current Email</label>
                                <div class="email-input-wrap">
                                    <input type="email" id="currentEmail" value="<?=htmlspecialchars($admin['email'])?>" readonly />
                                    <span class="input-readonly-icon"><i class="fas fa-lock"></i></span>
                                </div>
                            </div>
                            <div class="email-field">
                                <label class="email-label">New Email</label>
                                <div class="email-input-wrap">
                                    <input type="email" name="new_email" id="newEmail" placeholder="Enter new email address" />
                                </div>
                            </div>
                            <div class="email-field">
                                <label class="email-label">Confirm New Email</label>
                                <div class="email-input-wrap">
                                    <input type="email" name="confirm_email" id="confirmEmail" placeholder="Confirm new email address" />
                                </div>
                            </div>
                            <div class="email-field">
                                <label class="email-label">Full Name</label>
                                <div class="email-input-wrap">
                                    <input type="text" name="new_name" id="newName" value="<?=htmlspecialchars($admin['full_name'])?>" placeholder="Enter full name" />
                                </div>
                            </div>
                            <button id="updateEmailBtn" class="update-email-btn" type="submit">Update Profile</button>
                        </form>
                    </div>
                </div>

                <!-- Change Password (same size as cards above) -->
                <div class="settings-card password-card">
                    <h2 class="card-title">
                        <i class="fas fa-lock"></i> Change Password
                    </h2>
                    <p class="card-sub">Update your password to keep your account secure.</p>
                    <div class="password-fields">
                        <form method="post" action="Admin-Settings.php">
                            <input type="hidden" name="action" value="update_password" />
                            <div class="password-field">
                                <label class="password-label">Current Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="current_password" id="currentPassword" placeholder="Enter current password" />
                                    <button type="button" class="toggle-password" data-target="currentPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="password-field">
                                <label class="password-label">New Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="new_password" id="newPassword" placeholder="Enter new password" />
                                    <button type="button" class="toggle-password" data-target="newPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="password-field">
                                <label class="password-label">Confirm New Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm new password" />
                                    <button type="button" class="toggle-password" data-target="confirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <button id="updatePasswordBtn" class="update-password-btn" type="submit">Update Password</button>
                        </form>
                    </div>
                </div>

            </div>
        </main>
    </div>
    <script src="../JS/Admin-Settings.js" defer></script>
</body>
</html>
