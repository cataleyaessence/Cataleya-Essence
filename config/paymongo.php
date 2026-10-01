<?php
/**
 * PayMongo server-side configuration and HTTP helpers.
 *
 * Secret keys are intentionally read from environment variables or the ignored
 * paymongo.local.php file. They must never be sent to the browser.
 */

function paymongoConfig(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $localConfig = [];
    $localConfigFile = __DIR__ . '/paymongo.local.php';
    if (is_file($localConfigFile)) {
        $loadedConfig = require $localConfigFile;
        if (is_array($loadedConfig)) {
            $localConfig = $loadedConfig;
        }
    }

    $readValue = static function (string $environmentName, string $configName) use ($localConfig): string {
        $environmentValue = getenv($environmentName);
        if (is_string($environmentValue) && trim($environmentValue) !== '') {
            return trim($environmentValue);
        }

        return isset($localConfig[$configName]) ? trim((string) $localConfig[$configName]) : '';
    };

    $config = [
        'secret_key' => $readValue('PAYMONGO_SECRET_KEY', 'secret_key'),
        'public_key' => $readValue('PAYMONGO_PUBLIC_KEY', 'public_key'),
        'webhook_secret' => $readValue('PAYMONGO_WEBHOOK_SECRET', 'webhook_secret'),
        'app_url' => rtrim($readValue('APP_URL', 'app_url'), '/'),
    ];

    return $config;
}

function paymongoIsConfigured(): bool
{
    $config = paymongoConfig();
    $secretKey = $config['secret_key'];
    $publicKey = $config['public_key'];

    if (preg_match('/^sk_(test|live)_[A-Za-z0-9]+$/', $secretKey) !== 1
        || preg_match('/^pk_(test|live)_[A-Za-z0-9]+$/', $publicKey) !== 1) {
        return false;
    }

    // Do not mix test and live credentials in one checkout flow.
    return str_starts_with($secretKey, 'sk_test_') === str_starts_with($publicKey, 'pk_test_');
}

function paymongoIsTestMode(): bool
{
    return str_starts_with(paymongoConfig()['secret_key'], 'sk_test_');
}

function paymongoAppUrl(): string
{
    $configuredUrl = paymongoConfig()['app_url'];
    if ($configuredUrl !== '') {
        return $configuredUrl;
    }

    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[A-Za-z0-9.-]+(?::\d{1,5})?$/', $host)) {
        $host = 'localhost';
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = '';

    foreach (['/api/', '/Php/'] as $marker) {
        $position = strpos($scriptName, $marker);
        if ($position !== false) {
            $basePath = substr($scriptName, 0, $position);
            break;
        }
    }

    return $scheme . '://' . $host . rtrim($basePath, '/');
}

/**
 * @return array{status:int,body:array<string,mixed>}
 */
function paymongoApiRequest(string $method, string $path, ?array $payload = null, ?string $idempotencyKey = null): array
{
    if (!paymongoIsConfigured()) {
        throw new RuntimeException('PayMongo is not configured. Add a valid server-side secret key first.');
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('The PHP cURL extension is required for PayMongo payments.');
    }

    $url = 'https://api.paymongo.com' . $path;
    $curl = curl_init($url);
    $headers = [
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode(paymongoConfig()['secret_key'] . ':'),
    ];

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    if ($idempotencyKey !== null && $idempotencyKey !== '') {
        $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
    }

    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => false,
    ]);

    if ($payload !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    $rawResponse = curl_exec($curl);
    $curlError = curl_error($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if ($rawResponse === false) {
        throw new RuntimeException('Could not connect to PayMongo: ' . ($curlError ?: 'unknown network error'));
    }

    $body = json_decode($rawResponse, true);
    if (!is_array($body)) {
        throw new RuntimeException('PayMongo returned an invalid response.');
    }

    if ($status < 200 || $status >= 300) {
        $errorCode = (string) ($body['errors'][0]['code'] ?? 'request_failed');
        error_log('PayMongo API request failed: HTTP ' . $status . ' (' . $errorCode . ')');
        throw new RuntimeException('PayMongo could not start the payment. Please try again.');
    }

    return ['status' => $status, 'body' => $body];
}

/**
 * Returns the completed PayMongo payment embedded in a Checkout Session, or
 * null while the hosted payment remains unpaid.
 *
 * @return array<string,mixed>|null
 */
function paymongoPaidPayment(array $checkoutSession): ?array
{
    $attributes = $checkoutSession['data']['attributes'] ?? [];
    $payments = is_array($attributes['payments'] ?? null) ? $attributes['payments'] : [];

    foreach ($payments as $payment) {
        $paymentAttributes = is_array($payment['attributes'] ?? null) ? $payment['attributes'] : [];
        if (strtolower((string) ($paymentAttributes['status'] ?? '')) === 'paid') {
            return $payment;
        }
    }

    return null;
}

/**
 * Payment Intents are the authoritative resource for the in-page QR Ph flow.
 * PayMongo returns the QR after the client attaches a QR Ph payment method;
 * this helper only treats the Intent as paid once PayMongo marks it succeeded.
 *
 * @return array<string,mixed>|null
 */
function paymongoPaidPaymentIntent(array $paymentIntent): ?array
{
    $intent = is_array($paymentIntent['data'] ?? null) ? $paymentIntent['data'] : [];
    $attributes = is_array($intent['attributes'] ?? null) ? $intent['attributes'] : [];
    if (strtolower((string) ($attributes['status'] ?? '')) !== 'succeeded') {
        return null;
    }

    $payments = is_array($attributes['payments'] ?? null) ? $attributes['payments'] : [];
    foreach ($payments as $payment) {
        $paymentAttributes = is_array($payment['attributes'] ?? null) ? $payment['attributes'] : [];
        if (strtolower((string) ($paymentAttributes['status'] ?? '')) === 'paid') {
            return $payment;
        }
    }

    // A succeeded Intent is still proof of payment even if a retrieve response
    // omits the expanded payments array. The amount remains server-verified.
    return [
        'id' => (string) ($intent['id'] ?? ''),
        'attributes' => [
            'amount' => (int) ($attributes['amount'] ?? 0),
            'status' => 'paid',
        ],
    ];
}

function paymongoWebhookSignatureIsValid(string $rawPayload, string $signatureHeader): bool
{
    $webhookSecret = paymongoConfig()['webhook_secret'];
    if ($webhookSecret === '' || $signatureHeader === '') {
        return false;
    }

    $parts = [];
    foreach (explode(',', $signatureHeader) as $part) {
        [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
        $parts[$key] = $value;
    }

    $timestamp = (string) ($parts['t'] ?? '');
    $signature = paymongoIsTestMode() ? (string) ($parts['te'] ?? '') : (string) ($parts['li'] ?? '');
    if ($timestamp === '' || $signature === '' || !ctype_digit($timestamp)) {
        return false;
    }

    if (abs(time() - (int) $timestamp) > 300) {
        return false;
    }

    $expected = hash_hmac('sha256', $timestamp . '.' . $rawPayload, $webhookSecret);
    return hash_equals($expected, $signature);
}
