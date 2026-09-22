<?php
declare(strict_types=1);
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/auth.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnUrl = trim((string)($_POST['default_return_url'] ?? ''));
    $failedUrl = trim((string)($_POST['default_failed_url'] ?? ''));

    updateMerchantSettings((int)$user['id'], $returnUrl, $failedUrl);
}

header('Location: index.php?settings=saved#settings');
exit;