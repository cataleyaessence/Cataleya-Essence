<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_activity.php';

header('Content-Type: application/json; charset=utf-8');

function staffJson(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function uploadStaffPhoto(): array
{
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }

    $file = $_FILES['photo'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Photo upload failed. Please choose the photo again.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Staff photo must not exceed 5 MB.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $mimeType = $imageInfo['mime'] ?? '';
    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new InvalidArgumentException('Upload a valid JPG, PNG, GIF, or WebP photo.');
    }

    $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'staff' . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('Unable to create the staff photo folder.');
    }
    if (!is_writable($uploadDirectory)) {
        throw new RuntimeException('The staff photo folder is not writable.');
    }

    $filename = 'staff_' . bin2hex(random_bytes(12)) . '.' . $allowedMimeTypes[$mimeType];
    $filePath = $uploadDirectory . $filename;
    // move_uploaded_file is used for normal PHP uploads. Some local PHP server
    // adapters do not preserve PHP's upload marker, so use a validated copy as
    // a fallback. The final stream fallback also works with local dev proxies.
    $photoSaved = move_uploaded_file($file['tmp_name'], $filePath);
    if (!$photoSaved && is_file($file['tmp_name'])) {
        $photoSaved = @copy($file['tmp_name'], $filePath);
    }
    if (!$photoSaved && is_readable($file['tmp_name'])) {
        $photoContents = @file_get_contents($file['tmp_name']);
        if ($photoContents !== false) {
            $photoSaved = @file_put_contents($filePath, $photoContents, LOCK_EX) !== false;
        }
    }

    if (!$photoSaved || !is_file($filePath) || filesize($filePath) < 1) {
        if (is_file($filePath)) {
            @unlink($filePath);
        }
        error_log(sprintf(
            'Staff photo save failed. upload_error=%d temp_readable=%s target_writable=%s',
            (int) $file['error'],
            is_readable($file['tmp_name']) ? 'yes' : 'no',
            is_writable($uploadDirectory) ? 'yes' : 'no'
        ));
        throw new RuntimeException('The server could not write the selected photo.');
    }

    return ['../uploads/staff/' . $filename, $filePath];
}

function deleteUploadedStaffPhoto(?string $photoPath): void
{
    if (!$photoPath || !preg_match('#^\.\./uploads/staff/staff_[a-f0-9]{24}\.(jpg|png|gif|webp)$#', $photoPath)) {
        return;
    }

    $filePath = dirname(__DIR__) . '/uploads/staff/' . basename($photoPath);
    if (is_file($filePath)) {
        unlink($filePath);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    staffJson(405, ['success' => false, 'error' => 'Only POST requests are allowed.']);
}

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    staffJson(403, ['success' => false, 'error' => 'Administrator access is required.']);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['staff_csrf_token']) || !hash_equals($_SESSION['staff_csrf_token'], $csrfToken)) {
    staffJson(419, ['success' => false, 'error' => 'Your session has expired. Refresh the page and try again.']);
}

$action = $_GET['action'] ?? 'create';
if ($action === 'fire') {
    $staffId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($staffId === false || $staffId < 1) {
        staffJson(422, ['success' => false, 'error' => 'Select a valid staff member.']);
    }

    try {
        // Keep the record for previous bookings while freeing the email address
        // for a future staff member.
        $fireStmt = $pdo->prepare('UPDATE staff SET is_active = 0, is_available = 0, email = NULL WHERE id = ? AND is_active = 1');
        $fireStmt->execute([$staffId]);
        if ($fireStmt->rowCount() !== 1) {
            staffJson(404, ['success' => false, 'error' => 'Staff member was not found or has already been removed.']);
        }
        logAdminActivity($pdo, (int) $_SESSION['admin_id'], 'staff_fired', 'staff', $staffId, 'Staff member marked as fired');
        staffJson(200, ['success' => true]);
    } catch (PDOException $exception) {
        error_log('Admin staff firing API error: ' . $exception->getMessage());
        staffJson(500, ['success' => false, 'error' => 'Unable to remove the staff member. Please try again.']);
    }
}

if ($action !== 'create') {
    staffJson(400, ['success' => false, 'error' => 'Unknown staff action.']);
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$title = trim((string) ($_POST['title'] ?? ''));
$specialization = trim((string) ($_POST['specialization'] ?? ''));
$branch = strtolower(trim((string) ($_POST['branch'] ?? '')));
$experienceYears = filter_var($_POST['experienceYears'] ?? null, FILTER_VALIDATE_INT);
$isAvailable = isset($_POST['isAvailable']) && $_POST['isAvailable'] === '1' ? 1 : 0;

$branchCategories = [
    'cataleya' => 'Beauty Services',
    'serenity' => 'Spa Massage',
];

if ($name === '' || strlen($name) > 100) {
    staffJson(422, ['success' => false, 'error' => 'Enter a staff name with up to 100 characters.']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    staffJson(422, ['success' => false, 'error' => 'Enter a valid email address.']);
}
if (strlen($phone) > 20) {
    staffJson(422, ['success' => false, 'error' => 'Phone number must be 20 characters or fewer.']);
}
if ($title === '' || strlen($title) > 100) {
    staffJson(422, ['success' => false, 'error' => 'Enter a staff role with up to 100 characters.']);
}
if (strlen($specialization) > 255) {
    staffJson(422, ['success' => false, 'error' => 'Specialization must be 255 characters or fewer.']);
}
if (!isset($branchCategories[$branch])) {
    staffJson(422, ['success' => false, 'error' => 'Select either the Cataleya or Serenity branch.']);
}
if ($experienceYears === false || $experienceYears < 0 || $experienceYears > 60) {
    staffJson(422, ['success' => false, 'error' => 'Experience must be between 0 and 60 years.']);
}

try {
    // The existing category field is the branch source: Cataleya = Beauty Services, Serenity = Spa Massage.
    [$photo, $uploadedPhotoPath] = uploadStaffPhoto();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO staff (full_name, email, phone, specialization, category, title, image_url, experience_years, is_available)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $name,
            $email,
            $phone === '' ? null : $phone,
            $specialization === '' ? null : $specialization,
            $branchCategories[$branch],
            $title,
            $photo,
            $experienceYears,
            $isAvailable,
        ]);
    } catch (PDOException $exception) {
        if ($uploadedPhotoPath) {
            deleteUploadedStaffPhoto($photo);
        }
        throw $exception;
    }

    $staffId = (int) $pdo->lastInsertId();
    $staffStmt = $pdo->prepare(
        'SELECT id, full_name, title, category, specialization, image_url, experience_years, is_available
         FROM staff
         WHERE id = ? AND is_active = 1'
    );
    $staffStmt->execute([$staffId]);
    $staff = $staffStmt->fetch();
    logAdminActivity($pdo, (int) $_SESSION['admin_id'], 'staff_added', 'staff', $staffId, 'Added staff: ' . $name);
    staffJson(201, ['success' => true, 'staff' => $staff]);
} catch (InvalidArgumentException $exception) {
    staffJson(422, ['success' => false, 'error' => $exception->getMessage()]);
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        staffJson(422, ['success' => false, 'error' => 'That email address is already assigned to a staff member.']);
    }

    error_log('Admin staff API error: ' . $exception->getMessage());
    staffJson(500, ['success' => false, 'error' => 'Unable to add the staff member. Please try again.']);
} catch (RuntimeException $exception) {
    error_log('Admin staff photo upload error: ' . $exception->getMessage());
    staffJson(500, ['success' => false, 'error' => $exception->getMessage() . ' Restart the PHP server, then try again.']);
}
