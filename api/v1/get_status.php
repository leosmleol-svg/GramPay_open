<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../../storage.php';
require_once __DIR__ . '/../../PlategaService.php';
$config = require __DIR__ . '/../../config.php';

$apiKey = getApiKeyFromRequest();
$merchant = findUserByApiKey($apiKey);

if (!$merchant) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$invoiceId = trim((string)($_GET['invoice_id'] ?? $_GET['id'] ?? $_GET['tx'] ?? ''));
if (!$invoiceId) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Параметр invoice_id обязателен'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tx = findTransaction($invoiceId);
if (!$tx || (int)$tx['user_id'] !== (int)$merchant['id']) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Счет не найден'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (in_array($tx['status'], ['CONFIRMED', 'SUCCESS', 'PAID'], true)) {
    echo json_encode([
        'ok'           => true,
        'invoice_id'   => $tx['transaction_id'],
        'status'       => 'CONFIRMED',
        'amount'       => (int)$tx['amount'],
        'fee'          => (int)($tx['fee'] ?? 0),
        'total_amount' => (int)($tx['total_amount'] ?? $tx['amount']),
        'currency'     => $tx['currency'] ?? 'RUB',
        'payload'      => $tx['payload'] ?? null
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$platega = new PlategaService($config);
try {
    $res = $platega->getTransactionStatus($invoiceId);
    $remoteStatus = strtoupper(trim((string)($res['status'] ?? 'PENDING')));

    if (in_array($remoteStatus, ['CONFIRMED', 'SUCCESS', 'PAID', 'COMPLETED'], true)) {
        updateTransactionStatus($invoiceId, 'CONFIRMED');
        updateUserBalance((int)$tx['user_id'], (int)$tx['amount']);
        $remoteStatus = 'CONFIRMED';
    } elseif (in_array($remoteStatus, ['FAILED', 'EXPIRED', 'CANCELED'], true)) {
        updateTransactionStatus($invoiceId, $remoteStatus);
    }

    echo json_encode([
        'ok'           => true,
        'invoice_id'   => $tx['transaction_id'],
        'status'       => $remoteStatus,
        'amount'       => (int)$tx['amount'],
        'fee'          => (int)($tx['fee'] ?? 0),
        'total_amount' => (int)($tx['total_amount'] ?? $tx['amount']),
        'currency'     => $tx['currency'] ?? 'RUB',
        'payload'      => $tx['payload'] ?? null
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}