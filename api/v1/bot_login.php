<?php declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../storage.php';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput ?: '', true) ?? $_POST;

$login = trim((string)($input['login'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($login === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Пустой логин или пароль'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Загружаем всех пользователей
$users = loadJson('users.json');
$user = null;

// Переводим введенный логин в нижний регистр для сравнения
$searchLogin = mb_strtolower($login, 'UTF-8');

foreach ($users as $u) {
    $dbUsername = mb_strtolower((string)($u['username'] ?? ''), 'UTF-8');
    $dbEmail = mb_strtolower((string)($u['email'] ?? ''), 'UTF-8');
    
    if ($dbUsername === $searchLogin || $dbEmail === $searchLogin) {
        $user = $u;
        break;
    }
}

// Проверяем хэш пароля[cite: 1]
if ($user && password_verify($password, (string)$user['password_hash'])) {
    echo json_encode([
        'ok'       => true,
        'user_id'  => $user['id'],
        'username' => $user['username'],
        'api_key'  => $user['api_key'],
        'balance'  => $user['balance'] ?? 0
    ], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Неверный логин или пароль'], JSON_UNESCAPED_UNICODE);
}