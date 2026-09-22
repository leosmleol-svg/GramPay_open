<?php
declare(strict_types=1);

return [
    'platega' => [
        'base_url'    => 'https://app.platega.io',
        'merchant_id' => 'MERCHANT_ID',
        'secret'      => 'APIKEY',
        'return_url'  => 'https://grampay.net/index.php?payment=success',
        'failed_url'  => 'https://grampay.net/index.php?payment=fail',
    ],
    // Активные методы оплаты
    'payment_methods' => [
        2  => 'СБП (QR-код)',
        11 => 'Карточный эквайринг (РФ)',
        13 => 'Криптовалюта (USDT)',
        14 => 'Sberpay',
    ],
    // Валюта для всех методов
    'method_currencies' => [
        2  => 'RUB',
        11 => 'RUB',
        13 => 'USDT',
        14 => 'RUB',
    ],
    // Минимальная сумма
    'method_min_amounts' => [
        2  => 1,
        11 => 1,
        13 => 1,
        14 => 1,
    ],
    // Надбавка: <= 5 RUB -> 0 RUB, > 5 RUB -> +5 RUB
    'fee_calculator' => function (int $amount): int {
        return ($amount > 5) ? 5 : 0;
    }
];