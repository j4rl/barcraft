<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/views.php';

$q = trim((string) ($_GET['q'] ?? ''));
$all_drinks = fetch_drinks($db);
$drinks = $q !== '' ? filter_search($all_drinks, $q) : $all_drinks;

$selected_id = (int) ($_GET['id'] ?? 0);
$selected = null;
if ($selected_id > 0) {
    foreach ($drinks as $drink) {
        if ((int) $drink['id'] === $selected_id) {
            $selected = $drink;
            break;
        }
    }
}

$page_title = t('drinks_title');
$nav_active = 'drinks';
$body_class = 'page-drinks';
require __DIR__ . '/partials/header.php';
?>

<main class="container">
    <section class="section">
        <div class="section-head section-head-row">
            <div>
                <h2><?= e(t('drinks_title')) ?></h2>
                <p><?= e(t('drinks_hint')) ?></p>
            </div>
            <a class="btn" href="create.php"><?= e(t('drinks_create_btn')) ?></a>
        </div>
    </section>

    <section class="section">
        <form class="form form-compact" method="get" action="drinks.php">
            <div class="field">
                <label for="q"><?= e(t('search_label')) ?></label>
                <input id="q" name="q" type="text" placeholder="<?= e(t('search_placeholder')) ?>" value="<?= e($q) ?>">
            </div>
            <button type="submit" class="btn btn-small"><?= e(t('search_button')) ?></button>
        </form>

        <?php if (!$selected): ?>
            <div class="list-hint"><?= e(t('drinks_select')) ?></div>
        <?php endif; ?>

        <div class="scroll-panel drink-scroll">
            <div class="drink-list">
                <?php if ($drinks === []): ?>
                    <div class="empty"><?= e(t('drinks_empty')) ?></div>
                <?php else: ?>
                    <?php foreach ($drinks as $drink): ?>
                        <?php
                        $item_active = $selected && (int) $drink['id'] === (int) $selected['id'];
                        $link = 'drinks.php?id=' . (int) $drink['id'];
                        if ($q !== '') {
                            $link .= '&q=' . rawurlencode($q);
                        }
                        $link .= '#detail';
                        $desc = trim((string) ($drink['description'] ?? ''));
                        if ($desc === '') {
                            $desc = t('drinks_no_description');
                        }
                        ?>
                        <a class="drink-row<?= $item_active ? ' is-active' : '' ?>" href="<?= e($link) ?>">
                            <div>
                                <strong><?= e((string) $drink['name']) ?></strong>
                                <span class="muted"><?= e($desc) ?></span>
                            </div>
                            <span class="pill"><?= (int) ($drink['is_classic'] ?? 0) === 1 ? e(t('card_classic')) : e(t('card_user')) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($selected): ?>
            <div class="drink-detail-panel" id="detail">
                <?php render_drink_card($selected); ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
