<?php declare(strict_types=1); require_once __DIR__.'/site_extra.php'; gpPageHeader('FAQ GRAMPAY','Ответы на частые вопросы по платежам, API и проверке статуса.'); ?>
<section class="hero"><div class="eyebrow">FAQ</div><h1>Частые вопросы</h1><p>Практические ответы для магазина и разработчика.</p></section>
<section class="card"><h2>Считать ли return_url подтверждением оплаты?</h2><p class="muted">Нет. После возврата проверяйте статус по invoice ID серверным запросом к GRAMPAY.</p></section>
<section class="card"><h2>Нужна ли проверка сайта для API?</h2><p class="muted">В текущей версии API доступ активируется сразу после подачи заявки; бот можно подключать без обязательной проверки сайта.</p></section>
<section class="card"><h2>Какая минимальная сумма?</h2><p class="muted">Минимальная сумма счёта — 1 RUB.</p></section>
<section class="card"><h2>Как часто проверять статус?</h2><p class="muted">Для клиентской страницы оплаты текущая реализация выполняет автоматическую проверку каждые несколько секунд. Для backend-интеграции избегайте агрессивного опроса и сохраняйте последний подтверждённый статус.</p></section>
<?php gpPageFooter(); ?>
