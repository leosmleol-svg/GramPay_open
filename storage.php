<?php
declare(strict_types=1);

define('DATA_DIR', __DIR__ . '/data');

function initStorage(): void
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0777, true);
    }
    if (!file_exists(DATA_DIR . '/users.json')) {
        file_put_contents(DATA_DIR . '/users.json', json_encode([], JSON_PRETTY_PRINT));
    }
    if (!file_exists(DATA_DIR . '/transactions.json')) {
        file_put_contents(DATA_DIR . '/transactions.json', json_encode([], JSON_PRETTY_PRINT));
    }
}

function loadJson(string $filename): array
{
    initStorage();
    $path = DATA_DIR . '/' . $filename;
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content ?: '[]', true);
    return is_array($data) ? $data : [];
}

function saveJson(string $filename, array $data): void
{
    initStorage();
    $path = DATA_DIR . '/' . $filename;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function getApiKeyFromRequest(): ?string
{
    if (!empty($_SERVER['HTTP_X_API_KEY'])) return trim((string)$_SERVER['HTTP_X_API_KEY']);
    if (!empty($_SERVER['HTTP_X_APIKEY'])) return trim((string)$_SERVER['HTTP_X_APIKEY']);

    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!empty($auth) && preg_match('/Bearer\s+(.+)/i', (string)$auth, $m)) {
        return trim((string)$m[1]);
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $val) {
            $lower = strtolower((string)$name);
            if ($lower === 'x-api-key' || $lower === 'x-apikey') {
                return trim((string)$val);
            }
            if ($lower === 'authorization' && preg_match('/Bearer\s+(.+)/i', (string)$val, $m)) {
                return trim((string)$m[1]);
            }
        }
    }

    if (!empty($_GET['api_key'])) return trim((string)$_GET['api_key']);
    if (!empty($_POST['api_key'])) return trim((string)$_POST['api_key']);

    // Не читаем php://input здесь: endpoint может уже разобрать JSON-тело
    // запроса, а повторное чтение может вернуть пустую строку.

    return null;
}

function findUserByApiKey(?string $apiKey): ?array
{
    if (empty($apiKey)) return null;
    $apiKey = trim($apiKey);
    $users = loadJson('users.json');
    foreach ($users as $user) {
        if (!empty($user['api_key']) && hash_equals(trim((string)$user['api_key']), $apiKey)) {
            return $user;
        }
    }
    return null;
}

function generateUserApiKey(int $userId): string
{
    $users = loadJson('users.json');
    $newKey = 'gp_sec_' . bin2hex(random_bytes(24));
    
    foreach ($users as &$user) {
        if ((int)($user['id'] ?? 0) === $userId) {
            $user['api_key'] = $newKey;
            $user['api_key_created_at'] = date('Y-m-d H:i:s');
            saveJson('users.json', $users);
            return $newKey;
        }
    }
    return $newKey;
}

function updateMerchantSettings(int $userId, string $returnUrl, string $failedUrl): bool
{
    $users = loadJson('users.json');
    foreach ($users as &$user) {
        if ((int)($user['id'] ?? 0) === $userId) {
            $user['default_return_url'] = trim($returnUrl);
            $user['default_failed_url'] = trim($failedUrl);
            saveJson('users.json', $users);
            return true;
        }
    }
    return false;
}

function findUserById(int $id): ?array
{
    $users = loadJson('users.json');
    foreach ($users as &$user) {
        if ((int)($user['id'] ?? 0) === $id) {
            if (empty($user['api_key'])) {
                $user['api_key'] = 'gp_sec_' . bin2hex(random_bytes(24));
                saveJson('users.json', $users);
            }
            return $user;
        }
    }
    return null;
}

function findUserByLogin(string $login): ?array
{
    $users = loadJson('users.json');
    foreach ($users as $user) {
        if (($user['username'] ?? '') === $login || ($user['email'] ?? '') === $login) {
            return $user;
        }
    }
    return null;
}

function createUser(string $username, string $email, string $password): array
{
    $users = loadJson('users.json');
    $newId = empty($users) ? 1 : (max(array_column($users, 'id')) + 1);

    $newUser = [
        'id'                 => $newId,
        'username'           => $username,
        'email'              => $email,
        'password_hash'      => password_hash($password, PASSWORD_DEFAULT),
        'api_key'            => 'gp_sec_' . bin2hex(random_bytes(24)),
        'default_return_url' => '',
        'default_failed_url' => '',
        'balance'            => 0,
        'created_at'         => date('Y-m-d H:i:s'),
    ];

    $users[] = $newUser;
    saveJson('users.json', $users);
    return $newUser;
}

function updateUserBalance(int $userId, int $amountToAdd): bool
{
    $users = loadJson('users.json');
    foreach ($users as &$user) {
        if ((int)($user['id'] ?? 0) === $userId) {
            $user['balance'] = (int)($user['balance'] ?? 0) + $amountToAdd;
            saveJson('users.json', $users);
            return true;
        }
    }
    return false;
}

function saveTransaction(array $tx): void
{
    $txs = loadJson('transactions.json');
    $txs[] = $tx;
    saveJson('transactions.json', $txs);
}

function findTransactionByIdempotencyKey(int $userId, string $key): ?array
{
    $key = trim($key);
    if ($key === '') return null;
    $txs = loadJson('transactions.json');
    foreach ($txs as $tx) {
        if ((int)($tx['user_id'] ?? 0) === $userId && (string)($tx['idempotency_key'] ?? '') === $key) {
            return $tx;
        }
    }
    return null;
}

function findTransaction(string $transactionId): ?array
{
    $txs = loadJson('transactions.json');
    foreach ($txs as $tx) {
        if (($tx['transaction_id'] ?? '') === $transactionId) {
            return $tx;
        }
    }
    return null;
}

function updateTransactionStatus(string $transactionId, string $status): bool
{
    $txs = loadJson('transactions.json');
    foreach ($txs as &$tx) {
        if (($tx['transaction_id'] ?? '') === $transactionId) {
            $tx['status'] = $status;
            $tx['updated_at'] = date('Y-m-d H:i:s');
            saveJson('transactions.json', $txs);
            return true;
        }
    }
    return false;
}

function getUserTransactions(int $userId): array
{
    $txs = loadJson('transactions.json');
    $result = [];
    foreach ($txs as $tx) {
        if ((int)($tx['user_id'] ?? 0) === $userId) {
            $result[] = $tx;
        }
    }
    usort($result, function ($a, $b) {
        return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
    });
    return $result;
}