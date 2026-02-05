<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/views.php';

$q = trim((string) ($_GET['q'] ?? ''));
$all_drinks = fetch_drinks($db);
$results = $q !== '' ? filter_search($all_drinks, $q) : [];

$classic_count = count(array_filter($all_drinks, static function (array $drink): bool {
    return (int) ($drink['is_classic'] ?? 0) === 1;
}));

$user_count = count($all_drinks) - $classic_count;

$page_title = $config['app']['name'] ?? 'Barcraft';
$nav_active = 'home';
$body_class = 'page-home';
require __DIR__ . '/partials/header.php';
?>

<section class="hero hero-home">
    <div class="hero-inner">
        <div>
            <p class="eyebrow"><?= e(t('app_eyebrow')) ?></p>
            <h1><?= e(t('hero_title')) ?></h1>
            <p class="lead"><?= e(t('hero_lead')) ?></p>
            <div class="hero-actions">
                <a class="btn" href="drinks.php"><?= e(t('home_browse_cta')) ?></a>
                <a class="btn btn-light" href="pantry.php"><?= e(t('home_pantry_cta')) ?></a>
            </div>
        </div>
        <div class="hero-card">
            <div class="hero-stats">
                <div>
                    <span class="stat"><?= count($all_drinks) ?></span>
                    <span class="label"><?= e(t('stat_total')) ?></span>
                </div>
                <div>
                    <span class="stat"><?= $classic_count ?></span>
                    <span class="label"><?= e(t('stat_classics')) ?></span>
                </div>
                <div>
                    <span class="stat"><?= $user_count ?></span>
                    <span class="label"><?= e(t('stat_user')) ?></span>
                </div>
            </div>
            <p class="hero-note"><?= e(t('hero_note')) ?></p>
        </div>
    </div>
</section>

<main class="container">
    <section class="section search-section">
        <div class="section-head">
            <h2><?= e(t('search_title')) ?></h2>
            <p><?= e(t('search_hint')) ?></p>
        </div>
        <form class="form search-form" method="get" action="index.php">
            <div class="field">
                <label for="q"><?= e(t('search_label')) ?></label>
                <input id="q" name="q" type="text" placeholder="<?= e(t('search_placeholder')) ?>" value="<?= e($q) ?>">
            </div>
            <button type="submit" class="btn"><?= e(t('search_button')) ?></button>
        </form>

        <?php if ($q !== ''): ?>
            <p class="result-note"><?= count($results) ?> <?= e(t('search_result')) ?> "<?= e($q) ?>".</p>
            <?php if ($results !== []): ?>
                <div class="drink-list compact">
                    <?php foreach ($results as $drink): ?>
                        <a class="drink-row" href="drinks.php?id=<?= (int) $drink['id'] ?>">
                            <div>
                                <strong><?= e((string) $drink['name']) ?></strong>
                                <span class="muted">
                                    <?= e(trim((string) ($drink['description'] ?? '')) !== '' ? (string) $drink['description'] : t('drinks_no_description')) ?>
                                </span>
                            </div>
                            <span class="pill"><?= (int) ($drink['is_classic'] ?? 0) === 1 ? e(t('card_classic')) : e(t('card_user')) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty"><?= e(t('browse_empty')) ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
