<?php declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';

$user = requireAuth();
$error = '';
$success = '';

function validPassword(string $p): bool { return strlen($p) >= 8 && strlen($p) <= 200; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = (string)($_POST['old_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $stored = (string)($user['password'] ?? $user['password_hash'] ?? '');

    $validOld = password_verify($old, $stored);
    if (!$validOld && hash_equals($stored, $old)) {
        // Совместимость со старыми аккаунтами, если старый проект хранил пароль открытым текстом.
        $validOld = true;
    }

    if (!$validOld) $error = 'Старый пароль указан неверно.';
    elseif (!validPassword($new)) $error = 'Новый пароль должен содержать минимум 8 символов.';
    elseif ($new !== $confirm) $error = 'Пароли не совпадают.';
    elseif ($new === $old) $error = 'Новый пароль должен отличаться от старого.';
    else {
        $users = loadJson('users.json');
        foreach ($users as &$u) {
            if ((int)($u['id'] ?? 0) === (int)$user['id']) {
                $u['password'] = password_hash($new, PASSWORD_DEFAULT);
                unset($u['password_hash']);
                $u['updated_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        unset($u);
        saveJson('users.json', $users);
        session_regenerate_id(true);
        $success = 'Пароль изменён.';
    }
}
function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Смена пароля — GRAMPAY</title>
<style>
*{box-sizing:border-box}html{scroll-behavior:smooth}:root{--bg:#05070d;--surface:rgba(14,19,32,.78);--surface2:rgba(255,255,255,.045);--border:rgba(255,255,255,.09);--text:#f8fafc;--muted:#8793a8;--secondary:#cbd5e1;--primary:#6366f1;--cyan:#22d3ee;--green:#34d399;--red:#fb7185}html[data-theme=light]{--bg:#f3f6fb;--surface:rgba(255,255,255,.82);--surface2:rgba(15,23,42,.04);--border:rgba(15,23,42,.09);--text:#0f172a;--muted:#64748b;--secondary:#334155;--primary:#4f46e5;--cyan:#0891b2;--green:#059669;--red:#e11d48}body{margin:0;min-height:100vh;background:radial-gradient(circle at 15% -10%,rgba(99,102,241,.2),transparent 35%),radial-gradient(circle at 100% 20%,rgba(34,211,238,.1),transparent 28%),var(--bg);color:var(--text);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;padding:32px 16px}.wrap{width:min(720px,100%);margin:auto}.brand{display:flex;align-items:center;gap:11px;color:var(--text);text-decoration:none;font-weight:900;font-size:19px;margin-bottom:22px}.brand-icon{width:42px;height:42px;border-radius:13px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#6366f1,#22d3ee);color:#fff;box-shadow:0 14px 35px rgba(99,102,241,.25)}.card{border:1px solid var(--border);background:var(--surface);backdrop-filter:blur(28px);border-radius:24px;box-shadow:0 28px 80px rgba(0,0,0,.23);overflow:hidden}.header{padding:25px;border-bottom:1px solid var(--border)}.header h1{margin:0;font-size:26px;font-weight:900;letter-spacing:-.6px}.header p{margin:6px 0 0;color:var(--muted);font-size:12px}.body{padding:25px}.label{display:block;margin:0 0 8px;color:var(--secondary);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.7px}.field{width:100%;min-height:50px;border:1px solid var(--border);border-radius:13px;background:rgba(0,0,0,.16);color:var(--text);padding:0 15px;outline:0;font-size:13px;transition:.2s}.field:focus{border-color:rgba(99,102,241,.65);box-shadow:0 0 0 4px rgba(99,102,241,.09)}html[data-theme=light] .field{background:rgba(255,255,255,.7)}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:48px;padding:0 17px;border:0;border-radius:12px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;text-decoration:none;font-size:11px;font-weight:850;cursor:pointer;box-shadow:0 12px 25px rgba(79,70,229,.22)}.btn.secondary{background:var(--surface2);border:1px solid var(--border);color:var(--secondary);box-shadow:none}.row{display:flex;gap:10px;flex-wrap:wrap}.notice{padding:13px 15px;border-radius:13px;margin-bottom:16px;font-size:11px;line-height:1.5}.error{border:1px solid rgba(251,113,133,.2);background:rgba(244,63,94,.06);color:#fda4af}.success{border:1px solid rgba(52,211,153,.2);background:rgba(52,211,153,.06);color:#6ee7b7}.info{border:1px solid rgba(34,211,238,.18);background:rgba(34,211,238,.05);color:#a5f3fc}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.security-nav{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px}.nav-card{padding:14px;border:1px solid var(--border);border-radius:14px;background:var(--surface2);text-decoration:none;color:var(--secondary);font-size:11px;font-weight:800}.nav-card strong{display:block;color:var(--text);font-size:12px;margin-bottom:3px}.footer{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:18px;color:var(--muted);font-size:10px}.code{font:800 24px/1 ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:5px}.secret{word-break:break-all;padding:12px;border-radius:12px;background:#080b12;color:#a5f3fc;font:11px ui-monospace,monospace;border:1px solid var(--border)}.qr{display:flex;justify-content:center;padding:14px;background:#fff;border-radius:16px;width:max-content;margin:14px 0}.theme{background:none;border:1px solid var(--border);color:var(--secondary);border-radius:10px;padding:8px 10px;cursor:pointer}@media(max-width:620px){body{padding:18px 12px}.header,.body{padding:20px}.grid,.security-nav{grid-template-columns:1fr}.footer{align-items:flex-start;flex-direction:column}.row .btn{width:100%}}</style>
<script>const t=localStorage.getItem('grampay_theme')||'dark';document.documentElement.dataset.theme=t;function toggleTheme(){const n=document.documentElement.dataset.theme==='dark'?'light':'dark';document.documentElement.dataset.theme=n;localStorage.setItem('grampay_theme',n)}</script>
</head><body><div class="wrap"><a class="brand" href="panel.php"><span class="brand-icon">ϟ</span><span>GRAMPAY</span></a><div class="card"><div class="header"><button class="theme" style="float:right" onclick="toggleTheme()">◐ Тема</button><h1>Смена пароля</h1><p>Обновите пароль для защиты аккаунта</p></div><div class="body"><div class="security-nav"><a class="nav-card" href="account.php"><strong>👤 Профиль</strong>Данные аккаунта</a><a class="nav-card" href="change_password.php"><strong>🔑 Пароль</strong>Безопасность</a><a class="nav-card" href="2fa.php"><strong>🛡 2FA</strong>Защита входа</a></div><?php if($error): ?><div class="notice error"><?=h($error)?></div><?php endif; ?><?php if($success): ?><div class="notice success"><?=h($success)?></div><?php endif; ?><form method="post"><div style="margin-bottom:14px"><label class="label">Текущий пароль</label><input class="field" type="password" name="old_password" autocomplete="current-password" required></div><div class="grid"><div><label class="label">Новый пароль</label><input class="field" type="password" name="new_password" minlength="8" autocomplete="new-password" required></div><div><label class="label">Повторите пароль</label><input class="field" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></div></div><div class="notice info" style="margin-top:14px">Минимум 8 символов. После смены пароля активная сессия будет обновлена.</div><div class="row"><button class="btn" type="submit">Изменить пароль</button><a class="btn secondary" href="panel.php">← В панель</a></div></form><div class="footer"><span>Рекомендуем использовать уникальный пароль</span><span>GRAMPAY Security</span></div></div></div></div></body></html>