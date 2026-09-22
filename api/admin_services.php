<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_activity.php';

header('Content-Type: application/json; charset=utf-8');

function sendJson(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function getRequestData(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data)) {
        throw new InvalidArgumentException('Invalid request data.');
    }

    return $data;
}

function validateService(array $data): array
{
    $name = trim((string)($data['name'] ?? ''));
    $category = trim((string)($data['category'] ?? ''));
    $subCategory = trim((string)($data['subCategory'] ?? ''));
    $description = trim((string)($data['description'] ?? ''));
    $price = filter_var($data['price'] ?? null, FILTER_VALIDATE_FLOAT);
    $durationMinutes = filter_var($data['durationMinutes'] ?? null, FILTER_VALIDATE_INT);

    $allowedSubCategories = [
        'Beauty Services' => ['Facial Services', 'RF + Lipo Cav', 'EyeLash Enhancement', 'Hair Laser Removal', 'Laser Whitening', 'Mesolipo', 'Gluta Whitening', 'Semi-Permanent Make Up', 'Waxing', 'HIFU Ultheraphy', 'Nail Services'],
        'Spa Massage' => ['Body Care', 'Traditional Body Care', 'Body Skin Treatment'],
    ];

    if ($name === '' || strlen($name) > 255) {
        throw new InvalidArgumentException('Enter a service name with up to 255 characters.');
    }
    if (!isset($allowedSubCategories[$category])) {
        throw new InvalidArgumentException('Select a valid service category.');
    }
    if (!in_array($subCategory, $allowedSubCategories[$category], true)) {
        throw new InvalidArgumentException('Select a valid sub-category for the chosen category.');
    }
    if ($price === false || $price < 0 || $price > 99999999.99) {
        throw new InvalidArgumentException('Enter a valid service price.');
    }
    if ($durationMinutes === false || $durationMinutes < 1 || $durationMinutes > 1440) {
        throw new InvalidArgumentException('Duration must be between 1 and 1,440 minutes.');
    }
    return [$name, $description === '' ? null : $description, $category, $subCategory, $price, $durationMinutes];
}

function uploadServiceImage(?string $currentImage = null): array
{
    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        return [$currentImage, null];
    }

    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Image upload failed. Please choose the image again.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Image size must not exceed 5 MB.');
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
        throw new InvalidArgumentException('Upload a valid JPG, PNG, GIF, or WebP image.');
    }

    $uploadDirectory = __DIR__ . '/../uploads/services/';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('Unable to create the service image folder.');
    }

    $filename = 'service_' . bin2hex(random_bytes(12)) . '.' . $allowedMimeTypes[$mimeType];
    $filePath = $uploadDirectory . $filename;
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new RuntimeException('Unable to save the uploaded image.');
    }

    return ['../uploads/services/' . $filename, $filePath];
}

function deleteUploadedServiceImage(?string $imagePath): void
{
    if (!$imagePath || !preg_match('#^\.\./uploads/services/service_[a-f0-9]{24}\.(jpg|png|gif|webp)$#', $imagePath)) {
        return;
    }

    $filePath = dirname(__DIR__) . '/uploads/services/' . basename($imagePath);
    if (is_file($filePath)) {
        unlink($filePath);
    }
}

function getService(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, name, description, main_category AS category, sub_category AS subCategory, price,
                duration_minutes AS durationMinutes, image_url AS image
         FROM services
         WHERE id = ? AND is_active = 1'
    );
    $stmt->execute([$id]);
    $service = $stmt->fetch();

    return $service ?: null;
}

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    sendJson(403, ['success' => false, 'error' => 'Administrator access is required.']);
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['success' => false, 'error' => 'Only POST requests are allowed.']);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['service_csrf_token']) || !hash_equals($_SESSION['service_csrf_token'], $csrfToken)) {
    sendJson(419, ['success' => false, 'error' => 'Your session has expired. Refresh the page and try again.']);
}

try {
    $data = getRequestData();

    if ($action === 'create') {
        [$name, $description, $category, $subCategory, $price, $durationMinutes] = validateService($data);
        [$image, $uploadedFilePath] = uploadServiceImage();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO services (name, description, main_category, sub_category, price, duration_minutes, image_url, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
            );
            $stmt->execute([$name, $description, $category, $subCategory, $price, $durationMinutes, $image]);
        } catch (PDOException $exception) {
            if ($uploadedFilePath) {
                deleteUploadedServiceImage($image);
            }
            throw $exception;
        }
        $serviceId = (int) $pdo->lastInsertId();
        $service = getService($pdo, $serviceId);
        logAdminActivity($pdo, (int) $_SESSION['admin_id'], 'service_added', 'service', $serviceId, 'Added service: ' . $name);
        sendJson(201, ['success' => true, 'service' => $service]);
    }

    if ($action === 'update') {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $currentService = $id !== false && $id >= 1 ? getService($pdo, $id) : null;
        if (!$currentService) {
            sendJson(404, ['success' => false, 'error' => 'Service not found. Refresh the page and try again.']);
        }

        [$name, $description, $category, $subCategory, $price, $durationMinutes] = validateService($data);
        [$image, $uploadedFilePath] = uploadServiceImage($currentService['image']);
        try {
            $stmt = $pdo->prepare(
                'UPDATE services
                 SET name = ?, description = ?, main_category = ?, sub_category = ?, price = ?, duration_minutes = ?, image_url = ?
                 WHERE id = ? AND is_active = 1'
            );
            $stmt->execute([$name, $description, $category, $subCategory, $price, $durationMinutes, $image, $id]);
        } catch (PDOException $exception) {
            if ($uploadedFilePath) {
                deleteUploadedServiceImage($image);
            }
            throw $exception;
        }
        if ($uploadedFilePath) {
            deleteUploadedServiceImage($currentService['image']);
        }
        logAdminActivity($pdo, (int) $_SESSION['admin_id'], 'service_updated', 'service', $id, 'Updated service: ' . $name);
        sendJson(200, ['success' => true, 'service' => getService($pdo, $id)]);
    }

    if ($action === 'delete') {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $currentService = $id !== false && $id >= 1 ? getService($pdo, $id) : null;
        if (!$currentService) {
            sendJson(404, ['success' => false, 'error' => 'Service not found. Refresh the page and try again.']);
        }

        // Preserve services referenced by past bookings while removing them from the booking catalog.
        $stmt = $pdo->prepare('UPDATE services SET is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);
        logAdminActivity($pdo, (int) $_SESSION['admin_id'], 'service_removed', 'service', $id, 'Removed service: ' . $currentService['name']);
        sendJson(200, ['success' => true]);
    }

    sendJson(400, ['success' => false, 'error' => 'Unknown service action.']);
} catch (InvalidArgumentException $exception) {
    sendJson(422, ['success' => false, 'error' => $exception->getMessage()]);
} catch (RuntimeException $exception) {
    error_log('Admin service image upload error: ' . $exception->getMessage());
    sendJson(500, ['success' => false, 'error' => 'Unable to save the uploaded image. Please try again.']);
} catch (PDOException $exception) {
    error_log('Admin service API error: ' . $exception->getMessage());
    sendJson(500, ['success' => false, 'error' => 'Unable to save the service. Please try again.']);
}
