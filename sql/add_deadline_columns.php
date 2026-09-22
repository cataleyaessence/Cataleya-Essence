<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo->exec("ALTER TABLE bookings ADD COLUMN deadline DATETIME DEFAULT NULL AFTER booking_time");
    echo "✓ Added deadline column\n";
} catch (PDOException $e) {
    echo "Note: deadline column may already exist - " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE bookings ADD COLUMN auto_cancelled BOOLEAN DEFAULT 0 AFTER status");
    echo "✓ Added auto_cancelled column\n";
} catch (PDOException $e) {
    echo "Note: auto_cancelled column may already exist - " . $e->getMessage() . "\n";
}

echo "\nDatabase schema updated successfully.\n";
