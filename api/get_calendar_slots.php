<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $timeZone = new DateTimeZone('Asia/Manila');
    $now = new DateTimeImmutable('now', $timeZone);
    $todayDate = $now->format('Y-m-d');
    $month = isset($_GET['month']) ? intval($_GET['month']) : (int) $now->format('n');
    $year = isset($_GET['year']) ? intval($_GET['year']) : (int) $now->format('Y');

    // Get the first and last day of the month
    $firstDay = mktime(0, 0, 0, $month, 1, $year);
    $lastDay = mktime(0, 0, 0, $month + 1, 0, $year);
    
    $startDate = date('Y-m-d', $firstDay);
    $endDate = date('Y-m-d', $lastDay);

    // A calendar day is selectable whenever it has at least one free active
    // time slot. Do not treat the presence of one booked slot record as a
    // fully booked day.
    $activeSlotStatement = $pdo->query('SELECT COUNT(*) FROM time_slots WHERE is_active = 1');
    $activeSlotCount = (int) $activeSlotStatement->fetchColumn();

    // Start with every day which has either an availability record or an
    // active booking. Cross joining those days with all active time slots lets
    // us count missing daily_slot_availability rows as available slots.
    $stmt = $pdo->prepare("
        SELECT 
            calendar_days.slot_date,
            COUNT(ts.id) AS total_slots,
            SUM(
                CASE
                    WHEN GREATEST(COALESCE(dsa.current_bookings, 0), COALESCE(active_bookings.booking_count, 0))
                         >= COALESCE(NULLIF(dsa.max_bookings, 0), 1)
                    THEN 1 ELSE 0
                END
            ) AS booked_count
        FROM (
            SELECT slot_date
            FROM daily_slot_availability
            WHERE slot_date BETWEEN ? AND ?
            GROUP BY slot_date
            UNION
            SELECT booking_date AS slot_date
            FROM bookings
            WHERE booking_date BETWEEN ? AND ?
              AND status IN ('confirmed', 'rescheduled')
            GROUP BY booking_date
        ) AS calendar_days
        CROSS JOIN time_slots ts
        LEFT JOIN daily_slot_availability dsa
            ON dsa.slot_date = calendar_days.slot_date
           AND dsa.slot_id = ts.id
        LEFT JOIN (
            SELECT booking_date, booking_time, COUNT(*) AS booking_count
            FROM bookings
            WHERE booking_date BETWEEN ? AND ?
              AND status IN ('confirmed', 'rescheduled')
            GROUP BY booking_date, booking_time
        ) AS active_bookings
            ON active_bookings.booking_date = calendar_days.slot_date
           AND active_bookings.booking_time = ts.slot_time
        WHERE ts.is_active = 1
        GROUP BY calendar_days.slot_date
        ORDER BY calendar_days.slot_date
    ");
    $stmt->execute([$startDate, $endDate, $startDate, $endDate, $startDate, $endDate]);
    $dailyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $calendarData = [];
    foreach ($dailyData as $day) {
        $total = $day['total_slots'];
        $booked = $day['booked_count'];
        $available = max(0, $total - $booked);

        // The day remains available until every active slot is occupied.
        $status = $available > 0 ? 'available' : 'booked';

        $calendarData[$day['slot_date']] = [
            'status' => $status,
            'available' => $available,
            'booked' => $booked,
            'filling' => 0,
            'total' => $total
        ];
    }

    // Initialize slots for dates that don't exist in database
    $currentDate = $startDate;
    while ($currentDate <= $endDate) {
        if (!isset($calendarData[$currentDate])) {
            // Check if it's a past date
            if ($currentDate < $todayDate) {
                $calendarData[$currentDate] = [
                    'status' => 'past',
                    'available' => 0,
                    'booked' => 0,
                    'filling' => 0,
                    'total' => 0
                ];
            } else {
                // Initialize as available (will be created on first access)
                $calendarData[$currentDate] = [
                    'status' => 'available',
                    'available' => $activeSlotCount,
                    'booked' => 0,
                    'filling' => 0,
                    'total' => $activeSlotCount
                ];
            }
        }
        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
    }

    // For the current date, base the day state on the remaining time slots
    // only. This prevents a morning slot from keeping today selectable after
    // its time has already passed.
    if ($todayDate >= $startDate && $todayDate <= $endDate) {
        $remainingSlotsStatement = $pdo->prepare(
            "SELECT
                COUNT(ts.id) AS total_slots,
                SUM(
                    CASE
                        WHEN LOWER(COALESCE(dsa.status, 'available')) IN ('booked', 'unavailable')
                             OR GREATEST(COALESCE(dsa.current_bookings, 0), COALESCE(active_bookings.booking_count, 0))
                                >= COALESCE(NULLIF(dsa.max_bookings, 0), 1)
                        THEN 1 ELSE 0
                    END
                ) AS booked_count
             FROM time_slots ts
             LEFT JOIN daily_slot_availability dsa
                ON dsa.slot_date = ? AND dsa.slot_id = ts.id
             LEFT JOIN (
                SELECT booking_time, COUNT(*) AS booking_count
                FROM bookings
                WHERE booking_date = ? AND status IN ('confirmed', 'rescheduled')
                GROUP BY booking_time
             ) AS active_bookings ON active_bookings.booking_time = ts.slot_time
             WHERE ts.is_active = 1 AND ts.slot_time > ?"
        );
        $remainingSlotsStatement->execute([$todayDate, $todayDate, $now->format('H:i:s')]);
        $remainingSlots = $remainingSlotsStatement->fetch(PDO::FETCH_ASSOC) ?: [];
        $remainingTotal = (int) ($remainingSlots['total_slots'] ?? 0);
        $remainingBooked = (int) ($remainingSlots['booked_count'] ?? 0);
        $remainingAvailable = max(0, $remainingTotal - $remainingBooked);

        $calendarData[$todayDate] = [
            'status' => $remainingTotal === 0 ? 'past' : ($remainingAvailable > 0 ? 'available' : 'booked'),
            'available' => $remainingAvailable,
            'booked' => $remainingBooked,
            'filling' => 0,
            'total' => $remainingTotal,
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $calendarData,
        'month' => $month,
        'year' => $year
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
