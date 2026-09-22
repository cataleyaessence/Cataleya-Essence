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
    // Get time slots for the selected date
    $stmt = $pdo->prepare("
        SELECT d.slot_id, d.status, d.max_bookings, d.current_bookings, t.display_time, t.slot_time
        FROM daily_slot_availability d
        JOIN time_slots t ON d.slot_id = t.id
        WHERE d.slot_date = ? AND t.is_active = 1
        ORDER BY t.sort_order
    ");
    $stmt->execute([$date]);
    $date_slots = $stmt->fetchAll();

    // If no slots found for this date, get all active time slots as available
    if (empty($date_slots)) {
        $stmt = $pdo->prepare("SELECT id, slot_time, display_time FROM time_slots WHERE is_active = 1 ORDER BY sort_order");
        $stmt->execute();
        $all_slots = $stmt->fetchAll();

        $date_slots = array_map(function($slot) {
            return [
                'slot_id' => $slot['id'],
                'status' => 'available',
                'max_bookings' => 1,
                'current_bookings' => 0,
                'display_time' => $slot['display_time'],
                'slot_time' => $slot['slot_time']
            ];
        }, $all_slots);
    }

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
