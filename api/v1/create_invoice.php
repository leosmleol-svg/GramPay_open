<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../storage.php';
require_once __DIR__ . '/../../PlategaService.php';

function apiResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    apiResponse(['ok' => false, 'error' => 'Method Not Allowed'], 405);
}

// Read body once. Supports JSON, form-data and обычный POST.
// Важно: php://input читаем до разбора API-ключа, чтобы поток не был потерян.
$rawInput = file_get_contents('php://input') ?: '';
$input = [];
if ($rawInput !== '') {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}
if (!$input && !empty($_POST)) {
    $input = $_POST;
}

// Common API-key formats.
$apiKey = getApiKeyFromRequest();
if ($apiKey === null || trim($apiKey) === '') {
    $apiKey = trim((string)($input['api_key'] ?? ''));
}
if ($apiKey === '') {
    apiResponse(['ok' => false, 'error' => 'API key is missing'], 401);
}

$merchant = findUserByApiKey($apiKey);
if (!$merchant) {
    apiResponse(['ok' => false, 'error' => 'Invalid API key'], 401);
}

// API НЕ зависит от проверки сайта и решения модерации.
// Idempotency: repeated requests with the same key return the existing invoice
// instead of creating a second payment.
$idempotencyKey = trim((string)($_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? ($input['idempotency_key'] ?? '')));
if ($idempotencyKey !== '') {
    if (strlen($idempotencyKey) > 128) {
        apiResponse(['ok' => false, 'error' => 'Idempotency-Key is too long'], 400);
    }
    $existing = findTransactionByIdempotencyKey((int)$merchant['id'], $idempotencyKey);
    if ($existing) {
        $paymentUrl = 'https://grampay.net/pay_wait.php?tx=' . rawurlencode((string)$existing['transaction_id']);
        $oldRedirect = (string)($existing['redirect_url'] ?? '');
        if ($oldRedirect !== '') {
            $oldParsed = parse_url($oldRedirect);
            if (is_array($oldParsed) && !empty($oldParsed['query'])) {
                parse_str((string)$oldParsed['query'], $oldQ);
                if (!empty($oldQ['mh'])) $paymentUrl .= '&mh=' . rawurlencode((string)$oldQ['mh']);
            }
        }
        apiResponse([
            'ok' => true, 'idempotent' => true,
            'invoice_id' => $existing['transaction_id'],
            'transaction_id' => $existing['transaction_id'],
            'amount' => (int)$existing['amount'],
            'fee' => (int)($existing['fee'] ?? 0),
            'total_amount' => (int)($existing['total_amount'] ?? $existing['amount']),
            'currency' => $existing['currency'] ?? 'RUB',
            'status' => $existing['status'] ?? 'PENDING',
            'payment_url' => $paymentUrl,
            'redirect_url' => $paymentUrl,
        ]);
    }
}

$amount = filter_var($input['amount'] ?? null, FILTER_VALIDATE_INT);
if ($amount === false || $amount < 1) {
    apiResponse(['ok' => false, 'error' => 'Invalid amount. Minimum is 1 RUB'], 400);
}

$config = require __DIR__ . '/../../config.php';
$paymentMethod = (int)($input['payment_method'] ?? 2);
if (!isset($config['payment_methods'][$paymentMethod])) {
    apiResponse(['ok' => false, 'error' => 'Invalid payment method'], 400);
}

$minAmount = (int)($config['method_min_amounts'][$paymentMethod] ?? 1);
if ($amount < $minAmount) {
    apiResponse([
        'ok' => false,
        'error' => 'Amount is below minimum',
        'minimum_amount' => $minAmount,
        'currency' => 'RUB'
    ], 400);
}

$orderId = trim((string)($input['order_id'] ?? $input['orderId'] ?? ''));
$payload = trim((string)($input['payload'] ?? ''));
$returnUrl = trim((string)($input['return_url'] ?? $input['return'] ?? ''));
$failedUrl = trim((string)($input['failed_url'] ?? $input['failedUrl'] ?? ''));

if ($returnUrl === '') {
    $returnUrl = trim((string)($merchant['default_return_url'] ?? ''));
}
if ($failedUrl === '') {
    $failedUrl = trim((string)($merchant['default_failed_url'] ?? ''));
}
if ($returnUrl === '') {
    $returnUrl = trim((string)($config['platega']['return_url'] ?? 'https://grampay.net/index.php?payment=success'));
}
if ($failedUrl === '') {
    $failedUrl = trim((string)($config['platega']['failed_url'] ?? 'https://grampay.net/index.php?payment=fail'));
}

// GRAMPAY принимает сумму от API в RUB. Комиссия включается отдельно.
$fee = ($amount > 5) ? 5 : 0;
$totalAmount = $amount + $fee;

$service = new PlategaService($config);
$methodName = (string)$config['payment_methods'][$paymentMethod];
$description = 'GRAMPAY #' . (int)$merchant['id'] . ' - ' . $methodName;
$metadata = [
    'service' => 'GRAMPAY',
    'merchantId' => (string)$merchant['id'],
    'merchantName' => (string)($merchant['username'] ?? ''),
    'orderId' => $orderId,
];
if ($payload !== '') {
    $metadata['payload'] = $payload;
}

try {
    $gateway = $service->createTransaction(
        paymentMethod: $paymentMethod,
        totalAmount: $totalAmount,
        currency: 'RUB',
        description: $description,
        returnUrl: $returnUrl,
        failedUrl: $failedUrl,
        payload: $payload !== '' ? $payload : ('GRAMPAY order ' . ($orderId !== '' ? $orderId : 'api')),
        metadata: $metadata
    );

    $transactionId = (string)($gateway['transactionId'] ?? $gateway['transaction_id'] ?? $gateway['id'] ?? '');
    $redirectUrl = (string)($gateway['redirect'] ?? $gateway['paymentUrl'] ?? $gateway['payment_url'] ?? $gateway['url'] ?? '');

    // API payment link must open the GRAMPAY payment page, not Platega directly.
    // Preserve the Platega merchant hash (mh) from its redirect URL.
    $plategaQuery = [];
    if ($redirectUrl !== '') {
        $parsed = parse_url($redirectUrl);
        if (is_array($parsed) && !empty($parsed['query'])) {
            parse_str((string)$parsed['query'], $plategaQuery);
        }
    }
    $merchantHash = trim((string)($plategaQuery['mh'] ?? ''));

    if ($transactionId === '') {
        throw new RuntimeException('Platega did not return transactionId');
    }
    if ($redirectUrl === '') {
        throw new RuntimeException('Platega did not return payment redirect URL');
    }

    $now = date('Y-m-d H:i:s');
    saveTransaction([
        'transaction_id' => $transactionId,
        'user_id' => (int)$merchant['id'],
        'payment_method' => $paymentMethod,
        'amount' => (int)$amount,
        'fee' => $fee,
        'total_amount' => $totalAmount,
        'currency' => 'RUB',
        'status' => (string)($gateway['status'] ?? 'PENDING'),
        'redirect_url' => $redirectUrl,
        'return_url' => $returnUrl,
        'failed_url' => $failedUrl,
        'order_id' => $orderId,
        'payload' => $payload,
        'idempotency_key' => $idempotencyKey,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $paymentUrl = 'https://grampay.net/pay_wait.php?tx=' . rawurlencode($transactionId);
    if ($merchantHash !== '') {
        $paymentUrl .= '&mh=' . rawurlencode($merchantHash);
    }

    apiResponse([
        'ok' => true,
        'invoice_id' => $transactionId,
        'transaction_id' => $transactionId,
        'amount' => (int)$amount,
        'fee' => $fee,
        'total_amount' => $totalAmount,
        'currency' => 'RUB',
        'status' => (string)($gateway['status'] ?? 'PENDING'),
        'payment_url' => $paymentUrl,
        'redirect_url' => $paymentUrl,
    ]);
} catch (Throwable $e) {
    apiResponse([
        'ok' => false,
        'error' => 'Failed to create invoice',
        'message' => $e->getMessage(),
    ], 502);
}
