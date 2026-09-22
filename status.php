<?php declare(strict_types=1); require_once __DIR__.'/site_extra.php';
$checks = [
 'Публичная страница' => is_file(__DIR__.'/index.php'),
 'API создания счёта' => is_file(__DIR__.'/api/v1/create_invoice.php'),
 'API статуса' => is_file(__DIR__.'/api/v1/get_status.php'),
 'Страница оплаты' => is_file(__DIR__.'/pay_wait.php'),
];
gpPageHeader('Статус GRAMPAY','Техническая страница состояния компонентов GRAMPAY.'); ?>
<section class="hero"><div class="eyebrow">System status</div><h1>Состояние сервисов</h1><p>Проверка структуры текущего развёртывания сайта. Это не независимый мониторинг доступности внешнего платёжного провайдера.</p></section>
<?php foreach($checks as $name=>$ok): ?><section class="card"><h2><?=htmlspecialchars($name)?></h2><p class="muted"><strong><?= $ok ? 'Работает' : 'Проблема' ?></strong> · локальная проверка компонента на сервере.</p></section><?php endforeach; ?>
<?php gpPageFooter(); ?>
