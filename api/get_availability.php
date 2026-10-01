<?php
error_reporting(0); // Suppress PHP warnings/errors from polluting JSON output
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['date'])) {
    echo json_encode(['error' => 'Date parameter required']);
    exit;
}

$date = $_GET['date'];
$timeZone = new DateTimeZone('Asia/Manila');
$now = new DateTimeImmutable('now', $timeZone);

// Validate date format
$dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $timeZone);
if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

try {
    // Always return every active time slot. Resolve its state from both the
    // daily counter and active bookings so a newly confirmed booking is shown
    // as booked even before a daily availability row has been synchronized.
    $stmt = $pdo->prepare("
        SELECT t.id AS slot_id,
               COALESCE(d.status, 'available') AS daily_status,
               COALESCE(d.max_bookings, 1) AS max_bookings,
               COALESCE(d.current_bookings, 0) AS current_bookings,
               COALESCE(active_bookings.booking_count, 0) AS active_booking_count,
               t.display_time,
               t.slot_time
        FROM time_slots t
        LEFT JOIN daily_slot_availability d
            ON d.slot_id = t.id AND d.slot_date = ?
        LEFT JOIN (
            SELECT booking_time, COUNT(*) AS booking_count
            FROM bookings
            WHERE booking_date = ?
              AND status IN ('confirmed', 'rescheduled')
            GROUP BY booking_time
        ) AS active_bookings ON active_bookings.booking_time = t.slot_time
        WHERE t.is_active = 1
        ORDER BY t.sort_order
    ");
    $stmt->execute([$date, $date]);
    $date_slots = $stmt->fetchAll();

    // Check if user is logged in and fetch their bookings for this date
    $user_booked_slots = [];
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("
            SELECT booking_time
            FROM bookings
            WHERE user_id = ?
              AND booking_date = ?
              AND status IN ('confirmed', 'rescheduled')
        ");
        $stmt->execute([$user_id, $date]);
        $user_bookings = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $user_booked_slots = $user_bookings;
    }

    // Normalize every slot to either available or booked for the calendar.
    foreach ($date_slots as &$slot) {
        $currentBookings = max((int) $slot['current_bookings'], (int) $slot['active_booking_count']);
        $maxBookings = max(1, (int) $slot['max_bookings']);
        $dailyStatus = strtolower((string) $slot['daily_status']);
        $slot['user_booked'] = in_array($slot['slot_time'], $user_booked_slots, true);
        $slotStart = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $date . ' ' . substr((string) $slot['slot_time'], 0, 8),
            $timeZone
        );
        $slot['time_has_passed'] = $slotStart instanceof DateTimeImmutable && $slotStart <= $now;
        $slot['status'] = $slot['time_has_passed']
            ? 'past'
            : ($currentBookings >= $maxBookings || $slot['user_booked'] || in_array($dailyStatus, ['booked', 'unavailable'], true)
                ? 'booked'
                : 'available');
    }

    echo json_encode(['success' => true, 'slots' => $date_slots]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
