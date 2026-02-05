<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/views.php';

$flash = null;
$pantry_names = [];
$pantry_norm = [];
$filter = trim((string) ($_GET['filter'] ?? ($_POST['filter'] ?? '')));
$almost_filter = (int) ($_GET['missing'] ?? 0);
if (!in_array($almost_filter, [0, 1, 2], true)) {
    $almost_filter = 0;
}

if (is_logged_in($current_user)) {
    $pantry_names = fetch_user_pantry($db, (int) $current_user['id']);
    foreach ($pantry_names as $name) {
        $pantry_norm[] = normalize_ingredient($name);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_pantry') {
        if (!is_logged_in($current_user)) {
            $flash = ['type' => 'error', 'message' => t('flash_login_required')];
        } else {
            $selected = $_POST['pantry'] ?? [];
            if (!is_array($selected)) {
                $selected = [];
            }

            $list = [];
            foreach ($selected as $item) {
                $norm = normalize_ingredient((string) $item);
                if ($norm !== '') {
                    $list[] = $norm;
                }
            }

            replace_user_pantry($db, (int) $current_user['id'], $list);
            $flash = ['type' => 'success', 'message' => t('flash_pantry_saved')];
            $pantry_names = $list;
            $pantry_norm = $list;
        }
    }
}

$ingredients = fetch_all_ingredients($db);
$filtered_ingredients = [];
if ($filter !== '') {
    $needle = str_lower($filter);
    foreach ($ingredients as $name) {
        $hay = str_lower($name);
        if (strpos($hay, $needle) !== false) {
            $filtered_ingredients[] = $name;
        }
    }
} else {
    $filtered_ingredients = $ingredients;
}

$ingredient_map = array_fill_keys($pantry_norm, true);

$all_drinks = fetch_drinks($db);
$possible = $pantry_norm !== [] ? filter_possible($all_drinks, $pantry_norm) : [];
$possible_filtered = false;
if ($almost_filter !== 0) {
    $possible_filtered = true;
}
$almost = [];
$almost_all = [];
$almost_counts = [1 => 0, 2 => 0];
if ($pantry_norm !== []) {
    $pantry_map = array_fill_keys($pantry_norm, true);
    foreach ($all_drinks as $drink) {
        $required = $drink['ingredient_norms'] ?? [];
        if ($required === []) {
            continue;
        }
        $missing = [];
        foreach ($required as $req) {
            if (!isset($pantry_map[$req])) {
                $missing[] = $req;
            }
        }
        $missing_count = count($missing);
        if ($missing_count >= 1 && $missing_count <= 2) {
            $drink['missing_ingredients'] = $missing;
            $almost_all[] = $drink;
            $almost_counts[$missing_count] = ($almost_counts[$missing_count] ?? 0) + 1;
        }
    }
}
if ($almost_filter === 1 || $almost_filter === 2) {
    foreach ($almost_all as $drink) {
        if (count($drink['missing_ingredients']) === $almost_filter) {
            $almost[] = $drink;
        }
    }
} else {
    $almost = $almost_all;
}
$filter_param = $filter !== '' ? '&filter=' . rawurlencode($filter) : '';

$page_title = t('pantry_title');
$nav_active = 'pantry';
$body_class = 'page-pantry';
require __DIR__ . '/partials/header.php';
?>

<main class="container">
    <section class="section">
        <div class="section-head">
            <h2><?= e(t('pantry_title')) ?></h2>
            <p><?= e(t('pantry_hint')) ?></p>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (!is_logged_in($current_user)): ?>
            <div class="empty"><?= e(t('flash_login_required')) ?></div>
        <?php else: ?>
            <form class="form pantry-form" method="post" action="pantry.php">
                <input type="hidden" name="action" value="save_pantry">
                <input type="hidden" name="filter" value="<?= e($filter) ?>">
                <div class="scroll-panel pantry-scroll">
                    <div class="pantry-grid">
                        <?php if ($ingredients === []): ?>
                            <div class="empty"><?= e(t('pantry_empty')) ?></div>
                        <?php elseif ($filtered_ingredients === []): ?>
                            <div class="empty"><?= e(t('pantry_filter_empty')) ?></div>
                        <?php else: ?>
                            <?php foreach ($filtered_ingredients as $name): ?>
                                <?php $norm = normalize_ingredient($name); ?>
                                <label class="check-item">
                                    <input type="checkbox" name="pantry[]" value="<?= e($norm) ?>" <?= isset($ingredient_map[$norm]) ? 'checked' : '' ?>>
                                    <span><?= e(display_ingredient($name)) ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="btn"><?= e(t('pantry_save')) ?></button>
            </form>

            <form class="form filter-form" method="get" action="pantry.php">
                <div class="field">
                    <label for="filter"><?= e(t('pantry_filter_label')) ?></label>
                    <input id="filter" name="filter" type="text" placeholder="<?= e(t('pantry_filter_placeholder')) ?>" value="<?= e($filter) ?>">
                </div>
                <button type="submit" class="btn btn-light btn-small"><?= e(t('pantry_filter_button')) ?></button>
            </form>

            <div class="section-head section-head-row">
                <div>
                    <h3><?= e(t('pantry_possible')) ?></h3>
                    <p><?= e(t('pantry_possible_hint')) ?></p>
                </div>
                <?php if ($pantry_norm !== []): ?>
                    <span class="pill"><?= $possible_filtered ? 0 : count($possible) ?> <?= e(t('mix_count')) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($pantry_norm === []): ?>
                <div class="empty"><?= e(t('pantry_none_selected')) ?></div>
            <?php elseif ($possible_filtered): ?>
                <div class="empty"><?= e(t('pantry_possible_filtered')) ?></div>
            <?php elseif ($possible === []): ?>
                <div class="empty"><?= e(t('mix_none')) ?></div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($possible as $drink): ?>
                        <?php render_drink_card($drink); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="section-head section-head-row">
                <div>
                    <h3><?= e(t('pantry_almost')) ?></h3>
                    <p><?= e(t('pantry_almost_hint')) ?></p>
                </div>
                <?php if ($pantry_norm !== []): ?>
                    <div class="almost-filter">
                        <span class="muted"><?= e(t('pantry_almost_filter_label')) ?></span>
                        <div class="filter-pills">
                            <a class="filter-pill<?= $almost_filter === 0 ? ' is-active' : '' ?>" href="pantry.php?missing=0<?= e($filter_param) ?>"><?= e(t('pantry_almost_filter_all')) ?></a>
                            <a class="filter-pill<?= $almost_filter === 1 ? ' is-active' : '' ?>" href="pantry.php?missing=1<?= e($filter_param) ?>"><?= e(t('pantry_almost_filter_one', ['count' => (string) ($almost_counts[1] ?? 0)])) ?></a>
                            <a class="filter-pill<?= $almost_filter === 2 ? ' is-active' : '' ?>" href="pantry.php?missing=2<?= e($filter_param) ?>"><?= e(t('pantry_almost_filter_two', ['count' => (string) ($almost_counts[2] ?? 0)])) ?></a>
                        </div>
                    </div>
                    <span class="pill"><?= count($almost) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($pantry_norm !== [] && $almost !== []): ?>
                <div class="legend">
                    <span class="chip chip-missing"><?= e(t('pantry_legend_chip')) ?></span>
                    <span><?= e(t('pantry_legend_missing')) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($pantry_norm === []): ?>
                <div class="empty"><?= e(t('pantry_none_selected')) ?></div>
            <?php elseif ($almost === []): ?>
                <div class="empty"><?= e(t('pantry_almost_none')) ?></div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($almost as $drink): ?>
                        <?php render_drink_card($drink); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
