<?php declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';

$user = getCurrentUser();
$apiKey = !empty($user['api_key']) ? $user['api_key'] : 'gp_sec_ВАШ_КЛЮЧ';
?>
<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Документация API — GRAMPAY</title>
    <script>
        const savedTheme = localStorage.getItem('grampay_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <style>
        * { box-sizing: border-box; font-family: Inter, monospace, sans-serif; }
        :root[data-theme="dark"] { --bg:#05070d; --surface:rgba(14,19,32,.72); --border:rgba(255,255,255,.09); --text:#f8fafc; --muted:#7f8ba3; --code-bg:rgba(0,0,0,.3); --code-color:#38bdf8; }
        :root[data-theme="light"] { --bg:#f3f6fb; --surface:rgba(255,255,255,.78); --border:rgba(15,23,42,.08); --text:#0f172a; --muted:#64748b; --code-bg:rgba(255,255,255,.5); --code-color:#0284c7; }
        body { margin:0; min-height:100vh; color:var(--text); font-family:Inter,sans-serif; padding-bottom:60px; background:radial-gradient(circle at 10% -10%,rgba(99,102,241,.19),transparent 33%),radial-gradient(circle at 100% 25%,rgba(34,211,238,.10),transparent 27%),var(--bg); }
        .navbar { display:flex; justify-content:space-between; align-items:center; padding:15px 30px; background:var(--surface); border-bottom:1px solid var(--border); backdrop-filter:blur(25px); position:sticky; top:0; z-index:100; }
        .logo { font-size:20px; font-weight:900; color:var(--text); text-decoration:none; display:flex; align-items:center; gap:8px; }
        .nav-links { display:flex; gap:12px; }
        .btn-nav { background:rgba(255,255,255,.05); border:1px solid var(--border); color:var(--text); padding:8px 16px; border-radius:10px; font-size:12px; font-weight:700; cursor:pointer; text-decoration:none; }
        .btn-nav:hover { background:rgba(255,255,255,.1); }
        
        .container { max-width:900px; margin:40px auto; padding:0 20px; }
        .card { background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:30px; margin-bottom:24px; backdrop-filter:blur(25px); box-shadow:0 25px 70px rgba(0,0,0,.16); }
        .shelf { font-size:10px; font-weight:800; color:#38bdf8; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px; }
        h1, h4 { margin:0 0 15px 0; }
        h4 { display:flex; align-items:center; gap:10px; font-size:18px; font-weight:800; }
        p { color:var(--muted); font-size:13px; line-height:1.6; margin:0 0 15px 0; }
        
        .badge { font-family:monospace; font-size:11px; padding:4px 8px; border-radius:8px; font-weight:800; }
        .badge.post { background:rgba(34,197,94,.15); color:#4ade80; border:1px solid rgba(34,197,94,.3); }
        .badge.get { background:rgba(56,189,248,.15); color:#38bdf8; border:1px solid rgba(56,189,248,.3); }

        .table-wrap { border:1px solid var(--border); border-radius:12px; overflow:hidden; margin-bottom:20px; }
        table { width:100%; border-collapse:collapse; text-align:left; }
        th, td { padding:12px 15px; border-bottom:1px solid var(--border); font-size:12px; }
        th { background:rgba(0,0,0,.1); color:var(--muted); font-size:10px; text-transform:uppercase; font-weight:800; }
        td code { font-family:monospace; color:#a5b4fc; background:rgba(99,102,241,.1); padding:2px 6px; border-radius:4px; }
        
        .code-box { position:relative; background:var(--code-bg); border:1px solid var(--border); border-radius:12px; padding:16px; margin-bottom:20px; overflow-x:auto; }
        .code-box code { font-family:monospace; font-size:13px; color:var(--code-color); white-space:pre-wrap; }
        .copy-btn { position:absolute; top:10px; right:10px; background:rgba(255,255,255,.1); border:1px solid var(--border); color:var(--text); padding:5px 10px; font-size:10px; border-radius:6px; cursor:pointer; font-weight:700; }
        .copy-btn:hover { background:rgba(255,255,255,.2); }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">⚡ GRAMPAY API</a>
        <div class="nav-links">
            <button class="btn-nav" onclick="toggleTheme()">🌓 Тема</button>
            <a href="index.php" class="btn-nav">Личный кабинет</a>
        </div>
    </nav>

    <div class="container">
        <div style="margin-bottom: 30px;">
            <h1 style="font-weight:900; font-size:32px;">Документация (v1)</h1>
            <p>Базовый URL: <code>https://grampay.net/api/v1/</code> | Формат: <code>JSON</code></p>
        </div>

        <!-- Авторизация -->
        <div class="card">
            <div class="shelf">Базовые параметры</div>
            <h4>Авторизация запросов</h4>
            <p>Передавайте ваш секретный ключ в заголовке <code>X-API-Key</code> при каждом запросе.</p>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Заголовок</th><th>Значение</th></tr></thead>
                    <tbody>
                        <tr><td><code>X-API-Key</code></td><td><code><?= htmlspecialchars($apiKey) ?></code></td></tr>
                        <tr><td><code>Content-Type</code></td><td><code>application/json</code></td></tr>
                    </tbody>
                </table>
            </div>
            <p style="font-size:11px;">* Минимальная сумма платежа: <strong>1 RUB</strong>. API доступен после подачи заявки; проверка сайта не является обязательным условием для Telegram-бота.</p>
        </div>

        <!-- Создание платежа -->
        <div class="card">
            <div class="shelf">Полка 1</div>
            <h4><span class="badge post">POST</span> /create_invoice.php</h4>
            <p>Создаёт счёт и возвращает ссылку на страницу оплаты <code>payment_url</code>.</p>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Параметр</th><th>Тип</th><th>Обязат.</th><th>Описание</th></tr></thead>
                    <tbody>
                        <tr><td><code>amount</code></td><td>Int</td><td>Да</td><td>Сумма (в рублях)</td></tr>
                        <tr><td><code>payment_method</code></td><td>Int</td><td>Нет</td><td>2: СБП, 11: Карты, 13: USDT</td></tr>
                        <tr><td><code>return_url</code></td><td>Str</td><td>Нет</td><td>Куда вернуть при успехе</td></tr>
                        <tr><td><code>payload</code></td><td>Str</td><td>Нет</td><td>Ваш ID пользователя или заказа</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="code-box">
                <button class="copy-btn" onclick="copyCode(this)">Копировать</button>
                <code>curl -X POST https://grampay.net/api/v1/create_invoice.php \
  -H "X-API-Key: <?= htmlspecialchars($apiKey) ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100,
    "payment_method": 2,
    "return_url": "https://yoursite.com/success",
    "payload": "user_123"
  }'</code>
            </div>
        </div>


        <div class="card">
            <div class="shelf">Продакшен</div>
            <h4>Рекомендации по интеграции</h4>
            <p>Не считайте переход пользователя на <code>return_url</code> доказательством оплаты. После возврата запросите <code>get_status.php</code> с вашего backend и сопоставьте <code>invoice_id</code>, сумму и ваш <code>payload</code>/<code>order_id</code>.</p>
            <p>Храните API-ключ только на сервере. Для повторяемых запросов сохраняйте идентификатор заказа у себя, чтобы один заказ не был зачислен дважды.</p>
            <p>Текущая версия API использует обычные PHP endpoints. Это не меняет контракт: все ответы — JSON, а базовый URL остаётся <code>https://grampay.net/api/v1/</code>.</p>
        </div>
        <div class="card">
            <div class="shelf">Ошибки</div>
            <h4>Основные HTTP-ответы</h4>
            <div class="table-wrap"><table><thead><tr><th>Код</th><th>Значение</th></tr></thead><tbody>
            <tr><td><code>200</code></td><td>Запрос выполнен</td></tr>
            <tr><td><code>400</code></td><td>Неверные параметры запроса</td></tr>
            <tr><td><code>401</code></td><td>Не указан или неверен API-ключ</td></tr>
            <tr><td><code>404</code></td><td>Счёт не найден</td></tr>
            <tr><td><code>405</code></td><td>Неверный HTTP-метод</td></tr>
            <tr><td><code>502</code>/<code>500</code></td><td>Ошибка связи с внешним платёжным сервисом или сервера</td></tr>
            </tbody></table></div>
        </div>

        <div class="card">
            <div class="shelf">Webhook</div>
            <h4>Callback статуса платежа</h4>
            <p>Для автоматического уведомления merchant можно указать в Platega Callback URL: <code>https://grampay.net/api/platega_callback.php</code>. Platega отправляет POST с JSON и заголовками <code>X-MerchantId</code> и <code>X-Secret</code>. Сервис проверяет оба заголовка и обновляет внутренний статус транзакции.</p>
            <p>При подтверждении сервер также зачисляет сумму на баланс только один раз. Для внешнего магазина всё равно рекомендуется дополнительно сверять invoice ID, сумму и ваш order ID/payload.</p>
            <div class="code-box" style="margin-bottom:0;"><button class="copy-btn" onclick="copyCode(this)">Копировать</button><code>POST https://grampay.net/api/platega_callback.php
X-MerchantId: &lt;ваш merchant id&gt;
X-Secret: &lt;ваш secret&gt;
Content-Type: application/json

{
  "id": "UUID",
  "amount": 100,
  "currency": "RUB",
  "status": "CONFIRMED",
  "paymentMethod": 2
}</code></div>
        </div>

        <div class="card">
            <div class="shelf">Idempotency</div>
            <h4>Защита от повторного создания счёта</h4>
            <p>Передавайте уникальный <code>Idempotency-Key</code> на запрос создания счёта. Повтор запроса с тем же ключом для того же merchant возвращает уже созданный счёт вместо второго платежа.</p>
            <div class="code-box" style="margin-bottom:0;"><button class="copy-btn" onclick="copyCode(this)">Копировать</button><code>-H "Idempotency-Key: order-123-unique"</code></div>
        </div>

        <!-- Статус -->
        <div class="card">
            <div class="shelf">Полка 2</div>
            <h4><span class="badge get">GET</span> /get_status.php</h4>
            <p>Проверка статуса по номеру <code>invoice_id</code>, полученному при создании счёта.</p>
            <div class="code-box">
                <button class="copy-btn" onclick="copyCode(this)">Копировать</button>
                <code>curl -X GET "https://grampay.net/api/v1/get_status.php?invoice_id=UUID" \
  -H "X-API-Key: <?= htmlspecialchars($apiKey) ?>"</code>
            </div>
            <div class="code-box" style="margin-bottom:0;">
                <button class="copy-btn" onclick="copyCode(this)">Копировать</button>
                <code>{
  "ok": true,
  "invoice_id": "UUID",
  "status": "CONFIRMED",
  "amount": 100,
  "payload": "user_123"
}</code>
            </div>
        </div>
    </div>

    <script>
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('grampay_theme', next);
        }
        function copyCode(btn) {
            const code = btn.nextElementSibling.innerText;
            navigator.clipboard.writeText(code).then(() => {
                const old = btn.innerText;
                btn.innerText = '✓ Скопировано';
                setTimeout(() => btn.innerText = old, 1500);
            });
        }
    </script>
</body>
</html>