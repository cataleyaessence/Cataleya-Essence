<?php

/**
 * Temporary booking endpoint for the legacy, static GCash QR screen.
 *
 * PayMongo uses its own create/verify endpoints and remains the source of
 * truth whenever the live QR payment UI is enabled again.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paymongo_fulfillment.php';
require_once __DIR__ . '/../config/mail.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please sign in before confirming a booking.']);
    exit;
}

$csrfToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (empty($_SESSION['payment_csrf']) || !hash_equals($_SESSION['payment_csrf'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Your booking session has expired. Please refresh the page and try again.']);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid booking details.']);
    exit;
}

try {
    // Reuse the same server-side validation and pricing used by PayMongo.
    // In particular, no service price or date is trusted from the browser.
    $draft = paymongoBookingDraft($pdo, (int) $_SESSION['user_id'], $input);

    $pdo->beginTransaction();

    $slotStatement = $pdo->prepare(
        'SELECT id, current_bookings, max_bookings
         FROM daily_slot_availability
         WHERE slot_date = ? AND slot_id = ?
         FOR UPDATE'
    );
    $slotStatement->execute([$draft['booking_date'], $draft['slot_id']]);
    $slot = $slotStatement->fetch();

    if ($slot && (int) $slot['current_bookings'] >= (int) $slot['max_bookings']) {
        throw new DomainException('This appointment time is no longer available. Please choose another time.');
    }

    $existingBookingStatement = $pdo->prepare(
        "SELECT id
         FROM bookings
         WHERE user_id = ? AND booking_date = ? AND booking_time = ?
           AND status IN ('confirmed', 'rescheduled')
         LIMIT 1
         FOR UPDATE"
    );
    $existingBookingStatement->execute([$draft['user_id'], $draft['booking_date'], $draft['booking_time']]);
    if ($existingBookingStatement->fetch()) {
        throw new DomainException('You already have a booking at this time. Please choose another available time.');
    }

    paymongoAssertStaffCanBeBooked(
        $pdo,
        isset($draft['staff_id']) ? (int) $draft['staff_id'] : null,
        (string) $draft['booking_date'],
        (string) $draft['booking_time']
    );

    $phoneStatement = $pdo->prepare('UPDATE users SET phone = ? WHERE id = ?');
    $phoneStatement->execute([$draft['phone'], $draft['user_id']]);

    $deadline = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $bookingStatement = $pdo->prepare(
        "INSERT INTO bookings (
            user_id, service_id, staff_id, booking_date, booking_time, deadline,
            total_amount, notes, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', NOW())"
    );
    $bookingStatement->execute([
        $draft['user_id'],
        $draft['service_id'],
        $draft['staff_id'],
        $draft['booking_date'],
        $draft['booking_time'],
        $deadline,
        $draft['total_amount'],
        $draft['special_requests'],
    ]);
    $bookingId = (int) $pdo->lastInsertId();

    $paymentStatement = $pdo->prepare(
        "INSERT INTO payments (booking_id, user_id, amount, payment_method, status, created_at, updated_at)
         VALUES (?, ?, ?, 'gcash', 'completed', NOW(), NOW())"
    );
    $paymentStatement->execute([
        $bookingId,
        $draft['user_id'],
        $draft['downpayment_amount'],
    ]);

    if ($slot) {
        $newCurrentBookings = (int) $slot['current_bookings'] + 1;
        $newStatus = $newCurrentBookings >= (int) $slot['max_bookings'] ? 'booked' : 'filling';
        $slotUpdate = $pdo->prepare(
            'UPDATE daily_slot_availability SET current_bookings = ?, status = ? WHERE id = ?'
        );
        $slotUpdate->execute([$newCurrentBookings, $newStatus, $slot['id']]);
    } else {
        $slotInsert = $pdo->prepare(
            "INSERT INTO daily_slot_availability (slot_date, slot_id, status, max_bookings, current_bookings)
             VALUES (?, ?, 'booked', 1, 1)"
        );
        $slotInsert->execute([$draft['booking_date'], $draft['slot_id']]);
    }

    $booking = paymongoBookingById($pdo, $bookingId);
    if (!$booking) {
        throw new RuntimeException('Unable to load the new booking.');
    }

    $pdo->commit();

    if (!empty($booking['customer_email']) && !sendBookingConfirmationEmail($booking['customer_email'], $booking)) {
        error_log('Placeholder-QR booking email could not be sent for booking ' . $bookingId);
    }

    echo json_encode(['success' => true, 'booking' => $booking]);
} catch (InvalidArgumentException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
} catch (DomainException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($exception instanceof PDOException && $exception->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'This appointment time was just reserved. Please choose another time.']);
        exit;
    }

    error_log('Unable to create placeholder-QR booking: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to save the booking. Please try again.']);
}
