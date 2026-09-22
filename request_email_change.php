<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/email_service.php';

$user = requireAuth();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: account.php');
    exit;
}

$newEmail = trim((string)($_POST['email'] ?? ''));
$currentEmail = trim((string)($user['email'] ?? ''));

if ($newEmail === '') {
    header('Location: account.php?email_error=' . rawurlencode('Введите новый Email.'));
    exit;
}

if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    header('Location: account.php?email_error=' . rawurlencode('Укажите корректный Email.'));
    exit;
}

if ($currentEmail !== '' && mb_strtolower($currentEmail) === mb_strtolower($newEmail)) {
    header('Location: account.php?email_error=' . rawurlencode('Этот Email уже установлен.'));
    exit;
}

$users = loadJson('users.json');

foreach ($users as $u) {
    if ((int)($u['id'] ?? 0) === (int)$user['id']) {
        continue;
    }

    $otherEmail = trim((string)($u['email'] ?? ''));
    $otherPendingEmail = trim((string)($u['pending_email'] ?? ''));

    if ($otherEmail !== '' && mb_strtolower($otherEmail) === mb_strtolower($newEmail)) {
        header('Location: account.php?email_error=' . rawurlencode('Этот Email уже используется другим аккаунтом.'));
        exit;
    }

    if ($otherPendingEmail !== '' && mb_strtolower($otherPendingEmail) === mb_strtolower($newEmail)) {
        header('Location: account.php?email_error=' . rawurlencode('Этот Email уже находится на подтверждении у другого аккаунта.'));
        exit;
    }
}

$code = (string)random_int(100000, 999999);
$expires = time() + 600;

$mailHtml = '<!doctype html>'
    . '<html lang="ru"><head><meta charset="UTF-8"></head>'
    . '<body style="margin:0;background:#f3f6fb;padding:32px 16px;font-family:Arial,Helvetica,sans-serif;color:#111827">'
    . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:22px;overflow:hidden;box-shadow:0 20px 60px rgba(15,23,42,.10)">'
    . '<div style="padding:24px 26px;background:linear-gradient(135deg,#6366f1,#22d3ee);color:#fff">'
    . '<div style="font-size:12px;font-weight:800;letter-spacing:1.5px;opacity:.85">GRAMPAY SECURITY</div>'
    . '<div style="font-size:27px;font-weight:900;margin-top:7px">Подтверждение Email</div>'
    . '</div>'
    . '<div style="padding:28px 26px">'
    . '<p style="margin:0 0 10px;font-size:15px">Вы запросили изменение Email в аккаунте GRAMPAY.</p>'
    . '<p style="margin:0;color:#6b7280;font-size:13px;line-height:1.6">Для подтверждения нового адреса введите код ниже.</p>'
    . '<div style="margin:25px 0;padding:22px;text-align:center;border:1px solid #e5e7eb;border-radius:16px;background:#f8fafc">'
    . '<div style="font-size:12px;color:#64748b;margin-bottom:9px">Ваш код</div>'
    . '<div style="font-size:38px;line-height:1;font-weight:900;letter-spacing:10px;color:#111827">' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
    . '</div>'
    . '<div style="font-size:12px;color:#64748b;line-height:1.6">Код действует 10 минут. Если вы не запрашивали смену Email, просто проигнорируйте это письмо.</div>'
    . '</div></div></body></html>';

if (!sendGrampayEmail($newEmail, 'GRAMPAY — подтверждение нового Email', $mailHtml)) {
    header('Location: account.php?email_error=' . rawurlencode('Не удалось отправить письмо. Проверьте SMTP-настройки в .env.'));
    exit;
}

foreach ($users as &$u) {
    if ((int)($u['id'] ?? 0) !== (int)$user['id']) {
        continue;
    }

    $u['pending_email'] = $newEmail;
    $u['email_change_code'] = $code;
    $u['email_change_code_expires'] = $expires;
    $u['updated_at'] = date('Y-m-d H:i:s');
    break;
}
unset($u);

saveJson('users.json', $users);

header('Location: confirm_email_change.php');
exit;
