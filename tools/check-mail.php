<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/email_service.php';
$options = getopt('', ['to:']);
$to = (string)($options['to'] ?? '');
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php tools/check-mail.php --to=your-address@example.com\n");
    exit(2);
}
$ok = sendGrampayEmail($to, 'GRAMPAY — проверка почты', '<p>Проверка отправки SMTP из GRAMPAY.</p>');
echo $ok ? "SMTP принял письмо. Проверьте входящие и спам.\n" : "Отправка не выполнена. Этап ошибки указан в PHP error_log.\n";
exit($ok ? 0 : 1);
