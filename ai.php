<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/views.php';

$flash = null;
$ai_form = [
    'name' => '',
    'notes' => '',
];
$ai_result = null;
$ai_ingredients_text = '';
$ai_error_detail = '';
$ai_enabled = !empty($config['ai']['enabled']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!$ai_enabled) {
        $flash = ['type' => 'error', 'message' => t('ai_disabled')];
    }

    if ($action === 'generate_ai') {
        $ai_form['name'] = trim((string) ($_POST['ai_name'] ?? ''));
        $ai_form['notes'] = trim((string) ($_POST['ai_notes'] ?? ''));

        if (!$ai_enabled) {
            // handled above
        } elseif (!is_logged_in($current_user)) {
            $flash = ['type' => 'error', 'message' => t('flash_login_required')];
        } elseif ($ai_form['name'] === '') {
            $flash = ['type' => 'error', 'message' => t('error_name_required')];
        } else {
            $lang_current = i18n_lang($config, $current_user);
            $result = ai_generate_drink($config, $ai_form['name'], $ai_form['notes'], $lang_current);
            if (!$result['ok']) {
                if (($result['error'] ?? '') === 'missing_api_key') {
                    $flash = ['type' => 'error', 'message' => t('ai_missing_key')];
                } else {
                    $flash = ['type' => 'error', 'message' => t('ai_error_failed')];
                    $ai_error_detail = (string) ($result['error'] ?? '');
                    if ($ai_error_detail !== '') {
                        error_log('[AI] ' . $ai_error_detail);
                    }
                }
            } else {
                $ai_result = $result['drink'];
                $ai_ingredients_text = ingredients_to_text($ai_result['ingredients']);
            }
        }
    }

    if ($action === 'save_ai') {
        if (!$ai_enabled) {
            // handled above
        } elseif (!is_logged_in($current_user)) {
            $flash = ['type' => 'error', 'message' => t('flash_login_required')];
        } else {
            $name = trim((string) ($_POST['ai_name'] ?? ''));
            $description = trim((string) ($_POST['ai_description'] ?? ''));
            $instructions = trim((string) ($_POST['ai_instructions'] ?? ''));
            $quote = trim((string) ($_POST['ai_quote'] ?? ''));
            $ingredients_text = trim((string) ($_POST['ai_ingredients'] ?? ''));

            $errors = [];
            if ($name === '') {
                $errors[] = t('error_name_required');
            }
            if ($instructions === '') {
                $errors[] = t('error_instructions_required');
            }

            $ingredients = parse_ingredient_lines($ingredients_text);
            if ($ingredients === []) {
                $errors[] = t('error_ingredients_required');
            }

            if ($errors !== []) {
                $flash = ['type' => 'error', 'message' => implode(' ', $errors)];
            } else {
                try {
                    $drink_id = insert_drink($db, [
                        'name' => $name,
                        'description' => $description,
                        'instructions' => $instructions,
                        'quote' => $quote,
                        'is_classic' => 0,
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

$page_title = t('ai_title');
$nav_active = 'ai';
$body_class = 'page-ai';
require __DIR__ . '/partials/header.php';
?>

<main class="container">
    <section class="section">
        <div class="section-head">
            <h2><?= e(t('ai_title')) ?></h2>
            <p><?= e(t('ai_hint')) ?></p>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php if ($ai_error_detail !== ''): ?>
                <p class="muted ai-error-detail"><?= e($ai_error_detail) ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!$ai_enabled): ?>
            <div class="empty"><?= e(t('ai_disabled')) ?></div>
        <?php elseif (!is_logged_in($current_user)): ?>
            <div class="empty"><?= e(t('ai_login')) ?></div>
        <?php elseif (trim((string) ($config['gemini']['api_key'] ?? '')) === ''): ?>
            <div class="empty"><?= e(t('ai_missing_key')) ?></div>
        <?php else: ?>
            <form class="form" method="post" action="ai.php">
                <input type="hidden" name="action" value="generate_ai">
                <div class="field">
                    <label for="ai_name"><?= e(t('ai_name_label')) ?></label>
                    <input id="ai_name" name="ai_name" type="text" value="<?= e($ai_form['name']) ?>" placeholder="<?= e(t('ai_name_placeholder')) ?>">
                </div>
                <div class="field">
                    <label for="ai_notes"><?= e(t('ai_notes_label')) ?></label>
                    <textarea id="ai_notes" name="ai_notes" rows="2" placeholder="<?= e(t('ai_notes_placeholder')) ?>"><?= e($ai_form['notes']) ?></textarea>
                </div>
                <button type="submit" class="btn"><?= e(t('ai_button')) ?></button>
            </form>
        <?php endif; ?>

        <?php if (is_array($ai_result)): ?>
            <?php if ($ai_ingredients_text === '') { $ai_ingredients_text = ingredients_to_text($ai_result['ingredients']); } ?>
            <h3 class="section-sub"><?= e(t('ai_result_title')) ?></h3>
            <div class="grid">
                <?php render_drink_card([
                    'name' => $ai_result['name'],
                    'description' => $ai_result['description'],
                    'instructions' => $ai_result['instructions'],
                    'quote' => $ai_result['quote'],
                    'ingredients' => $ai_result['ingredients'],
                    'is_classic' => 0,
                ]); ?>
            </div>
            <form class="form" method="post" action="ai.php">
                <input type="hidden" name="action" value="save_ai">
                <input type="hidden" name="ai_name" value="<?= e($ai_result['name']) ?>">
                <input type="hidden" name="ai_description" value="<?= e($ai_result['description']) ?>">
                <input type="hidden" name="ai_instructions" value="<?= e($ai_result['instructions']) ?>">
                <input type="hidden" name="ai_quote" value="<?= e($ai_result['quote']) ?>">
                <textarea name="ai_ingredients" class="hidden-field"><?= e($ai_ingredients_text) ?></textarea>
                <button type="submit" class="btn"><?= e(t('ai_save_button')) ?></button>
            </form>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
