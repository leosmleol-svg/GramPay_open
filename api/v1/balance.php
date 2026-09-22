<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../../storage.php';

$apiKey = getApiKeyFromRequest();
$merchant = findUserByApiKey($apiKey);

if (!$merchant) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok'       => true,
    'user_id'  => (int)$merchant['id'],
    'username' => $merchant['username'],
    'balance'  => (int)($merchant['balance'] ?? 0),
    'currency' => 'RUB'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);