<?php
require_once 'config/database.php';

// Check services table structure
$stmt = $pdo->query("DESCRIBE services");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Services table structure:\n";
foreach ($columns as $column) {
    echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
}

// Check if there are any services with images
$stmt = $pdo->query("SELECT id, name, image_url FROM services LIMIT 5");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nSample services:\n";
foreach ($services as $service) {
    echo "- ID: " . $service['id'] . ", Name: " . $service['name'] . ", Image: " . ($service['image_url'] ?? 'NULL') . "\n";
}
?>
