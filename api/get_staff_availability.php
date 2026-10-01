<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please sign in to view therapist availability.']);
    exit;
}

$category = trim((string) ($_GET['category'] ?? ''));
$bookingDate = trim((string) ($_GET['date'] ?? ''));
$bookingTime = trim((string) ($_GET['time'] ?? ''));

if (!in_array($category, ['Beauty Services', 'Spa Massage'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid service category.']);
    exit;
}

$dateObject = DateTime::createFromFormat('!Y-m-d', $bookingDate);
if (!$dateObject || $dateObject->format('Y-m-d') !== $bookingDate || $bookingDate < date('Y-m-d')) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Choose a valid appointment date.']);
    exit;
}

try {
    $slotStatement = $pdo->prepare(
        'SELECT slot_time
         FROM time_slots
         WHERE is_active = 1 AND (slot_time = ? OR display_time = ?)
         LIMIT 1'
    );
    $slotStatement->execute([$bookingTime, $bookingTime]);
    $slot = $slotStatement->fetch();
    if (!$slot) {
        throw new InvalidArgumentException('Choose a valid appointment time.');
    }

    $staffStatement = $pdo->prepare(
        "SELECT
            st.id,
            COUNT(b.id) AS daily_booking_count,
            SUM(CASE WHEN b.booking_time = ? THEN 1 ELSE 0 END) AS same_time_booking_count
         FROM staff st
         LEFT JOIN bookings b
            ON b.staff_id = st.id
           AND b.booking_date = ?
           AND b.status IN ('confirmed', 'rescheduled', 'completed')
         WHERE st.category = ?
           AND st.is_active = 1
           AND st.is_available = 1
         GROUP BY st.id
         ORDER BY st.full_name"
    );
    $staffStatement->execute([(string) $slot['slot_time'], $bookingDate, $category]);

    $availability = [];
    foreach ($staffStatement->fetchAll(PDO::FETCH_ASSOC) as $staff) {
        $dailyBookingCount = (int) $staff['daily_booking_count'];
        $sameTimeBookingCount = (int) $staff['same_time_booking_count'];
        $unavailableReason = $dailyBookingCount >= 3
            ? 'daily_limit'
            : ($sameTimeBookingCount > 0 ? 'time_conflict' : null);
        $availability[] = [
            'id' => (int) $staff['id'],
            'available' => $unavailableReason === null,
            'daily_booking_count' => $dailyBookingCount,
            'same_time_booking_count' => $sameTimeBookingCount,
            'unavailable_reason' => $unavailableReason,
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $availability,
        'date' => $bookingDate,
        'time' => (string) $slot['slot_time'],
    ]);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
} catch (Throwable $exception) {
    error_log('Staff availability lookup failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to check therapist availability. Please try again.']);
}
