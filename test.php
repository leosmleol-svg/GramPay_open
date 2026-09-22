<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/PlategaService.php';

$config = require __DIR__ . '/config.php';
$platega = new PlategaService($config);

echo "<h2>Диагностика GRAMPAY -> Platega.io</h2>";

// Список методов для проверки
$methodsToTest = [
    2  => ['name' => 'СБП (2)',            'currency' => 'RUB', 'amount' => 1],
    3  => ['name' => 'ЕРИП (3, BYN)',      'currency' => 'BYN', 'amount' => 1],
    31 => ['name' => 'ЕРИП (3, RUB)',      'currency' => 'RUB', 'amount' => 1, 'method' => 3],
    11 => ['name' => 'Карты РФ (11, RUB)', 'currency' => 'RUB', 'amount' => 1],
    12 => ['name' => 'Международная (12, USD)', 'currency' => 'USD', 'amount' => 1],
    121=> ['name' => 'Международная (12, RUB)', 'currency' => 'RUB', 'amount' => 1, 'method' => 12],
];

echo "<table border='1' cellpadding='8' style='border-collapse:collapse; font-family:sans-serif;'>";
echo "<tr style='background:#eee;'><th>Метод</th><th>Валюта</th><th>Сумма</th><th>Результат Platega</th></tr>";

foreach ($methodsToTest as $test) {
    $methodId = $test['method'] ?? array_search($test, $methodsToTest, true);
    if (!is_int($methodId)) {
        $methodId = (int)$test['paymentMethod'] ?? (int)explode(' ', $test['name']);
    }
    
    // Получение курса
    $rate = $platega->getRate((int)$methodId, 'RUB', $test['currency']);

    // Тестовый запрос
    $ch = curl_init('https://app.platega.io/transaction/process');
    $payload = [
        'paymentMethod'  => (int)$methodId,
        'paymentDetails' => [
            'amount'   => (int)$test['amount'],
            'currency' => $test['currency']
        ],
        'description'    => "Тест {$test['name']}",
        'return'         => 'https://grampay.net/index.php?payment=success',
        'failedUrl'      => 'https://grampay.net/index.php?payment=fail',
        'payload'        => 'diag_test',
        'metadata'       => [
            'service'  => 'GRAMPAY',
            'clientIp' => '127.0.0.1'
        ]
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'X-MerchantId: ' . $config['platega']['merchant_id'],
            'X-Secret: ' . $config['platega']['secret'],
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $isOk = ($httpCode === 200 && str_contains((string)$response, 'transactionId'));
    $bg = $isOk ? '#dcfce7' : '#fee2e2';

    echo "<tr style='background:{$bg};'>";
    echo "<td><strong>{$test['name']}</strong> (ID: {$methodId})</td>";
    echo "<td>{$test['currency']} (Курс к RUB: {$rate})</td>";
    echo "<td>{$test['amount']}</td>";
    echo "<td><strong>HTTP {$httpCode}</strong>: " . htmlspecialchars((string)$response) . ($err ? " | Error: {$err}" : "") . "</td>";
    echo "</tr>";
}

echo "</table>";