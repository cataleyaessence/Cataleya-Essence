<?php
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: signin.php');
    exit;
}

$today = date('Y-m-d');
$staffStmt = $pdo->query(
    "SELECT id, full_name, title, category, specialization, image_url, experience_years, is_available
     FROM staff
     WHERE is_active = 1
     ORDER BY category, full_name"
);
$staffList = $staffStmt->fetchAll();

$appointmentStmt = $pdo->prepare(
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE booking_date = ? AND status IN ('pending', 'confirmed', 'completed')"
);
$appointmentStmt->execute([$today]);
$todayAppointments = (int) $appointmentStmt->fetch()['total'];

function staffBrand(?string $category = null): string
{
    return $category === 'Spa Massage' ? 'serenity' : 'cataleya';
}

function staffSpecialties(?string $specialization, ?string $category): array
{
    $specialties = array_filter(array_map('trim', explode(',', $specialization ?? '')));
    return $specialties ?: [$category ?: 'Expert'];
}

function staffInitials(string $name): string
{
    $parts = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: []));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return $initials ?: 'S';
}

if (empty($_SESSION['staff_csrf_token'])) {
    $_SESSION['staff_csrf_token'] = bin2hex(random_bytes(32));
}
$staffCsrfToken = $_SESSION['staff_csrf_token'];
$adminName = $_SESSION['full_name'] ?? 'Admin';
$adminInitial = strtoupper(substr(trim($adminName), 0, 1)) ?: 'A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Staff Management - Cataleya Essence</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/Admin-StaffSelection.css" />
    <link rel="stylesheet" href="../css/admin-sidebar.css" />
</head>
<body class="admin-page">
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
                <span class="user-name"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="user-role">Manager</span>
            </div>
            <div class="user-avatar"><?= htmlspecialchars($adminInitial, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a href="Admin-Sched.php" class="nav-link"><i class="fas fa-calendar-check"></i>Schedule</a>
                <a href="Admin-BookingStatus.php" class="nav-link"><i class="fas fa-check-circle"></i>Booking Status</a>
                <a href="Admin-Service.php" class="nav-link"><i class="fas fa-hand-sparkles"></i>Services</a>
                <a href="Admin-Staff.php" class="nav-link active"><i class="fas fa-user-tie"></i>Staff</a>
                <a href="Admin-Analytics.php" class="nav-link"><i class="fas fa-chart-pie"></i>Analytics Reports</a>
                <a href="Admin-ActivityLog.php" class="nav-link"><i class="fas fa-clipboard-list"></i>Activity Log</a>
                <a href="Admin-Settings.php" class="nav-link"><i class="fas fa-cog"></i>Settings</a>
            </nav>
            <div class="sidebar-footer"><a href="../auth/logout.php" class="nav-link logout"><i class="fas fa-sign-out-alt" aria-hidden="true"></i>Logout</a></div>
        </aside>

        <main class="main">
            <div class="page-header">
                <div>
                    <h1 class="page-title" id="pageTitle">Staff Management</h1>
                    <p class="page-sub" id="pageSub">Manage staff, availability, and schedules</p>
                </div>
                <div class="header-actions">
                    <div class="brand-toggle" aria-label="Select staff branch">
                        <span class="toggle-label active" id="toggleLabelLeft">Cataleya</span>
                        <label class="switch" title="Switch between Cataleya and Serenity">
                            <input type="checkbox" id="brandToggle" />
                            <span class="slider round"></span>
                        </label>
                        <span class="toggle-label" id="toggleLabelRight">Serenity</span>
                    </div>
                    <button class="header-btn export" type="button" id="addStaffBtn"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Add Staff</span></button>
                </div>
            </div>

            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-card-value" id="totalStaff">0</div>
                    <div class="stat-card-label" id="totalStaffLabel">Cataleya Staff</div>
                    <div class="stat-card-trend" id="staffAvailabilityTrend">No staff yet</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-value" id="availableStaff">0</div>
                    <div class="stat-card-label">Available Now</div>
                    <div class="stat-card-trend" id="unavailableStaffTrend">0 unavailable</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-value"><?= $todayAppointments ?></div>
                    <div class="stat-card-label">Today's Appointments</div>
                    <div class="stat-card-trend"><?= $todayAppointments ?> scheduled</div>
                </div>
            </div>

            <div class="bottom-grid">
                <section class="staff-grid-card" aria-labelledby="staffGridTitle">
                    <div class="staff-card-header">
                        <h2 class="card-title" id="staffGridTitle">Cataleya Staff</h2>
                        <span class="staff-card-count" id="staffCardCount">0 staff</span>
                    </div>
                    <div class="staff-scroll-wrap">
                        <div class="staff-grid" id="staffGrid">
                            <?php foreach ($staffList as $staff): ?>
                                <?php
                                    $brand = staffBrand($staff['category']);
                                    $role = $staff['title'] ?: ($brand === 'serenity' ? 'Therapist' : 'Aesthetician');
                                    $specialties = staffSpecialties($staff['specialization'], $staff['category']);
                                    $avatarUrl = $staff['image_url'] ?: null;
                                ?>
                                <article class="staff-card" data-id="<?= (int) $staff['id'] ?>" data-brand="<?= $brand ?>" data-available="<?= $staff['is_available'] ? '1' : '0' ?>"<?= $brand === 'serenity' ? ' hidden' : '' ?>>
                                    <div class="avatar-row">
                                        <div class="staff-avatar">
                                            <?php if ($avatarUrl): ?>
                                                <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($staff['full_name'], ENT_QUOTES, 'UTF-8') ?>" />
                                            <?php else: ?>
                                                <span><?= htmlspecialchars(staffInitials($staff['full_name']), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="staff-name"><?= htmlspecialchars($staff['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="staff-role"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>
                                    </div>
                                    <div class="staff-meta">
                                        <span class="availability-status <?= $staff['is_available'] ? 'available' : 'unavailable' ?>"><i class="fas fa-circle"></i> <?= $staff['is_available'] ? 'Available' : 'Unavailable' ?></span>
                                    </div>
                                    <div class="staff-specialties">
                                        <?php foreach ($specialties as $tag): ?>
                                            <span class="tag"><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="staff-actions">
                                        <button class="view-btn" type="button" data-id="<?= (int) $staff['id'] ?>"><i class="fas fa-calendar-alt"></i> View Schedule</button>
                                        <button class="fire-btn" type="button" data-id="<?= (int) $staff['id'] ?>"><i class="fas fa-user-slash"></i> Fired Staff</button>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="empty-state" id="staffEmptyState" hidden>No staff have been added to this branch yet.</div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="modalOverlay" aria-hidden="true">
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <h2 id="modalTitle">Schedule</h2>
            <p id="modalMessage"></p>
            <button class="modal-close" id="modalCloseBtn" type="button">Got it</button>
        </div>
    </div>

    <div class="modal-overlay" id="addStaffModal" aria-hidden="true">
        <div class="modal-box staff-form-modal" role="dialog" aria-modal="true" aria-labelledby="addStaffTitle">
            <button class="modal-icon-close" type="button" id="addStaffClose" aria-label="Close add staff form"><i class="fas fa-times"></i></button>
            <h2 id="addStaffTitle"><i class="fas fa-user-plus"></i> Add Staff</h2>
            <p>Fill in the staff details, choose the Cataleya or Serenity branch, then add a photo if available.</p>
            <form id="addStaffForm" class="staff-form" enctype="multipart/form-data">
                <div class="staff-form-row">
                    <label>Full Name<input type="text" name="name" maxlength="100" required autocomplete="name" /></label>
                    <label>Branch
                        <select name="branch" id="staffBranch" required>
                            <option value="cataleya">Cataleya</option>
                            <option value="serenity">Serenity</option>
                        </select>
                    </label>
                </div>
                <div class="staff-form-row">
                    <label>Email<input type="email" name="email" maxlength="100" required autocomplete="email" /></label>
                    <label>Phone<input type="tel" name="phone" maxlength="20" autocomplete="tel" /></label>
                </div>
                <div class="staff-form-row">
                    <label>Role<input type="text" name="title" maxlength="100" placeholder="e.g. Aesthetician" required /></label>
                    <label>Experience (years)<input type="number" name="experienceYears" min="0" max="60" value="0" required /></label>
                </div>
                <label>Specialization<input type="text" name="specialization" maxlength="255" placeholder="e.g. Facial Services, Waxing" /></label>
                <div class="staff-photo-field">
                    <span class="staff-field-label">Staff Photo <em>(optional)</em></span>
                    <input class="staff-photo-input" type="file" id="staffPhoto" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" />
                    <label class="staff-photo-picker" for="staffPhoto">
                        <i class="fas fa-image" aria-hidden="true"></i>
                        <span class="staff-photo-name" id="staffPhotoFileName">Choose a staff photo</span>
                        <span class="staff-photo-action">Choose file</span>
                    </label>
                    <div class="staff-photo-preview" id="staffPhotoPreview" aria-live="polite"><span>No photo selected</span></div>
                    <small>JPG, PNG, GIF, or WebP up to 5 MB.</small>
                </div>
                <label class="availability-check"><input type="checkbox" name="isAvailable" value="1" checked /> Available for bookings</label>
                <p class="form-feedback" id="addStaffFeedback" role="status"></p>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="addStaffCancel">Cancel</button>
                    <button type="submit" class="btn-primary" id="addStaffSubmit"><i class="fa-solid fa-plus"></i> Add Staff</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.staffAdminConfig = {
            apiUrl: '../api/admin_staff.php',
            csrfToken: <?= json_encode($staffCsrfToken) ?>
        };
    </script>
    <script src="../JS/Admin-StaffSelection.js"></script>
</body>
</html>
