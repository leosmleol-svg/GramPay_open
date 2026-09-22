<?php
declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/PlategaService.php';

$config = require __DIR__ . '/config.php';
$platega = new PlategaService($config);

// Берём последнюю транзакцию из файла
$txs = loadJson('transactions.json');
$lastTx = end($txs);

if (!$lastTx) {
    die('Транзакций не найдено в data/transactions.json');
}

$txId = $_GET['tx'] ?? $lastTx['transaction_id'];

echo "<h3>Проверка транзакции: " . htmlspecialchars($txId) . "</h3>";

try {
    $res = $platega->getTransactionStatus($txId);
    echo "<b>Ответ от API Platega:</b><pre>" . print_r($res, true) . "</pre>";

    $status = strtoupper($res['status'] ?? 'UNKNOWN');
    if ($status === 'CONFIRMED' || $status === 'SUCCESS') {
        updateTransactionStatus($txId, 'CONFIRMED');
        updateUserBalance((int)$lastTx['user_id'], (float)$lastTx['amount']);
        echo "<p style='color:green; font-weight:bold;'>✓ Статус CONFIRMED! Баланс успешно пополнен на {$lastTx['amount']} RUB.</p>";
        echo "<a href='index.php'>Вернуться в Личный кабинет</a>";
    } else {
        echo "<p style='color:orange;'>Текущий статус транзакции: <b>{$status}</b></p>";
    }
} catch (\Throwable $e) {
    echo "<p style='color:red;'>Ошибка запроса: " . htmlspecialchars($e->getMessage()) . "</p>";
}