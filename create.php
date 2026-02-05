<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/app.php';

$flash = null;
$create_form = [
    'name' => '',
    'description' => '',
    'instructions' => '',
    'quote' => '',
    'ingredients' => '',
    'is_classic' => '0',
];

if (isset($_GET['added']) && $_GET['added'] === '1') {
    $flash = ['type' => 'success', 'message' => t('flash_saved')];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        if (!is_logged_in($current_user)) {
            $flash = ['type' => 'error', 'message' => t('flash_login_required')];
        } else {
            $create_form['name'] = trim((string) ($_POST['name'] ?? ''));
            $create_form['description'] = trim((string) ($_POST['description'] ?? ''));
            $create_form['instructions'] = trim((string) ($_POST['instructions'] ?? ''));
            $create_form['quote'] = trim((string) ($_POST['quote'] ?? ''));
            $create_form['ingredients'] = trim((string) ($_POST['ingredients'] ?? ''));
            $create_form['is_classic'] = (string) ((int) ($_POST['is_classic'] ?? 0));

            $errors = [];
            if ($create_form['name'] === '') {
                $errors[] = t('error_name_required');
            }
            if ($create_form['instructions'] === '') {
                $errors[] = t('error_instructions_required');
            }

            $ingredients = parse_ingredient_lines($create_form['ingredients']);
            if ($ingredients === []) {
                $errors[] = t('error_ingredients_required');
            }

            if ($errors !== []) {
                $flash = ['type' => 'error', 'message' => implode(' ', $errors)];
            } else {
                $is_classic = is_admin($current_user) ? (int) $create_form['is_classic'] : 0;
                try {
                    $drink_id = insert_drink($db, [
                        'name' => $create_form['name'],
                        'description' => $create_form['description'],
                        'instructions' => $create_form['instructions'],
                        'quote' => $create_form['quote'],
                        'is_classic' => $is_classic,
                    ], $ingredients);

                    header('Location: drinks.php?id=' . $drink_id);
                    exit;
                } catch (mysqli_sql_exception $e) {
                    $flash = ['type' => 'error', 'message' => t('flash_db_error')];
                }
            }
        }
    }
}

$page_title = t('create_title');
$nav_active = 'drinks';
$body_class = 'page-create';
require __DIR__ . '/partials/header.php';
?>

<main class="container">
    <section class="section">
        <div class="section-head">
            <h2><?= e(t('create_title')) ?></h2>
            <p><?= e(t('create_hint')) ?></p>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (!is_logged_in($current_user)): ?>
            <div class="empty"><?= e(t('create_login')) ?></div>
        <?php else: ?>
            <form class="form" method="post" action="create.php">
                <input type="hidden" name="action" value="add">
                <div class="field">
                    <label for="name"><?= e(t('field_name')) ?></label>
                    <input id="name" name="name" type="text" value="<?= e($create_form['name']) ?>" placeholder="<?= e(t('placeholder_name')) ?>">
                </div>
                <div class="field">
                    <label for="description"><?= e(t('field_description')) ?></label>
                    <input id="description" name="description" type="text" value="<?= e($create_form['description']) ?>" placeholder="<?= e(t('placeholder_description')) ?>">
                </div>
                <div class="field">
                    <label for="ingredients"><?= e(t('field_ingredients')) ?></label>
                    <textarea id="ingredients" name="ingredients" rows="4" placeholder="<?= e(t('placeholder_ingredients')) ?>"><?= e($create_form['ingredients']) ?></textarea>
                </div>
                <div class="field">
                    <label for="instructions"><?= e(t('field_instructions')) ?></label>
                    <textarea id="instructions" name="instructions" rows="4" placeholder="<?= e(t('placeholder_instructions')) ?>"><?= e($create_form['instructions']) ?></textarea>
                </div>
                <div class="field">
                    <label for="quote"><?= e(t('field_quote')) ?></label>
                    <textarea id="quote" name="quote" rows="2" placeholder="<?= e(t('placeholder_quote')) ?>"><?= e($create_form['quote']) ?></textarea>
                </div>
                <?php if (is_admin($current_user)): ?>
                    <div class="field">
                        <label for="is_classic"><?= e(t('field_type')) ?></label>
                        <select id="is_classic" name="is_classic">
                            <option value="0" <?= $create_form['is_classic'] === '0' ? 'selected' : '' ?>><?= e(t('type_user')) ?></option>
                            <option value="1" <?= $create_form['is_classic'] === '1' ? 'selected' : '' ?>><?= e(t('type_classic')) ?></option>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="is_classic" value="0">
                <?php endif; ?>
                <button type="submit" class="btn"><?= e(t('button_save')) ?></button>
            </form>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
