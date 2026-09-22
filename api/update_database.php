<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Check if profile_photo column already exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'profile_photo'");
    $stmt->execute();
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        // Add profile_photo column
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) AFTER postal_code");
        echo "Added profile_photo column to users table.<br>";
    } else {
        echo "profile_photo column already exists in users table.<br>";
    }

    // Check if phone column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'phone'");
    $stmt->execute();
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        // Add phone and address fields
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER email");
        $pdo->exec("ALTER TABLE users ADD COLUMN address_line1 VARCHAR(255) AFTER phone");
        $pdo->exec("ALTER TABLE users ADD COLUMN address_line2 VARCHAR(255) AFTER address_line1");
        $pdo->exec("ALTER TABLE users ADD COLUMN city VARCHAR(100) AFTER address_line2");
        $pdo->exec("ALTER TABLE users ADD COLUMN province VARCHAR(100) AFTER city");
        $pdo->exec("ALTER TABLE users ADD COLUMN postal_code VARCHAR(20) AFTER province");
        echo "Added phone and address columns to users table.<br>";
    } else {
        echo "Phone and address columns already exist in users table.<br>";
    }

    echo "<br>Database update completed successfully!";
    echo "<br><a href='../Php/bookings.php'>Go to Bookings Page</a>";

} catch (PDOException $e) {
    echo "Database update error: " . $e->getMessage();
}
