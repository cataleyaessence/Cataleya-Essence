<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $timeZone = new DateTimeZone('Asia/Manila');
    $now = new DateTimeImmutable('now', $timeZone);
    $date = isset($_GET['date']) ? $_GET['date'] : $now->format('Y-m-d');

    // Validate date format
    $requestedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $timeZone);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
        || !$requestedDate
        || $requestedDate->format('Y-m-d') !== $date) {
        throw new Exception('Invalid date format');
    }

    // Check if date is in the past
    if ($date < $now->format('Y-m-d')) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'Past date - no slots available'
        ]);
        exit;
    }

    // Resolve availability by slot, not by the date as a whole. The booking
    // count is included as a safeguard for older rows where the daily counter
    // was not created or was not yet synchronized.
    $stmt = $pdo->prepare("
        SELECT 
            ts.id,
            ts.slot_time,
            ts.display_time,
            COALESCE(dsa.status, 'available') as status,
            COALESCE(dsa.current_bookings, 0) as current_bookings,
            COALESCE(dsa.max_bookings, 1) as max_bookings,
            COALESCE(active_bookings.booking_count, 0) as active_booking_count
        FROM time_slots ts
        LEFT JOIN daily_slot_availability dsa ON ts.id = dsa.slot_id AND dsa.slot_date = ?
        LEFT JOIN (
            SELECT booking_time, COUNT(*) AS booking_count
            FROM bookings
            WHERE booking_date = ?
              AND status IN ('confirmed', 'rescheduled')
            GROUP BY booking_time
        ) AS active_bookings ON active_bookings.booking_time = ts.slot_time
        WHERE ts.is_active = 1
        ORDER BY ts.sort_order ASC
    ");
    $stmt->execute([$date, $date]);
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // A customer can have multiple appointments in one day, but may not take
    // the same time twice. Keep that customer's booked time unavailable even
    // when a slot has capacity for more than one booking.
    $userBookedTimes = [];
    if (!empty($_SESSION['user_id'])) {
        $userBookingStatement = $pdo->prepare(
            "SELECT booking_time
             FROM bookings
             WHERE user_id = ? AND booking_date = ?
               AND status IN ('confirmed', 'rescheduled')"
        );
        $userBookingStatement->execute([(int) $_SESSION['user_id'], $date]);
        $userBookedTimes = $userBookingStatement->fetchAll(PDO::FETCH_COLUMN);
    }

    // Format the response
    $timeSlots = [];
    $availableCount = 0;
    $bookedCount = 0;

    foreach ($slots as $slot) {
        $currentBookings = max((int) $slot['current_bookings'], (int) $slot['active_booking_count']);
        $maxBookings = max(1, (int) $slot['max_bookings']);
        $userBooked = in_array((string) $slot['slot_time'], $userBookedTimes, true);
        $slotStart = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $date . ' ' . substr((string) $slot['slot_time'], 0, 8),
            $timeZone
        );
        $timeHasPassed = $slotStart instanceof DateTimeImmutable && $slotStart <= $now;

        // Past slots are not booking choices anymore. This does not change
        // any existing booking record; it only removes elapsed times from
        // the user-facing calendar.
        if ($timeHasPassed) {
            continue;
        }

        $isAvailable = $currentBookings < $maxBookings
            && !in_array(strtolower((string) $slot['status']), ['booked', 'unavailable'], true)
            && !$userBooked;
        
        if ($isAvailable) {
            $availableCount++;
        } else {
            $bookedCount++;
        }

        $timeSlots[] = [
            'id' => $slot['id'],
            'time' => $slot['display_time'],
            'slot_time' => $slot['slot_time'],
            'status' => $isAvailable ? 'available' : 'booked',
            'bookings' => $currentBookings,
            'max_bookings' => $maxBookings,
            'user_booked' => $userBooked,
            'time_has_passed' => $timeHasPassed
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $timeSlots,
        'date' => $date,
        'available_count' => $availableCount,
        'booked_count' => $bookedCount
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
