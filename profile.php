<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/app.php';

$page_title = t('profile_title');
$nav_active = 'profile';
$body_class = 'page-profile';
require __DIR__ . '/partials/header.php';
?>

<main class="container">
    <section class="section">
        <div class="section-head">
            <h2><?= e(t('profile_title')) ?></h2>
            <p><?= e(t('profile_intro')) ?></p>
        </div>

        <?php if (!is_logged_in($current_user)): ?>
            <div class="empty"><?= e(t('flash_login_required')) ?></div>
        <?php else: ?>
            <div class="profile-grid">
                <div class="card card-flat">
                    <h3><?= e(t('profile_overview')) ?></h3>
                    <p class="profile-line"><strong><?= e(t('profile_name')) ?>:</strong> <?= e((string) ($current_user['name'] ?? '')) ?></p>
                    <p class="profile-line"><strong><?= e(t('profile_email')) ?>:</strong> <?= e((string) ($current_user['email'] ?? '')) ?></p>
                    <p class="profile-line">
                        <strong><?= e(t('profile_role')) ?>:</strong>
                        <?= is_admin($current_user) ? e(t('profile_role_admin')) : e(t('profile_role_user')) ?>
                    </p>
                </div>

                <div class="card card-flat">
                    <h3><?= e(t('profile_next')) ?></h3>
                    <div class="stack">
                        <a class="btn" href="settings.php"><?= e(t('profile_settings_cta')) ?></a>
                        <a class="btn btn-light" href="pantry.php"><?= e(t('profile_pantry_cta')) ?></a>
                        <a class="btn btn-light" href="drinks.php"><?= e(t('profile_drinks_cta')) ?></a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
