<?php declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../storage.php';

// Получаем текущий API-ключ из заголовков
$apiKey = getApiKeyFromRequest();
$merchant = findUserByApiKey($apiKey);

if (!$merchant) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Генерируем новый ключ с помощью встроенной функции[cite: 1]
$newKey = generateUserApiKey((int)$merchant['id']);

echo json_encode([
    'ok' => true,
    'new_api_key' => $newKey
], JSON_UNESCAPED_UNICODE);