<?php
error_reporting(0); // Suppress PHP warnings/errors from polluting JSON output
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['year']) || !isset($_GET['month'])) {
    echo json_encode(['error' => 'Year and month parameters required']);
    exit;
}

$year = intval($_GET['year']);
$month = intval($_GET['month']);

// Validate year and month
if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
    echo json_encode(['error' => 'Invalid year or month']);
    exit;
}

try {
    // Get first and last day of the month
    $first_day = new DateTime("$year-$month-01");
    $last_day = new DateTime("$year-$month-" . $first_day->format('t'));

    // A date is fully booked only when all of its active time slots have an
    // active booking (or have been marked unavailable). This lets the user
    // select dates that still have at least one open time.
    $stmt = $pdo->prepare("
        SELECT
            calendar_days.slot_date,
            COUNT(ts.id) AS total_slots,
            SUM(
                CASE
                    WHEN LOWER(COALESCE(dsa.status, 'available')) IN ('booked', 'unavailable')
                         OR GREATEST(COALESCE(dsa.current_bookings, 0), COALESCE(active_bookings.booking_count, 0))
                            >= COALESCE(NULLIF(dsa.max_bookings, 0), 1)
                    THEN 1 ELSE 0
                END
            ) AS booked_slots
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
    $startDate = $first_day->format('Y-m-d');
    $endDate = $last_day->format('Y-m-d');
    $stmt->execute([$startDate, $endDate, $startDate, $endDate, $startDate, $endDate]);
    $dailyRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $daily_availability = [];
    foreach ($dailyRows as $day) {
        $totalSlots = (int) $day['total_slots'];
        $bookedSlots = (int) $day['booked_slots'];
        $daily_availability[(string) $day['slot_date']] = $totalSlots > 0 && $bookedSlots >= $totalSlots
            ? 'booked'
            : ($bookedSlots > 0 ? 'filling' : 'available');
    }

    echo json_encode(['success' => true, 'availability' => $daily_availability]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
