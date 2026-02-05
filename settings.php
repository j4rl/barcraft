<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/app.php';

$flash = null;
$lang = i18n_lang($config, $current_user);
$language_options = supported_languages_with_flags();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_language') {
        $language = (string) ($_POST['language'] ?? '');
        if (!is_logged_in($current_user)) {
            $flash = ['type' => 'error', 'message' => t('flash_login_required')];
        } elseif (isset($language_options[$language])) {
            update_user_language($db, (int) $current_user['id'], $language);
            $current_user['language'] = $language;
            $GLOBALS['current_user'] = $current_user;
            $lang = $language;
            $flash = ['type' => 'success', 'message' => t('flash_language_saved')];
        }
    }
}

$page_title = t('settings_title');
$nav_active = 'settings';
$body_class = 'page-settings';
require __DIR__ . '/partials/header.php';
?>

<main class="container">
    <section class="section">
        <div class="section-head">
            <h2><?= e(t('settings_title')) ?></h2>
            <p><?= e(t('settings_intro')) ?></p>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (!is_logged_in($current_user)): ?>
            <div class="empty"><?= e(t('flash_login_required')) ?></div>
        <?php else: ?>
            <div class="settings-grid">
                <form class="form" method="post" action="settings.php">
                    <input type="hidden" name="action" value="save_language">
                    <div class="field">
                        <label><?= e(t('profile_language')) ?></label>
                        <div class="lang-options" role="radiogroup" aria-label="<?= e(t('profile_language')) ?>">
                            <?php foreach ($language_options as $code => $option): ?>
                                <label class="lang-option<?= $code === $lang ? ' is-selected' : '' ?>">
                                    <input type="radio" name="language" value="<?= e($code) ?>" <?= $code === $lang ? 'checked' : '' ?>>
                                    <img src="<?= e($option['flag']) ?>" alt="" class="lang-flag" width="18" height="12" loading="lazy">
                                    <span><?= e($option['label']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn"><?= e(t('profile_language_btn')) ?></button>
                </form>

                <div class="form">
                    <div class="field">
                        <label><?= e(t('settings_theme')) ?></label>
                        <p class="muted"><?= e(t('settings_theme_hint')) ?></p>
                        <div class="theme-grid">
                            <button type="button" class="theme-choice" data-theme-choice="light">
                                <span><?= e(t('theme_light')) ?></span>
                                <small><?= e(t('theme_light_hint')) ?></small>
                            </button>
                            <button type="button" class="theme-choice" data-theme-choice="dark">
                                <span><?= e(t('theme_dark')) ?></span>
                                <small><?= e(t('theme_dark_hint')) ?></small>
                            </button>
                            <button type="button" class="theme-choice" data-theme-choice="system">
                                <span><?= e(t('theme_system')) ?></span>
                                <small><?= e(t('theme_system_hint')) ?></small>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
