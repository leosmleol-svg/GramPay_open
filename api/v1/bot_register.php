<?php declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../storage.php';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput ?: '', true) ?? $_POST;

$username = trim((string)($input['username'] ?? ''));
$email    = trim((string)($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($username === '' || $email === '' || mb_strlen($password, 'UTF-8') < 6) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Заполните все поля (пароль минимум 6 символов)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Загружаем всех пользователей[cite: 1]
$users = loadJson('users.json');
$searchUser = mb_strtolower($username, 'UTF-8');
$searchEmail = mb_strtolower($email, 'UTF-8');

// Проверка на дубликаты без учета регистра
foreach ($users as $u) {
    $dbUsername = mb_strtolower((string)($u['username'] ?? ''), 'UTF-8');
    $dbEmail = mb_strtolower((string)($u['email'] ?? ''), 'UTF-8');
    
    if ($dbUsername === $searchUser || $dbEmail === $searchEmail) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Пользователь с таким логином или email уже существует'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Создаем нового пользователя[cite: 1]
$user = createUser($username, $email, $password);

echo json_encode([
    'ok'       => true,
    'user_id'  => $user['id'],
    'username' => $user['username'],
    'api_key'  => $user['api_key'],
    'balance'  => 0
], JSON_UNESCAPED_UNICODE);