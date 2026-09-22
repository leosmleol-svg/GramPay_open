<?php declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain = trim($_POST['domain'] ?? '');
    
    // Очищаем ввод: убираем http://, https:// и слеш на конце
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = rtrim($domain, '/');

    if ($domain !== '') {
        $filename = "grampay_verify_{$user['id']}.txt";
        // Контент файла — это MD5 хэш от ID и API-ключа (чтобы было уникально и безопасно)
        $expectedContent = md5((string)$user['id'] . $user['api_key']);
        
        // Настраиваем контекст (5 сек таймаут, игнорим ошибки чужих SSL)
        $context = stream_context_create([
            'http' => ['timeout' => 5],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false]
        ]);

        $urlHttps = "https://{$domain}/{$filename}";
        $urlHttp  = "http://{$domain}/{$filename}";

        // Пробуем прочитать файл (сначала HTTPS, потом HTTP)
        $content = @file_get_contents($urlHttps, false, $context);
        if ($content === false) {
            $content = @file_get_contents($urlHttp, false, $context);
        }

        // Если файл найден и содержимое совпадает
        if ($content !== false && trim($content) === $expectedContent) {
            $users = loadJson('users.json');
            foreach ($users as &$u) {
                if ($u['id'] === $user['id']) {
                    $u['is_website_verified'] = true;
                    $u['verified_domain'] = $domain;
                    break;
                }
            }
            saveJson('users.json', $users);
            header('Location: index.php?verify=success');
            exit;
        } else {
            header('Location: index.php?verify=error');
            exit;
        }
    }
}
header('Location: index.php');