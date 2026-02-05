<?php
$app_name = $config['app']['name'] ?? 'Barcraft';
$lang = i18n_lang($config, $current_user);
$language_options = supported_languages_with_flags();
$page_title = $page_title ?? $app_name;
$nav_active = $nav_active ?? '';
$body_class = $body_class ?? '';
$account_active = in_array($nav_active, ['profile', 'settings'], true);
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($body_class) ?>">
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="index.php"><?= e($app_name) ?></a>
            <nav class="nav">
                <a href="index.php" class="nav-link<?= $nav_active === 'home' ? ' is-active' : '' ?>"><?= e(t('nav_home')) ?></a>
                <a href="drinks.php" class="nav-link<?= $nav_active === 'drinks' ? ' is-active' : '' ?>"><?= e(t('nav_drinks')) ?></a>
                <a href="pantry.php" class="nav-link<?= $nav_active === 'pantry' ? ' is-active' : '' ?>"><?= e(t('nav_barskap')) ?></a>
                <?php if (is_logged_in($current_user) && !empty($config['ai']['enabled'])): ?>
                    <a href="ai.php" class="nav-link<?= $nav_active === 'ai' ? ' is-active' : '' ?>"><?= e(t('nav_ai')) ?></a>
                <?php endif; ?>
                <?php if (is_logged_in($current_user)): ?>
                    <details class="account-menu">
                        <summary class="nav-link<?= $account_active ? ' is-active' : '' ?>"><?= e(t('auth_title')) ?></summary>
                        <div class="account-panel">
                            <a class="account-link<?= $nav_active === 'profile' ? ' is-active' : '' ?>" href="profile.php"><?= e(t('nav_profile')) ?></a>
                            <a class="account-link<?= $nav_active === 'settings' ? ' is-active' : '' ?>" href="settings.php"><?= e(t('nav_settings')) ?></a>
                        </div>
                    </details>
                <?php endif; ?>
                <?php if (is_admin($current_user)): ?>
                    <a href="admin.php" class="nav-link<?= $nav_active === 'admin' ? ' is-active' : '' ?>"><?= e(t('nav_admin')) ?></a>
                <?php endif; ?>
            </nav>
            <div class="topbar-actions">
                <form method="post" action="language.php" class="lang-menu" aria-label="<?= e(t('profile_language')) ?>">
                    <input type="hidden" name="action" value="save_language">
                    <input type="hidden" name="return" value="<?= e((string) ($_SERVER['REQUEST_URI'] ?? 'index.php')) ?>">
                    <details>
                        <summary>
                            <img src="<?= e($language_options[$lang]['flag'] ?? 'assets/en.png') ?>" alt="" class="lang-flag" width="18" height="12" loading="lazy">
                            <span><?= e($language_options[$lang]['label'] ?? 'English') ?></span>
                            <span class="caret" aria-hidden="true"></span>
                        </summary>
                        <div class="lang-panel">
                            <?php foreach ($language_options as $code => $option): ?>
                                <button
                                    type="submit"
                                    class="lang-option-btn<?= $code === $lang ? ' is-selected' : '' ?>"
                                    name="language"
                                    value="<?= e($code) ?>"
                                >
                                    <img src="<?= e($option['flag']) ?>" alt="" class="lang-flag" width="18" height="12" loading="lazy">
                                    <span><?= e($option['label']) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </form>
                <button type="button" class="theme-toggle" data-theme-toggle>
                    <span class="theme-dot"></span>
                    <span class="theme-text"><?= e(t('theme_toggle')) ?></span>
                </button>
                <?php if (is_logged_in($current_user)): ?>
                    <span class="user-pill"><?= e((string) ($current_user['name'] ?? '')) ?></span>
                    <form method="post" action="account.php" class="logout-form">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn btn-light btn-small"><?= e(t('auth_logout')) ?></button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-light btn-small" href="account.php"><?= e(t('auth_login_btn')) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>
