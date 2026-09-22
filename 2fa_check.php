<?php declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/2fa_lib.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) { header('Location: login.php'); exit; }
$user = findUserById($userId);
if (!$user) { unset($_SESSION['user_id']); header('Location: login.php'); exit; }
if (empty($user['two_factor_enabled']) || empty($user['two_factor_secret'])) { $_SESSION['2fa_verified'] = $userId; header('Location: panel.php'); exit; }
if ((int)($_SESSION['2fa_verified'] ?? 0) === $userId) { header('Location: panel.php'); exit; }

$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $code=trim((string)($_POST['code'] ?? ''));
    if (verifyTotp((string)$user['two_factor_secret'], $code)) {
        session_regenerate_id(true);
        $_SESSION['2fa_verified']=$userId;
        header('Location: panel.php'); exit;
    }
    $error='Неверный код 2FA.';
}
function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>2FA — GRAMPAY</title></head>
<body><h1>Проверка 2FA</h1><p>Введите шестизначный код из приложения-аутентификатора.</p>
<?php if($error): ?><p style="color:red"><?=h($error)?></p><?php endif; ?>
<form method="post"><input name="code" inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" autofocus required><button>Продолжить</button></form>
<p><a href="logout.php">Выйти</a></p></body></html>
