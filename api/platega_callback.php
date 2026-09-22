<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../storage.php';
$config = require __DIR__ . '/../config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$merchantHeader = trim((string)($_SERVER['HTTP_X_MERCHANTID'] ?? ''));
$secretHeader = trim((string)($_SERVER['HTTP_X_SECRET'] ?? ''));
$expectedMerchant = trim((string)($config['platega']['merchant_id'] ?? ''));
$expectedSecret = trim((string)($config['platega']['secret'] ?? ''));

if ($merchantHeader === '' || $secretHeader === '' || $expectedMerchant === '' || $expectedSecret ===
    '' || !hash_equals($expectedMerchant, $merchantHeader) || !hash_equals($expectedSecret, $secretHeader)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
    exit;
}

$transactionId = trim((string)($data['id'] ?? $data['transactionId'] ?? ''));
$status = strtoupper(trim((string)($data['status'] ?? '')));

if ($transactionId === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Transaction id is missing'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tx = findTransaction($transactionId);
if (!$tx) {
    // Unknown callback: acknowledge nothing was changed, allowing the provider to stop retrying
    // only when desired. Here we return 404 to make a configuration issue visible.
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Transaction not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$confirmed = ['CONFIRMED', 'SUCCESS', 'PAID', 'COMPLETED'];
$failed = ['CANCELED', 'CANCELLED', 'FAILED', 'EXPIRED'];
$current = strtoupper((string)($tx['status'] ?? 'PENDING'));

if (in_array($status, $confirmed, true)) {
    if (!in_array($current, $confirmed, true)) {
        updateTransactionStatus($transactionId, 'CONFIRMED');
        updateUserBalance((int)$tx['user_id'], (int)$tx['amount']);
    }
} elseif (in_array($status, $failed, true)) {
    if (!in_array($current, $confirmed, true)) {
        updateTransactionStatus($transactionId, $status === 'CANCELLED' ? 'CANCELED' : $status);
    }
} else {
    updateTransactionStatus($transactionId, $status !== '' ? $status : 'PENDING');
}

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
