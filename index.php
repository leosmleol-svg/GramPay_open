<?php

declare(strict_types=1);

/*
 * GRAMPAY public landing page.
 * Dashboard/account area: panel.php
 */

session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
$isLoggedIn = !empty($_SESSION['user_id']);
$accountUrl = $isLoggedIn ? 'panel.php' : 'register.php';
$accountText = $isLoggedIn ? 'Открыть панель' : 'Подключить GRAMPAY';
?>
<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#070913">
    <meta name="description" content="GRAMPAY — платёжная платформа для сайтов, приложений и Telegram-ботов. Создавайте счета, принимайте оплату и проверяйте статусы через API.">
    <link rel="canonical" href="https://grampay.net/">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://grampay.net/">
    <meta property="og:title" content="GRAMPAY — платежи для сайтов и ботов">
    <meta property="og:description" content="Счета, платёжные ссылки и API для автоматизации платежей.">
    <title>GRAMPAY — Платежи для сайтов, приложений и ботов</title>

    <script>
        (() => {
            const saved = localStorage.getItem('grampay_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            color: var(--text);
            background: var(--bg);
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; }

        :root[data-theme="dark"] {
            --bg: #070913;
            --bg-soft: #0b0f1b;
            --surface: rgba(15, 20, 34, .72);
            --surface-solid: #101522;
            --surface-2: rgba(255,255,255,.045);
            --surface-hover: rgba(255,255,255,.075);
            --border: rgba(255,255,255,.085);
            --border-strong: rgba(255,255,255,.14);
            --text: #f8fafc;
            --text-2: #cbd5e1;
            --muted: #8491a7;
            --muted-2: #64748b;
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --cyan: #22d3ee;
            --green: #34d399;
            --shadow: 0 35px 100px rgba(0,0,0,.28);
            --nav-bg: rgba(7,9,19,.72);
        }

        :root[data-theme="light"] {
            --bg: #f6f8fc;
            --bg-soft: #eef2f8;
            --surface: rgba(255,255,255,.78);
            --surface-solid: #fff;
            --surface-2: rgba(15,23,42,.035);
            --surface-hover: rgba(15,23,42,.065);
            --border: rgba(15,23,42,.08);
            --border-strong: rgba(15,23,42,.13);
            --text: #0f172a;
            --text-2: #334155;
            --muted: #64748b;
            --muted-2: #94a3b8;
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --cyan: #0891b2;
            --green: #059669;
            --shadow: 0 30px 80px rgba(15,23,42,.10);
            --nav-bg: rgba(246,248,252,.76);
        }

        .page { min-height: 100vh; overflow: hidden; }
        .container { width: min(1180px, calc(100% - 40px)); margin: 0 auto; }

        /* background */
        .bg { position: fixed; inset: 0; pointer-events: none; z-index: -2; overflow: hidden; }
        .grid {
            position: absolute; inset: 0;
            opacity: .45;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 68px 68px;
            mask-image: linear-gradient(to bottom, black, transparent 74%);
        }
        .glow { position: absolute; border-radius: 50%; filter: blur(100px); opacity: .22; }
        .glow.one { width: 520px; height: 520px; background: #6366f1; top: -300px; left: -180px; }
        .glow.two { width: 430px; height: 430px; background: #22d3ee; top: 240px; right: -230px; }
        .glow.three { width: 420px; height: 420px; background: #8b5cf6; top: 900px; left: 28%; opacity: .12; }

        /* nav */
        .nav-wrap { position: sticky; top: 0; z-index: 30; padding: 14px 0; }
        .nav {
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 0 10px 0 15px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--nav-bg);
            backdrop-filter: blur(22px);
            box-shadow: 0 12px 40px rgba(0,0,0,.08);
        }
        .brand { display: inline-flex; align-items: center; gap: 10px; font-weight: 900; }
        .brand-icon {
            width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px;
            color: #fff; background: linear-gradient(135deg, #6366f1, #22d3ee);
            box-shadow: 0 10px 30px rgba(99,102,241,.28);
        }
        .brand-text { font-size: 17px; letter-spacing: -.5px; }
        .brand-sub { display: block; color: var(--muted); font-size: 7px; letter-spacing: 1.35px; margin-top: 1px; }
        .nav-links { display: flex; align-items: center; gap: 5px; }
        .nav-link { padding: 10px 11px; border-radius: 10px; color: var(--muted); font-size: 11px; font-weight: 700; }
        .nav-link:hover { background: var(--surface-hover); color: var(--text); }
        .nav-actions { display: flex; align-items: center; gap: 7px; }
        .theme-btn, .menu-btn {
            width: 40px; height: 40px; border: 1px solid var(--border); border-radius: 11px;
            background: var(--surface-2); color: var(--text-2); cursor: pointer;
        }
        .menu-btn { display: none; }
        .nav-cta {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            min-height: 40px; padding: 0 14px; border-radius: 11px;
            color: #fff; background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            font-size: 10px; font-weight: 800;
        }

        /* hero */
        .hero { padding: 92px 0 100px; }
        .hero-grid { display: grid; grid-template-columns: 1.1fr .9fr; align-items: center; gap: 70px; }
        .eyebrow {
            width: fit-content; display: inline-flex; align-items: center; gap: 8px;
            padding: 7px 10px; border: 1px solid rgba(99,102,241,.22); border-radius: 10px;
            background: rgba(99,102,241,.07); color: #a5b4fc; font-size: 9px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 1.2px;
        }
        .eyebrow-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green); box-shadow: 0 0 0 4px rgba(52,211,153,.09); }
        .hero h1 { margin: 19px 0 0; font-size: clamp(48px, 6.2vw, 80px); line-height: .96; letter-spacing: -4px; font-weight: 900; }
        .hero h1 span { background: linear-gradient(90deg, #a5b4fc, #67e8f9); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .hero-copy { max-width: 610px; margin: 23px 0 0; color: var(--muted); font-size: 15px; line-height: 1.75; }
        .hero-actions { display: flex; align-items: center; gap: 10px; margin-top: 29px; flex-wrap: wrap; }
        .btn-primary, .btn-secondary {
            min-height: 49px; padding: 0 17px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            border-radius: 13px; font-size: 11px; font-weight: 800;
        }
        .btn-primary { color: #fff; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); box-shadow: 0 15px 36px rgba(79,70,229,.23); }
        .btn-secondary { border: 1px solid var(--border); background: var(--surface); color: var(--text-2); }
        .btn-primary:hover, .btn-secondary:hover { transform: translateY(-2px); }
        .trust-row { display: flex; align-items: center; gap: 16px; margin-top: 25px; color: var(--muted-2); font-size: 9px; }
        .trust { display: inline-flex; align-items: center; gap: 5px; }
        .trust i { color: var(--green); }

        /* fake checkout visual */
        .hero-visual { position: relative; min-height: 470px; display: grid; place-items: center; }
        .visual-glow { position: absolute; width: 370px; height: 370px; border-radius: 50%; background: radial-gradient(circle, rgba(99,102,241,.24), transparent 68%); filter: blur(20px); }
        .checkout {
            position: relative; width: min(420px, 100%); padding: 18px; border: 1px solid var(--border-strong); border-radius: 24px;
            background: linear-gradient(145deg, rgba(17,23,38,.92), rgba(10,14,24,.88));
            box-shadow: var(--shadow); transform: rotate(1.2deg);
        }
        :root[data-theme="light"] .checkout { background: rgba(255,255,255,.91); }
        .checkout-head { display:flex; align-items:center; justify-content:space-between; padding-bottom:14px; border-bottom:1px solid var(--border); }
        .checkout-logo { display:flex; align-items:center; gap:7px; font-size:10px; font-weight:850; }
        .tiny-icon { width:25px; height:25px; display:grid; place-items:center; border-radius:7px; color:#fff; background:linear-gradient(135deg,#6366f1,#22d3ee); }
        .checkout-lock { color: var(--green); font-size: 8px; font-weight: 750; }
        .checkout-label { margin-top: 25px; color: var(--muted); font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .checkout-price { margin-top: 4px; font-size: 45px; letter-spacing: -2px; font-weight: 900; }
        .checkout-price small { color: var(--muted); font-size: 15px; letter-spacing: 0; }
        .method-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:7px; margin-top:18px; }
        .method { padding:11px 6px; border:1px solid var(--border); border-radius:10px; background:var(--surface-2); text-align:center; font-size:8px; font-weight:700; color:var(--text-2); }
        .method.active { border-color:rgba(99,102,241,.42); background:rgba(99,102,241,.11); color:#c7d2fe; }
        .checkout-button { margin-top:11px; min-height:46px; display:grid; place-items:center; border-radius:11px; color:#fff; background:linear-gradient(135deg,#6366f1,#4f46e5); font-size:10px; font-weight:800; }
        .float-card { position:absolute; padding:12px 13px; border:1px solid var(--border); border-radius:14px; background:var(--surface); backdrop-filter:blur(18px); box-shadow:0 18px 45px rgba(0,0,0,.15); }
        .float-card.one { left:-10px; bottom:70px; }
        .float-card.two { right:-15px; top:62px; }
        .float-label { color:var(--muted); font-size:7px; font-weight:800; text-transform:uppercase; letter-spacing:.8px; }
        .float-value { margin-top:4px; font-size:15px; font-weight:900; }
        .float-green { color:var(--green); }

        /* common sections */
        .section { padding: 92px 0; }
        .section.alt { background: linear-gradient(180deg, transparent, var(--bg-soft), transparent); }
        .section-head { max-width: 700px; margin-bottom: 34px; }
        .section-kicker { color: #818cf8; font-size: 9px; font-weight: 850; text-transform: uppercase; letter-spacing: 1.4px; }
        .section-title { margin: 9px 0 0; font-size: clamp(31px, 4vw, 49px); line-height: 1.02; letter-spacing: -2px; font-weight: 900; }
        .section-text { margin: 14px 0 0; color: var(--muted); font-size: 13px; line-height: 1.7; }

        /* methods */
        .methods { display:grid; grid-template-columns:repeat(4,1fr); gap:13px; }
        .method-card { padding:21px; min-height:185px; border:1px solid var(--border); border-radius:18px; background:var(--surface); transition:.2s; }
        .method-card:hover { transform:translateY(-4px); border-color:var(--border-strong); background:var(--surface-hover); }
        .method-icon { width:41px; height:41px; display:grid; place-items:center; border-radius:12px; background:rgba(99,102,241,.11); color:#a5b4fc; font-size:19px; }
        .method-card h3 { margin:17px 0 0; font-size:14px; }
        .method-card p { margin:7px 0 0; color:var(--muted); font-size:10px; line-height:1.6; }
        .method-note { margin-top:14px; color:#818cf8; font-size:9px; font-weight:750; }

        /* benefits */
        .benefits { display:grid; grid-template-columns:1.1fr .9fr; gap:15px; }
        .benefit-big, .benefit { border:1px solid var(--border); border-radius:20px; background:var(--surface); }
        .benefit-big { min-height:365px; padding:28px; position:relative; overflow:hidden; }
        .benefit-big::after { content:""; position:absolute; width:310px; height:310px; right:-150px; bottom:-170px; border-radius:50%; background:radial-gradient(circle,rgba(99,102,241,.18),transparent 69%); }
        .benefit-number { color:#a5b4fc; font-size:9px; font-weight:850; letter-spacing:1px; }
        .benefit-big h3 { max-width:550px; margin:15px 0 0; font-size:29px; letter-spacing:-1.1px; }
        .benefit-big p { max-width:540px; color:var(--muted); font-size:12px; line-height:1.7; }
        .benefit-mini-grid { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
        .benefit { padding:21px; }
        .benefit i { color:#a5b4fc; font-size:20px; }
        .benefit h4 { margin:15px 0 0; font-size:13px; }
        .benefit p { margin:6px 0 0; color:var(--muted); font-size:10px; line-height:1.55; }

        /* api */
        .api-box { position:relative; overflow:hidden; display:grid; grid-template-columns:1fr 1fr; gap:15px; padding:26px; border:1px solid rgba(99,102,241,.22); border-radius:22px; background:linear-gradient(135deg,rgba(99,102,241,.09),rgba(34,211,238,.025)),var(--surface); }
        .api-copy h3 { margin:9px 0 0; font-size:30px; letter-spacing:-1.3px; }
        .api-copy p { max-width:500px; color:var(--muted); font-size:12px; line-height:1.7; }
        .api-points { display:grid; gap:8px; margin-top:19px; }
        .api-point { display:flex; align-items:center; gap:8px; color:var(--text-2); font-size:10px; font-weight:650; }
        .api-point i { color:var(--green); }
        .code-window { min-height:300px; padding:17px; border:1px solid var(--border); border-radius:16px; background:#060911; color:#cbd5e1; font:10px/1.7 ui-monospace,SFMono-Regular,Consolas,monospace; box-shadow:inset 0 0 40px rgba(0,0,0,.2); overflow:auto; }
        .code-dim { color:#64748b; }
        .code-key { color:#a5b4fc; }
        .code-string { color:#67e8f9; }
        .code-value { color:#86efac; }

        /* how */
        .steps { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
        .step { padding:23px; border:1px solid var(--border); border-radius:18px; background:var(--surface); }
        .step-top { display:flex; align-items:center; justify-content:space-between; }
        .step-no { width:34px; height:34px; display:grid; place-items:center; border-radius:10px; background:rgba(99,102,241,.12); color:#a5b4fc; font-size:10px; font-weight:900; }
        .step i { color:var(--muted-2); }
        .step h3 { margin:19px 0 0; font-size:14px; }
        .step p { margin:7px 0 0; color:var(--muted); font-size:10px; line-height:1.65; }

        /* stats */
        .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
        .stat { padding:22px; border-top:1px solid var(--border); border-bottom:1px solid var(--border); text-align:center; }
        .stat-value { font-size:31px; font-weight:900; letter-spacing:-1px; }
        .stat-label { margin-top:5px; color:var(--muted); font-size:9px; font-weight:750; }

        /* faq */
        .faq { max-width:860px; display:grid; gap:8px; }
        details { border:1px solid var(--border); border-radius:14px; background:var(--surface); overflow:hidden; }
        summary { list-style:none; cursor:pointer; padding:17px 18px; display:flex; align-items:center; justify-content:space-between; gap:15px; font-size:11px; font-weight:800; }
        summary::-webkit-details-marker { display:none; }
        summary::after { content:'+'; color:var(--muted); font-size:16px; }
        details[open] summary::after { content:'−'; color:#a5b4fc; }
        details p { margin:0; padding:0 18px 18px; color:var(--muted); font-size:10px; line-height:1.65; }

        /* CTA */
        .cta { padding:70px 0 100px; }
        .cta-card { position:relative; overflow:hidden; padding:45px; text-align:center; border:1px solid var(--border-strong); border-radius:25px; background:linear-gradient(135deg,rgba(99,102,241,.17),rgba(34,211,238,.05)),var(--surface); box-shadow:var(--shadow); }
        .cta-card::before { content:""; position:absolute; width:400px; height:400px; left:-200px; top:-250px; border-radius:50%; background:radial-gradient(circle,rgba(99,102,241,.19),transparent 68%); }
        .cta-card h2 { position:relative; margin:0; font-size:clamp(31px,4vw,49px); letter-spacing:-2px; }
        .cta-card p { position:relative; max-width:620px; margin:13px auto 0; color:var(--muted); font-size:12px; line-height:1.7; }
        .cta-card .hero-actions { position:relative; justify-content:center; }

        /* footer */
        footer { padding:26px 0 35px; border-top:1px solid var(--border); }
        .footer-row { display:flex; align-items:center; justify-content:space-between; gap:20px; }
        .footer-copy { color:var(--muted-2); font-size:9px; }
        .footer-links { display:flex; gap:15px; }
        .footer-links a { color:var(--muted); font-size:9px; }
        .footer-links a:hover { color:var(--text); }

        /* responsive */
        @media (max-width: 980px) {
            .hero-grid { grid-template-columns:1fr; gap:35px; }
            .hero-copy { max-width:680px; }
            .hero-visual { min-height:420px; }
            .methods { grid-template-columns:repeat(2,1fr); }
            .benefits, .api-box { grid-template-columns:1fr; }
        }
        @media (max-width: 760px) {
            .container { width:min(100% - 24px, 600px); }
            .nav-wrap { padding-top:8px; }
            .nav { height:58px; border-radius:15px; }
            .nav-links { display:none; }
            .menu-btn { display:block; }
            .nav-actions .theme-btn { display:none; }
            .nav-cta { padding:0 11px; }
            .hero { padding:58px 0 70px; }
            .hero h1 { font-size:clamp(42px, 13vw, 62px); letter-spacing:-3px; }
            .hero-copy { font-size:13px; }
            .hero-visual { min-height:365px; }
            .checkout { max-width:370px; }
            .float-card.one { left:-1px; bottom:35px; }
            .float-card.two { right:-1px; top:28px; }
            .section { padding:68px 0; }
            .methods { grid-template-columns:1fr 1fr; gap:9px; }
            .method-card { min-height:165px; padding:16px; }
            .benefit-mini-grid { gap:9px; }
            .steps { grid-template-columns:1fr; }
            .stats { grid-template-columns:repeat(2,1fr); }
            .cta-card { padding:32px 20px; }
            .footer-row { flex-direction:column; text-align:center; }
        }
        @media (max-width: 460px) {
            .container { width:calc(100% - 18px); }
            .brand-sub { display:none; }
            .nav-cta { font-size:9px; }
            .hero-actions { flex-direction:column; align-items:stretch; }
            .btn-primary, .btn-secondary { width:100%; }
            .trust-row { flex-wrap:wrap; gap:8px 13px; }
            .hero-visual { min-height:315px; }
            .checkout { border-radius:19px; padding:14px; transform:none; }
            .checkout-price { font-size:39px; }
            .float-card { padding:9px 10px; }
            .float-card.one { left:-2px; }
            .float-card.two { right:-2px; }
            .float-value { font-size:12px; }
            .methods { grid-template-columns:1fr; }
            .method-card { min-height:auto; }
            .benefit-mini-grid { grid-template-columns:1fr; }
            .api-box { padding:18px; }
            .api-copy h3 { font-size:25px; }
            .code-window { min-height:260px; font-size:9px; }
            .stats { gap:7px; }
            .stat { padding:16px 8px; }
            .stat-value { font-size:25px; }
        }
    </style>

    <script type="application/ld+json">{"@context":"https://schema.org","@type":"WebSite","name":"GRAMPAY","url":"https://grampay.net/","description":"Платёжная платформа для сайтов, приложений и ботов."}</script>
</head>
<body>
<div class="page">
    <div class="bg">
        <div class="grid"></div>
        <div class="glow one"></div>
        <div class="glow two"></div>
        <div class="glow three"></div>
    </div>

    <div class="nav-wrap">
        <div class="container">
            <nav class="nav">
                <a href="index.php" class="brand">
                    <span class="brand-icon"><i class="bi bi-lightning-charge-fill"></i></span>
                    <span>
                        <span class="brand-text">GRAMPAY</span>
                        <span class="brand-sub">PAYMENT PLATFORM</span>
                    </span>
                </a>

                <div class="nav-links">
                    <a class="nav-link" href="#payments">Платежи</a>
                    <a class="nav-link" href="#advantages">Преимущества</a>
                    <a class="nav-link" href="#api">API</a>
                    <a class="nav-link" href="#how">Как начать</a>
                    <a class="nav-link" href="#faq">FAQ</a>
                    <a class="nav-link" href="pricing.php">Тарифы</a>
                </div>

                <div class="nav-actions">
                    <button class="theme-btn" type="button" onclick="toggleTheme()" aria-label="Сменить тему">
                        <i id="themeIcon" class="bi bi-sun-fill"></i>
                    </button>
                    <button class="menu-btn" type="button" onclick="toggleMobileMenu()" aria-label="Меню">
                        <i class="bi bi-list"></i>
                    </button>
                    <a class="nav-cta" href="<?= htmlspecialchars($accountUrl, ENT_QUOTES) ?>">
                        <?= htmlspecialchars($accountText) ?> <i class="bi bi-arrow-up-right"></i>
                    </a>
                </div>
            </nav>
        </div>
    </div>

    <main>
        <section class="hero">
            <div class="container hero-grid">
                <div>
                    <div class="eyebrow"><span class="eyebrow-dot"></span> Приём онлайн-платежей</div>
                    <h1>Платежи,<br><span>которые работают</span></h1>
                    <p class="hero-copy">
                        GRAMPAY помогает принимать оплату на сайте, в приложении и через ботов.
                        Создавайте счета, подключайте API и управляйте транзакциями из одной панели.
                    </p>
                    <div class="hero-actions">
                        <a class="btn-primary" href="<?= htmlspecialchars($accountUrl, ENT_QUOTES) ?>">
                            <?= htmlspecialchars($accountText) ?> <i class="bi bi-arrow-right"></i>
                        </a>
                        <a class="btn-secondary" href="docs.php">
                            <i class="bi bi-code-slash"></i> Документация API
                        </a>
                    </div>
                    <div class="trust-row">
                        <span class="trust"><i class="bi bi-check-circle-fill"></i> Быстрый старт</span>
                        <span class="trust"><i class="bi bi-check-circle-fill"></i> API для интеграции</span>
                        <span class="trust"><i class="bi bi-check-circle-fill"></i> Личный кабинет</span>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="visual-glow"></div>
                    <div class="checkout">
                        <div class="checkout-head">
                            <div class="checkout-logo"><span class="tiny-icon">⚡</span> GRAMPAY</div>
                            <div class="checkout-lock"><i class="bi bi-shield-lock-fill"></i> Защищённая оплата</div>
                        </div>
                        <div class="checkout-label">Сумма к оплате</div>
                        <div class="checkout-price">1 490 <small>₽</small></div>
                        <div class="method-grid">
                            <div class="method active">СБП</div>
                            <div class="method">QR-код</div>
                            <div class="method">Ссылка</div>
                        </div>
                        <div class="checkout-button">Перейти к оплате <i class="bi bi-arrow-up-right"></i></div>
                    </div>
                    <div class="float-card one">
                        <div class="float-label">Статус</div>
                        <div class="float-value float-green"><i class="bi bi-check-circle-fill"></i> Оплачено</div>
                    </div>
                    <div class="float-card two">
                        <div class="float-label">API</div>
                        <div class="float-value">Готово к работе</div>
                    </div>
                </div>
            </div>
        </section>

        <section id="payments" class="section alt">
            <div class="container">
                <div class="section-head">
                    <div class="section-kicker">Возможности</div>
                    <h2 class="section-title">Один сервис для ваших платежей</h2>
                    <p class="section-text">Создавайте удобный способ оплаты для клиентов и управляйте всеми операциями через GRAMPAY.</p>
                </div>

                <div class="methods">
                    <article class="method-card">
                        <div class="method-icon"><i class="bi bi-qr-code"></i></div>
                        <h3>СБП и QR</h3>
                        <p>Создавайте счета с переходом на страницу оплаты или используйте QR-сценарий.</p>
                        <div class="method-note">Быстрая оплата</div>
                    </article>
                    <article class="method-card">
                        <div class="method-icon"><i class="bi bi-link-45deg"></i></div>
                        <h3>Платёжные ссылки</h3>
                        <p>Отправляйте клиенту готовую ссылку на оплату через сайт, мессенджер или бота.</p>
                        <div class="method-note">Для продаж вне сайта</div>
                    </article>
                    <article class="method-card">
                        <div class="method-icon"><i class="bi bi-window-stack"></i></div>
                        <h3>Страница оплаты</h3>
                        <p>GRAMPAY показывает клиенту сумму, способ оплаты и автоматически отслеживает результат.</p>
                        <div class="method-note">Готовый checkout</div>
                    </article>
                    <article class="method-card">
                        <div class="method-icon"><i class="bi bi-hdd-network"></i></div>
                        <h3>API</h3>
                        <p>Создавайте транзакции программно и подключайте оплату к своему сайту, приложению или боту.</p>
                        <div class="method-note">Для разработчиков</div>
                    </article>
                </div>
            </div>
        </section>

        <section id="advantages" class="section">
            <div class="container">
                <div class="section-head">
                    <div class="section-kicker">Почему GRAMPAY</div>
                    <h2 class="section-title">Просто для клиента.<br>Мощно для бизнеса.</h2>
                </div>

                <div class="benefits">
                    <div class="benefit-big">
                        <div class="benefit-number">01 / ЕДИНАЯ ПАНЕЛЬ</div>
                        <h3>Все операции в одном месте</h3>
                        <p>
                            История транзакций, баланс, API-ключ, настройки URL и верификация домена —
                            без необходимости собирать инструменты по разным сервисам.
                        </p>
                        <div class="hero-actions">
                            <a class="btn-primary" href="<?= htmlspecialchars($accountUrl, ENT_QUOTES) ?>">Открыть GRAMPAY <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="benefit-mini-grid">
                        <div class="benefit">
                            <i class="bi bi-lightning-charge-fill"></i>
                            <h4>Быстрая интеграция</h4>
                            <p>Подключайте API и начинайте создавать счета без сложной платёжной формы.</p>
                        </div>
                        <div class="benefit">
                            <i class="bi bi-shield-check"></i>
                            <h4>Контроль безопасности</h4>
                            <p>Верификация сайта и отдельный API-ключ для работы интеграции.</p>
                        </div>
                        <div class="benefit">
                            <i class="bi bi-arrow-repeat"></i>
                            <h4>Статусы автоматически</h4>
                            <p>Следите за результатом операций и обрабатывайте успешные и отменённые платежи.</p>
                        </div>
                        <div class="benefit">
                            <i class="bi bi-phone"></i>
                            <h4>Адаптивный checkout</h4>
                            <p>Страница оплаты корректно работает на компьютерах и мобильных устройствах.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="api" class="section alt">
            <div class="container">
                <div class="api-box">
                    <div class="api-copy">
                        <div class="section-kicker">Для разработчиков</div>
                        <h3>Подключите GRAMPAY к своему продукту</h3>
                        <p>API позволяет создавать платежи из вашего backend и получать статус транзакции без ручной работы в панели.</p>
                        <div class="api-points">
                            <div class="api-point"><i class="bi bi-check-circle-fill"></i> JSON API</div>
                            <div class="api-point"><i class="bi bi-check-circle-fill"></i> HTTPS-запросы</div>
                            <div class="api-point"><i class="bi bi-check-circle-fill"></i> API-ключ в личном кабинете</div>
                            <div class="api-point"><i class="bi bi-check-circle-fill"></i> Документация и примеры</div>
                        </div>
                        <div class="hero-actions">
                            <a class="btn-primary" href="docs.php">Открыть документацию <i class="bi bi-arrow-up-right"></i></a>
                        </div>
                    </div>

                    <div class="code-window">
<span class="code-dim">POST</span> <span class="code-string">/api/transaction</span>

{
  <span class="code-key">"amount"</span>: <span class="code-value">1490</span>,
  <span class="code-key">"currency"</span>: <span class="code-string">"RUB"</span>,
  <span class="code-key">"description"</span>: <span class="code-string">"Заказ #1842"</span>,
  <span class="code-key">"return_url"</span>: <span class="code-string">"https://example.com/success"</span>
}

<span class="code-dim">// GRAMPAY возвращает данные счёта</span>
<span class="code-key">transaction_id</span>: <span class="code-string">"..."</span>
<span class="code-key">payment_url</span>: <span class="code-string">"https://grampay.net/pay_wait.php?tx=...&amp;mh=..."</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div class="section-kicker">Процесс</div>
                    <h2 class="section-title">Начать принимать платежи просто</h2>
                </div>

                <div id="how" class="steps">
                    <article class="step">
                        <div class="step-top">
                            <div class="step-no">01</div>
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <h3>Создайте аккаунт</h3>
                        <p>Зарегистрируйтесь в GRAMPAY и получите доступ к личному кабинету.</p>
                    </article>
                    <article class="step">
                        <div class="step-top">
                            <div class="step-no">02</div>
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h3>Выберите тип интеграции</h3>
                        <p>Для сайта можно подтвердить домен; Telegram-бот можно подключить без обязательной проверки сайта.</p>
                    </article>
                    <article class="step">
                        <div class="step-top">
                            <div class="step-no">03</div>
                            <i class="bi bi-code-slash"></i>
                        </div>
                        <h3>Подключите API</h3>
                        <p>Используйте API-ключ, создавайте счета и принимайте платежи в своём продукте.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section alt">
            <div class="container">
                <div class="stats">
                    <div class="stat"><div class="stat-value">24/7</div><div class="stat-label">доступ к панели</div></div>
                    <div class="stat"><div class="stat-value">API</div><div class="stat-label">для автоматизации</div></div>
                    <div class="stat"><div class="stat-value">1</div><div class="stat-label">единый кабинет</div></div>
                    <div class="stat"><div class="stat-value">5 сек</div><div class="stat-label">проверка статуса</div></div>
                </div>
            </div>
        </section>

        <section id="faq" class="section">
            <div class="container">
                <div class="section-head">
                    <div class="section-kicker">FAQ</div>
                    <h2 class="section-title">Частые вопросы</h2>
                </div>
                <div class="faq">
                    <details>
                        <summary>Что такое GRAMPAY?</summary>
                        <p>GRAMPAY — платёжная платформа для создания счетов, страниц оплаты и интеграции платежей через API.</p>
                    </details>
                    <details>
                        <summary>Можно ли подключить GRAMPAY к сайту или боту?</summary>
                        <p>Да. Для автоматизации используется API. Готовую документацию и параметры интеграции можно открыть в разделе документации.</p>
                    </details>
                    <details>
                        <summary>Где смотреть транзакции?</summary>
                        <p>Все доступные пользователю транзакции отображаются в личном кабинете GRAMPAY.</p>
                    </details>
                    <details>
                        <summary>Где взять API-ключ?</summary>
                        <p>После регистрации API-ключ доступен в личном кабинете. Для сайта подтверждение домена доступно как отдельный механизм. Для Telegram-бота обязательная проверка сайта не требуется.</p>
                    </details>
                    <details>
                        <summary>Есть ли мобильная версия?</summary>
                        <p>Да. Кабинет и страница оплаты адаптированы под мобильные устройства.</p>
                    </details>
                </div>
            </div>
        </section>

        <section class="cta">
            <div class="container">
                <div class="cta-card">
                    <div class="section-kicker">GRAMPAY</div>
                    <h2>Готовы подключить платежи?</h2>
                    <p>Создайте аккаунт, откройте панель и подключите свою первую интеграцию.</p>
                    <div class="hero-actions">
                        <a class="btn-primary" href="<?= htmlspecialchars($accountUrl, ENT_QUOTES) ?>">
                            <?= htmlspecialchars($accountText) ?> <i class="bi bi-arrow-right"></i>
                        </a>
                        <a class="btn-secondary" href="docs.php">Посмотреть API</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container footer-row">
            <div class="footer-copy">© <?= date('Y') ?> GRAMPAY · Payment Platform</div>
            <div class="footer-links">
                <a href="docs.php">Документация</a>
                <a href="pricing.php">Тарифы</a>
                <a href="security.php">Безопасность</a>
                <a href="legal.php">Документы</a>
                <a href="refunds.php">Возвраты</a>
                <a href="support.php">Поддержка</a>
                <a href="status.php">Статус</a>
                <a href="register.php">Регистрация</a>
                <a href="login.php">Войти</a>
            </div>
        </div>
    </footer>
</div>

<script>
    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || 'dark';
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('grampay_theme', next);
        updateThemeIcon(next);
    }

    function updateThemeIcon(theme) {
        const icon = document.getElementById('themeIcon');
        if (!icon) return;
        icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    }

    function toggleMobileMenu() {
        const links = document.querySelector('.nav-links');
        if (!links) return;
        const opened = links.dataset.open === '1';
        if (opened) {
            links.dataset.open = '0';
            links.style.display = '';
        } else {
            links.dataset.open = '1';
            links.style.display = 'flex';
            links.style.position = 'absolute';
            links.style.top = '70px';
            links.style.left = '0';
            links.style.right = '0';
            links.style.flexDirection = 'column';
            links.style.alignItems = 'stretch';
            links.style.padding = '8px';
            links.style.border = '1px solid var(--border)';
            links.style.borderRadius = '14px';
            links.style.background = 'var(--surface-solid)';
            links.style.boxShadow = 'var(--shadow)';
        }
    }

    updateThemeIcon(document.documentElement.getAttribute('data-theme'));
</script>
</body>
</html>
