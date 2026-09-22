<?php
declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';

$user = requireAuth();

$type = trim((string)($_POST['type'] ?? ''));
$target = trim((string)($_POST['target'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));

if (!in_array($type, ['website', 'bot'], true)) {
    header('Location: panel.php#integration-review');
    exit;
}

$error = '';

if ($type === 'website') {
    if (!filter_var($target, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $target)) {
        $error = 'Укажите корректный URL сайта.';
    }
} else {
    $target = ltrim($target, '@');
    if (!preg_match('/^[A-Za-z0-9_]{5,32}$/', $target)) {
        $error = 'Укажите корректный username Telegram-бота без @.';
    }
}

if ($error !== '') {
    header('Location: panel.php?review=error&message=' . rawurlencode($error) . '#integration-review');
    exit;
}

$requests = loadJson('verification_requests.json');
$now = date('Y-m-d H:i:s');
$updated = false;

// Не плодим несколько одинаковых заявок: переиспользуем последнюю заявку этого типа, если она ещё на проверке.
for ($i = count($requests) - 1; $i >= 0; $i--) {
    if ((int)($requests[$i]['user_id'] ?? 0) !== (int)$user['id']) continue;
    if ((string)($requests[$i]['type'] ?? '') !== $type) continue;
    if ((string)($requests[$i]['status'] ?? '') !== 'pending') continue;

    $requests[$i]['description'] = mb_substr($description, 0, 1000);
    $requests[$i]['updated_at'] = $now;
    if ($type === 'bot') $requests[$i]['bot_username'] = $target;
    else $requests[$i]['domain'] = $target;
    $updated = true;
    break;
}

if (!$updated) {
    $request = [
        'id' => bin2hex(random_bytes(12)),
        'user_id' => (int)$user['id'],
        'username' => (string)($user['username'] ?? ''),
        'type' => $type,
        'status' => 'pending',
        'reason' => '',
        'description' => mb_substr($description, 0, 1000),
        'created_at' => $now,
        'updated_at' => $now,
    ];
    if ($type === 'bot') $request['bot_username'] = $target;
    else $request['domain'] = $target;
    $requests[] = $request;
}

saveJson('verification_requests.json', $requests);

// Фиксируем состояние в users.json для быстрого отображения в панели.
$users = loadJson('users.json');
foreach ($users as &$u) {
    if ((int)($u['id'] ?? 0) !== (int)$user['id']) continue;
    $prefix = $type === 'bot' ? 'bot' : 'website';
    $u[$prefix . '_approval_status'] = 'pending';
    $u[$prefix . '_approval_reason'] = '';
    $u[$prefix . '_approval_requested_at'] = $now;
    if ($type === 'bot') $u['bot_username'] = $target;
    else $u['website_requested_domain'] = $target;
    break;
}
unset($u);
saveJson('users.json', $users);

header('Location: panel.php?review=sent#integration-review');
exit;
