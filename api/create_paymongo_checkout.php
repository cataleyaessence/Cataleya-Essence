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
    echo json_encode(['success' => false, 'error' => 'Please sign in before making a payment.']);
    exit;
}

$csrfToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (empty($_SESSION['payment_csrf']) || !hash_equals($_SESSION['payment_csrf'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Your payment session has expired. Please refresh the page and try again.']);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid checkout details.']);
    exit;
}

if (!paymongoIsConfigured()) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Online payments are not configured yet. Please contact the spa.']);
    exit;
}

try {
    $draft = paymongoBookingDraft($pdo, (int) $_SESSION['user_id'], $input);
    $payment = paymongoCreateQrPaymentIntent($pdo, $draft);

    echo json_encode([
        'success' => true,
        'payment_intent_id' => $payment['payment_intent_id'],
        'client_key' => $payment['client_key'],
        // A PayMongo public key is intentionally frontend-safe and is needed
        // only to create and attach the QR Ph payment method in the popup.
        'public_key' => paymongoConfig()['public_key'],
        'token' => $payment['return_token'],
        'reference_number' => $payment['reference_number'],
        'downpayment_amount' => $payment['downpayment_amount'],
    ]);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
} catch (DomainException $exception) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
} catch (Throwable $exception) {
    error_log('Unable to create PayMongo QR payment: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to prepare the secure QR payment. Please try again.']);
}
