<?php declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';

$user = requireAuth();
$config = require __DIR__ . '/config.php';
$transactions = getUserTransactions((int)$user['id']);

if (empty($user['api_key'])) {
    $apiKey = generateUserApiKey((int)$user['id']);
    $user = findUserById((int)$user['id']);
} else {
    $apiKey = $user['api_key'];
}

$verifyFileName = "grampay_verify_" . $user['id'] . ".txt";
$verifyFileContent = md5((string)$user['id'] . $user['api_key']);
$isVerified = !empty($user['is_website_verified']);
$verifiedDomain = $user['verified_domain'] ?? '';

$websiteApprovalStatus = (string)($user['website_approval_status'] ?? 'not_submitted');
$websiteApprovalReason = (string)($user['website_approval_reason'] ?? '');
$botApprovalStatus = (string)($user['bot_approval_status'] ?? 'not_submitted');
$botApprovalReason = (string)($user['bot_approval_reason'] ?? '');
$reviewRequests = loadJson('verification_requests.json');
$myWebsiteRequest = null;
$myBotRequest = null;
foreach ($reviewRequests as $request) {
    if ((int)($request['user_id'] ?? 0) !== (int)$user['id']) continue;
    if (($request['type'] ?? '') === 'website') $myWebsiteRequest = $request;
    if (($request['type'] ?? '') === 'bot') $myBotRequest = $request;
}

$totalTransactions = count($transactions);
$paidTransactions = 0;
$totalPaid = 0;

foreach ($transactions as $tx) {
    if (in_array($tx['status'], ['CONFIRMED', 'SUCCESS', 'PAID'], true)) {
        $paidTransactions++;
        $totalPaid += (int)$tx['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0,  viewport-fit=cover">
    <title>GRAMPAY — Личный кабинет</title>

    <script>
        const savedTheme = localStorage.getItem('grampay_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html { scroll-behavior: smooth; }

        :root[data-theme="dark"] {
            --bg:#05070d;
            --surface:rgba(14,19,32,.72);
            --surface2:rgba(255,255,255,.045);
            --surface-hover:rgba(255,255,255,.075);
            --border:rgba(255,255,255,.09);
            --border-strong:rgba(255,255,255,.15);
            --text:#f8fafc;
            --secondary:#cbd5e1;
            --muted:#7f8ba3;
            --primary:#6366f1;
            --cyan:#22d3ee;
            --green:#34d399;
            --red:#fb7185;
        }

        :root[data-theme="light"] {
            --bg:#f3f6fb;
            --surface:rgba(255,255,255,.78);
            --surface2:rgba(15,23,42,.035);
            --surface-hover:rgba(15,23,42,.06);
            --border:rgba(15,23,42,.08);
            --border-strong:rgba(15,23,42,.13);
            --text:#0f172a;
            --secondary:#334155;
            --muted:#64748b;
            --primary:#4f46e5;
            --cyan:#0891b2;
            --green:#059669;
            --red:#e11d48;
        }

        body {
            margin:0;
            min-height:100vh;
            color:var(--text);
            background:
                radial-gradient(circle at 10% -10%,rgba(99,102,241,.19),transparent 33%),
                radial-gradient(circle at 100% 25%,rgba(34,211,238,.10),transparent 27%),
                var(--bg);
            font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            padding-bottom:40px;
        }

        a { color:inherit; }

        .layout {
            display:grid;
            grid-template-columns:250px minmax(0,1fr);
            min-height:100vh;
        }

        .sidebar {
            position:sticky;
            top:0;
            height:100vh;
            padding:20px 15px;
            border-right:1px solid var(--border);
            background:rgba(8,12,21,.58);
            backdrop-filter:blur(28px);
        }

        :root[data-theme="light"] .sidebar { background:rgba(255,255,255,.68); }

        .brand {
            display:flex;
            align-items:center;
            gap:11px;
            padding:5px 9px 28px;
            text-decoration:none;
        }

        .brand-icon {
            width:42px;
            height:42px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:13px;
            color:#fff;
            background:linear-gradient(135deg,#6366f1,#22d3ee);
            box-shadow:0 12px 35px rgba(99,102,241,.3);
        }

        .brand-name { font-size:18px; font-weight:900; letter-spacing:-.4px; }
        .brand-subtitle { display:block; color:var(--muted); font-size:14px; font-weight:700; letter-spacing:1px; }

        .nav-label {
            padding:8px 10px;
            color:var(--muted);
            font-size:14px;
            font-weight:850;
            text-transform:uppercase;
            letter-spacing:1.3px;
        }

        .side-nav { display:flex; flex-direction:column; gap:4px; }

        .side-link {
            display:flex;
            align-items:center;
            gap:11px;
            padding:11px 12px;
            border-radius:12px;
            text-decoration:none;
            color:var(--secondary);
            font-size:12px;
            font-weight:700;
            transition:.2s;
        }

        .side-link i { width:18px; color:var(--muted); }
        .side-link:hover { background:var(--surface-hover); color:var(--text); transform:translateX(2px); }
        .side-link.active { background:linear-gradient(135deg,rgba(99,102,241,.20),rgba(34,211,238,.06)); color:#fff; }
        .side-link.active i { color:#a5b4fc; }

        .side-bottom { position:absolute; left:15px; right:15px; bottom:18px; }
        .account-box { padding:13px; border:1px solid var(--border); border-radius:15px; background:var(--surface); }
        .avatar { width:38px; height:38px; display:flex; align-items:center; justify-content:center; border-radius:11px; color:#fff; background:linear-gradient(135deg,#6366f1,#8b5cf6); font-weight:900; }

        .main { min-width:0; padding:30px; }

        .topbar {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            margin-bottom:26px;
        }

        .page-title { margin:0; font-size:28px; font-weight:900; letter-spacing:-.7px; }
        .page-description { margin-top:4px; color:var(--muted); font-size:12px; }
        .top-actions { display:flex; gap:8px; }

        .icon-button {
            width:42px;
            height:42px;
            display:flex;
            align-items:center;
            justify-content:center;
            border:1px solid var(--border);
            border-radius:12px;
            background:var(--surface);
            color:var(--text);
            text-decoration:none;
            transition:.2s;
        }

        .icon-button:hover { background:var(--surface-hover); transform:translateY(-2px); }
        .docs-button { color:#a5b4fc; }
        .docs-button:hover { border-color:rgba(99,102,241,.35); color:#c7d2fe; }

        .hero {
            position:relative;
            overflow:hidden;
            min-height:215px;
            padding:27px;
            border:1px solid var(--border-strong);
            border-radius:24px;
            background:linear-gradient(135deg,rgba(99,102,241,.16),rgba(34,211,238,.045)),var(--surface);
            backdrop-filter:blur(25px);
            box-shadow:0 30px 80px rgba(0,0,0,.24);
        }

        .hero::after {
            content:"";
            position:absolute;
            width:270px;
            height:270px;
            right:-90px;
            top:-120px;
            border-radius:50%;
            background:radial-gradient(circle,rgba(34,211,238,.18),transparent 70%);
        }

        .hero-label { color:var(--muted); font-size:14px; font-weight:800; text-transform:uppercase; letter-spacing:1px; }
        .balance { margin-top:8px; font-size:clamp(38px,4vw,54px); line-height:1; font-weight:900; letter-spacing:-2px; }
        .balance small { font-size:17px; color:var(--muted); letter-spacing:0; }
        .hero-meta { display:flex; align-items:center; gap:8px; margin-top:13px; color:var(--secondary); font-size:14px; }
        .online-dot { width:8px; height:8px; border-radius:50%; background:var(--green); box-shadow:0 0 0 5px rgba(52,211,153,.10); }

        .hero-button {
            position:absolute;
            right:27px;
            bottom:27px;
            z-index:2;
            padding:11px 16px;
            border:0;
            border-radius:12px;
            color:#fff;
            background:linear-gradient(135deg,#6366f1,#4f46e5);
            font-size:14px;
            font-weight:800;
            text-decoration:none;
            box-shadow:0 12px 28px rgba(79,70,229,.27);
        }

        .stats { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-top:14px; }
        .stat { padding:17px; border:1px solid var(--border); border-radius:17px; background:var(--surface); backdrop-filter:blur(20px); }
        .stat-icon { width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:11px; background:var(--surface2); color:#a5b4fc; }
        .stat-label { margin-top:13px; color:var(--muted); font-size:14px; font-weight:800; }
        .stat-value { margin-top:3px; font-size:21px; font-weight:900; }

        .card {
            border:1px solid var(--border);
            border-radius:20px;
            background:var(--surface);
            color:var(--text);
            overflow:hidden;
            backdrop-filter:blur(25px);
            box-shadow:0 25px 70px rgba(0,0,0,.16);
        }

        .card-header-custom { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:19px 21px; border-bottom:1px solid var(--border); }
        .card-title { display:flex; align-items:center; gap:10px; margin:0; font-size:14px; font-weight:850; }
        .title-icon { width:34px; height:34px; display:flex; align-items:center; justify-content:center; border-radius:10px; background:rgba(99,102,241,.12); color:#a5b4fc; }
        .card-body-custom { padding:21px; }

        .form-label-custom { margin-bottom:7px; color:var(--secondary); font-size:14px; font-weight:800; }
        .form-control,.form-select { min-height:48px; border:1px solid var(--border)!important; border-radius:12px!important; color:var(--text)!important; background:rgba(0,0,0,.16)!important; box-shadow:none!important; }
        :root[data-theme="light"] .form-control,:root[data-theme="light"] .form-select { background:rgba(255,255,255,.65)!important; }
        .form-control:focus,.form-select:focus { border-color:rgba(99,102,241,.65)!important; box-shadow:0 0 0 4px rgba(99,102,241,.09)!important; }
        .form-select option { background:#111827; color:#fff; }

        .primary-button { width:100%; min-height:48px; border:0; border-radius:12px; color:#fff; background:linear-gradient(135deg,#6366f1,#4f46e5); font-size:12px; font-weight:800; transition:.2s; }
        .primary-button:hover { transform:translateY(-2px); box-shadow:0 13px 28px rgba(79,70,229,.25); }

        .transactions-wrap { overflow-x:auto; }
        .transactions { width:100%; min-width:560px; border-collapse:collapse; }
        .transactions th { padding:12px 18px; text-align:left; color:var(--muted); font-size:14px; letter-spacing:.9px; text-transform:uppercase; border-bottom:1px solid var(--border); }
        .transactions td { padding:14px 18px; color:var(--secondary); font-size:14px; border-bottom:1px solid var(--border); }
        .transactions tr:last-child td { border-bottom:0; }
        .transactions tbody tr:hover { background:rgba(255,255,255,.025); }
        .amount { color:var(--text)!important; font-weight:800; }

        .status { display:inline-flex; align-items:center; gap:6px; padding:6px 9px; border-radius:8px; font-size:14px; font-weight:850; }
        .status-paid { color:#6ee7b7; background:rgba(16,185,129,.09); border:1px solid rgba(16,185,129,.17); }
        .status-failed { color:#fda4af; background:rgba(244,63,94,.09); border:1px solid rgba(244,63,94,.17); }

        .verification-box { padding:18px; border:1px solid rgba(251,113,133,.20); border-radius:15px; background:rgba(244,63,94,.045); }
        .verification-box.success { border-color:rgba(52,211,153,.20); background:rgba(52,211,153,.045); }
        .steps { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-top:16px; }
        .step { padding:13px; border:1px solid var(--border); border-radius:12px; background:var(--surface2); }
        .step-num { width:25px; height:25px; display:flex; align-items:center; justify-content:center; border-radius:8px; background:rgba(99,102,241,.13); color:#a5b4fc; font-size:14px; font-weight:900; margin-bottom:8px; }
        .step-text { color:var(--secondary); font-size:14px; line-height:1.5; }
        code { display:inline-block; max-width:100%; margin-top:5px; padding:5px 7px; border-radius:6px; background:#080b12; color:#a5f3fc; word-break:break-all; font-size:14px; }

        .settings-grid { margin-top:0; }
        .outline-button { min-height:40px; padding:0 13px; border:1px solid var(--border); border-radius:10px; background:var(--surface2); color:var(--secondary); font-size:14px; font-weight:800; }
        .danger-button { min-height:36px; padding:0 11px; border:1px solid rgba(251,113,133,.2); border-radius:10px; background:rgba(244,63,94,.07); color:#fb7185; font-size:14px; font-weight:850; }
        .api-row { display:flex; gap:8px; }
        .copy-button { min-width:105px; border:1px solid rgba(99,102,241,.25); border-radius:11px; background:rgba(99,102,241,.12); color:#a5b4fc; font-size:14px; font-weight:850; }

        .review-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        .review-panel { padding:18px; border:1px solid var(--border); border-radius:16px; background:var(--surface2); height:100%; }
        .review-head { display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .review-title { font-size:12px; font-weight:900; }
        .review-desc { margin-top:5px; color:var(--muted); font-size:14px; line-height:1.5; }
        .review-form { margin-top:13px; display:flex; gap:8px; flex-direction:column; }
        .mini-status { display:inline-flex; align-items:center; padding:6px 9px; border-radius:8px; font-size:14px; font-weight:850; }
        @media(max-width:760px) { .review-grid { grid-template-columns:1fr; } }

        /* Documentation section */
        .docs-card {
            position:relative;
            overflow:hidden;
            margin-top:16px;
            border:1px solid rgba(99,102,241,.20);
            border-radius:20px;
            background:
                linear-gradient(135deg,rgba(99,102,241,.12),rgba(34,211,238,.04)),
                var(--surface);
        }

        .docs-card::before {
            content:"";
            position:absolute;
            width:210px;
            height:210px;
            right:-85px;
            top:-90px;
            border-radius:50%;
            background:radial-gradient(circle,rgba(34,211,238,.16),transparent 70%);
        }

        .docs-content { position:relative; z-index:1; display:flex; align-items:center; justify-content:space-between; gap:20px; padding:23px; }
        .docs-icon { width:46px; height:46px; flex:0 0 auto; display:flex; align-items:center; justify-content:center; border-radius:13px; color:#c7d2fe; background:rgba(99,102,241,.14); font-size:21px; }
        .docs-title { margin:0; font-size:15px; font-weight:900; }
        .docs-text { margin:5px 0 0; color:var(--muted); font-size:14px; line-height:1.55; max-width:660px; }
        .docs-link { display:inline-flex; align-items:center; gap:8px; flex:0 0 auto; padding:11px 15px; border-radius:11px; color:#fff; background:linear-gradient(135deg,#6366f1,#4f46e5); text-decoration:none; font-size:14px; font-weight:850; box-shadow:0 10px 25px rgba(79,70,229,.25); }

        .footer-note { margin-top:20px; color:var(--muted); text-align:center; font-size:14px; }

        @media(max-width:1000px) {
            .layout { grid-template-columns:78px minmax(0,1fr); }
            .brand { justify-content:center; padding-inline:0; }
            .brand > div:last-child,.nav-label,.side-link span,.account-box { display:none; }
            .side-link { justify-content:center; }
            .side-link i { width:auto; font-size:17px; }
        }

        @media(max-width:760px) {
            .layout { display:block; }
            .sidebar { position:fixed; z-index:100; left:0; right:0; bottom:0; top:auto; width:100%; height:auto; padding:7px 10px; border-right:0; border-top:1px solid var(--border); background:rgba(8,12,21,.90); }
            :root[data-theme="light"] .sidebar { background:rgba(255,255,255,.92); }
            .brand,.nav-label,.side-bottom { display:none; }
            .side-nav { flex-direction:row; justify-content:space-around; gap:4px; }
            .side-link { flex:1; flex-direction:column; gap:3px; padding:8px 3px; font-size:14px; }
            .side-link span { display:block; }
            .side-link i { font-size:16px; }
            .main { padding:16px 14px 88px; }
            .topbar { margin-bottom:20px; }
            .page-title { font-size:22px; }
            .page-description { font-size:14px; }
            .hero { border-radius:19px; padding:22px; }
            .hero-button { left:22px; right:auto; bottom:21px; }
            .stats { grid-template-columns:1fr; }
            .steps { grid-template-columns:1fr; }
            .docs-content { flex-direction:column; align-items:flex-start; }
            .docs-link { width:100%; justify-content:center; }
        }

        @media(max-width:480px) {
            .logout-btn { display:none; }
            .hero { min-height:205px; }
            .api-row { flex-direction:column; }
            .copy-button { min-height:44px; }
        }
    </style>
<link rel="stylesheet" href="assets/interface.css?v=20260922">
<script src="assets/interface.js?v=20260922" defer></script>
</head>
<body class="gp-panel">

<div class="layout">

    <aside class="sidebar">
        <a href="index.php" class="brand">
            <div class="brand-icon"><i class="bi bi-lightning-charge-fill"></i></div>
            <div>
                <div class="brand-name">GRAMPAY</div>
                <span class="brand-subtitle">PAYMENT SYSTEM</span>
            </div>
        </a>

        <div class="nav-label">Кабинет</div>
        <nav class="side-nav">
            <a href="#dashboard" class="side-link active"><i class="bi bi-grid-1x2-fill"></i><span>Обзор</span></a>
            <a href="#transactions" class="side-link"><i class="bi bi-receipt"></i><span>Транзакции</span></a>
            <a href="#verification" class="side-link"><i class="bi bi-shield-check"></i><span>Верификация</span></a>
            <a href="#settings" class="side-link"><i class="bi bi-sliders2"></i><span>Настройки</span></a>
            <a href="#integration-review" class="side-link"><i class="bi bi-clipboard2-check"></i><span>Проверка</span></a>
            <a href="#account-tools" class="side-link"><i class="bi bi-person-circle"></i><span>Аккаунт</span></a>
            <a href="2fa.php" class="side-link"><i class="bi bi-shield-lock"></i><span>2FA</span></a>
            <a href="docs.php" class="side-link"><i class="bi bi-book"></i><span>Документация</span></a>
        </nav>

        <div class="side-bottom">
            <div class="account-box">
                <div class="d-flex align-items-center">
                    <div class="avatar">
                        <?= htmlspecialchars(mb_strtoupper(mb_substr((string)$user['username'], 0, 1))) ?>
                    </div>
                    <div class="ms-2 overflow-hidden">
                        <div class="fw-bold text-truncate" style="font-size:12px;"><?= htmlspecialchars($user['username']) ?></div>
                        <div style="font-size:14px;color:var(--muted);">ID #<?= (int)$user['id'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <main class="main">

        <div class="topbar">
            <div>
                <h1 class="page-title">Панель управления</h1>
                <div class="page-description">Платежи, API и настройки GRAMPAY в одном месте</div>
            </div>

            <div class="top-actions">
                <a href="docs.php" class="icon-button docs-button" title="Документация">
                    <i class="bi bi-book"></i>
                </a>
                <button class="icon-button" onclick="toggleTheme()" title="Сменить тему">
                    <i class="bi bi-moon-stars-fill"></i>
                </button>
                <a href="logout.php" class="icon-button logout-btn" title="Выход">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>

        <section id="dashboard">
            <div class="hero">
                <div class="hero-label">Текущий баланс</div>
                <div class="balance"><?= (int)$user['balance'] ?> <small>RUB</small></div>
                <div class="hero-meta">
                    <span class="online-dot"></span>
                    Аккаунт активен
                    <span style="opacity:.35;">•</span>
                    #<?= (int)$user['id'] ?>
                </div>
                <a href="#deposit" class="hero-button"><i class="bi bi-plus-lg me-1"></i>Пополнить</a>
            </div>

            <div class="stats">
                <div class="stat">
                    <div class="stat-icon"><i class="bi bi-arrow-left-right"></i></div>
                    <div class="stat-label">ТРАНЗАКЦИЙ</div>
                    <div class="stat-value"><?= $totalTransactions ?></div>
                </div>
                <div class="stat">
                    <div class="stat-icon" style="color:#34d399;"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="stat-label">УСПЕШНЫХ</div>
                    <div class="stat-value"><?= $paidTransactions ?></div>
                </div>
                <div class="stat">
                    <div class="stat-icon" style="color:#22d3ee;"><i class="bi bi-cash-stack"></i></div>
                    <div class="stat-label">ОПЛАЧЕНО</div>
                    <div class="stat-value"><?= $totalPaid ?> ₽</div>
                </div>
            </div>
        </section>

        <div class="row g-4 mt-1" id="deposit">
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header-custom">
                        <h2 class="card-title"><span class="title-icon"><i class="bi bi-wallet2"></i></span>Пополнение</h2>
                    </div>
                    <div class="card-body-custom">
                        <form action="process_deposit.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label-custom">СУММА</label>
                                <input type="number" name="amount" class="form-control" value="1" min="1" required>
                            </div>
                            <button class="primary-button" type="submit"><i class="bi bi-lightning-charge-fill me-1"></i>Создать счет</button>
                        </form>
                        <div class="mt-3" style="color:var(--muted);font-size:14px;line-height:1.5;">
                            <i class="bi bi-shield-lock me-1"></i>Все платежи проходят через защищенную систему GRAMPAY.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8" id="transactions">
                <div class="card h-100">
                    <div class="card-header-custom">
                        <h2 class="card-title"><span class="title-icon"><i class="bi bi-clock-history"></i></span>Последние транзакции</h2>
                        <span style="font-size:14px;color:var(--muted);"><?= $totalTransactions ?> записей</span>
                    </div>
                    <div class="transactions-wrap">
                        <table class="transactions">
                            <thead><tr><th>Дата</th><th>Метод</th><th>Сумма</th><th>Статус</th></tr></thead>
                            <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr><td colspan="4" class="text-center py-5" style="color:var(--muted);">Пока нет транзакций</td></tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $tx): ?>
                                    <tr>
                                        <td style="white-space:nowrap;"><?= date('d.m.Y H:i', strtotime((string)$tx['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($config['payment_methods'][$tx['payment_method']] ?? 'Метод') ?></td>
                                        <td class="amount"><?= (int)$tx['amount'] ?> ₽</td>
                                        <td>
                                            <?php if (in_array($tx['status'], ['CONFIRMED','SUCCESS','PAID'], true)): ?>
                                                <span class="status status-paid">ОПЛАЧЕН</span>
                                            <?php else: ?>
                                                <span class="status status-failed"><?= htmlspecialchars((string)$tx['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <section id="verification" class="card mt-4">
            <div class="card-header-custom">
                <h2 class="card-title"><span class="title-icon"><i class="bi bi-globe2"></i></span>Верификация домена</h2>
                <?php if ($isVerified): ?>
                    <span class="status status-paid">ПОДТВЕРЖДЕНО</span>
                <?php else: ?>
                    <span class="status status-failed">ТРЕБУЕТСЯ</span>
                <?php endif; ?>
            </div>
            <div class="card-body-custom">
                <?php if (isset($_GET['verify']) && $_GET['verify'] === 'error'): ?>
                    <div class="alert alert-danger py-2 small">Файл не найден или содержимое не совпадает.</div>
                <?php elseif (isset($_GET['verify']) && $_GET['verify'] === 'success'): ?>
                    <div class="alert alert-success py-2 small">Домен успешно подтвержден. API разблокирован.</div>
                <?php endif; ?>

                <?php if ($isVerified): ?>
                    <div class="verification-box success">
                        <strong><i class="bi bi-check-circle me-1"></i>Домен подтвержден</strong>
                        <div class="mt-2" style="font-size:14px;color:var(--secondary);">
                            <?= htmlspecialchars($verifiedDomain) ?> успешно прошел проверку. Доступ к API разрешен.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="verification-box">
                        <strong><i class="bi bi-exclamation-circle me-1"></i>API-запросы пока заблокированы</strong>
                        <p class="mt-2 mb-0" style="color:var(--muted);font-size:14px;line-height:1.55;">
                            Подтвердите владение сайтом, чтобы открыть возможность создания счетов через API.
                        </p>

                        <div class="steps">
                            <div class="step"><div class="step-num">01</div><div class="step-text">Создайте файл <strong><?= htmlspecialchars($verifyFileName) ?></strong> в корне сайта.</div></div>
                            <div class="step"><div class="step-num">02</div><div class="step-text">Вставьте значение:<br><code><?= htmlspecialchars($verifyFileContent) ?></code></div></div>
                            <div class="step"><div class="step-num">03</div><div class="step-text">Введите домен и запустите проверку.</div></div>
                        </div>

                        <form action="verify_domain.php" method="POST" class="mt-3">
                            <div class="row g-2" style="max-width:700px;">
                                <div class="col"><input type="text" name="domain" class="form-control" placeholder="example.com" required></div>
                                <div class="col-auto"><button type="submit" class="primary-button" style="width:auto;padding:0 20px;"><i class="bi bi-shield-check me-1"></i>Проверить</button></div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section id="settings" class="row g-4 mt-1">
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header-custom">
                        <h2 class="card-title"><span class="title-icon"><i class="bi bi-link-45deg"></i></span>URL уведомлений</h2>
                    </div>
                    <div class="card-body-custom">
                        <form action="save_settings.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label-custom">RETURN URL</label>
                                <input type="url" name="default_return_url" class="form-control" value="<?= htmlspecialchars($user['default_return_url'] ?? '') ?>" placeholder="https://example.com/success">
                            </div>
                            <button class="outline-button" type="submit"><i class="bi bi-save2 me-1"></i>Сохранить изменения</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header-custom">
                        <h2 class="card-title"><span class="title-icon"><i class="bi bi-key-fill"></i></span>API-ключ</h2>
                        <form action="generate_key.php" method="POST" onsubmit="return confirm('Перевыпустить API-ключ? Старый ключ перестанет работать.');">
                            <button class="danger-button"><i class="bi bi-arrow-repeat me-1"></i>Перевыпустить</button>
                        </form>
                    </div>
                    <div class="card-body-custom">
                        <label class="form-label-custom">ВАШ СЕКРЕТНЫЙ КЛЮЧ</label>
                        <div class="api-row">
                            <input type="text" id="apiKeyField" class="form-control font-monospace" value="<?= htmlspecialchars($apiKey) ?>" readonly>
                            <button class="copy-button" type="button" onclick="copyKey()"><i class="bi bi-copy me-1"></i>Копировать</button>
                        </div>
                        <div class="mt-2" style="color:var(--muted);font-size:14px;"><i class="bi bi-lock-fill me-1"></i>Никому не передавайте этот ключ.</div>
                    </div>
                </div>
            </div>
        </section>

        <section id="integration-review" class="card mt-4">
            <div class="card-header-custom">
                <div>
                    <h2 class="card-title"><span class="title-icon"><i class="bi bi-clipboard2-check"></i></span>Подключение и проверка</h2>
                    <div style="margin-top:5px;color:var(--muted);font-size:14px;">Администратор проверяет сайт или Telegram-бота. Для бота подтверждение домена не требуется.</div>
                </div>
            </div>
            <div class="card-body-custom">
                <div class="review-grid">
                    <div class="review-panel">
                        <div class="review-head">
                            <div class="review-title"><i class="bi bi-globe2 me-2" style="color:#22d3ee;"></i>Сайт</div>
                            <span class="mini-status <?= $websiteApprovalStatus === 'approved' ? 'status-paid' : ($websiteApprovalStatus === 'rejected' ? 'status-failed' : '') ?>" style="<?= $websiteApprovalStatus === 'pending' ? 'background:rgba(251,191,36,.1);color:#fbbf24;border:1px solid rgba(251,191,36,.2);' : '' ?>"><?= $websiteApprovalStatus === 'approved' ? 'ОДОБРЕНО' : ($websiteApprovalStatus === 'rejected' ? 'ОТКЛОНЕНО' : ($websiteApprovalStatus === 'pending' ? 'НА ПРОВЕРКЕ' : 'НЕ ПОДАНО')) ?></span>
                        </div>
                        <div class="review-desc">Сайт проходит обычную проверку владения и отдельное решение администратора.</div>
                        <?php if ($websiteApprovalReason): ?><div style="margin-top:10px;font-size:14px;color:var(--secondary);"><b>Причина:</b> <?= nl2br(htmlspecialchars($websiteApprovalReason)) ?></div><?php endif; ?>
                        <form action="submit_review.php" method="POST" class="review-form">
                            <input type="hidden" name="type" value="website">
                            <input type="url" name="target" class="form-control" placeholder="https://example.com" value="<?= htmlspecialchars((string)($myWebsiteRequest['domain'] ?? '')) ?>" required>
                            <textarea name="description" class="form-control" rows="2" placeholder="Кратко о сайте / проекте"><?= htmlspecialchars((string)($myWebsiteRequest['description'] ?? '')) ?></textarea>
                            <button type="submit" class="primary-button"><i class="bi bi-send me-1"></i><?= $websiteApprovalStatus === 'pending' ? 'Обновить заявку' : 'Отправить на проверку' ?></button>
                        </form>
                    </div>

                    <div class="review-panel">
                        <div class="review-head">
                            <div class="review-title"><i class="bi bi-telegram me-2" style="color:#38bdf8;"></i>Telegram-бот</div>
                            <span class="mini-status <?= $botApprovalStatus === 'approved' ? 'status-paid' : ($botApprovalStatus === 'rejected' ? 'status-failed' : '') ?>" style="<?= $botApprovalStatus === 'pending' ? 'background:rgba(251,191,36,.1);color:#fbbf24;border:1px solid rgba(251,191,36,.2);' : '' ?>"><?= $botApprovalStatus === 'approved' ? 'ОДОБРЕНО' : ($botApprovalStatus === 'rejected' ? 'ОТКЛОНЕНО' : ($botApprovalStatus === 'pending' ? 'НА ПРОВЕРКЕ' : 'НЕ ПОДАНО')) ?></span>
                        </div>
                        <div class="review-desc">Бота можно зарегистрировать без верификации сайта. Укажите username бота без @.</div>
                        <?php if ($botApprovalReason): ?><div style="margin-top:10px;font-size:14px;color:var(--secondary);"><b>Причина:</b> <?= nl2br(htmlspecialchars($botApprovalReason)) ?></div><?php endif; ?>
                        <form action="submit_review.php" method="POST" class="review-form">
                            <input type="hidden" name="type" value="bot">
                            <input type="text" name="target" class="form-control" placeholder="my_payment_bot" pattern="[A-Za-z0-9_]{5,32}" value="<?= htmlspecialchars((string)($myBotRequest['bot_username'] ?? '')) ?>" required>
                            <textarea name="description" class="form-control" rows="2" placeholder="Что делает бот / где используется"><?= htmlspecialchars((string)($myBotRequest['description'] ?? '')) ?></textarea>
                            <button type="submit" class="primary-button"><i class="bi bi-send me-1"></i><?= $botApprovalStatus === 'pending' ? 'Обновить заявку' : 'Отправить бота на проверку' ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- docs.php добавлен непосредственно в индекс как отдельный блок -->
        <section id="documentation" class="docs-card">
            <div class="docs-content">
                <div class="d-flex align-items-center gap-3">
                    <div class="docs-icon"><i class="bi bi-book-half"></i></div>
                    <div>
                        <h2 class="docs-title">Документация API</h2>
                        <p class="docs-text">
                            Полное описание интеграции GRAMPAY: авторизация, создание платежей,
                            статусы транзакций, подписки, ошибки и готовые примеры кода.
                        </p>
                    </div>
                </div>
                <a href="docs.php" class="docs-link">
                    Открыть docs.php
                    <i class="bi bi-arrow-up-right"></i>
                </a>
            </div>
        </section>

        <section id="account-tools" style="margin-top:20px;">
            <div class="card">
                <div class="card-header-custom">
                    <div>
                        <h2 class="card-title"><span class="title-icon"><i class="bi bi-person-gear"></i></span>Аккаунт и безопасность</h2>
                        <div style="margin-top:5px;color:var(--muted);font-size:14px;">Управление профилем, Email, паролем и двухфакторной защитой.</div>
                    </div>
                    <?php if (!empty($user['email'])): ?>
                        <span class="status status-paid"><i class="bi bi-envelope-check me-1"></i><?= htmlspecialchars((string)$user['email']) ?></span>
                    <?php else: ?>
                        <span class="status status-failed"><i class="bi bi-envelope-exclamation me-1"></i>Email не указан</span>
                    <?php endif; ?>
                </div>
                <div class="card-body-custom">
                    <?php if (isset($_GET['email']) && $_GET['email'] === 'sent'): ?>
                        <div class="alert alert-info py-2 small" style="border-radius:12px;background:rgba(34,211,238,.08);border-color:rgba(34,211,238,.18);color:var(--secondary);">Код подтверждения отправлен на новый Email. Проверьте входящие и перейдите по ссылке подтверждения.</div>
                    <?php elseif (isset($_GET['email']) && $_GET['email'] === 'error'): ?>
                        <div class="alert alert-danger py-2 small">Не удалось отправить письмо. Проверьте адрес и настройки почты на сервере.</div>
                    <?php elseif (isset($_GET['email']) && $_GET['email'] === 'confirmed'): ?>
                        <div class="alert alert-success py-2 small">Email успешно подтверждён и привязан к аккаунту.</div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-12 col-lg-7">
                            <div style="padding:17px;border:1px solid var(--border);border-radius:16px;background:var(--surface2);height:100%;">
                                <div style="font-size:12px;font-weight:850;"><i class="bi bi-envelope-at me-2" style="color:#a5b4fc;"></i>Смена Email</div>
                                <div style="margin-top:5px;color:var(--muted);font-size:14px;line-height:1.5;">Новый адрес не сохранится сразу. Сначала отправим на него одноразовый код на 10 минут.</div>
                                <form action="request_email_change.php" method="POST" class="mt-3">
                                    <div class="d-flex gap-2 flex-column flex-sm-row">
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars((string)($user['email'] ?? '')) ?>" placeholder="new@example.com" autocomplete="email" required>
                                        <button class="primary-button" type="submit" style="width:auto;padding:0 18px;white-space:nowrap;"><i class="bi bi-send me-1"></i>Отправить код</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="col-12 col-lg-5">
                            <div style="padding:17px;border:1px solid var(--border);border-radius:16px;background:var(--surface2);height:100%;">
                                <div style="font-size:12px;font-weight:850;margin-bottom:10px;"><i class="bi bi-shield-lock me-2" style="color:#22d3ee;"></i>Защита аккаунта</div>
                                <div style="display:flex;flex-direction:column;gap:8px;">
                                    <a href="account.php" class="outline-button" style="text-decoration:none;display:flex;align-items:center;justify-content:space-between;">Профиль <i class="bi bi-chevron-right"></i></a>
                                    <a href="change_password.php" class="outline-button" style="text-decoration:none;display:flex;align-items:center;justify-content:space-between;">Сменить пароль <i class="bi bi-chevron-right"></i></a>
                                    <a href="2fa.php" class="outline-button" style="text-decoration:none;display:flex;align-items:center;justify-content:space-between;">2FA <span><?= !empty($user['two_factor_enabled']) ? 'ВКЛ' : 'ВЫКЛ' ?></span></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="footer-note">
            GRAMPAY · Личный кабинет · API Documentation
        </div>

    </main>
</div>

<script>
    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || 'dark';
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('grampay_theme', next);
    }

    async function copyKey() {
        const field = document.getElementById('apiKeyField');
        const button = document.querySelector('.copy-button');

        try {
            await navigator.clipboard.writeText(field.value);
        } catch (e) {
            field.select();
            if (!document.execCommand('copy')) {
                window.gpCopyMessage(button, 'Не удалось скопировать. Скопируйте выделенный ключ вручную.');
                return;
            }
        }

        const old = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check-lg me-1"></i>Скопировано';
        setTimeout(() => button.innerHTML = old, 1800);
    }

    const sideLinks = document.querySelectorAll('.side-link');
    sideLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (link.getAttribute('href')?.startsWith('#')) {
                sideLinks.forEach(x => x.classList.remove('active'));
                link.classList.add('active');
            }
        });
    });
</script>
</body>
</html>

