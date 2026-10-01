<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paymongo_fulfillment.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please sign in to confirm your payment.']);
    exit;
}

$csrfToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (empty($_SESSION['payment_csrf']) || !hash_equals($_SESSION['payment_csrf'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Your payment session has expired. Please refresh the page and try again.']);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
$returnToken = is_array($input) ? (string) ($input['token'] ?? '') : '';
if (!preg_match('/^[a-f0-9]{64}$/', $returnToken)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid payment return link.']);
    exit;
}

try {
    $checkoutStatement = $pdo->prepare(
        'SELECT * FROM payment_checkouts WHERE return_token = ? AND user_id = ? LIMIT 1'
    );
    $checkoutStatement->execute([$returnToken, (int) $_SESSION['user_id']]);
    $checkout = $checkoutStatement->fetch();
    if (!$checkout) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Payment session not found.']);
        exit;
    }

    if (!empty($checkout['booking_id'])) {
        $booking = paymongoBookingById($pdo, (int) $checkout['booking_id']);
        if ($booking) {
            echo json_encode(['success' => true, 'booking' => $booking, 'already_confirmed' => true]);
            exit;
        }
    }

    if (!paymongoIsConfigured()) {
        throw new RuntimeException('PayMongo is not configured.');
    }

    // The browser never decides that a QR was paid. Retrieve the Payment
    // Intent with the server key and fulfill only after PayMongo says it
    // succeeded.
    $paymentIntentResponse = paymongoApiRequest('GET', '/v1/payment_intents/' . rawurlencode($checkout['checkout_session_id']));
    if (paymongoPaidPaymentIntent($paymentIntentResponse['body']) === null) {
        echo json_encode([
            'success' => false,
            'state' => 'pending',
            'error' => 'Waiting for your QR payment confirmation from PayMongo.',
        ]);
        exit;
    }

    $result = paymongoFulfillPaymentIntent($pdo, (string) $checkout['checkout_session_id'], $paymentIntentResponse['body']);
    echo json_encode(['success' => true, 'booking' => $result['booking'], 'already_confirmed' => !$result['created']]);
} catch (DomainException $exception) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
} catch (Throwable $exception) {
    error_log('Unable to verify PayMongo QR payment: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to verify the payment right now. Please refresh shortly or contact the spa.']);
}
