<?php

/**
 * Booking creation now happens only after a PayMongo Checkout Session has
 * been verified as paid. This endpoint is retained so any stale browser tab
 * gets a clear response instead of creating an unpaid confirmed booking.
 */
header('Content-Type: application/json; charset=utf-8');
http_response_code(410);
echo json_encode([
    'success' => false,
    'error' => 'Please complete the downpayment through the secure PayMongo checkout before confirming a booking.',
]);
