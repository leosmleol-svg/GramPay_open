<?php declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';

$error = '';
$captchaToken = trim((string)($_POST['smart-token'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($captchaToken === '') {
        $error = 'Подтвердите, что вы не робот.';
    } elseif ($username !== '' && $email !== '' && mb_strlen($password, 'UTF-8') >= 6) {
        $users = loadJson('users.json');
        $searchUser = mb_strtolower($username, 'UTF-8');
        $searchEmail = mb_strtolower($email, 'UTF-8');
        $duplicate = false;

        foreach ($users as $u) {
            if (mb_strtolower((string)($u['username'] ?? ''), 'UTF-8') === $searchUser || 
                mb_strtolower((string)($u['email'] ?? ''), 'UTF-8') === $searchEmail) {
                $duplicate = true;
                break;
            }
        }

        if ($duplicate) {
            $error = 'Пользователь с таким логином или email уже существует.';
        } else {
            $user = createUser($username, $email, $password);
            
            $verifyCode = (string)random_int(100000, 999999);
            $users = loadJson('users.json');
            foreach ($users as &$u) {
                if ($u['id'] === $user['id']) {
                    $u['is_verified'] = false;
                    $u['verify_code'] = $verifyCode;
                    break;
                }
            }
            saveJson('users.json', $users);

            // ==========================================
            // ГЕНЕРАЦИЯ РАНДОМНОГО EMAIL ОТПРАВИТЕЛЯ
            // ==========================================
            $randomId = random_int(1000, 9999);
            $randomEmail = "noreply-{$randomId}@grampay.net";

            $subject = "Код подтверждения регистрации GRAMPAY";
            $message = "Добро пожаловать в GRAMPAY!\n\nВы начали регистрацию аккаунта: {$username}\n\nВаш 6-значный код подтверждения:\n{$verifyCode}";
            
            $headers = "From: GRAMPAY <{$randomEmail}>\r\n" .
                       "Reply-To: {$randomEmail}\r\n" .
                       "Content-Type: text/plain; charset=UTF-8\r\n" .
                       "X-Mailer: PHP/" . phpversion();
            
            @mail($email, $subject, $message, $headers);

            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['pending_user_id'] = $user['id'];
            header('Location: verify.php');
            exit;
        }
    } else {
        $error = 'Заполните все поля (пароль от 6 символов).';
    }
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://smartcaptcha.yandexcloud.net/captcha.js" defer></script>
    <title>Регистрация — GRAMPAY</title>
    <script>
        const savedTheme = localStorage.getItem('grampay_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <style>
        * { box-sizing: border-box; font-family: Inter, sans-serif; }
        :root[data-theme="dark"] { --bg:#05070d; --surface:rgba(14,19,32,.72); --border:rgba(255,255,255,.09); --text:#f8fafc; --muted:#7f8ba3; --input:rgba(0,0,0,.16); }
        :root[data-theme="light"] { --bg:#f3f6fb; --surface:rgba(255,255,255,.78); --border:rgba(15,23,42,.08); --text:#0f172a; --muted:#64748b; --input:rgba(255,255,255,.65); }
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; color:var(--text); background:radial-gradient(circle at 10% -10%,rgba(99,102,241,.19),transparent 33%),radial-gradient(circle at 100% 25%,rgba(34,211,238,.10),transparent 27%),var(--bg); }
        .auth-card { width:100%; max-width:400px; padding:40px 30px; border:1px solid var(--border); border-radius:24px; background:var(--surface); backdrop-filter:blur(25px); box-shadow:0 30px 80px rgba(0,0,0,.24); }
        .logo { display:flex; align-items:center; justify-content:center; gap:10px; margin-bottom:30px; font-size:20px; font-weight:900; }
        .logo-icon { width:42px; height:42px; display:flex; align-items:center; justify-content:center; border-radius:13px; color:#fff; background:linear-gradient(135deg,#6366f1,#22d3ee); }
        .form-label { display:block; margin-bottom:8px; font-size:14px; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:1px; }
        .form-control { width:100%; min-height:48px; margin-bottom:16px; padding:0 16px; border:1px solid var(--border); border-radius:12px; background:var(--input); color:var(--text); outline:none; transition:.2s; }
        .form-control:focus { border-color:rgba(99,102,241,.65); box-shadow:0 0 0 4px rgba(99,102,241,.09); }
        .captcha-wrap { margin: 4px 0 10px; overflow: hidden; border-radius: 12px; }
        .btn-primary { width:100%; min-height:48px; border:0; border-radius:12px; background:linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; font-size:12px; font-weight:800; cursor:pointer; margin-top:10px; box-shadow:0 12px 28px rgba(79,70,229,.27); transition:.2s; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 15px 35px rgba(79,70,229,.35); }
        .error { background:rgba(244,63,94,.09); border:1px solid rgba(244,63,94,.17); color:#fda4af; padding:12px; border-radius:12px; font-size:14px; margin-bottom:20px; text-align:center; }
        .links { margin-top:24px; text-align:center; font-size:14px; color:var(--muted); }
        .links a { color:var(--text); text-decoration:none; font-weight:700; }
        .theme-btn { position:absolute; top:20px; right:20px; background:var(--surface); border:1px solid var(--border); color:var(--muted); padding:8px 12px; border-radius:10px; cursor:pointer; font-size:14px; font-weight:800; }
    </style>
<link rel="stylesheet" href="assets/interface.css?v=20260922">
<script src="assets/interface.js?v=20260922" defer></script>
</head>
<body class="gp-auth">
    <button class="theme-btn" onclick="toggleTheme()">🌓 ТЕМА</button>
    <div class="auth-card">
        <div class="logo"><div class="logo-icon">⚡</div>GRAMPAY</div>
        <?php if ($error !== ''): ?><div class="error" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <h1>Создать аккаунт</h1><p class="gp-auth-intro">Зарегистрируйтесь для работы с платежами.</p>
        <form method="POST">
            <label class="form-label">Ваш Логин</label>
            <input type="text" name="username" id="username" autocomplete="username" class="form-control" placeholder="Придумайте логин" required autofocus>
            <label class="form-label">Email</label>
            <input type="email" name="email" id="email" autocomplete="email" class="form-control" placeholder="name@domain.com" required>
            <label class="form-label">Пароль</label>
            <input type="password" name="password" id="password" autocomplete="new-password" class="form-control" placeholder="Мин. 6 символов" required>
            <div class="captcha-wrap">
                <div
                    style="height: 100px"
                    id="captcha-container"
                    class="smart-captcha"
                    data-sitekey="ysc1_fNl8ZUHZedZO75jh0qBC2O7NcFWiJs0p90quPajFe267b004"
                ></div>
            </div>
            <button type="submit" class="btn-primary">Создать аккаунт</button>
        </form>
        <div class="links">Уже есть аккаунт? <a href="login.php">Войти</a></div>
    </div>
    <script>
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('grampay_theme', next);
        }
    </script>
</body>
</html>
