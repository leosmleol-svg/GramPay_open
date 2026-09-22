<?php
declare(strict_types=1);
require_once __DIR__ . '/storage.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function getCurrentUser(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return findUserById((int)$_SESSION['user_id']);
}

function requireAuth(): array {
    $user = getCurrentUser();
    if (!$user) { header('Location: login.php'); exit; }

    $userId = (int)$user['id'];
    $twoFactorEnabled = !empty($user['two_factor_enabled']) && !empty($user['two_factor_secret']);
    $twoFactorVerified = (int)($_SESSION['2fa_verified'] ?? 0) === $userId;

    $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($twoFactorEnabled && !$twoFactorVerified && $script !== '2fa_check.php' && $script !== 'logout.php') {
        header('Location: 2fa_check.php'); exit;
    }
    return $user;
}
