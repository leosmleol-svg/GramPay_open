<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../storage.php';
require_once __DIR__ . '/../PlategaService.php';
$config = require __DIR__ . '/../config.php';

$transactionId = trim((string)($_GET['transaction_id'] ?? $_GET['tx'] ?? ''));
if (!$transactionId) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing transaction_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tx = findTransaction($transactionId);
if (!$tx) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Transaction not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (in_array($tx['status'], ['CONFIRMED', 'SUCCESS', 'PAID'], true)) {
    echo json_encode(['ok' => true, 'status' => 'CONFIRMED'], JSON_UNESCAPED_UNICODE);
    exit;
}

$platega = new PlategaService($config);

try {
    $res = $platega->getTransactionStatus($transactionId);
    $remoteStatus = strtoupper(trim((string)($res['status'] ?? 'PENDING')));

    if (in_array($remoteStatus, ['CONFIRMED', 'SUCCESS', 'PAID', 'COMPLETED'], true)) {
        updateTransactionStatus($transactionId, 'CONFIRMED');
        updateUserBalance((int)$tx['user_id'], (int)$tx['amount']);
        $remoteStatus = 'CONFIRMED';
    } elseif (in_array($remoteStatus, ['FAILED', 'EXPIRED', 'CANCELED'], true)) {
        updateTransactionStatus($transactionId, $remoteStatus);
    }

    echo json_encode(['ok' => true, 'status' => $remoteStatus], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Status check failed'], JSON_UNESCAPED_UNICODE);
}