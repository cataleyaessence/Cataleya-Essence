<?php

require_once __DIR__ . '/paymongo.php';

function paymongoNormalizeMobile(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    if (preg_match('/^09\d{9}$/', $digits)) {
        return '+63' . substr($digits, 1);
    }
    if (preg_match('/^639\d{9}$/', $digits)) {
        return '+' . $digits;
    }

    return '';
}

function paymongoDiscountRateForTier(string $tier): float
{
    return match (strtolower($tier)) {
        'silver' => 10.0,
        'gold' => 15.0,
        'platinum' => 20.0,
        default => 5.0,
    };
}

function paymongoBookingTimeZone(): DateTimeZone
{
    static $timeZone = null;
    if (!$timeZone instanceof DateTimeZone) {
        $timeZone = new DateTimeZone('Asia/Manila');
    }

    return $timeZone;
}

function paymongoAssertBookingTimeIsFuture(string $bookingDate, string $bookingTime): void
{
    $time = substr(trim($bookingTime), 0, 8);
    $appointmentStart = DateTimeImmutable::createFromFormat(
        '!Y-m-d H:i:s',
        $bookingDate . ' ' . $time,
        paymongoBookingTimeZone()
    );
    if (!$appointmentStart instanceof DateTimeImmutable) {
        throw new InvalidArgumentException('Please choose a valid appointment time.');
    }

    $now = new DateTimeImmutable('now', paymongoBookingTimeZone());
    if ($appointmentStart <= $now) {
        throw new DomainException('This appointment time has already passed. Please choose another available time.');
    }
}

/**
 * Confirms that a therapist is active, has fewer than three bookings for the
 * requested date, and is not already assigned at the requested time.
 */
function paymongoAssertStaffCanBeBooked(PDO $pdo, ?int $staffId, string $bookingDate, string $bookingTime): void
{
    if ($staffId === null) {
        return;
    }

    if ($staffId < 1) {
        throw new InvalidArgumentException('Please choose a valid staff member.');
    }

    $staffStatement = $pdo->prepare('SELECT id FROM staff WHERE id = ? AND is_available = 1 AND is_active = 1 LIMIT 1');
    $staffStatement->execute([$staffId]);
    if (!$staffStatement->fetch()) {
        throw new InvalidArgumentException('The selected staff member is no longer available.');
    }

    $bookingStatement = $pdo->prepare(
        "SELECT
            COUNT(id) AS daily_booking_count,
            SUM(CASE WHEN booking_time = ? THEN 1 ELSE 0 END) AS same_time_booking_count
         FROM bookings
         WHERE staff_id = ? AND booking_date = ?
           AND status IN ('confirmed', 'rescheduled', 'completed')"
    );
    $bookingStatement->execute([$bookingTime, $staffId, $bookingDate]);
    $bookingCounts = $bookingStatement->fetch() ?: [];
    if ((int) ($bookingCounts['daily_booking_count'] ?? 0) >= 3) {
        throw new DomainException('That therapist already has 3 bookings for the selected day. Please choose another therapist.');
    }
    if ((int) ($bookingCounts['same_time_booking_count'] ?? 0) > 0) {
        throw new DomainException('That therapist is already booked at the selected time. Please choose another therapist.');
    }
}

/**
 * Validates the customer selection against the database and returns the exact
 * values that will be paid. Client-provided prices are deliberately ignored.
 *
 * @return array<string,mixed>
 */
function paymongoBookingDraft(PDO $pdo, int $userId, array $input): array
{
    $fullName = trim((string) ($input['full_name'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $phone = paymongoNormalizeMobile((string) ($input['phone'] ?? ''));
    $notes = trim((string) ($input['special_requests'] ?? ''));
    $serviceId = (int) ($input['service_id'] ?? 0);
    $bookingDate = trim((string) ($input['booking_date'] ?? ''));
    $bookingTime = trim((string) ($input['booking_time'] ?? ''));
    $staffId = isset($input['staff_id']) && $input['staff_id'] !== '' ? (int) $input['staff_id'] : null;

    if ($fullName === '' || mb_strlen($fullName) > 255) {
        throw new InvalidArgumentException('Please enter your full name.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }
    if ($phone === '') {
        throw new InvalidArgumentException('Enter a valid Philippine mobile number.');
    }
    if ($serviceId < 1) {
        throw new InvalidArgumentException('Please choose a valid service.');
    }
    if (mb_strlen($notes) > 3000) {
        throw new InvalidArgumentException('Special requests must be 3,000 characters or fewer.');
    }

    $dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $bookingDate, paymongoBookingTimeZone());
    $today = (new DateTimeImmutable('now', paymongoBookingTimeZone()))->format('Y-m-d');
    if (!$dateObject || $dateObject->format('Y-m-d') !== $bookingDate || $bookingDate < $today) {
        throw new InvalidArgumentException('Please choose a valid future booking date.');
    }

    $serviceStatement = $pdo->prepare('SELECT id, name, price FROM services WHERE id = ? AND is_active = 1 LIMIT 1');
    $serviceStatement->execute([$serviceId]);
    $service = $serviceStatement->fetch();
    if (!$service) {
        throw new InvalidArgumentException('The selected service is no longer available.');
    }

    $slotStatement = $pdo->prepare(
        'SELECT id, slot_time, display_time
         FROM time_slots
         WHERE is_active = 1 AND (slot_time = ? OR display_time = ?)
         LIMIT 1'
    );
    $slotStatement->execute([$bookingTime, $bookingTime]);
    $timeSlot = $slotStatement->fetch();
    if (!$timeSlot) {
        throw new InvalidArgumentException('Please choose an available appointment time.');
    }

    $bookingTime = (string) $timeSlot['slot_time'];
    paymongoAssertBookingTimeIsFuture($bookingDate, $bookingTime);
    paymongoAssertStaffCanBeBooked($pdo, $staffId, $bookingDate, $bookingTime);
    $activeBookingStatement = $pdo->prepare(
        "SELECT id FROM bookings
         WHERE user_id = ? AND booking_date = ? AND booking_time = ?
           AND status IN ('confirmed', 'rescheduled')
         LIMIT 1"
    );
    $activeBookingStatement->execute([$userId, $bookingDate, $bookingTime]);
    if ($activeBookingStatement->fetch()) {
        throw new DomainException('You already have a booking at this time. Please choose another available time.');
    }

    $availabilityStatement = $pdo->prepare(
        'SELECT current_bookings, max_bookings
         FROM daily_slot_availability
         WHERE slot_date = ? AND slot_id = ?
         LIMIT 1'
    );
    $availabilityStatement->execute([$bookingDate, $timeSlot['id']]);
    $availability = $availabilityStatement->fetch();
    if ($availability && (int) $availability['current_bookings'] >= (int) $availability['max_bookings']) {
        throw new DomainException('This appointment time is no longer available. Please choose another time.');
    }

    $tierStatement = $pdo->prepare('SELECT tier FROM rewards WHERE user_id = ? LIMIT 1');
    $tierStatement->execute([$userId]);
    $tierRow = $tierStatement->fetch();
    $discountRate = paymongoDiscountRateForTier((string) ($tierRow['tier'] ?? 'bronze'));
    $totalAmount = round((float) $service['price'] * (100 - $discountRate) / 100, 2);
    $downpaymentAmount = round($totalAmount * 0.5, 2);

    return [
        'user_id' => $userId,
        'service_id' => (int) $service['id'],
        'service_name' => (string) $service['name'],
        'staff_id' => $staffId,
        'booking_date' => $bookingDate,
        'booking_time' => $bookingTime,
        'slot_id' => (int) $timeSlot['id'],
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'special_requests' => $notes,
        'total_amount' => $totalAmount,
        'downpayment_amount' => $downpaymentAmount,
    ];
}

/**
 * @return array<string,mixed>
 */
function paymongoCreateQrPaymentIntent(PDO $pdo, array $draft): array
{
    $returnToken = bin2hex(random_bytes(32));
    $referenceNumber = 'CAT-' . strtoupper(bin2hex(random_bytes(8)));
    $idempotencyKey = 'cataleya-' . strtolower($referenceNumber) . '-' . bin2hex(random_bytes(8));
    $amountCentavos = (int) round((float) $draft['downpayment_amount'] * 100);

    if ($amountCentavos < 100) {
        throw new InvalidArgumentException('The QR Ph downpayment must be at least ₱1.00.');
    }

    /* Retired hosted-checkout payload. QR Ph uses the Payment Intent payload
       immediately following this block and never returns a checkout URL.
    $intentPayload = [
        'data' => [
            'attributes' => [
                'line_items' => [[
                    'name' => $draft['service_name'] . ' — 50% Downpayment',
                    'amount' => $amountCentavos,
                    'currency' => 'PHP',
                    'quantity' => 1,
                ]],
                // QR Ph only: the PayMongo-hosted screen displays the live,
                // one-time QR code. No card or manual payment path is offered.
                'payment_method_types' => ['qrph'],
                'billing' => [
                    'name' => $draft['full_name'],
                    'email' => $draft['email'],
                    'phone' => $draft['phone'],
                ],
                'description' => '50% downpayment for ' . $draft['service_name'],
                'reference_number' => $referenceNumber,
                // No completion or cancel redirect is used by the QR popup.
                'send_email_receipt' => true,
                'show_description' => true,
                'show_line_items' => true,
                'metadata' => [
                    'return_token' => $returnToken,
                    'booking_date' => $draft['booking_date'],
                ],
            ],
        ],
    ];
    */

    $intentPayload = [
        'data' => [
            'attributes' => [
                'amount' => $amountCentavos,
                'currency' => 'PHP',
                'payment_method_allowed' => ['qrph'],
                'description' => '50% downpayment for ' . $draft['service_name'],
                'metadata' => [
                    'reference_number' => $referenceNumber,
                    'booking_date' => $draft['booking_date'],
                ],
            ],
        ],
    ];
    $response = paymongoApiRequest('POST', '/v1/payment_intents', $intentPayload, $idempotencyKey);
    $paymentIntent = $response['body']['data'] ?? [];
    $paymentIntentId = (string) ($paymentIntent['id'] ?? '');
    $clientKey = (string) ($paymentIntent['attributes']['client_key'] ?? '');
    if ($paymentIntentId === '' || $clientKey === '') {
        error_log('PayMongo returned a Payment Intent without a client key.');
        throw new RuntimeException('PayMongo could not prepare the QR payment. Please try again.');
    }

    $insertStatement = $pdo->prepare(
        'INSERT INTO payment_checkouts (
            checkout_session_id, return_token, reference_number, idempotency_key,
            user_id, service_id, staff_id, slot_id, booking_date, booking_time,
            full_name, email, phone, special_requests, total_amount, downpayment_amount, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'created\')'
    );
    $insertStatement->execute([
        $paymentIntentId,
        $returnToken,
        $referenceNumber,
        $idempotencyKey,
        $draft['user_id'],
        $draft['service_id'],
        $draft['staff_id'],
        $draft['slot_id'],
        $draft['booking_date'],
        $draft['booking_time'],
        $draft['full_name'],
        $draft['email'],
        $draft['phone'],
        $draft['special_requests'],
        $draft['total_amount'],
        $draft['downpayment_amount'],
    ]);

    return [
        'payment_intent_id' => $paymentIntentId,
        'client_key' => $clientKey,
        'return_token' => $returnToken,
        'reference_number' => $referenceNumber,
        'downpayment_amount' => (float) $draft['downpayment_amount'],
    ];
}

/**
 * @return array<string,mixed>|null
 */
function paymongoBookingById(PDO $pdo, int $bookingId): ?array
{
    $statement = $pdo->prepare(
        'SELECT b.id AS booking_id, b.booking_date, b.booking_time, b.total_amount, b.notes, b.status,
                s.name AS service_name, st.full_name AS therapist_name,
                u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
         FROM bookings b
         INNER JOIN services s ON s.id = b.service_id
         LEFT JOIN staff st ON st.id = b.staff_id
         INNER JOIN users u ON u.id = b.user_id
         WHERE b.id = ?
         LIMIT 1'
    );
    $statement->execute([$bookingId]);
    $booking = $statement->fetch();
    return $booking ?: null;
}

/**
 * Creates a confirmed booking only after PayMongo has reported a succeeded
 * Payment Intent. The payment row lock makes polling and webhook fulfillment
 * safe when they arrive at almost the same time.
 *
 * @return array{booking:array<string,mixed>,created:bool}
 */
function paymongoFulfillPaymentIntent(PDO $pdo, string $paymentIntentId, array $paymentIntent): array
{
    $paidPayment = paymongoPaidPaymentIntent($paymentIntent);
    if ($paidPayment === null) {
        throw new DomainException('Payment is still being confirmed by PayMongo.');
    }

    $paymentAttributes = is_array($paidPayment['attributes'] ?? null) ? $paidPayment['attributes'] : [];
    $paymentId = (string) ($paidPayment['id'] ?? '');
    $paidAmount = round(((int) ($paymentAttributes['amount'] ?? 0)) / 100, 2);

    $pdo->beginTransaction();
    try {
        $checkoutStatement = $pdo->prepare('SELECT * FROM payment_checkouts WHERE checkout_session_id = ? FOR UPDATE');
        $checkoutStatement->execute([$paymentIntentId]);
        $checkout = $checkoutStatement->fetch();
        if (!$checkout) {
            throw new RuntimeException('This payment session is not recognized by Cataleya Essence.');
        }

        if (!empty($checkout['booking_id'])) {
            $existingBooking = paymongoBookingById($pdo, (int) $checkout['booking_id']);
            $pdo->commit();
            if (!$existingBooking) {
                throw new RuntimeException('The completed booking could not be found.');
            }
            return ['booking' => $existingBooking, 'created' => false];
        }

        if (abs($paidAmount - (float) $checkout['downpayment_amount']) > 0.009) {
            $updateConflict = $pdo->prepare("UPDATE payment_checkouts SET status = 'conflict', payment_id = ?, paid_at = NOW() WHERE id = ?");
            $updateConflict->execute([$paymentId ?: null, $checkout['id']]);
            $pdo->commit();
            throw new DomainException('The payment amount needs review. Please contact the spa.');
        }

        try {
            paymongoAssertBookingTimeIsFuture((string) $checkout['booking_date'], (string) $checkout['booking_time']);
        } catch (DomainException $exception) {
            $updateConflict = $pdo->prepare("UPDATE payment_checkouts SET status = 'conflict', payment_id = ?, paid_at = NOW() WHERE id = ?");
            $updateConflict->execute([$paymentId ?: null, $checkout['id']]);
            $pdo->commit();
            throw new DomainException('This appointment time passed before the payment was confirmed. Please contact the spa.');
        }

        $slotStatement = $pdo->prepare(
            'SELECT id, current_bookings, max_bookings
             FROM daily_slot_availability
             WHERE slot_date = ? AND slot_id = ?
             FOR UPDATE'
        );
        $slotStatement->execute([$checkout['booking_date'], $checkout['slot_id'] ?? 0]);
        $slot = $slotStatement->fetch();

        // Older databases do not store slot_id in payment_checkouts. Resolve it
        // by the saved time if a migration was run from an earlier schema.
        if (!$slot) {
            $timeSlotStatement = $pdo->prepare('SELECT id FROM time_slots WHERE slot_time = ? LIMIT 1');
            $timeSlotStatement->execute([$checkout['booking_time']]);
            $timeSlot = $timeSlotStatement->fetch();
            if (!$timeSlot) {
                throw new RuntimeException('The appointment time is no longer available.');
            }
            $slotId = (int) $timeSlot['id'];
            $slotStatement->execute([$checkout['booking_date'], $slotId]);
            $slot = $slotStatement->fetch();
        } else {
            $slotId = (int) ($checkout['slot_id'] ?? 0);
        }

        if ($slot && (int) $slot['current_bookings'] >= (int) $slot['max_bookings']) {
            $updateConflict = $pdo->prepare("UPDATE payment_checkouts SET status = 'conflict', payment_id = ?, paid_at = NOW() WHERE id = ?");
            $updateConflict->execute([$paymentId ?: null, $checkout['id']]);
            $pdo->commit();
            throw new DomainException('That appointment time was just reserved. Please contact the spa about your completed payment.');
        }

        $activeBookingStatement = $pdo->prepare(
            "SELECT id FROM bookings
             WHERE user_id = ? AND booking_date = ? AND booking_time = ?
               AND status IN ('confirmed', 'rescheduled')
             LIMIT 1
             FOR UPDATE"
        );
        $activeBookingStatement->execute([$checkout['user_id'], $checkout['booking_date'], $checkout['booking_time']]);
        if ($activeBookingStatement->fetch()) {
            $updateConflict = $pdo->prepare("UPDATE payment_checkouts SET status = 'conflict', payment_id = ?, paid_at = NOW() WHERE id = ?");
            $updateConflict->execute([$paymentId ?: null, $checkout['id']]);
            $pdo->commit();
            throw new DomainException('You already have a booking at this time. Please contact the spa about your completed payment.');
        }

        $checkoutStaffId = !empty($checkout['staff_id']) ? (int) $checkout['staff_id'] : null;
        paymongoAssertStaffCanBeBooked($pdo, $checkoutStaffId, (string) $checkout['booking_date'], (string) $checkout['booking_time']);

        $phoneStatement = $pdo->prepare('UPDATE users SET phone = ? WHERE id = ?');
        $phoneStatement->execute([$checkout['phone'], $checkout['user_id']]);

        $deadline = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $bookingStatement = $pdo->prepare(
            "INSERT INTO bookings (
                user_id, service_id, staff_id, booking_date, booking_time, deadline,
                total_amount, notes, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', NOW())"
        );
        $bookingStatement->execute([
            $checkout['user_id'],
            $checkout['service_id'],
            $checkout['staff_id'] ?: null,
            $checkout['booking_date'],
            $checkout['booking_time'],
            $deadline,
            $checkout['total_amount'],
            $checkout['special_requests'],
        ]);
        $bookingId = (int) $pdo->lastInsertId();

        $paymentStatement = $pdo->prepare(
            "INSERT INTO payments (booking_id, user_id, amount, payment_method, status, transaction_id, created_at, updated_at)
             VALUES (?, ?, ?, 'qrph', 'completed', ?, NOW(), NOW())"
        );
        $paymentStatement->execute([
            $bookingId,
            $checkout['user_id'],
            $checkout['downpayment_amount'],
            $paymentId !== '' ? $paymentId : $paymentIntentId,
        ]);

        if ($slot) {
            $newCurrentBookings = (int) $slot['current_bookings'] + 1;
            $newStatus = $newCurrentBookings >= (int) $slot['max_bookings'] ? 'booked' : 'filling';
            $slotUpdate = $pdo->prepare('UPDATE daily_slot_availability SET current_bookings = ?, status = ? WHERE id = ?');
            $slotUpdate->execute([$newCurrentBookings, $newStatus, $slot['id']]);
        } else {
            $slotInsert = $pdo->prepare(
                "INSERT INTO daily_slot_availability (slot_date, slot_id, status, max_bookings, current_bookings)
                 VALUES (?, ?, 'booked', 1, 1)"
            );
            $slotInsert->execute([$checkout['booking_date'], $slotId]);
        }

        $checkoutUpdate = $pdo->prepare(
            "UPDATE payment_checkouts
             SET status = 'fulfilled', payment_method = 'qrph', payment_id = ?, booking_id = ?, paid_at = NOW(), fulfilled_at = NOW()
             WHERE id = ?"
        );
        $checkoutUpdate->execute([$paymentId ?: null, $bookingId, $checkout['id']]);

        $booking = paymongoBookingById($pdo, $bookingId);
        if (!$booking) {
            throw new RuntimeException('Unable to load the completed booking.');
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    require_once __DIR__ . '/mail.php';
    if (!empty($booking['customer_email']) && !sendBookingConfirmationEmail($booking['customer_email'], $booking)) {
        error_log('PayMongo booking confirmation email could not be sent for booking ' . $booking['booking_id']);
    }

    return ['booking' => $booking, 'created' => true];
}
