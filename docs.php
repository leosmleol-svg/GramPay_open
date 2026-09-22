<?php declare(strict_types=1); ?>
<!doctype html><html lang="ru" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Документация GramPay API: создание счетов, статусы, баланс и интеграция с ботами."><title>GramPay API — документация</title>
<script>try { document.documentElement.dataset.theme=localStorage.getItem('grampay_theme')==='light'?'light':'dark'; } catch (_) {}</script>
<link rel="stylesheet" href="assets/api-docs.css?v=20260922"><script src="assets/api-docs.js?v=20260922" defer></script></head><body>
<a href="#main" class="skip">К документации</a><header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">G</span>GramPay <span class="brand-divider">/</span><span class="brand-label">Developers</span></a><div class="top-actions"><a href="panel.php">Личный кабинет</a><button id="theme" aria-label="Переключить тему" type="button">◐</button><button id="menu" aria-controls="sidebar" aria-expanded="false" type="button">Разделы</button></div></header>
<div class="docs-layout"><aside class="sidebar" id="sidebar"><div class="nav-heading">API Reference <span>v1</span></div><label class="search-label" for="search">Найти раздел</label><div class="search-box"><input id="search" type="search" placeholder="Название или endpoint…" autocomplete="off"><kbd>/</kbd></div><p id="search-empty" role="status" hidden>Ничего не найдено. Попробуйте другой запрос.</p><nav aria-label="Документация"><div class="nav-group"><p>Начало работы</p><a href="#overview" data-search="Обзор API  Начало работы"><span class="nav-symbol">•</span><span>Обзор API</span></a><a href="#authentication" data-search="Авторизация  Начало работы"><span class="nav-symbol">•</span><span>Авторизация</span></a></div><div class="nav-group"><p>Платежи</p><a href="#create-invoice" data-search="Создать счёт create_invoice.php Платежи"><span class="method post">POST</span><span>Создать счёт</span></a><a href="#payment-status" data-search="Проверить статус get_status.php Платежи"><span class="method get">GET</span><span>Проверить статус</span></a></div><div class="nav-group"><p>Аккаунт</p><a href="#balance" data-search="Получить баланс balance.php Аккаунт"><span class="method get">GET</span><span>Получить баланс</span></a><a href="#rotate-key" data-search="Перевыпустить API-ключ bot_regenerate_key.php Аккаунт"><span class="method post">POST</span><span>Перевыпустить API-ключ</span></a></div><div class="nav-group"><p>Интеграция с ботом</p><a href="#bot-login" data-search="Вход в аккаунт bot_login.php Интеграция с ботом"><span class="method post">POST</span><span>Вход в аккаунт</span></a><a href="#bot-register" data-search="Регистрация аккаунта bot_register.php Интеграция с ботом"><span class="method post">POST</span><span>Регистрация аккаунта</span></a></div><div class="nav-group"><p>Справочник</p><a href="#methods" data-search="Способы оплаты и суммы  Справочник"><span class="nav-symbol">•</span><span>Способы оплаты и суммы</span></a><a href="#statuses" data-search="Статусы платежа  Справочник"><span class="nav-symbol">•</span><span>Статусы платежа</span></a><a href="#errors" data-search="Ошибки и повторные запросы  Справочник"><span class="nav-symbol">•</span><span>Ошибки и повторные запросы</span></a></div><div class="nav-group"><p>Интеграции</p><a href="#cms" data-search="Сайты и CMS  Интеграции"><span class="nav-symbol">•</span><span>Сайты и CMS</span></a></div></nav><div class="sidebar-footer"><span>Базовый адрес</span><code>grampay.net/api/v1</code></div></aside>
<main id="main" tabindex="-1"><div class="page-toolbar"><span>GRAMPAY API · v1</span><button type="button" id="copy-link">Копировать ссылку</button></div><article id="overview" class="doc-page" data-title="Обзор API"><div class="breadcrumbs">Документация / Начало работы</div><h1>Обзор API</h1><p class="lead">Всё необходимое для подключения платежей к сайту или боту.</p><h2>Быстрый старт</h2><ol class="steps"><li><strong>Получите API-ключ</strong><p>Откройте <a href="panel.php#settings">личный кабинет</a> и найдите ключ аккаунта.</p></li><li><strong>Создайте счёт</strong><p>Передайте сумму и номер заказа в <a href="#create-invoice">create_invoice.php</a>.</p></li><li><strong>Направьте клиента на оплату</strong><p>Используйте <code>payment_url</code> из ответа.</p></li><li><strong>Проверьте результат</strong><p>Запросите <a href="#payment-status">get_status.php</a> и обработайте <code>CONFIRMED</code> один раз.</p></li></ol><h2>Базовый адрес</h2><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>https://grampay.net/api/v1/</code></pre></div><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>Формат</th><th>Значение</th></tr></thead><tbody><tr><td>Транспорт</td><td>HTTPS</td></tr><tr><td>Ответы</td><td>JSON, UTF-8</td></tr><tr><td>Сумма</td><td>Целое число в RUB</td></tr><tr><td>Авторизация</td><td>X-API-Key</td></tr><tr><td>Версия</td><td>v1</td></tr></tbody></table></div><aside class="callout">Примеры содержат демонстрационные значения. Интерфейс документации не отправляет платежи и не подставляет ваш настоящий ключ.</aside></article><article id="authentication" class="doc-page" data-title="Авторизация"><div class="breadcrumbs">Документация / Начало работы</div><h1>Авторизация</h1><p class="lead">Серверный доступ к аккаунту и платёжным методам.</p><h2>Заголовки запроса</h2><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>X-API-Key: YOUR_API_KEY
Content-Type: application/json</code></pre></div><p>Храните ключ на сервере. Для методов счёта, баланса и перевыпуска ключ определяет аккаунт. Методы входа и регистрации используют логин и пароль вместо API-ключа.</p><h2>Поддерживаемые варианты</h2><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>Способ</th><th>Поддержка</th></tr></thead><tbody><tr><td>X-API-Key</td><td>Рекомендуемый заголовок</td></tr><tr><td>X-ApiKey</td><td>Альтернативное написание</td></tr><tr><td>Authorization: Bearer YOUR_API_KEY</td><td>Поддерживается</td></tr><tr><td>api_key в query/form</td><td>Поддерживается, но не рекомендуется: URL может попасть в журналы</td></tr><tr><td>api_key в JSON</td><td>Поддерживается в create_invoice.php</td></tr></tbody></table></div><h2>Защита ключа</h2><p>Не передавайте секрет в браузерный код и не публикуйте его. После утечки выполните перевыпуск в кабинете и обновите серверные интеграции.</p></article><article id="methods" class="doc-page" data-title="Способы оплаты и суммы"><div class="breadcrumbs">Документация / Справочник</div><h1>Способы оплаты и суммы</h1><p class="lead">Идентификаторы методов, валюта и расчёт итоговой суммы.</p><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>ID</th><th>Название</th><th>Сумма запроса</th></tr></thead><tbody><tr><td>2</td><td>СБП (QR-код)</td><td>RUB</td></tr><tr><td>11</td><td>Карточный эквайринг (РФ)</td><td>RUB</td></tr><tr><td>13</td><td>Криптовалюта (USDT)</td><td>RUB</td></tr><tr><td>14</td><td>SberPay</td><td>RUB</td></tr></tbody></table></div><h2>Комиссия</h2><p>В текущем обработчике при сумме до 5 RUB включительно надбавка равна 0. При сумме больше 5 RUB надбавка составляет 5 RUB.</p><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>amount</th><th>fee</th><th>total_amount</th></tr></thead><tbody><tr><td>5</td><td>0</td><td>5</td></tr><tr><td>100</td><td>5</td><td>105</td></tr></tbody></table></div><p>Общая минимальная сумма — 1 RUB. Если минимальная сумма выбранного метода выше, API возвращает 400 и поле minimum_amount. Доступность методов зависит от конфигурации.</p></article><article id="statuses" class="doc-page" data-title="Статусы платежа"><div class="breadcrumbs">Документация / Справочник</div><h1>Статусы платежа</h1><p class="lead">Как интерпретировать состояние счёта.</p><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>Статус</th><th>Что означает</th><th>Что делать</th></tr></thead><tbody><tr><td>PENDING</td><td>Ожидается оплата</td><td>Повторить проверку позже</td></tr><tr><td>CONFIRMED</td><td>Оплата подтверждена</td><td>Выдать заказ один раз</td></tr><tr><td>FAILED</td><td>Неудачная оплата</td><td>Сообщить покупателю</td></tr><tr><td>EXPIRED</td><td>Истёк срок</td><td>При необходимости создать новый счёт</td></tr><tr><td>CANCELED</td><td>Оплата отменена</td><td>Не выдавать заказ</td></tr></tbody></table></div><p>Статусы провайдера SUCCESS, PAID и COMPLETED при проверке нормализуются в CONFIRMED. Неизвестные статусы не считайте подтверждением оплаты.</p></article><article id="errors" class="doc-page" data-title="Ошибки и повторные запросы"><div class="breadcrumbs">Документация / Справочник</div><h1>Ошибки и повторные запросы</h1><p class="lead">Обрабатывайте ошибки без повторного создания заказов.</p><h2>Формат ошибки</h2><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>{
  &quot;ok&quot;: false,
  &quot;error&quot;: &quot;Unauthorized&quot;
}</code></pre></div><p>Проверяйте HTTP-код и поле <code>ok</code>. Сообщения и дополнительные поля зависят от метода: полный список находится на странице endpoint.</p><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>HTTP</th><th>Причина</th><th>Действие</th></tr></thead><tbody><tr><td>400</td><td>Некорректные параметры</td><td>Исправить данные</td></tr><tr><td>401</td><td>Авторизация не пройдена</td><td>Проверить ключ или пароль</td></tr><tr><td>404</td><td>Счёт не найден</td><td>Проверить ID и аккаунт</td></tr><tr><td>405</td><td>Неподходящий метод</td><td>Использовать POST для создания счёта</td></tr><tr><td>500 / 502</td><td>Ошибка сервера или провайдера</td><td>Проверить результат перед повтором</td></tr></tbody></table></div><aside class="callout">После сетевого сбоя не создавайте новый заказ автоматически. Повторяйте создание того же счёта с прежним Idempotency-Key и проверяйте сохранённый результат.</aside></article><article id="cms" class="doc-page" data-title="Сайты и CMS"><div class="breadcrumbs">Документация / Интеграции</div><h1>Сайты и CMS</h1><p class="lead">Сценарий подключения вашей платформы.</p><h2>Подключение сайта или CMS</h2><p>Интеграция состоит из создания счёта на сервере магазина, перехода на payment_url и серверной проверки оплаты. Сопоставляйте order_id или payload с заказом магазина.</p><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>Этап</th><th>GramPay</th></tr></thead><tbody><tr><td>Оформление заказа</td><td>POST create_invoice.php</td></tr><tr><td>Переход к оплате</td><td>payment_url из ответа</td></tr><tr><td>Возврат покупателя</td><td>return_url / failed_url</td></tr><tr><td>Подтверждение</td><td>GET get_status.php</td></tr><tr><td>Завершение заказа</td><td>Только после CONFIRMED</td></tr></tbody></table></div><aside class="callout">Готовые модули CMS и SDK GramPay в этом репозитории отсутствуют. Модули Platega не заявлены как совместимые с GramPay: их нельзя просто переименовать и подключить.</aside><h2>Уведомления</h2><p>В репозитории есть входящий callback платёжного провайдера. Это не документированный webhook для уведомлений ваших магазинов. Для интеграции используйте проверку статуса; не полагайтесь только на переход браузера.</p></article><article id="create-invoice" class="doc-page endpoint-page" data-title="Создать счёт"><div class="breadcrumbs">Документация / Платежи</div><h1>Создать счёт</h1><div class="endpoint-path"><span class="method post">POST</span><code>/api/v1/create_invoice.php</code></div><p class="lead">Создайте счёт и получите ссылку на страницу оплаты GramPay. Покупатель выбирает способ оплаты, а результат вы проверяете по идентификатору счёта.</p><div class="endpoint-layout"><div class="explanation"><h2>Авторизация</h2><p>Заголовок <code>X-API-Key</code> с ключом аккаунта.</p><h2>Тело запроса</h2><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>Параметр</th><th>Тип</th><th>Обязателен</th><th>Описание</th></tr></thead><tbody><tr><td>amount</td><td>integer</td><td>Да</td><td>Сумма в RUB, целое число от 1. Минимум может зависеть от метода.</td></tr><tr><td>payment_method</td><td>integer</td><td>Нет</td><td>По умолчанию 2. Доступные значения: 2, 11, 13, 14.</td></tr><tr><td>order_id</td><td>string</td><td>Нет</td><td>Ваш номер заказа. Также принимается orderId.</td></tr><tr><td>payload</td><td>string</td><td>Нет</td><td>Дополнительные данные; возвращаются при проверке статуса.</td></tr><tr><td>return_url</td><td>string</td><td>Нет</td><td>Адрес возврата после оплаты. Также принимается return.</td></tr><tr><td>failed_url</td><td>string</td><td>Нет</td><td>Адрес возврата при ошибке. Также принимается failedUrl.</td></tr><tr><td>idempotency_key</td><td>string</td><td>Нет</td><td>Ключ повторного запроса, до 128 байт. Лучше передавать заголовком Idempotency-Key.</td></tr></tbody></table></div><aside class="callout">Поле <code>description</code> сейчас не используется: описание формирует GramPay. Сумма запроса передаётся провайдеру в RUB, в том числе при выборе метода USDT.</aside><h2>Повторные запросы</h2><p>Передавайте один <code>Idempotency-Key</code> при повторе одного заказа. Если сохранённый счёт найден, ответ содержит <code>idempotent: true</code>. Новый ключ нужен для нового заказа. Не отправляйте параллельно несколько запросов с одним ключом: атомарная защита от одновременных запросов не гарантируется текущей реализацией.</p><h2>Ответ</h2><p>JSON-объект с <code>ok: true</code> при успехе. Пример справа показывает возвращаемые поля.</p><h2>Ошибки</h2><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>HTTP</th><th>error</th><th>Описание</th></tr></thead><tbody><tr><td>400</td><td>Invalid amount. Minimum is 1 RUB</td><td>Некорректная сумма.</td></tr><tr><td>400</td><td>Invalid payment method</td><td>Метод не включён в конфигурации.</td></tr><tr><td>400</td><td>Amount is below minimum</td><td>В ответе есть minimum_amount и currency.</td></tr><tr><td>400</td><td>Idempotency-Key is too long</td><td>Ключ длиннее 128 байт.</td></tr><tr><td>401</td><td>API key is missing / Invalid API key</td><td>Нет ключа или он не найден.</td></tr><tr><td>405</td><td>Method Not Allowed</td><td>Используйте POST.</td></tr><tr><td>502</td><td>Failed to create invoice</td><td>Ошибка обращения к платёжному провайдеру.</td></tr></tbody></table></div></div><aside class="samples" aria-label="Примеры кода"><div class="sample-head"><span>Пример запроса</span><label>Язык <select class="language"><option value="curl">cURL</option><option value="python">Python</option><option value="node">Node.js</option></select></label></div><div class="sample" data-lang="curl"><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>curl -X POST &#x27;https://grampay.net/api/v1/create_invoice.php&#x27; \
  -H &#x27;X-API-Key: YOUR_API_KEY&#x27; \
  -H &#x27;Content-Type: application/json&#x27; \
  -H &#x27;Idempotency-Key: order-105-attempt-1&#x27; \
  --data &#x27;{
  &quot;amount&quot;: 100,
  &quot;payment_method&quot;: 2,
  &quot;order_id&quot;: &quot;order-105&quot;,
  &quot;payload&quot;: &quot;order-105&quot;,
  &quot;return_url&quot;: &quot;https://example.com/success&quot;,
  &quot;failed_url&quot;: &quot;https://example.com/failure&quot;
}&#x27;</code></pre></div></div><div class="sample" data-lang="python" hidden><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>import requests

response = requests.post(
    &#x27;https://grampay.net/api/v1/create_invoice.php&#x27;,
    headers={&#x27;X-API-Key&#x27;: &#x27;YOUR_API_KEY&#x27;, &#x27;Content-Type&#x27;: &#x27;application/json&#x27;, &#x27;Idempotency-Key&#x27;: &#x27;order-105-attempt-1&#x27;},
    json={&#x27;amount&#x27;: 100, &#x27;payment_method&#x27;: 2, &#x27;order_id&#x27;: &#x27;order-105&#x27;, &#x27;payload&#x27;: &#x27;order-105&#x27;, &#x27;return_url&#x27;: &#x27;https://example.com/success&#x27;, &#x27;failed_url&#x27;: &#x27;https://example.com/failure&#x27;},
    timeout=30,
)
response.raise_for_status()
result = response.json()</code></pre></div></div><div class="sample" data-lang="node" hidden><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>// Выполняйте на сервере, не в браузере.
const response = await fetch(&quot;https://grampay.net/api/v1/create_invoice.php&quot;, {
  method: &quot;POST&quot;,
  headers: {
  &quot;X-API-Key&quot;: &quot;YOUR_API_KEY&quot;,
  &quot;Content-Type&quot;: &quot;application/json&quot;,
  &quot;Idempotency-Key&quot;: &quot;order-105-attempt-1&quot;
},
  body: JSON.stringify({
  &quot;amount&quot;: 100,
  &quot;payment_method&quot;: 2,
  &quot;order_id&quot;: &quot;order-105&quot;,
  &quot;payload&quot;: &quot;order-105&quot;,
  &quot;return_url&quot;: &quot;https://example.com/success&quot;,
  &quot;failed_url&quot;: &quot;https://example.com/failure&quot;
}),
});
if (!response.ok) throw new Error(`HTTP ${response.status}`);
const result = await response.json();</code></pre></div></div><div class="sample-head"><span>Пример ответа</span><span class="http-ok">200</span></div><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>{
  &quot;ok&quot;: true,
  &quot;invoice_id&quot;: &quot;example-invoice-id&quot;,
  &quot;transaction_id&quot;: &quot;example-invoice-id&quot;,
  &quot;amount&quot;: 100,
  &quot;fee&quot;: 5,
  &quot;total_amount&quot;: 105,
  &quot;currency&quot;: &quot;RUB&quot;,
  &quot;status&quot;: &quot;PENDING&quot;,
  &quot;payment_url&quot;: &quot;https://grampay.net/pay_wait.php?tx=example-invoice-id&quot;,
  &quot;redirect_url&quot;: &quot;https://grampay.net/pay_wait.php?tx=example-invoice-id&quot;
}</code></pre></div><p class="sample-note">Демонстрационные данные. Запросы из документации не выполняются.</p></aside></div></article><article id="payment-status" class="doc-page endpoint-page" data-title="Проверить статус"><div class="breadcrumbs">Документация / Платежи</div><h1>Проверить статус</h1><div class="endpoint-path"><span class="method get">GET</span><code>/api/v1/get_status.php</code></div><p class="lead">Получите состояние своего счёта. Неоплаченный счёт проверяется у провайдера; подтверждённый ответ используется для завершения заказа на вашей стороне.</p><div class="endpoint-layout"><div class="explanation"><h2>Авторизация</h2><p>Заголовок <code>X-API-Key</code> с ключом аккаунта.</p><h2>Query-параметры</h2><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>Параметр</th><th>Тип</th><th>Обязателен</th><th>Описание</th></tr></thead><tbody><tr><td>invoice_id</td><td>string</td><td>Да</td><td>Идентификатор из создания счёта. Поддерживаются альтернативы id и tx.</td></tr></tbody></table></div><aside class="callout">Возврат покупателя на <code>return_url</code> сам по себе не подтверждает оплату. Проверяйте <code>status</code> через серверный API. Повторное выполнение вашего заказа должно быть защищено от дублей.</aside><h2>Ответ</h2><p>JSON-объект с <code>ok: true</code> при успехе. Пример справа показывает возвращаемые поля.</p><h2>Ошибки</h2><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>HTTP</th><th>error</th><th>Описание</th></tr></thead><tbody><tr><td>400</td><td>Параметр invoice_id обязателен</td><td>Укажите идентификатор.</td></tr><tr><td>401</td><td>Unauthorized</td><td>Проверьте ключ.</td></tr><tr><td>404</td><td>Счет не найден</td><td>Счёт отсутствует или принадлежит другому пользователю.</td></tr><tr><td>500</td><td>error</td><td>Ошибка проверки у провайдера.</td></tr></tbody></table></div></div><aside class="samples" aria-label="Примеры кода"><div class="sample-head"><span>Пример запроса</span><label>Язык <select class="language"><option value="curl">cURL</option><option value="python">Python</option><option value="node">Node.js</option></select></label></div><div class="sample" data-lang="curl"><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>curl -X GET &#x27;https://grampay.net/api/v1/get_status.php?invoice_id=example-invoice-id&#x27; \
  -H &#x27;X-API-Key: YOUR_API_KEY&#x27;</code></pre></div></div><div class="sample" data-lang="python" hidden><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>import requests

response = requests.get(
    &#x27;https://grampay.net/api/v1/get_status.php?invoice_id=example-invoice-id&#x27;,
    headers={&#x27;X-API-Key&#x27;: &#x27;YOUR_API_KEY&#x27;},
    timeout=30,
)
response.raise_for_status()
result = response.json()</code></pre></div></div><div class="sample" data-lang="node" hidden><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>// Выполняйте на сервере, не в браузере.
const response = await fetch(&quot;https://grampay.net/api/v1/get_status.php?invoice_id=example-invoice-id&quot;, {
  method: &quot;GET&quot;,
  headers: {
  &quot;X-API-Key&quot;: &quot;YOUR_API_KEY&quot;
},
});
if (!response.ok) throw new Error(`HTTP ${response.status}`);
const result = await response.json();</code></pre></div></div><div class="sample-head"><span>Пример ответа</span><span class="http-ok">200</span></div><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>{
  &quot;ok&quot;: true,
  &quot;invoice_id&quot;: &quot;example-invoice-id&quot;,
  &quot;status&quot;: &quot;CONFIRMED&quot;,
  &quot;amount&quot;: 100,
  &quot;fee&quot;: 5,
  &quot;total_amount&quot;: 105,
  &quot;currency&quot;: &quot;RUB&quot;,
  &quot;payload&quot;: &quot;order-105&quot;
}</code></pre></div><p class="sample-note">Демонстрационные данные. Запросы из документации не выполняются.</p></aside></div></article><article id="balance" class="doc-page endpoint-page" data-title="Получить баланс"><div class="breadcrumbs">Документация / Аккаунт</div><h1>Получить баланс</h1><div class="endpoint-path"><span class="method get">GET</span><code>/api/v1/balance.php</code></div><p class="lead">Верните баланс и идентификатор аккаунта, которому принадлежит переданный API-ключ.</p><div class="endpoint-layout"><div class="explanation"><h2>Авторизация</h2><p>Заголовок <code>X-API-Key</code> с ключом аккаунта.</p><h2>Тело запроса</h2><p>Дополнительные параметры не требуются.</p><h2>Ответ</h2><p>JSON-объект с <code>ok: true</code> при успехе. Пример справа показывает возвращаемые поля.</p><h2>Ошибки</h2><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>HTTP</th><th>error</th><th>Описание</th></tr></thead><tbody><tr><td>401</td><td>Unauthorized</td><td>Нет действующего API-ключа.</td></tr></tbody></table></div></div><aside class="samples" aria-label="Примеры кода"><div class="sample-head"><span>Пример запроса</span><label>Язык <select class="language"><option value="curl">cURL</option><option value="python">Python</option><option value="node">Node.js</option></select></label></div><div class="sample" data-lang="curl"><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>curl -X GET &#x27;https://grampay.net/api/v1/balance.php&#x27; \
  -H &#x27;X-API-Key: YOUR_API_KEY&#x27;</code></pre></div></div><div class="sample" data-lang="python" hidden><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>import requests

response = requests.get(
    &#x27;https://grampay.net/api/v1/balance.php&#x27;,
    headers={&#x27;X-API-Key&#x27;: &#x27;YOUR_API_KEY&#x27;},
    timeout=30,
)
response.raise_for_status()
result = response.json()</code></pre></div></div><div class="sample" data-lang="node" hidden><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>// Выполняйте на сервере, не в браузере.
const response = await fetch(&quot;https://grampay.net/api/v1/balance.php&quot;, {
  method: &quot;GET&quot;,
  headers: {
  &quot;X-API-Key&quot;: &quot;YOUR_API_KEY&quot;
},
});
if (!response.ok) throw new Error(`HTTP ${response.status}`);
const result = await response.json();</code></pre></div></div><div class="sample-head"><span>Пример ответа</span><span class="http-ok">200</span></div><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>{
  &quot;ok&quot;: true,
  &quot;user_id&quot;: 42,
  &quot;username&quot;: &quot;demo_merchant&quot;,
  &quot;balance&quot;: 12500,
  &quot;currency&quot;: &quot;RUB&quot;
}</code></pre></div><p class="sample-note">Демонстрационные данные. Запросы из документации не выполняются.</p></aside></div></article><article id="bot-login" class="doc-page endpoint-page" data-title="Вход в аккаунт"><div class="breadcrumbs">Документация / Интеграция с ботом</div><h1>Вход в аккаунт</h1><div class="endpoint-path"><span class="method post">POST</span><code>/api/v1/bot_login.php</code></div><p class="lead">Авторизуйте пользователя по логину или email и паролю. Успешный ответ содержит API-ключ: обрабатывайте его только на сервере бота.</p><div class="endpoint-layout"><div class="explanation"><h2>Авторизация</h2><p>API-ключ не нужен. Данные аккаунта передаются в теле запроса.</p><h2>Тело запроса</h2><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>Параметр</th><th>Тип</th><th>Обязателен</th><th>Описание</th></tr></thead><tbody><tr><td>login</td><td>string</td><td>Да</td><td>Логин или email. Сравнение без учёта регистра.</td></tr><tr><td>password</td><td>string</td><td>Да</td><td>Пароль аккаунта.</td></tr></tbody></table></div><aside class="callout">Этот endpoint не выполняет двухфакторную проверку веб-входа. Не используйте его как замену входу с 2FA. Не помещайте пароль и API-ключ в URL, клиентский JavaScript или журналы.</aside><h2>Ответ</h2><p>JSON-объект с <code>ok: true</code> при успехе. Пример справа показывает возвращаемые поля.</p><h2>Ошибки</h2><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>HTTP</th><th>error</th><th>Описание</th></tr></thead><tbody><tr><td>400</td><td>Пустой логин или пароль</td><td>Заполните оба поля.</td></tr><tr><td>401</td><td>Неверный логин или пароль</td><td>Проверьте учётные данные.</td></tr></tbody></table></div></div><aside class="samples" aria-label="Примеры кода"><div class="sample-head"><span>Пример запроса</span><label>Язык <select class="language"><option value="curl">cURL</option><option value="python">Python</option><option value="node">Node.js</option></select></label></div><div class="sample" data-lang="curl"><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>curl -X POST &#x27;https://grampay.net/api/v1/bot_login.php&#x27; \
  -H &#x27;Content-Type: application/json&#x27; \
  --data &#x27;{
  &quot;login&quot;: &quot;demo_merchant&quot;,
  &quot;password&quot;: &quot;YOUR_PASSWORD&quot;
}&#x27;</code></pre></div></div><div class="sample" data-lang="python" hidden><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>import requests

response = requests.post(
    &#x27;https://grampay.net/api/v1/bot_login.php&#x27;,
    headers={&#x27;Content-Type&#x27;: &#x27;application/json&#x27;},
    json={&#x27;login&#x27;: &#x27;demo_merchant&#x27;, &#x27;password&#x27;: &#x27;YOUR_PASSWORD&#x27;},
    timeout=30,
)
response.raise_for_status()
result = response.json()</code></pre></div></div><div class="sample" data-lang="node" hidden><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>// Выполняйте на сервере, не в браузере.
const response = await fetch(&quot;https://grampay.net/api/v1/bot_login.php&quot;, {
  method: &quot;POST&quot;,
  headers: {
  &quot;Content-Type&quot;: &quot;application/json&quot;
},
  body: JSON.stringify({
  &quot;login&quot;: &quot;demo_merchant&quot;,
  &quot;password&quot;: &quot;YOUR_PASSWORD&quot;
}),
});
if (!response.ok) throw new Error(`HTTP ${response.status}`);
const result = await response.json();</code></pre></div></div><div class="sample-head"><span>Пример ответа</span><span class="http-ok">200</span></div><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>{
  &quot;ok&quot;: true,
  &quot;user_id&quot;: 42,
  &quot;username&quot;: &quot;demo_merchant&quot;,
  &quot;api_key&quot;: &quot;gp_sec_EXAMPLE_ONLY&quot;,
  &quot;balance&quot;: 12500
}</code></pre></div><p class="sample-note">Демонстрационные данные. Запросы из документации не выполняются.</p></aside></div></article><article id="bot-register" class="doc-page endpoint-page" data-title="Регистрация аккаунта"><div class="breadcrumbs">Документация / Интеграция с ботом</div><h1>Регистрация аккаунта</h1><div class="endpoint-path"><span class="method post">POST</span><code>/api/v1/bot_register.php</code></div><p class="lead">Создайте аккаунт через серверную интеграцию бота. Ответ содержит идентификатор пользователя и его ключ.</p><div class="endpoint-layout"><div class="explanation"><h2>Авторизация</h2><p>API-ключ не нужен. Данные аккаунта передаются в теле запроса.</p><h2>Тело запроса</h2><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>Параметр</th><th>Тип</th><th>Обязателен</th><th>Описание</th></tr></thead><tbody><tr><td>username</td><td>string</td><td>Да</td><td>Непустой логин; должен быть свободен.</td></tr><tr><td>email</td><td>string</td><td>Да</td><td>Непустой адрес; не должен совпадать с существующим.</td></tr><tr><td>password</td><td>string</td><td>Да</td><td>Не менее 6 символов.</td></tr></tbody></table></div><aside class="callout">В текущем обработчике нет подтверждения email и CAPTCHA веб-регистрации. Успех этого запроса не означает, что почтовый адрес подтверждён.</aside><h2>Ответ</h2><p>JSON-объект с <code>ok: true</code> при успехе. Пример справа показывает возвращаемые поля.</p><h2>Ошибки</h2><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>HTTP</th><th>error</th><th>Описание</th></tr></thead><tbody><tr><td>400</td><td>Заполните все поля (пароль минимум 6 символов)</td><td>Не хватает данных или пароль короткий.</td></tr><tr><td>400</td><td>Пользователь с таким логином или email уже существует</td><td>Используйте существующий аккаунт.</td></tr></tbody></table></div></div><aside class="samples" aria-label="Примеры кода"><div class="sample-head"><span>Пример запроса</span><label>Язык <select class="language"><option value="curl">cURL</option><option value="python">Python</option><option value="node">Node.js</option></select></label></div><div class="sample" data-lang="curl"><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>curl -X POST &#x27;https://grampay.net/api/v1/bot_register.php&#x27; \
  -H &#x27;Content-Type: application/json&#x27; \
  --data &#x27;{
  &quot;username&quot;: &quot;demo_merchant&quot;,
  &quot;email&quot;: &quot;merchant@example.com&quot;,
  &quot;password&quot;: &quot;YOUR_PASSWORD&quot;
}&#x27;</code></pre></div></div><div class="sample" data-lang="python" hidden><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>import requests

response = requests.post(
    &#x27;https://grampay.net/api/v1/bot_register.php&#x27;,
    headers={&#x27;Content-Type&#x27;: &#x27;application/json&#x27;},
    json={&#x27;username&#x27;: &#x27;demo_merchant&#x27;, &#x27;email&#x27;: &#x27;merchant@example.com&#x27;, &#x27;password&#x27;: &#x27;YOUR_PASSWORD&#x27;},
    timeout=30,
)
response.raise_for_status()
result = response.json()</code></pre></div></div><div class="sample" data-lang="node" hidden><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>// Выполняйте на сервере, не в браузере.
const response = await fetch(&quot;https://grampay.net/api/v1/bot_register.php&quot;, {
  method: &quot;POST&quot;,
  headers: {
  &quot;Content-Type&quot;: &quot;application/json&quot;
},
  body: JSON.stringify({
  &quot;username&quot;: &quot;demo_merchant&quot;,
  &quot;email&quot;: &quot;merchant@example.com&quot;,
  &quot;password&quot;: &quot;YOUR_PASSWORD&quot;
}),
});
if (!response.ok) throw new Error(`HTTP ${response.status}`);
const result = await response.json();</code></pre></div></div><div class="sample-head"><span>Пример ответа</span><span class="http-ok">200</span></div><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>{
  &quot;ok&quot;: true,
  &quot;user_id&quot;: 42,
  &quot;username&quot;: &quot;demo_merchant&quot;,
  &quot;api_key&quot;: &quot;gp_sec_EXAMPLE_ONLY&quot;,
  &quot;balance&quot;: 0
}</code></pre></div><p class="sample-note">Демонстрационные данные. Запросы из документации не выполняются.</p></aside></div></article><article id="rotate-key" class="doc-page endpoint-page" data-title="Перевыпустить API-ключ"><div class="breadcrumbs">Документация / Аккаунт</div><h1>Перевыпустить API-ключ</h1><div class="endpoint-path"><span class="method post">POST</span><code>/api/v1/bot_regenerate_key.php</code></div><p class="lead">Создайте новый API-ключ для текущего аккаунта. После перевыпуска обновите ключ во всех интеграциях.</p><div class="endpoint-layout"><div class="explanation"><h2>Авторизация</h2><p>Заголовок <code>X-API-Key</code> с ключом аккаунта.</p><h2>Тело запроса</h2><p>Дополнительные параметры не требуются.</p><aside class="callout">Операция изменяет ключ: старый перестанет работать. Пример использует POST. Текущий обработчик не ограничивает HTTP-метод, поэтому не открывайте этот URL как обычную страницу. Файл <code>api/v1/generate_key.php</code> пуст и не является рабочим endpoint.</aside><h2>Ответ</h2><p>JSON-объект с <code>ok: true</code> при успехе. Пример справа показывает возвращаемые поля.</p><h2>Ошибки</h2><div class="table-wrap" tabindex="0" role="region" aria-label="Таблица параметров"><table><thead><tr><th>HTTP</th><th>error</th><th>Описание</th></tr></thead><tbody><tr><td>401</td><td>Unauthorized</td><td>Текущий ключ не найден.</td></tr></tbody></table></div></div><aside class="samples" aria-label="Примеры кода"><div class="sample-head"><span>Пример запроса</span><label>Язык <select class="language"><option value="curl">cURL</option><option value="python">Python</option><option value="node">Node.js</option></select></label></div><div class="sample" data-lang="curl"><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>curl -X POST &#x27;https://grampay.net/api/v1/bot_regenerate_key.php&#x27; \
  -H &#x27;X-API-Key: YOUR_API_KEY&#x27; \
  -H &#x27;Content-Type: application/json&#x27; \
  --data &#x27;{}&#x27;</code></pre></div></div><div class="sample" data-lang="python" hidden><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>import requests

response = requests.post(
    &#x27;https://grampay.net/api/v1/bot_regenerate_key.php&#x27;,
    headers={&#x27;X-API-Key&#x27;: &#x27;YOUR_API_KEY&#x27;, &#x27;Content-Type&#x27;: &#x27;application/json&#x27;},
    json={},
    timeout=30,
)
response.raise_for_status()
result = response.json()</code></pre></div></div><div class="sample" data-lang="node" hidden><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>// Выполняйте на сервере, не в браузере.
const response = await fetch(&quot;https://grampay.net/api/v1/bot_regenerate_key.php&quot;, {
  method: &quot;POST&quot;,
  headers: {
  &quot;X-API-Key&quot;: &quot;YOUR_API_KEY&quot;,
  &quot;Content-Type&quot;: &quot;application/json&quot;
},
  body: JSON.stringify({}),
});
if (!response.ok) throw new Error(`HTTP ${response.status}`);
const result = await response.json();</code></pre></div></div><div class="sample-head"><span>Пример ответа</span><span class="http-ok">200</span></div><div class="code-block"><button class="copy-code" type="button">Копировать</button><pre><code>{
  &quot;ok&quot;: true,
  &quot;new_api_key&quot;: &quot;gp_sec_NEW_EXAMPLE_ONLY&quot;
}</code></pre></div><p class="sample-note">Демонстрационные данные. Запросы из документации не выполняются.</p></aside></div></article><footer class="doc-footer"><span>GramPay / Документация API</span><a href="support.php">Поддержка</a></footer></main></div><div id="feedback" role="status" aria-live="polite"></div></body></html>