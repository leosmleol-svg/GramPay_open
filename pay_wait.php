<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/PlategaService.php';

$config = require __DIR__ . '/config.php';

// Платёжная страница должна открываться по ссылке API без авторизации в GRAMPAY.
// Авторизация остаётся нужна только для режима выбора способа оплаты из панели.
$user = getCurrentUser();
$txId = trim((string)($_GET['tx'] ?? $_GET['invoice_id'] ?? ''));
$tx = $txId !== '' ? findTransaction($txId) : null;

if ($tx) {
    $amount = (int)$tx['total_amount'];
    $transactionId = (string)$tx['transaction_id'];
    $redirectUrl = (string)($tx['redirect_url'] ?? '');
    $methodId = (int)($tx['payment_method'] ?? 0);
    $methodName = $config['payment_methods'][$methodId] ?? 'Оплата';

    $returnUrl = !empty($tx['return_url'])
        ? $tx['return_url']
        : 'https://grampay.net/index.php?payment=success';

    $failedUrl = !empty($tx['failed_url'])
        ? $tx['failed_url']
        : 'https://grampay.net/index.php?payment=fail';

    $mode = 'existing';
} else {
    $amount = (int)($_GET['amount'] ?? 0);

    if ($amount <= 0) {
        header('Location: panel.php');
        exit;
    }

    $transactionId = '';
    $redirectUrl = '';
    $methodId = 0;
    $methodName = '';
    $returnUrl = !empty($user['default_return_url'])
        ? $user['default_return_url']
        : ($config['platega']['return_url'] ?? 'https://grampay.net/index.php?payment=success');
    $failedUrl = !empty($user['default_failed_url'])
        ? $user['default_failed_url']
        : ($config['platega']['failed_url'] ?? 'https://grampay.net/index.php?payment=fail');

    $mode = 'select';
}

$methods = $config['payment_methods'] ?? [];
$minAmounts = $config['method_min_amounts'] ?? [];
$errorKey = trim((string)($_GET['error'] ?? ''));
$errorText = $errorKey === 'min' ? 'Для выбранного способа оплаты минимальная сумма не соблюдена.' : '';
?>

<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0,  viewport-fit=cover">
    <meta name="theme-color" content="#070a12">
    <title>Оплата — GRAMPAY</title>

    <script>
        (() => {
            const theme = localStorage.getItem('grampay_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <style>
        *{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
        html{min-height:100%;background:var(--bg)}
        body{margin:0;min-height:100dvh;color:var(--text);font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;background:radial-gradient(circle at 10% 0%,rgba(99,102,241,.18),transparent 30%),radial-gradient(circle at 100% 10%,rgba(34,211,238,.12),transparent 28%),var(--bg);overflow-x:hidden}

        :root[data-theme="dark"]{--bg:#070a12;--surface:#0d111c;--surface2:#111725;--surface3:#171d2b;--border:rgba(255,255,255,.08);--border2:rgba(255,255,255,.13);--text:#f8fafc;--muted:#8b98ad;--muted2:#64748b;--primary:#6366f1;--primary2:#818cf8;--cyan:#22d3ee;--green:#34d399;--red:#fb7185;--buttonText:#fff;--shadow:0 25px 80px rgba(0,0,0,.45)}
        :root[data-theme="light"]{--bg:#f5f7fb;--surface:#fff;--surface2:#f8fafc;--surface3:#eef2f7;--border:rgba(15,23,42,.08);--border2:rgba(15,23,42,.12);--text:#0f172a;--muted:#64748b;--muted2:#94a3b8;--primary:#4f46e5;--primary2:#6366f1;--cyan:#0891b2;--green:#059669;--red:#e11d48;--buttonText:#fff;--shadow:0 25px 70px rgba(15,23,42,.12)}

        .background{position:fixed;inset:0;overflow:hidden;pointer-events:none;z-index:-1}
        .orb{position:absolute;border-radius:50%;filter:blur(100px);opacity:.25}
        .orb.one{width:320px;height:320px;background:#6366f1;top:-180px;left:-150px}
        .orb.two{width:280px;height:280px;background:#22d3ee;right:-160px;top:25%}
        .orb.three{width:300px;height:300px;background:#ec4899;bottom:-200px;left:25%}

        .page{width:min(100%,1080px);min-height:100dvh;margin:auto;padding:30px 22px;display:flex;flex-direction:column}
        .header{display:flex;align-items:center;justify-content:space-between;gap:15px}
        .logo{display:flex;align-items:center;gap:10px;color:var(--text);text-decoration:none;font-size:18px;font-weight:900;letter-spacing:-.5px}
        .logo-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:12px;color:#fff;background:linear-gradient(135deg,#6366f1,#22d3ee);box-shadow:0 10px 30px rgba(99,102,241,.25)}
        .theme-button{height:38px;padding:0 12px;display:flex;align-items:center;gap:7px;border:1px solid var(--border);border-radius:11px;background:var(--surface2);color:var(--muted);font-size:14px;font-weight:700;cursor:pointer}

        .main{width:100%;flex:1;display:grid;grid-template-columns:1fr 440px;align-items:center;gap:70px;padding:55px 20px}
        .intro{max-width:540px}
        .badge{width:fit-content;display:flex;align-items:center;gap:7px;padding:7px 10px;border:1px solid rgba(99,102,241,.2);border-radius:9px;background:rgba(99,102,241,.07);color:var(--primary2);font-size:14px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase}
        .badge-dot{width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 0 4px rgba(52,211,153,.08)}
        .intro h1{margin:20px 0 0;font-size:clamp(42px,6vw,68px);line-height:.94;letter-spacing:-3px;font-weight:950}
        .intro h1 span{background:linear-gradient(90deg,#a5b4fc,#67e8f9);-webkit-background-clip:text;background-clip:text;color:transparent}
        .intro-text{max-width:500px;margin:20px 0 0;color:var(--muted);font-size:13px;line-height:1.7}
        .features{display:flex;flex-wrap:wrap;gap:8px;margin-top:25px}
        .feature{padding:8px 10px;border:1px solid var(--border);border-radius:9px;background:var(--surface2);color:var(--muted);font-size:14px;font-weight:700}.feature span{color:var(--green)}

        .payment-card{width:100%;padding:25px;border:1px solid var(--border2);border-radius:25px;background:linear-gradient(145deg,var(--surface),var(--surface2));box-shadow:var(--shadow)}
        .card-top{display:flex;justify-content:space-between;align-items:center}.card-label{color:var(--muted2);font-size:14px;font-weight:800;letter-spacing:1px;text-transform:uppercase}.secure-mini{display:flex;align-items:center;gap:5px;color:var(--green);font-size:14px;font-weight:750}
        .amount{margin-top:9px;font-size:53px;line-height:1;letter-spacing:-3px;font-weight:950}.currency{color:var(--muted);font-size:19px;letter-spacing:0}

        .details{margin-top:25px;border:1px solid var(--border);border-radius:16px;overflow:hidden}.detail{min-height:48px;padding:12px 14px;display:flex;align-items:center;justify-content:space-between;gap:20px;border-bottom:1px solid var(--border)}.detail:last-child{border-bottom:0}.detail-label{color:var(--muted);font-size:14px}.detail-value{max-width:60%;color:var(--text);text-align:right;font-size:14px;font-weight:750;overflow-wrap:anywhere}.mono{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:14px}

        .method-title{margin-top:22px;margin-bottom:9px;color:var(--muted);font-size:14px;font-weight:850;letter-spacing:.9px;text-transform:uppercase}
        .methods{display:grid;gap:8px}
        .method-option{position:relative}
        .method-option input{position:absolute;opacity:0;pointer-events:none}
        .method-label{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 14px;border:1px solid var(--border);border-radius:14px;background:var(--surface2);cursor:pointer;transition:.18s}
        .method-label:hover{border-color:rgba(99,102,241,.35);transform:translateY(-1px)}
        .method-option input:checked + .method-label{border-color:rgba(99,102,241,.65);background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(34,211,238,.05));box-shadow:0 0 0 3px rgba(99,102,241,.07)}
        .method-left{display:flex;align-items:center;gap:11px;min-width:0}.method-icon{width:36px;height:36px;flex:0 0 auto;display:grid;place-items:center;border-radius:11px;background:rgba(99,102,241,.12);font-size:17px}.method-name{font-size:14px;font-weight:800}.method-min{margin-top:3px;color:var(--muted2);font-size:14px}.method-right{color:var(--green);font-size:14px;font-weight:800;white-space:nowrap}.method-disabled{opacity:.45;cursor:not-allowed}.method-disabled:hover{transform:none;border-color:var(--border)}

        .pay-button{width:100%;min-height:56px;margin-top:17px;display:flex;align-items:center;justify-content:center;gap:10px;border:0;border-radius:15px;background:linear-gradient(135deg,var(--primary),#7c3aed);color:var(--buttonText);text-decoration:none;font-size:13px;font-weight:850;box-shadow:0 15px 35px rgba(99,102,241,.22);transition:transform .18s ease,box-shadow .18s ease;cursor:pointer}.pay-button:hover{transform:translateY(-2px);box-shadow:0 20px 45px rgba(99,102,241,.3)}.pay-button:active{transform:scale(.98)}.pay-button:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none}.arrow{font-size:17px}
        .status{min-height:44px;margin-top:10px;padding:10px 12px;display:flex;align-items:center;justify-content:center;gap:8px;border:1px solid var(--border);border-radius:12px;background:var(--surface2);color:var(--muted);font-size:14px;font-weight:650;text-align:center}.spinner{width:14px;height:14px;flex-shrink:0;border:2px solid var(--border2);border-top-color:var(--cyan);border-radius:50%;animation:spin .75s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}
        .card-footer{margin-top:14px;display:flex;align-items:center;justify-content:space-between;color:var(--muted2);font-size:14px}.online{display:flex;align-items:center;gap:5px}.online-dot{width:6px;height:6px;border-radius:50%;background:var(--green)}
        .success{padding:15px 0;text-align:center}.success-icon{width:72px;height:72px;margin:0 auto 17px;display:grid;place-items:center;border-radius:22px;background:rgba(52,211,153,.09);border:1px solid rgba(52,211,153,.18);color:var(--green);font-size:34px}.success h2{margin:0;font-size:24px;font-weight:900}.success p{margin:10px 0 0;color:var(--muted);font-size:14px;line-height:1.6}.timer{margin:15px 0;color:var(--muted);font-size:14px}.return-button{width:100%;min-height:50px;display:flex;align-items:center;justify-content:center;border-radius:13px;background:linear-gradient(135deg,var(--primary),#7c3aed);color:white;text-decoration:none;font-size:14px;font-weight:800}
        .footer{padding:15px;color:var(--muted2);text-align:center;font-size:14px}

        @media(max-width:900px){.page{padding:22px 16px}.main{grid-template-columns:1fr;max-width:560px;margin:0 auto;gap:35px;padding:45px 0 25px}.intro{max-width:100%;text-align:center}.badge{margin:auto}.intro-text{margin-left:auto;margin-right:auto}.features{justify-content:center}}
        @media(max-width:600px){body{background:radial-gradient(circle at 0% 0%,rgba(99,102,241,.16),transparent 45%),var(--bg)}.page{min-height:100dvh;padding:max(12px,env(safe-area-inset-top)) 12px max(12px,env(safe-area-inset-bottom))}.header{height:42px}.logo{font-size:16px}.logo-icon{width:34px;height:34px;border-radius:10px}.theme-button{height:34px;padding:0 9px;border-radius:10px;font-size:14px}.main{display:flex;flex-direction:column;gap:16px;padding:28px 0 15px}.payment-card{order:1;padding:18px;border-radius:21px}.intro{order:2;width:100%}.badge{display:none}.intro h1{margin-top:5px;font-size:27px;letter-spacing:-1.4px}.intro-text{margin-top:10px;font-size:14px;line-height:1.55}.features{margin-top:12px;gap:5px}.feature{padding:6px 8px;font-size:14px}.amount{font-size:46px;letter-spacing:-2.5px}.currency{font-size:16px}.details{margin-top:18px;border-radius:14px}.detail{min-height:44px;padding:10px 11px;gap:12px}.detail-label{font-size:14px}.detail-value{max-width:58%;font-size:14px}.mono{font-size:14px}.method-label{padding:12px}.method-icon{width:34px;height:34px}.method-name{font-size:14px}.method-min{font-size:14px}.method-right{font-size:14px}.pay-button{min-height:56px;margin-top:13px;border-radius:14px;font-size:13px}.status{min-height:42px;margin-top:8px;padding:9px;border-radius:11px;font-size:14px}.card-footer{margin-top:11px;font-size:7px}.footer{padding:8px 0;font-size:7px}}
        @media(max-width:380px){.page{padding-left:8px;padding-right:8px}.main{padding-top:20px}.payment-card{padding:15px;border-radius:18px}.amount{font-size:41px}.detail{min-height:42px}.detail-label,.detail-value{font-size:14px}.pay-button{min-height:53px}.status{font-size:7.5px}}
    </style>
<link rel="stylesheet" href="assets/interface.css?v=20260922">
<script src="assets/interface.js?v=20260922" defer></script>
</head>
<body class="gp-checkout">

<div class="background"><div class="orb one"></div><div class="orb two"></div><div class="orb three"></div></div>

<div class="page">
    <header class="header">
        <a href="https://grampay.net/" class="logo"><span class="logo-icon">⚡</span><span>GRAMPAY</span></a>
        <button type="button" class="theme-button" onclick="toggleTheme()"><span id="themeIcon">☀️</span><span id="themeText">Светлая</span></button>
    </header>

    <main class="main">
        <section class="intro">
            <div class="badge"><span class="badge-dot"></span>Secure checkout</div>
            <h1>Безопасная<br><span>оплата</span></h1>
            <p class="intro-text">Выберите удобный способ оплаты. После подтверждения мы автоматически проверим платёж и вернём вас на сайт.</p>
            <div class="features">
                <div class="feature"><span>✓</span> HTTPS</div>
                <div class="feature"><span>✓</span> Защищённый платёж</div>
                <div class="feature"><span>✓</span> Автопроверка</div>
            </div>
        </section>

        <section class="payment-card" id="cardContent">
            <div class="card-top">
                <div class="card-label">К оплате</div>
                <div class="secure-mini">🔒 Защищено</div>
            </div>

            <div class="amount">
                <?= $amount ?> <span class="currency">₽</span>
            </div>

            <div class="details">
                <div class="detail">
                    <span class="detail-label">Сумма</span>
                    <span class="detail-value"><?= $amount ?> ₽</span>
                </div>
                <div class="detail">
                    <span class="detail-label">Валюта</span>
                    <span class="detail-value">RUB</span>
                </div>
                <div class="detail" id="selectedMethodRow" <?= $mode === 'select' ? 'style="display:none"' : '' ?>>
                    <span class="detail-label">Способ оплаты</span>
                    <span class="detail-value" id="selectedMethodName"><?= htmlspecialchars($methodName) ?></span>
                </div>
                <?php if ($mode === 'existing'): ?>
                    <div class="detail">
                        <span class="detail-label">Номер счёта</span>
                        <span class="detail-value mono"><?= htmlspecialchars(substr($transactionId, 0, 16)) ?>...</span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($mode === 'select'): ?>
                <div class="method-title">Выберите способ оплаты</div>
                <div class="methods">
                    <?php foreach ($methods as $id => $name):
                        $id = (int)$id;
                        $min = (int)($minAmounts[$id] ?? 1);
                        $available = $amount >= $min;
                        $lower = mb_strtolower((string)$name);
                        if (str_contains($lower, 'крип')) {
                            $icon = '₿';
                        } elseif (str_contains($lower, 'qr') || str_contains($lower, 'сбп')) {
                            $icon = '▣';
                        } elseif (str_contains($lower, 'card') || str_contains($lower, 'карт')) {
                            $icon = '▤';
                        } else {
                            $icon = '₽';
                        }
                    ?>
                        <div class="method-option">
                            <input type="radio" name="payment_method" id="method_<?= $id ?>" value="<?= $id ?>" data-name="<?= htmlspecialchars((string)$name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-min="<?= $min ?>" <?= !$available ? 'disabled' : '' ?> onchange="selectMethod(this)">
                            <label for="method_<?= $id ?>" class="method-label <?= !$available ? 'method-disabled' : '' ?>">
                                <span class="method-left">
                                    <span class="method-icon"><?= $icon ?></span>
                                    <span>
                                        <span class="method-name"><?= htmlspecialchars((string)$name) ?></span>
                                        <span class="method-min">Минимум: <?= $min ?> ₽ · Валюта RUB</span>
                                    </span>
                                </span>
                                <span class="method-right"><?= $available ? 'Доступен' : 'Недоступен' ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($errorText !== ''): ?>
                    <div class="status" style="margin-top:12px;color:var(--red)">✕ <?= htmlspecialchars($errorText) ?></div>
                <?php endif; ?>

                <button type="button" class="pay-button" id="createButton" onclick="createPayment()" disabled>
                    Выбрать способ и продолжить <span class="arrow">→</span>
                </button>
            <?php else: ?>
                <a href="<?= htmlspecialchars($redirectUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="pay-button">
                    Перейти к оплате <span class="arrow">→</span>
                </a>

                <div class="status" id="statusBox"><div class="spinner"></div><span>Ожидаем подтверждения платежа</span></div>
            <?php endif; ?>

            <div class="card-footer">
                <span class="online"><span class="online-dot"></span>Система работает</span>
                <span>Проверка каждые 5 сек.</span>
            </div>
        </section>
    </main>

    <footer class="footer">GRAMPAY • Безопасная обработка платежей</footer>
</div>

<script>
function updateThemeUI(theme){
    const icon=document.getElementById('themeIcon');
    const text=document.getElementById('themeText');
    if(theme==='dark'){icon.textContent='☀️';text.textContent='Светлая'}else{icon.textContent='🌙';text.textContent='Тёмная'}
}

function toggleTheme(){
    const current=document.documentElement.getAttribute('data-theme')||'dark';
    const next=current==='dark'?'light':'dark';
    document.documentElement.setAttribute('data-theme',next);
    localStorage.setItem('grampay_theme',next);
    updateThemeUI(next);
}

updateThemeUI(document.documentElement.getAttribute('data-theme'));

<?php if ($mode === 'select'): ?>
let selectedMethod = null;
const amount = <?= json_encode($amount) ?>;

function selectMethod(input){
    if (!input || input.disabled) return;
    selectedMethod = {
        id: Number(input.value),
        name: input.dataset.name || '',
        min: Number(input.dataset.min || 1)
    };

    const row=document.getElementById('selectedMethodRow');
    const name=document.getElementById('selectedMethodName');
    const btn=document.getElementById('createButton');

    if(row) row.style.display='flex';
    if(name) name.textContent=selectedMethod.name + ' · RUB';
    if(btn){
        btn.disabled=false;
        btn.innerHTML='Перейти к оплате <span class="arrow">→</span>';
    }
}

async function createPayment(){
    if(!selectedMethod) return;

    const btn=document.getElementById('createButton');
    btn.disabled=true;
    btn.innerHTML='<span class="spinner"></span> Создаём платёж…';

    const form=document.createElement('form');
    form.method='POST';
    form.action='process_deposit.php';

    const fields={
        amount:String(amount),
        payment_method:String(selectedMethod.id)
    };

    Object.entries(fields).forEach(([name,value])=>{
        const input=document.createElement('input');
        input.type='hidden';
        input.name=name;
        input.value=value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}
<?php else: ?>
const txId=<?= json_encode($transactionId, JSON_UNESCAPED_UNICODE) ?>;
const returnUrl=<?= json_encode($returnUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const checkUrl='api/check_payment.php?transaction_id='+encodeURIComponent(txId)+'&t=';
const failedUrl=<?= json_encode($failedUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
let handled=false;

function showSuccessScreen(){
    if(handled)return;
    handled=true;
    let seconds=3;
    const card=document.getElementById('cardContent');
    card.innerHTML=`<div class="success"><div class="success-icon">✓</div><h2>Оплата подтверждена</h2><p>Платёж успешно проведён. Сейчас вернём вас на сайт.</p><div class="timer">Перенаправление через <strong id="timer">${seconds}</strong> сек.</div><a href="${returnUrl}" class="return-button">Вернуться на сайт</a></div>`;
    const interval=setInterval(()=>{
        seconds--;
        const timer=document.getElementById('timer');
        if(timer)timer.textContent=seconds;
        if(seconds<=0){clearInterval(interval);window.location.href=returnUrl;}
    },1000);
}

function showFailed(){
    if(handled)return;
    handled=true;
    const box=document.getElementById('statusBox');
    if(box){box.innerHTML='<span style="color:var(--red);font-weight:800">✕ Счёт отменён или срок действия истёк</span>'}
    setTimeout(()=>{window.location.href=failedUrl},1500);
}

async function checkStatus(){
    try{
        const response=await fetch(checkUrl+Date.now(),{cache:'no-store',headers:{'Accept':'application/json'}});
        if(!response.ok) throw new Error('HTTP '+response.status);
        const data=await response.json();
        const statusBox=document.getElementById('statusBox');
        const status=String(data.status||'PENDING').toUpperCase();
        if(statusBox){
            if(status==='PENDING') statusBox.innerHTML='<span class="spinner"></span> Ожидаем подтверждение платежа…';
            else if(['CONFIRMED','SUCCESS','PAID','COMPLETED'].includes(status)) statusBox.innerHTML='<span style="color:var(--green);font-weight:800">✓ Платёж подтверждён</span>';
            else if(['FAILED','EXPIRED','CANCELED'].includes(status)) statusBox.innerHTML='<span style="color:var(--red);font-weight:800">✕ Счёт отменён или истёк</span>';
        }
        if(['CONFIRMED','SUCCESS','PAID','COMPLETED'].includes(status))showSuccessScreen();
        else if(['FAILED','EXPIRED','CANCELED'].includes(status))showFailed();
    }catch(error){console.error('Payment status error:',error)}
}

checkStatus();
setInterval(checkStatus,3000);
<?php endif; ?>
</script>

</body>
</html>

