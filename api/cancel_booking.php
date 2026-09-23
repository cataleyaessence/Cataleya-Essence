<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/rewards.php';

header('Content-Type: application/json; charset=utf-8');

function cancelBookingResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cancelBookingResponse(['success' => false, 'error' => 'Invalid request method.'], 405);
}

if (empty($_SESSION['user_id'])) {
    cancelBookingResponse(['success' => false, 'error' => 'Please sign in to cancel a booking.'], 401);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['booking_csrf_token']) || !hash_equals($_SESSION['booking_csrf_token'], $csrfToken)) {
    cancelBookingResponse(['success' => false, 'error' => 'Your session has expired. Please refresh the page and try again.'], 403);
}

$input = json_decode((string) file_get_contents('php://input'), true);
$bookingId = is_array($input)
    ? filter_var($input['booking_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;

if (!$bookingId) {
    cancelBookingResponse(['success' => false, 'error' => 'Invalid booking.'], 422);
}

$userId = (int) $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    $bookingStmt = $pdo->prepare(
        "SELECT b.id, b.booking_date, b.booking_time, b.status
         FROM bookings b
         LEFT JOIN services s ON s.id = b.service_id
         WHERE b.id = ? AND b.user_id = ? AND b.status IN ('confirmed', 'rescheduled')
         FOR UPDATE"
    );
    $bookingStmt->execute([$bookingId, $userId]);
    $booking = $bookingStmt->fetch();
    if (!$booking) {
        $pdo->rollBack();
        cancelBookingResponse(['success' => false, 'error' => 'Only active bookings can be cancelled.'], 409);
    }

    $slotStmt = $pdo->prepare('SELECT id FROM time_slots WHERE slot_time = ? LIMIT 1');
    $slotStmt->execute([substr((string) $booking['booking_time'], 0, 8)]);
    $slot = $slotStmt->fetch();
    if ($slot) {
        $availabilityStmt = $pdo->prepare(
            'SELECT id, max_bookings, current_bookings
             FROM daily_slot_availability
             WHERE slot_date = ? AND slot_id = ?
             FOR UPDATE'
        );
        $availabilityStmt->execute([$booking['booking_date'], (int) $slot['id']]);
        $availability = $availabilityStmt->fetch();
        if ($availability) {
            $newCurrent = max(0, (int) $availability['current_bookings'] - 1);
            $maximum = max(1, (int) $availability['max_bookings']);
            $newStatus = $newCurrent === 0 ? 'available' : ($newCurrent >= $maximum ? 'booked' : 'filling');
            $updateAvailability = $pdo->prepare(
                'UPDATE daily_slot_availability SET current_bookings = ?, status = ? WHERE id = ?'
            );
            $updateAvailability->execute([$newCurrent, $newStatus, (int) $availability['id']]);
        }
    }

    // Confirmed and rescheduled bookings do not earn rewards yet. This only
    // cleans up any points that may have been saved under the old policy.
    removeBookingRewardPoints($pdo, $bookingId, $userId);

    // The completed downpayment is intentionally preserved. This cancellation
    // never creates a refund or changes the payment's completed status.
    $cancelStmt = $pdo->prepare(
        "UPDATE bookings
         SET status = 'cancelled', updated_at = NOW()
         WHERE id = ? AND user_id = ?"
    );
    $cancelStmt->execute([$bookingId, $userId]);

    $pdo->commit();
    cancelBookingResponse([
        'success' => true,
        'message' => 'Booking cancelled. The downpayment is non-refundable and this booking earns no rewards points.',
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Booking cancellation error: ' . $exception->getMessage());
    cancelBookingResponse(['success' => false, 'error' => 'Unable to cancel this booking. Please try again.'], 500);
}
