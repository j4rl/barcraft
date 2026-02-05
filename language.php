<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/app.php';

$return = trim((string) ($_POST['return'] ?? 'index.php'));
if ($return === '' ||
    strpos($return, '://') !== false ||
    strpos($return, "\n") !== false ||
    strpos($return, "\r") !== false ||
    !preg_match('/^\\/?[a-z0-9_\\-\\/]+\\.php(\\?.*)?$/i', $return)
) {
    $return = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $language = (string) ($_POST['language'] ?? '');
    $languages = supported_languages();

    if (isset($languages[$language])) {
        if (is_logged_in($current_user)) {
            update_user_language($db, (int) $current_user['id'], $language);
            $current_user['language'] = $language;
            $GLOBALS['current_user'] = $current_user;
        } else {
            $_SESSION['lang'] = $language;
        }
    }
}

header('Location: ' . $return);
exit;
