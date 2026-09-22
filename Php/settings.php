<?php
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'update_name':
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $full_name = trim($first_name . ' ' . $last_name);

                if (empty($full_name)) {
                    $error_message = 'Name cannot be empty.';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                    $stmt->execute([$full_name, $user_id]);
                    $success_message = 'Name updated successfully!';
                }
                break;

            case 'update_phone':
                $phone = trim($_POST['phone'] ?? '');
                if (empty($phone)) {
                    $error_message = 'Phone number cannot be empty.';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
                    $stmt->execute([$phone, $user_id]);
                    $success_message = 'Phone number updated successfully!';
                }
                break;

            case 'update_email':
                $email = trim($_POST['email'] ?? '');
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error_message = 'Invalid email format.';
                } else {
                    // Check if email already exists for another user
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $user_id]);
                    if ($stmt->fetch()) {
                        $error_message = 'Email already exist, Please try Again';
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                        $stmt->execute([$email, $user_id]);
                        $success_message = 'Email updated successfully!';
                    }
                }
                break;

            case 'send_email_otp':
                $email = trim($_POST['email'] ?? '');
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error_message = 'Invalid email format.';
                } else {
                    // Check if email already exists for another user
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $user_id]);
                    if ($stmt->fetch()) {
                        $error_message = 'Email already exist, Please try Again';
                    } else {
                        // Generate OTP
                        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                        
                        // Store OTP in session
                        $_SESSION['email_change_otp'] = $otp;
                        $_SESSION['email_change_otp_time'] = time();
                        $_SESSION['email_change_new_email'] = $email;
                        
                        // Send OTP email
                        require_once __DIR__ . '/../config/mail.php';
                        if (sendOTPEmail($email, $otp, 'email_change')) {
                            $success_message = 'OTP sent to your email. Please enter the code to verify.';
                        } else {
                            $error_message = 'Failed to send OTP. Please try again.';
                        }
                    }
                }
                break;

            case 'verify_email_otp':
                $otp = trim($_POST['otp'] ?? '');
                $new_email = $_SESSION['email_change_new_email'] ?? '';
                
                if (empty($otp)) {
                    $error_message = 'OTP is required.';
                } elseif (empty($new_email)) {
                    $error_message = 'Session expired. Please request a new OTP.';
                } elseif (!isset($_SESSION['email_change_otp']) || !isset($_SESSION['email_change_otp_time'])) {
                    $error_message = 'Session expired. Please request a new OTP.';
                } elseif (time() - $_SESSION['email_change_otp_time'] > 300) { // 5 minutes expiry
                    $error_message = 'OTP expired. Please request a new OTP.';
                    unset($_SESSION['email_change_otp']);
                    unset($_SESSION['email_change_otp_time']);
                    unset($_SESSION['email_change_new_email']);
                } elseif ($otp !== $_SESSION['email_change_otp']) {
                    $error_message = 'Invalid OTP. Please try again.';
                } else {
                    // OTP is valid, update email
                    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                    $stmt->execute([$new_email, $user_id]);
                    
                    // Clear session
                    unset($_SESSION['email_change_otp']);
                    unset($_SESSION['email_change_otp_time']);
                    unset($_SESSION['email_change_new_email']);
                    
                    $success_message = 'Email updated successfully!';
                }
                break;

            case 'update_password':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error_message = 'All password fields are required.';
                } elseif (strlen($new_password) < 8) {
                    $error_message = 'New password must be at least 8 characters.';
                } elseif ($new_password !== $confirm_password) {
                    $error_message = 'New passwords do not match.';
                } else {
                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user_data = $stmt->fetch();

                    if (!$user_data || !password_verify($current_password, $user_data['password_hash'])) {
                        $error_message = 'Current password is incorrect.';
                    } else {
                        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        $stmt->execute([$new_password_hash, $user_id]);
                        $success_message = 'Password updated successfully!';
                    }
                }
                break;

            case 'update_address':
                $address_line1 = trim($_POST['address_line1'] ?? '');
                $address_line2 = trim($_POST['address_line2'] ?? '');
                $city = trim($_POST['city'] ?? '');
                $province = trim($_POST['province'] ?? '');
                $postal_code = trim($_POST['postal_code'] ?? '');

                if (empty($address_line1) || empty($city) || empty($province) || empty($postal_code)) {
                    $error_message = 'Required address fields cannot be empty.';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET address_line1 = ?, address_line2 = ?, city = ?, province = ?, postal_code = ? WHERE id = ?");
                    $stmt->execute([$address_line1, $address_line2, $city, $province, $postal_code, $user_id]);
                    $success_message = 'Address updated successfully!';
                }
                break;
        }
    } catch (PDOException $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT id, full_name, email, phone, address_line1, address_line2, city, province, postal_code, profile_photo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: signin.php');
    exit;
}

// Split full name into first and last name
$name_parts = explode(' ', trim($user['full_name']), 2);
$first_name = $name_parts[0] ?? '';
$last_name = $name_parts[1] ?? '';

// Build initials for avatar
$name_parts = array_filter(explode(' ', trim($user['full_name'])));
if (count($name_parts) >= 2) {
    $initials = strtoupper(substr(reset($name_parts), 0, 1) . substr(end($name_parts), 0, 1));
} elseif (count($name_parts) === 1) {
    $initials = strtoupper(substr(reset($name_parts), 0, 2));
} else {
    $initials = 'U';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Settings · Cataleya Essence</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/settings.css" />
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

    <div class="dropdown-overlay" id="dropdownOverlay"></div>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="profile-page">

        <!-- Page Header -->
        <div class="profile-header">
            <div>
                <h1>Settings</h1>
                <p>Update your personal information and preferences</p>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Layout: Sidebar + Main -->
        <div class="profile-layout">

            <!-- Sidebar Navigation -->
            <aside class="profile-sidebar">
                <nav class="sidebar-nav">
                    <a href="profile.php" class="sidebar-link"><i class="fas fa-user"></i> Profile Overview</a>
                    <a href="bookings.php" class="sidebar-link"><i class="fas fa-calendar-check"></i> My Bookings</a>
                    <a href="settings.php" class="sidebar-link active"><i class="fas fa-cog"></i> Settings</a>
                    <a href="../auth/logout.php" class="sidebar-link sidebar-link--signout"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                </nav>
            </aside>

            <!-- Main Content Area -->
            <div class="profile-main">

                <!-- ===== PROFILE PHOTO ===== -->
                <section class="settings-form">
                    <h3><i class="fas fa-camera"></i> Profile Photo</h3>
                    <div class="profile-photo-section">
                        <div class="current-photo">
                            <?php if ($user['profile_photo']): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile Photo" id="currentProfilePhoto" />
                            <?php else: ?>
                                <div class="photo-placeholder">
                                    <i class="fas fa-user"></i>
                                    <span>No photo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="photo-upload">
                            <input type="file" id="profilePhotoInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="display: none;" />
                            <button type="button" class="btn-upload" id="uploadPhotoBtn">
                                <i class="fas fa-upload"></i> Upload Photo
                            </button>
                            <button type="button" class="btn-remove" id="removePhotoBtn" <?php echo $user['profile_photo'] ? '' : 'style="display: none;"'; ?>>
                                <i class="fas fa-trash"></i> Remove Photo
                            </button>
                            <p class="photo-hint">Supported formats: JPG, PNG, GIF, WebP (max 5MB)</p>
                        </div>
                    </div>
                </section>

                <!-- ===== CHANGE NAME ===== -->
                <section class="settings-form">
                    <h3><i class="fas fa-user-edit"></i> Change Name</h3>
                    <form action="#" method="POST">
                        <input type="hidden" name="action" value="update_name" />
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required />
                            </div>
                        </div>
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Update Name</button>
                    </form>
                </section>

                <!-- ===== UPDATE PHONE ===== -->
                <section class="settings-form">
                    <h3><i class="fas fa-phone-alt"></i> Update Phone Number</h3>
                    <form action="#" method="POST">
                        <input type="hidden" name="action" value="update_phone" />
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required />
                        </div>
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Update Phone</button>
                    </form>
                </section>

                <!-- ===== CHANGE EMAIL ===== -->
                <section class="settings-form">
                    <h3><i class="fas fa-envelope"></i> Change Email Address</h3>
                    <form action="#" method="POST" id="emailForm">
                        <input type="hidden" name="action" value="send_email_otp" id="emailAction" />
                        <div class="form-group">
                            <label for="email">New Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required />
                        </div>
                        <button type="submit" class="btn-save" id="sendOtpBtn"><i class="fas fa-paper-plane"></i> Send OTP</button>
                    </form>
                    
                    <!-- OTP Verification Form (hidden initially) -->
                    <form action="#" method="POST" id="otpForm" style="display: none;">
                        <input type="hidden" name="action" value="verify_email_otp" />
                        <input type="hidden" name="otp" id="otpHidden" />
                        <div class="form-group">
                            <label>Enter OTP sent to your email</label>
                            <div class="otp-inputs">
                                <input type="text" class="otp-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" />
                                <input type="text" class="otp-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" />
                                <input type="text" class="otp-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" />
                                <input type="text" class="otp-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" />
                                <input type="text" class="otp-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" />
                                <input type="text" class="otp-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" />
                            </div>
                        </div>
                        <div class="form-row">
                            <button type="submit" class="btn-save"><i class="fas fa-check"></i> Verify & Update Email</button>
                            <button type="button" class="btn-cancel" id="cancelOtpBtn"><i class="fas fa-times"></i> Cancel</button>
                        </div>
                    </form>
                </section>

                <!-- ===== CHANGE PASSWORD ===== -->
                <section class="settings-form">
                    <h3><i class="fas fa-lock"></i> Change Password</h3>
                    <form action="#" method="POST">
                        <input type="hidden" name="action" value="update_password" />
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <div class="password-wrapper">
                                <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required />
                                <button type="button" class="toggle-password" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="new_password" name="new_password" placeholder="New password" required />
                                    <button type="button" class="toggle-password" data-target="new_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required />
                                    <button type="button" class="toggle-password" data-target="confirm_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Update Password</button>
                    </form>
                </section>

                <!-- ===== ADD / CHANGE ADDRESS ===== -->
                <section class="settings-form">
                    <h3><i class="fas fa-map-pin"></i> Address</h3>
                    <form action="#" method="POST">
                        <input type="hidden" name="action" value="update_address" />
                        <div class="form-group">
                            <label for="address_line1">Barangay</label>
                            <input type="text" id="address_line1" name="address_line1" value="<?php echo htmlspecialchars($user['address_line1'] ?? ''); ?>" required />
                        </div>
                        <div class="form-group">
                            <label for="address_line2">Street address (Optional) </label>
                            <input type="text" id="address_line2" name="address_line2" value="<?php echo htmlspecialchars($user['address_line2'] ?? ''); ?>" />
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="province">Province</label>
                                <input type="text" id="province" name="province" value="<?php echo htmlspecialchars($user['province'] ?? ''); ?>" required />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="postal_code">Postal Code</label>
                                <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>" required />
                            </div>
                        </div>
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Update Address</button>
                    </form>
                </section>

            </div>
        </div>
    </main>

    <script src="../JS/settings.js"></script>

</body>
</html>