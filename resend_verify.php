<?php declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/email_service.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$userId = (int)($_SESSION['pending_user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: login.php');
    exit;
}

$users = loadJson('users.json');
$sent = false;
foreach ($users as &$u) {
    if ((int)($u['id'] ?? 0) === $userId) {
        $email = (string)($u['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'У аккаунта не указан корректный Email.';
            break;
        }
        $code = (string)random_int(100000, 999999);
        $u['verify_code'] = $code;
        $u['verify_code_expires'] = time() + 600;
        $sent = true;
        $mailOk = sendVerificationCodeEmail($email, $code, 'подтверждение Email');
        break;
    }
}
unset($u);

if (!$sent) {
    $message = $message ?? 'Пользователь не найден.';
} else {
    saveJson('users.json', $users);
    if ($mailOk) {
        $message = 'Новый код отправлен на Email.';
    } else {
        $message = 'Код создан, но PHP не смог передать письмо почтовому серверу. Проверьте настройки mail()/MTA на хостинге.';
    }
}

header('Location: verify.php?message=' . rawurlencode($message));
exit;
