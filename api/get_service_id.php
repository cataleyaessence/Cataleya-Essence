<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$service_name = $_GET['name'] ?? '';

if (!$service_name) {
    echo json_encode(['success' => false, 'error' => 'Service name is required']);
    exit;
}

try {
    // Try exact match first
    $stmt = $pdo->prepare("SELECT id, name, price FROM services WHERE name = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$service_name]);
    $service = $stmt->fetch();
    
    if (!$service) {
        // Try case-insensitive match
        $stmt = $pdo->prepare("SELECT id, name, price FROM services WHERE LOWER(name) = LOWER(?) AND is_active = 1 LIMIT 1");
        $stmt->execute([$service_name]);
        $service = $stmt->fetch();
    }
    
    if ($service) {
        echo json_encode(['success' => true, 'service' => $service]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Service not found: ' . $service_name]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
