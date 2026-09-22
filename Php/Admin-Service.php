<?php
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: signin.php');
    exit;
}

$subCategories = [
    'Beauty Services' => ['Facial Services', 'RF + Lipo Cav', 'EyeLash Enhancement', 'Hair Laser Removal', 'Laser Whitening', 'Mesolipo', 'Gluta Whitening', 'Semi-Permanent Make Up', 'Waxing', 'HIFU Ultheraphy', 'Nail Services'],
    'Spa Massage' => ['Body Care', 'Traditional Body Care', 'Body Skin Treatment']
];

$serviceStmt = $pdo->query(
    'SELECT id, name, description, main_category AS category, sub_category AS subCategory, price,
            duration_minutes AS durationMinutes, image_url AS image
     FROM services
     WHERE is_active = 1
     ORDER BY main_category, sub_category, name'
);
$services = $serviceStmt->fetchAll();

if (empty($_SESSION['service_csrf_token'])) {
    $_SESSION['service_csrf_token'] = bin2hex(random_bytes(32));
}
$serviceCsrfToken = $_SESSION['service_csrf_token'];
$adminName = $_SESSION['full_name'] ?? 'Admin';
$adminInitial = strtoupper(substr(trim($adminName), 0, 1)) ?: 'A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Services Booking – Cataleya Essence</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="../css/Admin-ServicesBooking.css" />
    <link rel="stylesheet" href="../css/admin-sidebar.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body>

    <!-- ─── NAVBAR ─── -->
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
                <span class="user-name"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="user-role">Manager</span>
            </div>
            <div class="user-avatar"><?= htmlspecialchars($adminInitial, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </header>

    <!-- ─── LAYOUT ─── -->
    <div class="layout">

        <!-- ─── SIDEBAR ─── -->
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a href="Admin-Sched.php" class="nav-link"><i class="fas fa-calendar-check"></i> Schedule</a>
                <a href="Admin-BookingStatus.php" class="nav-link"><i class="fas fa-check-circle"></i> Booking Status</a>
                <a href="Admin-Service.php" class="nav-link active"><i class="fas fa-hand-sparkles"></i> Services</a>
                <a href="Admin-Staff.php" class="nav-link"><i class="fas fa-user-tie"></i> Staff</a>
                <a href="Admin-Analytics.php" class="nav-link"><i class="fas fa-chart-pie"></i> Analytics Reports</a>
                <a href="Admin-ActivityLog.php" class="nav-link"><i class="fas fa-clipboard-list"></i> Activity Log</a>
                <a href="Admin-Settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-link logout" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- ─── MAIN ─── -->
        <main class="main">

            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title-group">
                    <h1 class="page-title">Service Catalog</h1>
                    <p class="page-sub">Add, edit, or remove services from your booking catalog</p>
                </div>
            </div>

            <!-- Add Service Form -->
            <div class="card form-card">
                <h2 class="card-title form-card-title"><i class="fas fa-plus-circle"></i> Add New Service</h2>
                <form id="addServiceForm" class="service-form" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="serviceName">Service Name</label>
                            <input type="text" id="serviceName" placeholder="e.g. Signature Facial" required />
                        </div>
                        <div class="form-group">
                            <label for="serviceCategory">Category</label>
                            <select id="serviceCategory" required>
                                <?php foreach (array_keys($subCategories) as $cat): ?>
                                    <option value="<?= $cat ?>"><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="serviceSubCategory">Sub-Category</label>
                            <select id="serviceSubCategory" required>
                                <!-- populated by JS -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="servicePrice">Price (₱)</label>
                            <input type="number" id="servicePrice" placeholder="e.g. 599" required min="0" step="0.01" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Duration</label>
                            <div class="duration-inputs">
                                <div>
                                    <input type="number" id="serviceHours" placeholder="0" min="0" max="24" step="1" aria-label="Duration in hours" />
                                    <span>Hours</span>
                                </div>
                                <div>
                                    <input type="number" id="serviceMinutes" placeholder="0" min="0" max="59" step="1" aria-label="Duration in minutes" />
                                    <span>Minutes</span>
                                </div>
                            </div>
                            <small>Enter hours, minutes, or both.</small>
                        </div>
                        <div class="form-group image-upload-group">
                            <label for="serviceImage">Service Image</label>
                            <input class="file-input" type="file" id="serviceImage" accept="image/jpeg,image/png,image/gif,image/webp" />
                            <label class="file-picker" for="serviceImage">
                                <i class="fas fa-image" aria-hidden="true"></i>
                                <span class="file-picker-name" id="serviceImageFileName">Choose a service image</span>
                                <span class="file-picker-action">Choose file</span>
                            </label>
                            <small>Upload a JPG, PNG, GIF, or WebP image (maximum 5 MB).</small>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label for="serviceDescription">Description</label>
                        <textarea id="serviceDescription" rows="3" placeholder="Describe the service…"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add Service</button>
                        <button type="reset" class="btn-reset"><i class="fas fa-undo"></i> Reset</button>
                    </div>
                </form>
            </div>

            <!-- Existing Services Table -->
            <div class="card table-card">
                <div class="table-header">
                    <h2 class="card-title" style="margin-bottom:0;"><i class="fas fa-list"></i> Existing Services</h2>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="tableSearch" placeholder="Search services…" />
                    </div>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Service</th>
                                <th>Category</th>
                                <th>Sub-Category</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="serviceTableBody">
                            <!-- populated by JS -->
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <span id="rowCount">0 services</span>
                </div>
            </div>

        </main>
    </div>

    <!-- ─── EDIT MODAL ─── -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-container">
            <button class="modal-close" id="editModalClose"><i class="fas fa-times"></i></button>
            <h2 class="modal-title">Edit Service</h2>
            <form id="editServiceForm" enctype="multipart/form-data">
                <input type="hidden" id="editServiceId" />
                <div class="form-row">
                    <div class="form-group">
                        <label for="editServiceName">Service Name</label>
                        <input type="text" id="editServiceName" required />
                    </div>
                    <div class="form-group">
                        <label for="editServiceCategory">Category</label>
                        <select id="editServiceCategory" required>
                            <?php foreach (array_keys($subCategories) as $cat): ?>
                                <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="editServiceSubCategory">Sub-Category</label>
                        <select id="editServiceSubCategory" required></select>
                    </div>
                    <div class="form-group">
                        <label for="editServicePrice">Price (₱)</label>
                        <input type="number" id="editServicePrice" required min="0" step="0.01" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Duration</label>
                        <div class="duration-inputs">
                            <div>
                                <input type="number" id="editServiceHours" placeholder="0" min="0" max="24" step="1" aria-label="Duration in hours" />
                                <span>Hours</span>
                            </div>
                            <div>
                                <input type="number" id="editServiceMinutes" placeholder="0" min="0" max="59" step="1" aria-label="Duration in minutes" />
                                <span>Minutes</span>
                            </div>
                        </div>
                        <small>Enter hours, minutes, or both.</small>
                    </div>
                    <div class="form-group image-upload-group">
                        <label for="editServiceImage">Replace Service Image</label>
                        <input class="file-input" type="file" id="editServiceImage" accept="image/jpeg,image/png,image/gif,image/webp" />
                        <label class="file-picker" for="editServiceImage">
                            <i class="fas fa-image" aria-hidden="true"></i>
                            <span class="file-picker-name" id="editImageFileName">Choose a replacement image</span>
                            <span class="file-picker-action">Choose file</span>
                        </label>
                        <div class="current-image" id="editImagePreview" aria-live="polite"></div>
                        <small>Leave empty to keep the current image. JPG, PNG, GIF, or WebP up to 5 MB.</small>
                    </div>
                </div>
                <div class="form-group full-width">
                    <label for="editServiceDescription">Description</label>
                    <textarea id="editServiceDescription" rows="3"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Service</button>
                    <button type="button" class="btn-reset" id="editModalCancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── TOAST ─── -->
    <div class="toast" id="toast"></div>
    <script>
        window.serviceData = {
            services: <?= json_encode($services, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            subCategories: <?= json_encode($subCategories, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            apiUrl: '../api/admin_services.php',
            csrfToken: <?= json_encode($serviceCsrfToken) ?>
        };
    </script>
    <script src="../JS/Admin-ServicesBooking.js"></script>
</body>
</html>
