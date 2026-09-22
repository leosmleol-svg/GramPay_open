<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/PlategaService.php';

$user = requireAuth();
$config = require __DIR__ . '/config.php';

$amountPost = (int)($_POST['amount'] ?? 0);
$paymentMethodRaw = $_POST['payment_method'] ?? null;

if ($paymentMethodRaw === null || $paymentMethodRaw === '') {
    if ($amountPost <= 0) {
        die('Укажите корректную сумму.');
    }

    $_SESSION['grampay_pending_amount'] = $amountPost;
    unset($_SESSION['grampay_pending_payment_method']);

    header('Location: pay_wait.php?amount=' . urlencode((string)$amountPost));
    exit;
}

$amount = (int)($_SESSION['grampay_pending_amount'] ?? $amountPost);
$paymentMethod = (int)$paymentMethodRaw;

if ($amount <= 0) {
    die('Срок выбора способа оплаты истёк. Вернитесь в панель и создайте счёт заново.');
}

if (!isset($config['payment_methods'][$paymentMethod])) {
    die('Некорректный способ оплаты.');
}

$minAmount = (int)($config['method_min_amounts'][$paymentMethod] ?? 1);
if ($amount < $minAmount) {
    $_SESSION['grampay_pending_amount'] = $amount;
    header('Location: pay_wait.php?amount=' . urlencode((string)$amount) . '&error=min');
    exit;
}

// Для GRAMPAY все способы оплаты, включая криптовалютные,
// создаются с валютой RUB.
// GRAMPAY принимает все способы оплаты в рублях, включая крипто-методы.
$currency = 'RUB';

$returnUrl = trim((string)($_POST['return_url'] ?? ''));
if ($returnUrl === '') {
    $returnUrl = !empty($user['default_return_url'])
        ? $user['default_return_url']
        : $config['platega']['return_url'];
}

$failedUrl = trim((string)($_POST['failed_url'] ?? ''));
if ($failedUrl === '') {
    $failedUrl = !empty($user['default_failed_url'])
        ? $user['default_failed_url']
        : $config['platega']['failed_url'];
}

$fee = ($amount > 5) ? 5 : 0;
$totalAmount = $amount + $fee;

$platega = new PlategaService($config);

try {
    $methodName = $config['payment_methods'][$paymentMethod];
    $description = "GRAMPAY #{$user['id']} ({$user['username']}) - {$methodName}";
    $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    $response = $platega->createTransaction(
        paymentMethod: $paymentMethod,
        totalAmount: $totalAmount,
        currency: $currency,
        description: $description,
        returnUrl: $returnUrl,
        failedUrl: $failedUrl,
        payload: "Deposit user {$user['id']}",
        metadata: [
            'service'      => 'GRAMPAY',
            'merchantId'   => (string)$user['id'],
            'merchantName' => $user['username'],
            'clientIp'     => $clientIp
        ]
    );

    $transactionId = $response['transactionId'] ?? $response['id'] ?? null;
    $redirectUrl = $response['redirect'] ?? $response['url'] ?? null;

    if (!$transactionId || !$redirectUrl) {
        throw new RuntimeException(
            'Не получен URL оплаты: ' .
            json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    saveTransaction([
        'transaction_id' => $transactionId,
        'user_id'        => (int)$user['id'],
        'payment_method' => $paymentMethod,
        'amount'         => $amount,
        'fee'            => $fee,
        'total_amount'   => $totalAmount,
        'currency'       => $currency,
        'status'         => 'PENDING',
        'redirect_url'   => $redirectUrl,
        'return_url'     => $returnUrl,
        'failed_url'     => $failedUrl,
        'created_at'     => date('Y-m-d H:i:s'),
        'updated_at'     => date('Y-m-d H:i:s')
    ]);

    unset($_SESSION['grampay_pending_amount']);
    unset($_SESSION['grampay_pending_payment_method']);

    header('Location: pay_wait.php?tx=' . urlencode((string)$transactionId));
    exit;

} catch (Throwable $e) {
    header('Content-Type: text/html; charset=utf-8');

    echo "<!DOCTYPE html><html lang='ru'><head>";
    echo "<meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>";
    echo "<title>Ошибка GRAMPAY</title></head>";
    echo "<body style='background:#070a12;color:#f8fafc;font-family:Inter,system-ui,sans-serif;padding:30px 16px;'>";
    echo "<div style='max-width:650px;margin:0 auto;background:#0d111c;border:1px solid rgba(255,255,255,.10);border-radius:22px;padding:24px;'>";
    echo "<h2 style='margin-top:0;color:#fb7185;'>Не удалось создать платёж</h2>";
    echo "<p style='color:#8b98ad;'>Ответ платёжного шлюза:</p>";
    echo "<pre style='background:#070a12;color:#93c5fd;border:1px solid rgba(255,255,255,.08);padding:15px;border-radius:12px;overflow:auto;white-space:pre-wrap;'>" .
        htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
        "</pre>";
    echo "<a href='pay_wait.php' style='display:inline-block;margin-top:12px;background:#6366f1;color:#fff;padding:11px 17px;border-radius:11px;text-decoration:none;font-weight:800;'>← Выбрать способ оплаты</a>";
    echo "</div></body></html>";
    exit;
}
