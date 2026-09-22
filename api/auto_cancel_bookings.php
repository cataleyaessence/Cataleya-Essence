<?php
require_once __DIR__ . '/../config/database.php';

echo "Starting auto-cancel for expired bookings...\n";

// Find bookings that are:
// - Still pending
// - Have passed their deadline
// - Not already auto-cancelled
$stmt = $pdo->prepare("
    SELECT id, user_id, service_id, total_amount
    FROM bookings
    WHERE status = 'pending'
    AND deadline < NOW()
    AND auto_cancelled = 0
");
$stmt->execute();
$expired_bookings = $stmt->fetchAll();

if (empty($expired_bookings)) {
    echo "No expired bookings to cancel.\n";
    exit;
}

echo "Found " . count($expired_bookings) . " expired booking(s) to cancel.\n";

foreach ($expired_bookings as $booking) {
    $pdo->beginTransaction();
    
    try {
        // Update booking status to cancelled and mark as auto-cancelled
        $stmt = $pdo->prepare("
            UPDATE bookings
            SET status = 'cancelled',
                auto_cancelled = 1,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$booking['id']]);
        
        // Note: Points are NOT deducted as per user request
        // Points remain with the user even when booking is auto-cancelled
        
        $pdo->commit();
        echo "✓ Cancelled booking ID " . $booking['id'] . " (User ID: " . $booking['user_id'] . ")\n";
        echo "  Points NOT deducted - user keeps their rewards\n";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "✗ Error cancelling booking ID " . $booking['id'] . ": " . $e->getMessage() . "\n";
    }
}

echo "\nAuto-cancel complete.\n";
echo "Summary: " . count($expired_bookings) . " booking(s) cancelled.\n";
echo "Note: Points were NOT deducted from users' rewards.\n";
