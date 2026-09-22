<?php declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/2fa_lib.php';

$user = requireAuth();
$error = ''; $success = '';
$enabled = !empty($user['two_factor_enabled']) && !empty($user['two_factor_secret']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $code = trim((string)($_POST['code'] ?? ''));
    $users = loadJson('users.json');

    if ($action === 'start') {
        $_SESSION['pending_2fa_secret'] = generateTotpSecret();
        header('Location: 2fa.php?setup=1'); exit;
    }
    if ($action === 'confirm') {
        $secret = (string)($_SESSION['pending_2fa_secret'] ?? '');
        if ($secret === '' || !verifyTotp($secret, $code)) {
            $error = 'Неверный код. Откройте приложение-аутентификатор и попробуйте ещё раз.';
        } else {
            foreach ($users as &$u) if ((int)($u['id'] ?? 0) === (int)$user['id']) {
                $u['two_factor_enabled'] = true; $u['two_factor_secret'] = $secret; break;
            }
            unset($u); saveJson('users.json', $users); unset($_SESSION['pending_2fa_secret']);
            $success = '2FA включена.'; $enabled = true; $user = requireAuth();
        }
    }
    if ($action === 'disable') {
        if (!$enabled || !verifyTotp((string)$user['two_factor_secret'], $code)) {
            $error = 'Для отключения нужен текущий код 2FA.';
        } else {
            foreach ($users as &$u) if ((int)($u['id'] ?? 0) === (int)$user['id']) {
                $u['two_factor_enabled'] = false; unset($u['two_factor_secret']); break;
            }
            unset($u); saveJson('users.json', $users); $enabled = false; $success = '2FA отключена.';
        }
    }
}

$setup = isset($_GET['setup']) && !empty($_SESSION['pending_2fa_secret']);
$secret = $setup ? (string)$_SESSION['pending_2fa_secret'] : '';
$issuer = 'GRAMPAY';
$label = $issuer . ':' . (string)($user['username'] ?? ('user-' . $user['id']));
$otpauth = 'otpauth://totp/' . rawurlencode($label) . '?secret=' . rawurlencode($secret) . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Двухфакторная аутентификация — GRAMPAY</title>
<style>
*{box-sizing:border-box}html{scroll-behavior:smooth}:root{--bg:#05070d;--surface:rgba(14,19,32,.78);--surface2:rgba(255,255,255,.045);--border:rgba(255,255,255,.09);--text:#f8fafc;--muted:#8793a8;--secondary:#cbd5e1;--primary:#6366f1;--cyan:#22d3ee;--green:#34d399;--red:#fb7185}html[data-theme=light]{--bg:#f3f6fb;--surface:rgba(255,255,255,.82);--surface2:rgba(15,23,42,.04);--border:rgba(15,23,42,.09);--text:#0f172a;--muted:#64748b;--secondary:#334155;--primary:#4f46e5;--cyan:#0891b2;--green:#059669;--red:#e11d48}body{margin:0;min-height:100vh;background:radial-gradient(circle at 15% -10%,rgba(99,102,241,.2),transparent 35%),radial-gradient(circle at 100% 20%,rgba(34,211,238,.1),transparent 28%),var(--bg);color:var(--text);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;padding:32px 16px}.wrap{width:min(720px,100%);margin:auto}.brand{display:flex;align-items:center;gap:11px;color:var(--text);text-decoration:none;font-weight:900;font-size:19px;margin-bottom:22px}.brand-icon{width:42px;height:42px;border-radius:13px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#6366f1,#22d3ee);color:#fff;box-shadow:0 14px 35px rgba(99,102,241,.25)}.card{border:1px solid var(--border);background:var(--surface);backdrop-filter:blur(28px);border-radius:24px;box-shadow:0 28px 80px rgba(0,0,0,.23);overflow:hidden}.header{padding:25px;border-bottom:1px solid var(--border)}.header h1{margin:0;font-size:26px;font-weight:900;letter-spacing:-.6px}.header p{margin:6px 0 0;color:var(--muted);font-size:12px}.body{padding:25px}.label{display:block;margin:0 0 8px;color:var(--secondary);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.7px}.field{width:100%;min-height:50px;border:1px solid var(--border);border-radius:13px;background:rgba(0,0,0,.16);color:var(--text);padding:0 15px;outline:0;font-size:13px;transition:.2s}.field:focus{border-color:rgba(99,102,241,.65);box-shadow:0 0 0 4px rgba(99,102,241,.09)}html[data-theme=light] .field{background:rgba(255,255,255,.7)}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:48px;padding:0 17px;border:0;border-radius:12px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;text-decoration:none;font-size:11px;font-weight:850;cursor:pointer;box-shadow:0 12px 25px rgba(79,70,229,.22)}.btn.secondary{background:var(--surface2);border:1px solid var(--border);color:var(--secondary);box-shadow:none}.row{display:flex;gap:10px;flex-wrap:wrap}.notice{padding:13px 15px;border-radius:13px;margin-bottom:16px;font-size:11px;line-height:1.5}.error{border:1px solid rgba(251,113,133,.2);background:rgba(244,63,94,.06);color:#fda4af}.success{border:1px solid rgba(52,211,153,.2);background:rgba(52,211,153,.06);color:#6ee7b7}.info{border:1px solid rgba(34,211,238,.18);background:rgba(34,211,238,.05);color:#a5f3fc}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.security-nav{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px}.nav-card{padding:14px;border:1px solid var(--border);border-radius:14px;background:var(--surface2);text-decoration:none;color:var(--secondary);font-size:11px;font-weight:800}.nav-card strong{display:block;color:var(--text);font-size:12px;margin-bottom:3px}.footer{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:18px;color:var(--muted);font-size:10px}.code{font:800 24px/1 ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:5px}.secret{word-break:break-all;padding:12px;border-radius:12px;background:#080b12;color:#a5f3fc;font:11px ui-monospace,monospace;border:1px solid var(--border)}.qr{display:flex;justify-content:center;padding:14px;background:#fff;border-radius:16px;width:max-content;margin:14px 0}.theme{background:none;border:1px solid var(--border);color:var(--secondary);border-radius:10px;padding:8px 10px;cursor:pointer}@media(max-width:620px){body{padding:18px 12px}.header,.body{padding:20px}.grid,.security-nav{grid-template-columns:1fr}.footer{align-items:flex-start;flex-direction:column}.row .btn{width:100%}}</style>
<script>const t=localStorage.getItem('grampay_theme')||'dark';document.documentElement.dataset.theme=t;function toggleTheme(){const n=document.documentElement.dataset.theme==='dark'?'light':'dark';document.documentElement.dataset.theme=n;localStorage.setItem('grampay_theme',n)}</script>
</head><body><div class="wrap"><a class="brand" href="panel.php"><span class="brand-icon">ϟ</span><span>GRAMPAY</span></a><div class="card"><div class="header"><button class="theme" style="float:right" onclick="toggleTheme()">◐ Тема</button><h1>2FA</h1><p>Двухфакторная аутентификация для защиты входа</p></div><div class="body"><div class="security-nav"><a class="nav-card" href="account.php"><strong>👤 Профиль</strong>Данные аккаунта</a><a class="nav-card" href="change_password.php"><strong>🔑 Пароль</strong>Безопасность</a><a class="nav-card" href="2fa.php"><strong>🛡 2FA</strong>Защита входа</a></div><?php if($error): ?><div class="notice error"><?=h($error)?></div><?php endif; ?><?php if($success): ?><div class="notice success"><?=h($success)?></div><?php endif; ?><?php if($enabled): ?><div class="notice success">● 2FA включена и будет запрашиваться при входе в аккаунт.</div><form method="post"><input type="hidden" name="action" value="disable"><label class="label">Текущий код 2FA</label><input class="field" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000" required style="max-width:280px"><div class="row" style="margin-top:14px"><button class="btn" type="submit">Отключить 2FA</button><a class="btn secondary" href="panel.php">← В панель</a></div></form><?php elseif($setup): ?><div class="notice info">Откройте Google Authenticator, Aegis, 1Password или другое TOTP-приложение и добавьте этот ключ.</div><div id="qrcode" class="qr"></div><div class="label">Секретный ключ</div><div class="secret"><?=h($secret)?></div><div class="label" style="margin-top:14px">TOTP URI</div><div class="secret"><?=h($otpauth)?></div><form method="post" style="margin-top:16px"><input type="hidden" name="action" value="confirm"><label class="label">Код из приложения</label><input class="field" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000" required style="max-width:280px"><div class="row" style="margin-top:14px"><button class="btn" type="submit">Подтвердить 2FA</button><a class="btn secondary" href="2fa.php">Отмена</a></div></form><script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script><script>new QRCode(document.getElementById('qrcode'), <?=json_encode($otpauth)?>);</script><?php else: ?><div class="notice info">2FA сейчас выключена. Включите её, чтобы при входе дополнительно требовался код из приложения-аутентификатора.</div><form method="post"><input type="hidden" name="action" value="start"><button class="btn" type="submit">Включить 2FA</button><a class="btn secondary" href="panel.php" style="margin-left:8px">← В панель</a></form><?php endif; ?><div class="footer"><span>Ваш аккаунт защищён TOTP-кодом</span><span>GRAMPAY Security</span></div></div></div></div></body></html>