<?php
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle photo removal
if (isset($_POST['action']) && $_POST['action'] === 'remove') {
    try {
        // Get current photo
        $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && $user['profile_photo']) {
            // Delete file from server
            $filepath = __DIR__ . '/../' . $user['profile_photo'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            // Update database
            $stmt = $pdo->prepare("UPDATE users SET profile_photo = NULL WHERE id = ?");
            $stmt->execute([$user_id]);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Profile photo removed successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No profile photo to remove']);
        }
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['profile_photo'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.']);
    exit;
}

// Validate file size (max 5MB)
$max_size = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $max_size) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
    exit;
}

// Create uploads directory if it doesn't exist
$upload_dir = __DIR__ . '/../uploads/profile_photos/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

// Get old photo to delete
$stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$old_user = $stmt->fetch();

// Update database
$photo_url = '../uploads/profile_photos/' . $filename;
try {
    $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
    $stmt->execute([$photo_url, $user_id]);
    
    // Delete old photo file
    if ($old_user && $old_user['profile_photo']) {
        $old_filepath = __DIR__ . '/../' . $old_user['profile_photo'];
        if (file_exists($old_filepath)) {
            unlink($old_filepath);
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Profile photo updated successfully',
        'photo_url' => $photo_url
    ]);
} catch (PDOException $e) {
    // Delete file if database update failed
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
