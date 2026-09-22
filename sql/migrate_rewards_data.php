<?php
require_once __DIR__ . '/../config/database.php';

echo "Starting rewards data migration...\n";

// Update rewards table structure if needed
try {
    $pdo->exec("ALTER TABLE rewards ADD COLUMN IF NOT EXISTS visits INT DEFAULT 0 AFTER points");
    $pdo->exec("ALTER TABLE rewards ADD COLUMN IF NOT EXISTS last_birthday_bonus_year YEAR DEFAULT NULL AFTER visits");
    echo "✓ Database structure updated\n";
} catch (PDOException $e) {
    echo "Note: Columns may already exist - " . $e->getMessage() . "\n";
}

// Get all users
$stmt = $pdo->query("SELECT id FROM users");
$users = $stmt->fetchAll();

echo "Processing " . count($users) . " users...\n";

foreach ($users as $user) {
    $user_id = $user['id'];
    
    // Count bookings (pending and completed) for this user
    $stmt = $pdo->prepare("SELECT COUNT(*) as booking_count FROM bookings WHERE user_id = ? AND status IN ('pending', 'completed')");
    $stmt->execute([$user_id]);
    $booking_count = $stmt->fetch()['booking_count'];
    
    // Calculate total points based on bookings (pending and completed)
    $stmt = $pdo->prepare("
        SELECT s.price
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE b.user_id = ? AND b.status IN ('pending', 'completed')
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll();
    
    $total_points = 0;
    foreach ($bookings as $booking) {
        $price = $booking['price'];
        if ($price <= 400) {
            $total_points += 10;
        } elseif ($price <= 799) {
            $total_points += 15;
        } else {
            $total_points += 20;
        }
    }
    
    // Determine tier based on new thresholds
    if ($total_points >= 200) {
        $tier = 'platinum';
    } elseif ($total_points >= 150) {
        $tier = 'gold';
    } elseif ($total_points >= 100) {
        $tier = 'silver';
    } elseif ($total_points >= 50) {
        $tier = 'bronze';
    } else {
        $tier = 'bronze'; // Default to bronze
    }
    
    // Check if rewards record exists
    $stmt = $pdo->prepare("SELECT id FROM rewards WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing record
        $stmt = $pdo->prepare("UPDATE rewards SET points = ?, visits = ?, tier = ? WHERE user_id = ?");
        $stmt->execute([$total_points, $booking_count, $tier, $user_id]);
        echo "✓ Updated user ID $user_id: $total_points pts, $booking_count visits, $tier tier\n";
    } else {
        // Create new record
        $stmt = $pdo->prepare("INSERT INTO rewards (user_id, points, visits, tier) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $total_points, $booking_count, $tier]);
        echo "✓ Created rewards for user ID $user_id: $total_points pts, $booking_count visits, $tier tier\n";
    }
}

echo "\nMigration complete!\n";
echo "Summary:\n";
echo "- Points calculated based on service price ranges (₱120-₱400: 10pts, ₱401-₱799: 15pts, ₱800+: 20pts)\n";
echo "- Visits counted from completed bookings\n";
echo "- Tiers assigned: Bronze (50+), Silver (100+), Gold (150+), Platinum (200+)\n";
