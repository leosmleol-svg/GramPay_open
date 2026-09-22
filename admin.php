<?php declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';

$user = requireAuth();

if ((int)$user['id'] !== 1) {
    http_response_code(403);
    die('Доступ запрещен. Вы не являетесь администратором.');
}

$message = '';
$config = require __DIR__ . '/config.php';

function adminSaveReviewRequests(array $requests): void {
    saveJson('verification_requests.json', array_values($requests));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    if ($action === 'review_request') {
        $requestId = trim((string)($_POST['request_id'] ?? ''));
        $decision = trim((string)($_POST['decision'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));
        $requests = loadJson('verification_requests.json');
        $found = false;

        foreach ($requests as &$request) {
            if ((string)($request['id'] ?? '') !== $requestId) continue;
            $found = true;
            if (!in_array($decision, ['approved', 'rejected'], true)) {
                $message = '❌ Некорректное решение.';
                break;
            }
            if ($reason === '') {
                $message = '❌ Укажите причину решения.';
                break;
            }

            $request['status'] = $decision;
            $request['reason'] = mb_substr($reason, 0, 1000);
            $request['reviewed_at'] = date('Y-m-d H:i:s');
            $request['reviewed_by'] = (int)$user['id'];

            $uid = (int)($request['user_id'] ?? 0);
            $users = loadJson('users.json');
            foreach ($users as &$targetUser) {
                if ((int)($targetUser['id'] ?? 0) !== $uid) continue;
                $type = (string)($request['type'] ?? 'website');
                $prefix = $type === 'bot' ? 'bot' : 'website';
                $targetUser[$prefix . '_approval_status'] = $decision;
                $targetUser[$prefix . '_approval_reason'] = mb_substr($reason, 0, 1000);
                $targetUser[$prefix . '_approved_at'] = $request['reviewed_at'];
                break;
            }
            unset($targetUser);
            saveJson('users.json', $users);

            $message = ($decision === 'approved' ? '✅ Заявка одобрена.' : '🚫 Заявка отклонена.') . ' Причина сохранена.';
            break;
        }
        unset($request);

        if (!$found && $message === '') $message = '❌ Заявка не найдена.';
        adminSaveReviewRequests($requests);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_balance') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $amount = (int)($_POST['amount'] ?? 0);
        if ($targetUserId > 0 && $amount !== 0) {
            if (updateUserBalance($targetUserId, $amount)) {
                $message = "✅ Баланс пользователя #{$targetUserId} изменен на {$amount} RUB.";
            } else {
                $message = "❌ Пользователь не найден.";
            }
        }
    }
    
    if ($action === 'regen_key') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        if ($targetUserId > 0) {
            generateUserApiKey($targetUserId);
            $message = "🔑 API-ключ для пользователя #{$targetUserId} обновлен.";
        }
    }

    if ($action === 'set_tx_status') {
        $txId = trim($_POST['tx_id'] ?? '');
        $newStatus = trim($_POST['status'] ?? '');
        
        if ($txId && $newStatus) {
            $tx = findTransaction($txId);
            if ($tx && $tx['status'] === 'PENDING') {
                if ($newStatus === 'CONFIRMED') {
                    updateTransactionStatus($txId, 'CONFIRMED');
                    updateUserBalance((int)$tx['user_id'], (int)$tx['amount']);
                    $message = "💸 Транзакция {$txId} подтверждена.";
                } elseif ($newStatus === 'CANCELED') {
                    updateTransactionStatus($txId, 'CANCELED');
                    $message = "🚫 Транзакция {$txId} отменена.";
                }
            } else {
                $message = "❌ Транзакция не найдена или уже обработана.";
            }
        }
    }
}

$allUsers = loadJson('users.json');
$allTxs = loadJson('transactions.json');

$totalUsers = count($allUsers);
$totalTxs = count($allTxs);
$successfulTxs = 0;
$totalVolume = 0;
$totalSystemProfit = 0;
$userDeposits = []; 
$methodVolume = [];

foreach ($allTxs as $tx) {
    if (in_array($tx['status'], ['CONFIRMED', 'SUCCESS', 'PAID'])) {
        $successfulTxs++;
        $amount = (float)($tx['amount'] ?? 0);
        $fee = (float)($tx['fee'] ?? 0);
        $uid = (int)($tx['user_id'] ?? 0);
        $method = (int)($tx['payment_method'] ?? 0);
        
        $totalVolume += $amount;
        $totalSystemProfit += $fee;
        
        $userDeposits[$uid] = ($userDeposits[$uid] ?? 0) + $amount;
        $methodVolume[$method] = ($methodVolume[$method] ?? 0) + $amount;
    }
}

usort($allTxs, function ($a, $b) {
    return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
});

$reviewRequests = loadJson('verification_requests.json');
usort($reviewRequests, function ($a, $b) {
    return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
});
$pendingReviews = 0;
foreach ($reviewRequests as $request) {
    if (($request['status'] ?? 'pending') === 'pending') $pendingReviews++;
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>GRAMPAY — Админ-центр</title>
    <script>
        const savedTheme = localStorage.getItem('grampay_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        :root[data-theme="dark"] {
            --bg:#05070d; --surface:rgba(14,19,32,.72); --surface2:rgba(255,255,255,.045);
            --surface-hover:rgba(255,255,255,.075); --border:rgba(255,255,255,.09);
            --text:#f8fafc; --secondary:#cbd5e1; --muted:#7f8ba3; --primary:#6366f1;
            --green:#34d399; --red:#fb7185;
        }
        :root[data-theme="light"] {
            --bg:#f3f6fb; --surface:rgba(255,255,255,.78); --surface2:rgba(15,23,42,.035);
            --surface-hover:rgba(15,23,42,.06); --border:rgba(15,23,42,.08);
            --text:#0f172a; --secondary:#334155; --muted:#64748b; --primary:#4f46e5;
            --green:#059669; --red:#e11d48;
        }
        body { margin:0; min-height:100vh; color:var(--text); background:radial-gradient(circle at 10% -10%,rgba(99,102,241,.19),transparent 33%),radial-gradient(circle at 100% 25%,rgba(34,211,238,.10),transparent 27%),var(--bg); font-family:Inter,sans-serif; padding-bottom:40px; }
        .layout { display:grid; grid-template-columns:250px minmax(0,1fr); min-height:100vh; }
        .sidebar { position:sticky; top:0; height:100vh; padding:20px 15px; border-right:1px solid var(--border); background:rgba(8,12,21,.58); backdrop-filter:blur(28px); }
        :root[data-theme="light"] .sidebar { background:rgba(255,255,255,.68); }
        .brand { display:flex; align-items:center; gap:11px; padding:5px 9px 28px; text-decoration:none; color:var(--text); }
        .brand-icon { width:42px; height:42px; display:flex; align-items:center; justify-content:center; border-radius:13px; color:#fff; background:linear-gradient(135deg,#ef4444,#fb923c); box-shadow:0 12px 35px rgba(239,68,68,.3); }
        .side-link { display:flex; align-items:center; gap:11px; padding:11px 12px; border-radius:12px; text-decoration:none; color:var(--secondary); font-size:12px; font-weight:700; transition:.2s; }
        .side-link:hover { background:var(--surface-hover); color:var(--text); }
        .side-link.active { background:linear-gradient(135deg,rgba(239,68,68,.20),rgba(251,146,60,.06)); color:#fff; }
        .main { padding:30px; }
        .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:26px; }
        .icon-button { width:42px; height:42px; display:flex; align-items:center; justify-content:center; border:1px solid var(--border); border-radius:12px; background:var(--surface); color:var(--text); text-decoration:none; transition:.2s; cursor:pointer; }
        .stats { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:24px; }
        .stat { padding:17px; border:1px solid var(--border); border-radius:17px; background:var(--surface); backdrop-filter:blur(20px); }
        .card { border:1px solid var(--border); border-radius:20px; background:var(--surface); color:var(--text); overflow:hidden; backdrop-filter:blur(25px); box-shadow:0 25px 70px rgba(0,0,0,.16); margin-bottom:24px; }
        .card-header-custom { display:flex; justify-content:space-between; padding:19px 21px; border-bottom:1px solid var(--border); font-weight:850; font-size:14px; }
        .transactions { width:100%; min-width:600px; border-collapse:collapse; }
        .transactions th { padding:12px 18px; text-align:left; color:var(--muted); font-size:9px; text-transform:uppercase; border-bottom:1px solid var(--border); }
        .transactions td { padding:14px 18px; color:var(--secondary); font-size:11px; border-bottom:1px solid var(--border); }
        .status { padding:6px 9px; border-radius:8px; font-size:9px; font-weight:850; }
        .status-paid { color:#6ee7b7; background:rgba(16,185,129,.09); border:1px solid rgba(16,185,129,.17); }
        .status-failed { color:#fda4af; background:rgba(244,63,94,.09); border:1px solid rgba(244,63,94,.17); }
        .form-control { border:1px solid var(--border)!important; border-radius:8px!important; color:var(--text)!important; background:rgba(0,0,0,.16)!important; font-size:11px; }
        .primary-button { border:0; border-radius:8px; color:#fff; background:linear-gradient(135deg,#6366f1,#4f46e5); font-size:10px; font-weight:800; padding:6px 12px; }
        .danger-button { border:0; border-radius:8px; color:#fff; background:linear-gradient(135deg,#ef4444,#b91c1c); font-size:10px; font-weight:800; padding:6px 12px; }
        @media(max-width:1000px) { .layout { grid-template-columns:1fr; } .sidebar { display:none; } .stats { grid-template-columns:repeat(2,1fr); } }
        @media(max-width:640px) { .stats { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <a href="index.php" class="brand">
            <div class="brand-icon"><i class="bi bi-shield-lock-fill"></i></div>
            <div>
                <div style="font-size:18px; font-weight:900;">SUPER ADMIN</div>
                <span style="color:var(--muted); font-size:9px; font-weight:700;">GRAMPAY CONTROL</span>
            </div>
        </a>
        <nav style="display:flex; flex-direction:column; gap:4px;">
            <a href="admin.php" class="side-link active"><i class="bi bi-speedometer2"></i><span>Дашборд</span></a>
            <a href="index.php" class="side-link"><i class="bi bi-arrow-left"></i><span>Вернуться в ЛК</span></a>
        </nav>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h1 style="margin:0; font-size:28px; font-weight:900;">Админ-Центр</h1>
            </div>
            <div style="display:flex; gap:8px;">
                <button class="icon-button" onclick="toggleTheme()"><i class="bi bi-moon-stars-fill"></i></button>
                <a href="logout.php" class="icon-button"><i class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="padding:15px; background:rgba(16,185,129,.1); border:1px solid #10b981; color:#10b981; border-radius:12px; margin-bottom:20px; font-weight:bold; font-size:12px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat"><div style="color:var(--muted); font-size:10px; font-weight:800;">ЮЗЕРОВ</div><div style="font-size:21px; font-weight:900;"><?= $totalUsers ?></div></div>
            <div class="stat"><div style="color:var(--muted); font-size:10px; font-weight:800;">ОПЛАЧЕНО</div><div style="font-size:21px; font-weight:900; color:#34d399;"><?= $successfulTxs ?></div></div>
            <div class="stat"><div style="color:var(--muted); font-size:10px; font-weight:800;">ОБОРОТ</div><div style="font-size:21px; font-weight:900;"><?= number_format($totalVolume, 0, '.', ' ') ?> ₽</div></div>
            <div class="stat"><div style="color:var(--muted); font-size:10px; font-weight:800;">ПРИБЫЛЬ</div><div style="font-size:21px; font-weight:900; color:#fb7185;"><?= number_format($totalSystemProfit, 0, '.', ' ') ?> ₽</div></div>
            <div class="stat"><div style="color:var(--muted); font-size:10px; font-weight:800;">НА ПРОВЕРКЕ</div><div style="font-size:21px; font-weight:900; color:#fbbf24;"><?= $pendingReviews ?></div></div>
        </div>

        <div class="card">
            <div class="card-header-custom">
                <span><i class="bi bi-clipboard2-check me-2"></i>Заявки на подключение сайта / Telegram-бота</span>
                <span class="status <?= $pendingReviews ? '' : 'status-paid' ?>" style="<?= $pendingReviews ? 'background:rgba(251,191,36,.1);color:#fbbf24;border:1px solid rgba(251,191,36,.2);' : '' ?>"><?= $pendingReviews ?> ожидают</span>
            </div>
            <div style="padding:0 21px 21px;">
                <?php if (empty($reviewRequests)): ?>
                    <div style="padding:24px 0;color:var(--muted);font-size:11px;text-align:center;">Заявок пока нет.</div>
                <?php else: ?>
                    <?php foreach ($reviewRequests as $request):
                        $rtype = (($request['type'] ?? 'website') === 'bot') ? 'bot' : 'website';
                        $rstatus = (string)($request['status'] ?? 'pending');
                        $rUser = (int)($request['user_id'] ?? 0);
                        $rName = (string)($request['username'] ?? ('user-' . $rUser));
                        $target = $rtype === 'bot' ? (string)($request['bot_username'] ?? '') : (string)($request['domain'] ?? '');
                    ?>
                    <div style="padding:17px 0;border-bottom:1px solid var(--border);">
                        <div style="display:flex;justify-content:space-between;gap:15px;align-items:flex-start;flex-wrap:wrap;">
                            <div>
                                <div style="font-size:12px;font-weight:900;"><?= $rtype === 'bot' ? '🤖 Telegram-бот' : '🌐 Сайт' ?> · #<?= $rUser ?> <?= htmlspecialchars($rName) ?></div>
                                <div style="margin-top:4px;color:var(--secondary);font-size:11px;"><?= htmlspecialchars($target) ?></div>
                                <?php if (!empty($request['description'])): ?><div style="margin-top:7px;color:var(--muted);font-size:10px;line-height:1.5;"><?= nl2br(htmlspecialchars((string)$request['description'])) ?></div><?php endif; ?>
                                <div style="margin-top:6px;color:var(--muted);font-size:9px;">Создана: <?= htmlspecialchars((string)($request['created_at'] ?? '')) ?></div>
                                <?php if (!empty($request['reason']) && $rstatus !== 'pending'): ?><div style="margin-top:8px;padding:8px 10px;border-radius:9px;background:var(--surface2);font-size:10px;color:var(--secondary);"><b>Причина:</b> <?= nl2br(htmlspecialchars((string)$request['reason'])) ?></div><?php endif; ?>
                            </div>
                            <span class="status <?= $rstatus === 'approved' ? 'status-paid' : ($rstatus === 'rejected' ? 'status-failed' : '') ?>" style="<?= $rstatus === 'pending' ? 'background:rgba(251,191,36,.1);color:#fbbf24;border:1px solid rgba(251,191,36,.2);' : '' ?>"><?= $rstatus === 'approved' ? 'ОДОБРЕНО' : ($rstatus === 'rejected' ? 'ОТКЛОНЕНО' : 'ОЖИДАЕТ') ?></span>
                        </div>
                        <?php if ($rstatus === 'pending'): ?>
                        <form method="POST" style="margin-top:12px;">
                            <input type="hidden" name="action" value="review_request">
                            <input type="hidden" name="request_id" value="<?= htmlspecialchars((string)$request['id']) ?>">
                            <div style="display:grid;grid-template-columns:minmax(0,1fr) auto auto;gap:8px;">
                                <input class="form-control" name="reason" maxlength="1000" placeholder="Причина решения (обязательно)" required>
                                <button class="primary-button" name="decision" value="approved" type="submit" onclick="return confirm('Одобрить заявку?')"><i class="bi bi-check-lg"></i> Одобрить</button>
                                <button class="danger-button" name="decision" value="rejected" type="submit" onclick="return confirm('Отклонить заявку?')"><i class="bi bi-x-lg"></i> Отклонить</button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header-custom">Пользователи платформы</div>
            <div style="overflow-x:auto;">
                <table class="transactions">
                    <thead><tr><th>ID / Логин</th><th>Email</th><th>Депозиты</th><th>Баланс</th><th>Действия</th></tr></thead>
                    <tbody>
                        <?php foreach ($allUsers as $u): $totalDep = $userDeposits[$u['id']] ?? 0; ?>
                        <tr>
                            <td><strong>#<?= $u['id'] ?> <?= htmlspecialchars($u['username']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td style="color:#34d399; font-weight:bold;"><?= $totalDep > 0 ? "+{$totalDep} ₽" : "0 ₽" ?></td>
                            <td style="color:var(--text); font-weight:bold;"><?= $u['balance'] ?? 0 ?> ₽</td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <form method="POST" style="display:flex; gap:4px; margin:0;">
                                        <input type="hidden" name="action" value="add_balance">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="number" name="amount" class="form-control" style="width:70px;" placeholder="+ / - ₽" required>
                                        <button type="submit" class="primary-button">Изменить</button>
                                    </form>
                                    <form method="POST" style="margin:0;" onsubmit="return confirm('Сбросить API?');">
                                        <input type="hidden" name="action" value="regen_key">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="primary-button" style="background:var(--surface2); border:1px solid var(--border); color:var(--text);">🔄 API</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header-custom">Все транзакции</div>
            <div style="overflow-x:auto;">
                <table class="transactions">
                    <thead><tr><th>Дата</th><th>Юзер</th><th>Метод</th><th>Сумма</th><th>Статус</th><th>Управление</th></tr></thead>
                    <tbody>
                        <?php foreach ($allTxs as $tx): $methodName = $config['payment_methods'][$tx['payment_method']] ?? 'Метод #' . $tx['payment_method']; ?>
                        <tr>
                            <td><?= date('d.m.y H:i', strtotime((string)$tx['created_at'])) ?></td>
                            <td><strong>#<?= $tx['user_id'] ?></strong></td>
                            <td><?= htmlspecialchars($methodName) ?></td>
                            <td style="color:var(--text); font-weight:bold;"><?= $tx['amount'] ?> ₽</td>
                            <td>
                                <?php if (in_array($tx['status'], ['CONFIRMED', 'SUCCESS', 'PAID'])): ?>
                                    <span class="status status-paid">ОПЛАЧЕН</span>
                                <?php elseif ($tx['status'] === 'PENDING'): ?>
                                    <span class="status" style="background:rgba(251,191,36,.1); color:#fbbf24; border:1px solid rgba(251,191,36,.2);">ОЖИДАЕТ</span>
                                <?php else: ?>
                                    <span class="status status-failed"><?= htmlspecialchars((string)$tx['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($tx['status'] === 'PENDING'): ?>
                                    <div style="display:flex; gap:4px;">
                                        <form method="POST" style="margin:0;" onsubmit="return confirm('Подтвердить?');">
                                            <input type="hidden" name="action" value="set_tx_status">
                                            <input type="hidden" name="tx_id" value="<?= $tx['transaction_id'] ?>">
                                            <input type="hidden" name="status" value="CONFIRMED">
                                            <button type="submit" class="primary-button" style="background:#059669;">✔</button>
                                        </form>
                                        <form method="POST" style="margin:0;" onsubmit="return confirm('Отменить?');">
                                            <input type="hidden" name="action" value="set_tx_status">
                                            <input type="hidden" name="tx_id" value="<?= $tx['transaction_id'] ?>">
                                            <input type="hidden" name="status" value="CANCELED">
                                            <button type="submit" class="danger-button">✖</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div style="font-family:monospace; font-size:9px; color:var(--muted);"><?= substr((string)$tx['transaction_id'], 0, 13) ?>...</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
</script>
</body>
</html>