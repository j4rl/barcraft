<?php
declare(strict_types=1);

session_start();

$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/ai.php';

$db = db_connect($config);

$current_user = null;
if (isset($_SESSION['user_id'])) {
    $current_user = fetch_user_by_id($db, (int) $_SESSION['user_id']);
    if (!$current_user || (int) $current_user['is_approved'] !== 1) {
        unset($_SESSION['user_id']);
        $current_user = null;
    }
}

$GLOBALS['current_user'] = $current_user;
$GLOBALS['config'] = $config;

function is_logged_in(?array $user): bool
{
    return $user !== null && (int) $user['is_approved'] === 1;
}

function is_admin(?array $user): bool
{
    return is_logged_in($user) && (int) $user['is_admin'] === 1;
}

function require_login(?array $user, string $redirect = 'account.php'): void
{
    if (!is_logged_in($user)) {
        header('Location: ' . $redirect);
        exit;
    }
}
