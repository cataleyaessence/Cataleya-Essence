<?php
error_reporting(0); // Suppress PHP warnings/errors from polluting JSON output
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['date'])) {
    echo json_encode(['error' => 'Date parameter required']);
    exit;
}

$date = $_GET['date'];

// Validate date format
if (!DateTime::createFromFormat('Y-m-d', $date)) {
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

try {
    // Always return every active time slot. A left join preserves available
    // slots even when another slot on the same date already has a record.
    $stmt = $pdo->prepare("
        SELECT t.id AS slot_id,
               COALESCE(d.status, 'available') AS status,
               COALESCE(d.max_bookings, 1) AS max_bookings,
               COALESCE(d.current_bookings, 0) AS current_bookings,
               t.display_time,
               t.slot_time
        FROM time_slots t
        LEFT JOIN daily_slot_availability d
            ON d.slot_id = t.id AND d.slot_date = ?
        WHERE t.is_active = 1
        ORDER BY t.sort_order
    ");
    $stmt->execute([$date]);
    $date_slots = $stmt->fetchAll();

    // Check if user is logged in and fetch their bookings for this date
    $user_booked_slots = [];
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("
            SELECT booking_time
            FROM bookings
            WHERE user_id = ? AND booking_date = ?
        ");
        $stmt->execute([$user_id, $date]);
        $user_bookings = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $user_booked_slots = $user_bookings;
    }

    // Add user booking info to each slot
    foreach ($date_slots as &$slot) {
        $slot['user_booked'] = in_array($slot['slot_time'], $user_booked_slots);
    }

    echo json_encode(['success' => true, 'slots' => $date_slots]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
