<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

header('Content-Type: application/json; charset=utf-8');

function rescheduleResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rescheduleResponse(['success' => false, 'error' => 'Invalid request method.'], 405);
}

if (empty($_SESSION['user_id'])) {
    rescheduleResponse(['success' => false, 'error' => 'Please sign in to reschedule a booking.'], 401);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['booking_csrf_token']) || !hash_equals($_SESSION['booking_csrf_token'], $csrfToken)) {
    rescheduleResponse(['success' => false, 'error' => 'Your session has expired. Please refresh the page and try again.'], 403);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    rescheduleResponse(['success' => false, 'error' => 'Invalid request data.'], 400);
}

$bookingId = filter_var($input['booking_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$serviceId = filter_var($input['service_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$bookingDate = trim((string) ($input['booking_date'] ?? ''));
$bookingTimeInput = trim((string) ($input['booking_time'] ?? ''));

if (!$bookingId || !$serviceId || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $bookingDate)) {
    rescheduleResponse(['success' => false, 'error' => 'Choose a valid service and booking date.'], 422);
}

$dateObject = DateTime::createFromFormat('!Y-m-d', $bookingDate);
if (!$dateObject || $dateObject->format('Y-m-d') !== $bookingDate) {
    rescheduleResponse(['success' => false, 'error' => 'Choose a valid booking date.'], 422);
}

if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $bookingTimeInput)) {
    rescheduleResponse(['success' => false, 'error' => 'Choose an available time slot.'], 422);
}

$bookingTime = substr($bookingTimeInput, 0, 5) . ':00';
$scheduledDateTime = DateTime::createFromFormat('!Y-m-d H:i:s', $bookingDate . ' ' . $bookingTime);
if (!$scheduledDateTime || $scheduledDateTime <= new DateTime()) {
    rescheduleResponse(['success' => false, 'error' => 'Choose a future date and time.'], 422);
}

$userId = (int) $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    $bookingStmt = $pdo->prepare(
        "SELECT b.id, b.service_id, b.booking_date, b.booking_time, b.total_amount,
                s.price AS original_service_price, u.full_name AS customer_name, u.email AS customer_email
         FROM bookings b
         INNER JOIN services s ON s.id = b.service_id
         INNER JOIN users u ON u.id = b.user_id
         WHERE b.id = ? AND b.user_id = ? AND b.status = 'confirmed'
         FOR UPDATE"
    );
    $bookingStmt->execute([$bookingId, $userId]);
    $booking = $bookingStmt->fetch();

    if (!$booking) {
        $pdo->rollBack();
        rescheduleResponse(['success' => false, 'error' => 'Only confirmed bookings can be rescheduled.'], 409);
    }

    $serviceStmt = $pdo->prepare('SELECT id, name, price FROM services WHERE id = ? AND is_active = 1 FOR UPDATE');
    $serviceStmt->execute([$serviceId]);
    $selectedService = $serviceStmt->fetch();
    if (!$selectedService) {
        $pdo->rollBack();
        rescheduleResponse(['success' => false, 'error' => 'The selected service is no longer available.'], 422);
    }

    $originalServicePrice = (float) $booking['original_service_price'];
    $selectedServicePrice = (float) $selectedService['price'];
    if (abs($selectedServicePrice - $originalServicePrice) >= 0.005) {
        $pdo->rollBack();
        rescheduleResponse([
            'success' => false,
            'error' => 'Choose a service priced at ₱' . number_format($originalServicePrice, 2) . ', the same as your original booking.',
        ], 422);
    }

    // Same-price service changes retain the original discounted total and
    // completed downpayment rather than recalculating the booking amount.
    $updatedTotalAmount = (float) $booking['total_amount'];

    if ($booking['booking_date'] === $bookingDate && substr((string) $booking['booking_time'], 0, 8) === $bookingTime) {
        $pdo->rollBack();
        rescheduleResponse(['success' => false, 'error' => 'Choose a different date or time to reschedule.'], 422);
    }

    $slotStmt = $pdo->prepare('SELECT id FROM time_slots WHERE slot_time = ? AND is_active = 1 FOR UPDATE');
    $slotStmt->execute([$bookingTime]);
    $targetSlot = $slotStmt->fetch();
    if (!$targetSlot) {
        $pdo->rollBack();
        rescheduleResponse(['success' => false, 'error' => 'The selected time slot is no longer available.'], 422);
    }
    $targetSlotId = (int) $targetSlot['id'];

    $availabilityStmt = $pdo->prepare(
        'SELECT id, max_bookings, current_bookings
         FROM daily_slot_availability
         WHERE slot_date = ? AND slot_id = ?
         FOR UPDATE'
    );
    $availabilityStmt->execute([$bookingDate, $targetSlotId]);
    $targetAvailability = $availabilityStmt->fetch();
    if ($targetAvailability && (int) $targetAvailability['current_bookings'] >= (int) $targetAvailability['max_bookings']) {
        $pdo->rollBack();
        rescheduleResponse(['success' => false, 'error' => 'That time slot has already been booked. Please choose another time.'], 409);
    }

    $conflictStmt = $pdo->prepare(
        "SELECT id
         FROM bookings
         WHERE booking_date = ? AND booking_time = ? AND id <> ?
           AND status IN ('confirmed', 'rescheduled')
         LIMIT 1
         FOR UPDATE"
    );
    $conflictStmt->execute([$bookingDate, $bookingTime, $bookingId]);
    if ($conflictStmt->fetch()) {
        $pdo->rollBack();
        rescheduleResponse(['success' => false, 'error' => 'That time slot has already been booked. Please choose another time.'], 409);
    }

    $oldSlotStmt = $pdo->prepare('SELECT id FROM time_slots WHERE slot_time = ? LIMIT 1');
    $oldSlotStmt->execute([substr((string) $booking['booking_time'], 0, 8)]);
    $oldSlot = $oldSlotStmt->fetch();

    if ($oldSlot) {
        $availabilityStmt->execute([$booking['booking_date'], (int) $oldSlot['id']]);
        $oldAvailability = $availabilityStmt->fetch();
        if ($oldAvailability) {
            $oldCurrent = max(0, (int) $oldAvailability['current_bookings'] - 1);
            $oldMaximum = max(1, (int) $oldAvailability['max_bookings']);
            $oldStatus = $oldCurrent === 0 ? 'available' : ($oldCurrent >= $oldMaximum ? 'booked' : 'filling');
            $updateAvailability = $pdo->prepare('UPDATE daily_slot_availability SET current_bookings = ?, status = ? WHERE id = ?');
            $updateAvailability->execute([$oldCurrent, $oldStatus, (int) $oldAvailability['id']]);
        }
    }

    if ($targetAvailability) {
        $targetCurrent = (int) $targetAvailability['current_bookings'] + 1;
        $targetMaximum = max(1, (int) $targetAvailability['max_bookings']);
        $targetStatus = $targetCurrent >= $targetMaximum ? 'booked' : 'filling';
        $updateAvailability = $pdo->prepare('UPDATE daily_slot_availability SET current_bookings = ?, status = ? WHERE id = ?');
        $updateAvailability->execute([$targetCurrent, $targetStatus, (int) $targetAvailability['id']]);
    } else {
        $insertAvailability = $pdo->prepare(
            "INSERT INTO daily_slot_availability (slot_date, slot_id, status, max_bookings, current_bookings)
             VALUES (?, ?, 'booked', 1, 1)"
        );
        $insertAvailability->execute([$bookingDate, $targetSlotId]);
    }

    $updateBooking = $pdo->prepare(
        "UPDATE bookings
         SET service_id = ?, booking_date = ?, booking_time = ?, total_amount = ?, status = 'rescheduled', auto_cancelled = 0, updated_at = NOW()
         WHERE id = ? AND user_id = ?"
    );
    $updateBooking->execute([$serviceId, $bookingDate, $bookingTime, $updatedTotalAmount, $bookingId, $userId]);

    $pdo->commit();

    // Send the updated appointment details only after the reschedule has been
    // committed. A mail delivery issue must not undo a valid reschedule.
    $emailSent = false;
    $customerEmail = trim((string) ($booking['customer_email'] ?? ''));
    if (filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        try {
            $emailSent = sendBookingRescheduledEmail($customerEmail, [
                'booking_id' => $bookingId,
                'customer_name' => $booking['customer_name'] ?? '',
                'service_name' => $selectedService['name'] ?? 'Service',
                'booking_date' => $bookingDate,
                'booking_time' => $bookingTime,
                'total_amount' => $updatedTotalAmount,
            ]);
        } catch (Throwable $mailException) {
            error_log('Booking reschedule email error: ' . $mailException->getMessage());
        }

        if (!$emailSent) {
            error_log('Failed to send booking reschedule email to: ' . $customerEmail);
        }
    }

    rescheduleResponse([
        'success' => true,
        'message' => $emailSent
            ? 'Your booking has been rescheduled. A confirmation email has been sent.'
            : 'Your booking has been rescheduled. Your updated details are shown in My Bookings.',
        'email_sent' => $emailSent,
        'booking' => [
            'id' => $bookingId,
            'service_id' => $serviceId,
            'booking_date' => $bookingDate,
            'booking_time' => $bookingTime,
            'total_amount' => $updatedTotalAmount,
            'status' => 'rescheduled',
        ],
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Booking reschedule error: ' . $exception->getMessage());
    rescheduleResponse(['success' => false, 'error' => 'Unable to reschedule this booking. Please try again.'], 500);
}
