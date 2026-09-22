<?php
require_once __DIR__ . '/config/database.php';

// Test database connection and therapist data
try {
    // Test connection
    echo "Testing database connection...\n";
    echo "Connected successfully!\n\n";
    
    // Check if staff table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM staff");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total therapists in database: " . $result['count'] . "\n\n";
    
    // Check if new columns exist
    $stmt = $pdo->query("DESCRIBE staff");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Staff table columns:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    echo "\n";
    
    // Test fetching Beauty Services therapists
    echo "Testing Beauty Services therapists:\n";
    $stmt = $pdo->prepare("SELECT id, full_name, title, category FROM staff WHERE category = 'Beauty Services' LIMIT 3");
    $stmt->execute();
    $therapists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($therapists as $t) {
        echo "- ID: " . $t['id'] . ", Name: " . $t['full_name'] . ", Title: " . $t['title'] . "\n";
    }
    echo "\n";
    
    // Test fetching Spa Massage therapists
    echo "Testing Spa Massage therapists:\n";
    $stmt = $pdo->prepare("SELECT id, full_name, title, category FROM staff WHERE category = 'Spa Massage' LIMIT 3");
    $stmt->execute();
    $therapists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($therapists as $t) {
        echo "- ID: " . $t['id'] . ", Name: " . $t['full_name'] . ", Title: " . $t['title'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
