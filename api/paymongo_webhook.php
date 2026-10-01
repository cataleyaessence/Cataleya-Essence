<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paymongo_fulfillment.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$rawPayload = (string) file_get_contents('php://input');
$signatureHeader = (string) ($_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? $_SERVER['HTTP_X_PAYMONGO_SIGNATURE'] ?? '');
if (!paymongoWebhookSignatureIsValid($rawPayload, $signatureHeader)) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

$payload = json_decode($rawPayload, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false]);
    exit;
}

// Supports both PayMongo's current event envelope and its older v1 envelope.
$eventData = is_array($payload['data'] ?? null) ? $payload['data'] : [];
$eventType = (string) ($eventData['type'] ?? $eventData['attributes']['type'] ?? '');
$resource = $eventData['data'] ?? $eventData['attributes']['data'] ?? [];
if (!is_array($resource)) {
    echo json_encode(['success' => true, 'ignored' => true]);
    exit;
}

$resourceAttributes = is_array($resource['attributes'] ?? null) ? $resource['attributes'] : [];
$paymentIntentId = '';
if ($eventType === 'payment_intent.succeeded') {
    $paymentIntentId = (string) ($resource['id'] ?? '');
} elseif ($eventType === 'payment.paid') {
    $paymentIntentId = (string) ($resourceAttributes['payment_intent_id'] ?? '');
}

if ($paymentIntentId === '') {
    echo json_encode(['success' => true, 'ignored' => true]);
    exit;
}

try {
    // Retrieve the Intent before fulfillment. A webhook event is useful for
    // reliability, but the amount and paid status still come from PayMongo's
    // authoritative Payment Intent record.
    $intentResponse = paymongoApiRequest('GET', '/v1/payment_intents/' . rawurlencode($paymentIntentId));
    if (paymongoPaidPaymentIntent($intentResponse['body']) === null) {
        throw new RuntimeException('The payment intent is not yet succeeded.');
    }
    paymongoFulfillPaymentIntent($pdo, $paymentIntentId, $intentResponse['body']);
    echo json_encode(['success' => true]);
} catch (Throwable $exception) {
    // A non-2xx response asks PayMongo to retry a genuine event if the
    // database was temporarily unavailable. No payment details are logged.
    error_log('PayMongo webhook fulfillment failed for payment intent ' . $paymentIntentId . ': ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false]);
}
