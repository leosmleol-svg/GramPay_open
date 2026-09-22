<?php
declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    generateUserApiKey((int)$user['id']);
}

header('Location: index.php#api');
exit;