<?php declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['pending_user_id'])) {
    header('Location: register.php');
    exit;
}

$error = '';
$message = trim((string)($_GET['message'] ?? ''));
$userId = (int)$_SESSION['pending_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim((string)($_POST['code'] ?? ''));
    if (!preg_match('/^\d{6}$/', $code)) {
        $error = 'Введите 6-значный код из письма.';
    } else {
        $users = loadJson('users.json');
        $verified = false;
        $expired = false;
        foreach ($users as &$u) {
            if ((int)($u['id'] ?? 0) === $userId) {
                if (isset($u['verify_code_expires']) && (int)$u['verify_code_expires'] < time()) {
                    $expired = true;
                    break;
                }
                if (isset($u['verify_code']) && (string)$u['verify_code'] === $code) {
                    $u['is_verified'] = true;
                    unset($u['verify_code'], $u['verify_code_expires']);
                    $verified = true;
                }
                break;
            }
        }
        unset($u);

        if ($verified) {
            saveJson('users.json', $users);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            unset($_SESSION['pending_user_id']);
            header('Location: index.php');
            exit;
        }

        $error = $expired ? 'Срок действия кода истёк. Запросите новый код.' : 'Неверный код. Проверьте письмо и попробуйте ещё раз.';
    }
}

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="ru" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Подтверждение Email — GRAMPAY</title>
<script>document.documentElement.dataset.theme=localStorage.getItem('grampay_theme')||'dark';</script>
<style>
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
html{min-height:100%;color-scheme:dark}
:root[data-theme="dark"]{--bg:#05070d;--card:rgba(14,19,32,.76);--card2:rgba(255,255,255,.045);--border:rgba(255,255,255,.10);--text:#f8fafc;--muted:#8995ab;--secondary:#cbd5e1;--primary:#6366f1;--primary2:#22d3ee;--danger:#fb7185;--ok:#34d399}
:root[data-theme="light"]{--bg:#f3f6fb;--card:rgba(255,255,255,.84);--card2:rgba(15,23,42,.04);--border:rgba(15,23,42,.09);--text:#0f172a;--muted:#64748b;--secondary:#334155;--primary:#4f46e5;--primary2:#0891b2;--danger:#e11d48;--ok:#059669}
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:28px 16px;color:var(--text);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;background:radial-gradient(circle at 15% 10%,rgba(99,102,241,.20),transparent 33%),radial-gradient(circle at 90% 85%,rgba(34,211,238,.14),transparent 30%),var(--bg)}
.wrap{width:100%;max-width:470px}.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}.brand{display:flex;align-items:center;gap:10px;color:var(--text);text-decoration:none;font-weight:900;letter-spacing:-.4px}.logo{width:38px;height:38px;display:grid;place-items:center;border-radius:12px;color:#fff;background:linear-gradient(135deg,var(--primary),var(--primary2));box-shadow:0 12px 30px rgba(99,102,241,.25)}
.theme{width:40px;height:40px;border:1px solid var(--border);border-radius:12px;background:var(--card2);color:var(--text);cursor:pointer}.card{position:relative;overflow:hidden;border:1px solid var(--border);border-radius:26px;background:var(--card);backdrop-filter:blur(28px);box-shadow:0 30px 90px rgba(0,0,0,.28);padding:32px}.card:before{content:"";position:absolute;width:220px;height:220px;right:-100px;top:-115px;border-radius:50%;background:radial-gradient(circle,rgba(34,211,238,.18),transparent 70%)}.icon{position:relative;width:58px;height:58px;display:grid;place-items:center;border-radius:17px;color:#c7d2fe;background:rgba(99,102,241,.13);font-size:25px;margin-bottom:20px}.eyebrow{position:relative;color:var(--muted);font-size:10px;font-weight:900;letter-spacing:1.2px;text-transform:uppercase}.title{position:relative;margin:7px 0 8px;font-size:28px;line-height:1.1;letter-spacing:-.8px}.desc{position:relative;margin:0 0 25px;color:var(--secondary);font-size:12px;line-height:1.6}.notice{padding:12px 14px;border:1px solid rgba(52,211,153,.20);background:rgba(52,211,153,.07);border-radius:13px;color:var(--secondary);font-size:11px;line-height:1.5;margin-bottom:18px}.notice b{color:var(--text)}.alert{padding:12px 14px;border:1px solid rgba(251,113,133,.23);background:rgba(244,63,94,.07);border-radius:13px;color:var(--danger);font-size:11px;line-height:1.5;margin-bottom:16px}.message{padding:12px 14px;border:1px solid rgba(99,102,241,.20);background:rgba(99,102,241,.07);border-radius:13px;color:var(--secondary);font-size:11px;line-height:1.5;margin-bottom:16px}.label{display:block;margin:0 0 8px;color:var(--secondary);font-size:10px;font-weight:850}.code{width:100%;height:62px;border:1px solid var(--border);border-radius:15px;background:var(--card2);color:var(--text);font-size:27px;font-weight:900;letter-spacing:9px;text-align:center;outline:none;transition:.2s}.code::placeholder{color:var(--muted);opacity:.5;letter-spacing:7px}.code:focus{border-color:rgba(99,102,241,.7);box-shadow:0 0 0 5px rgba(99,102,241,.10)}.submit{width:100%;min-height:50px;margin-top:13px;border:0;border-radius:14px;color:#fff;background:linear-gradient(135deg,var(--primary),#4f46e5);font-size:12px;font-weight:850;cursor:pointer;box-shadow:0 14px 30px rgba(79,70,229,.25);transition:.2s}.submit:hover{transform:translateY(-2px);box-shadow:0 17px 34px rgba(79,70,229,.30)}.links{display:flex;justify-content:space-between;gap:10px;margin-top:17px}.links a{color:var(--muted);text-decoration:none;font-size:10px;font-weight:750}.links a:hover{color:var(--text)}.footer{text-align:center;color:var(--muted);font-size:9px;margin-top:17px}.timer{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:10px;margin-top:12px}.dot{width:7px;height:7px;border-radius:50%;background:var(--ok);box-shadow:0 0 0 5px rgba(52,211,153,.08)}
@media(max-width:520px){body{padding:18px 12px}.card{padding:24px 20px;border-radius:21px}.title{font-size:24px}.code{height:58px;font-size:24px;letter-spacing:7px}.top{margin-bottom:12px}}
</style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <a class="brand" href="index.php"><span class="logo">⚡</span><span>GRAMPAY</span></a>
        <button class="theme" type="button" onclick="toggleTheme()" title="Сменить тему">◐</button>
    </div>

    <main class="card">
        <div class="icon">✉</div>
        <div class="eyebrow">Проверка безопасности</div>
        <h1 class="title">Подтвердите Email</h1>
        <p class="desc">Мы отправили на вашу почту одноразовый код. Введите его ниже, чтобы завершить регистрацию и открыть аккаунт GRAMPAY.</p>

        <?php if ($message): ?><div class="message"><?= h($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert">⚠ <?= h($error) ?></div><?php endif; ?>

        <div class="notice"><b>Код состоит из 6 цифр.</b><br>Он действует ограниченное время. Не передавайте его другим людям.</div>

        <form method="post" autocomplete="off">
            <label class="label" for="code">КОД ИЗ ПИСЬМА</label>
            <input id="code" class="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" minlength="6" placeholder="000000" autocomplete="one-time-code" required autofocus>
            <button class="submit" type="submit">Проверить код →</button>
        </form>

        <div class="timer"><span class="dot"></span> Код отправляется через защищённый процесс подтверждения.</div>
        <div class="links"><a href="resend_verify.php">Отправить новый код</a><a href="login.php">Вернуться ко входу</a></div>
    </main>
    <div class="footer">GRAMPAY · Защита аккаунта</div>
</div>
<script>
function toggleTheme(){const c=document.documentElement.dataset.theme||'dark';const n=c==='dark'?'light':'dark';document.documentElement.dataset.theme=n;localStorage.setItem('grampay_theme',n)}
document.getElementById('code').addEventListener('input',function(){this.value=this.value.replace(/\D/g,'').slice(0,6)});
</script>
</body>
</html>
