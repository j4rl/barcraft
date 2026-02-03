<?php
declare(strict_types=1);

session_start();

$config = require __DIR__ . '/config.php';
require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/i18n.php';
require __DIR__ . '/lib/ai.php';

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

function render_drink_card(array $drink): void
{
    $title = e($drink['name']);
    $description = trim((string) ($drink['description'] ?? ''));
    $instructions = trim((string) ($drink['instructions'] ?? ''));
    $quote = trim((string) ($drink['quote'] ?? ''));
    $is_classic = (int) ($drink['is_classic'] ?? 0) === 1;

    echo '<article class="card">';
    echo '<div class="card-head">';
    echo '<h3>' . $title . '</h3>';
    echo '<span class="tag ' . ($is_classic ? 'tag-classic' : 'tag-user') . '">' . ($is_classic ? e(t('card_classic')) : e(t('card_user'))) . '</span>';
    echo '</div>';

    if ($description !== '') {
        echo '<p class="desc">' . nl2br(e($description)) . '</p>';
    }

    if (!empty($drink['ingredients'])) {
        echo '<div class="chips">';
        foreach ($drink['ingredients'] as $ingredient) {
            $label = display_ingredient($ingredient['name']);
            if (!empty($ingredient['amount'])) {
                $label .= ' · ' . $ingredient['amount'];
            }
            echo '<span class="chip">' . e($label) . '</span>';
        }
        echo '</div>';
    }

    if ($instructions !== '') {
        echo '<div class="instructions"><h4>' . e(t('card_how')) . '</h4><div class="text">' . nl2br(e($instructions)) . '</div></div>';
    }

    if ($quote !== '') {
        echo '<blockquote>' . nl2br(e($quote)) . '</blockquote>';
    }

    echo '</article>';
}

function ingredients_to_text(array $ingredients): string
{
    $lines = [];
    foreach ($ingredients as $ingredient) {
        if (!is_array($ingredient)) {
            continue;
        }
        $name = trim((string) ($ingredient['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $amount = trim((string) ($ingredient['amount'] ?? ''));
        $lines[] = $amount !== '' ? ($name . ' - ' . $amount) : $name;
    }

    return implode("\n", $lines);
}

$flash = null;
$create_form = [
    'name' => '',
    'description' => '',
    'instructions' => '',
    'quote' => '',
    'ingredients' => '',
    'is_classic' => '0',
];

$ai_form = [
    'name' => '',
    'notes' => '',
];
$ai_result = null;
$ai_ingredients_text = '';

$login_form = ['email' => ''];
$register_form = ['name' => '', 'email' => ''];

$pantry_text = '';
$pantry_names = [];

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $flash = ['type' => 'success', 'message' => t('auth_registered')];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $login_form['email'] = str_lower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($login_form['email'], FILTER_VALIDATE_EMAIL) || $password === '') {
            $flash = ['type' => 'error', 'message' => t('auth_invalid')];
        } else {
            $user = fetch_user_by_email($db, $login_form['email']);
            if (!$user || !password_verify($password, (string) $user['password_hash'])) {
                $flash = ['type' => 'error', 'message' => t('auth_invalid')];
            } elseif ((int) $user['is_approved'] !== 1) {
                $flash = ['type' => 'error', 'message' => t('auth_pending')];
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                header('Location: index.php');
                exit;
            }
        }
    }

    if ($action === 'register') {
        $register_form['name'] = trim((string) ($_POST['name'] ?? ''));
        $register_form['email'] = str_lower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        $errors = [];
        if ($register_form['name'] === '') {
            $errors[] = t('error_name_required');
        }
        if (!filter_var($register_form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = t('error_email_invalid');
        }
        if (strlen($password) < 6) {
            $errors[] = t('auth_password_short');
        }

        if ($errors !== []) {
            $flash = ['type' => 'error', 'message' => implode(' ', $errors)];
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $default_lang = $config['app']['default_lang'] ?? 'en';

            try {
                create_user($db, $register_form['name'], $register_form['email'], $password_hash, $default_lang);
                header('Location: index.php?registered=1');
                exit;
            } catch (mysqli_sql_exception $e) {
                if ((int) $e->getCode() === 1062) {
                    $flash = ['type' => 'error', 'message' => t('auth_exists')];
                } else {
                    $flash = ['type' => 'error', 'message' => t('flash_db_error')];
                }
            }
        }
    }

    if ($action === 'logout') {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        header('Location: index.php');
        exit;
    }

    if ($action === 'save_language') {
        if (!is_logged_in($current_user)) {
            $flash = ['type' => 'error', 'message' => t('flash_login_required')];
        } else {
            $language = (string) ($_POST['language'] ?? '');
            $languages = supported_languages();
            if (isset($languages[$language])) {
                update_user_language($db, (int) $current_user['id'], $language);
                $current_user['language'] = $language;
                $GLOBALS['current_user'] = $current_user;
                $flash = ['type' => 'success', 'message' => t('flash_language_saved')];
            }
        }
    }

    if ($action === 'save_pantry') {
        if (!is_logged_in($current_user)) {
            $flash = ['type' => 'error', 'message' => t('flash_login_required')];
        } else {
            $pantry_text = trim((string) ($_POST['pantry'] ?? ''));
            $pantry_list = parse_list($pantry_text);
            replace_user_pantry($db, (int) $current_user['id'], $pantry_list);
            $flash = ['type' => 'success', 'message' => t('flash_pantry_saved')];
        }
    }

    if ($action === 'generate_ai') {
        $ai_form['name'] = trim((string) ($_POST['ai_name'] ?? ''));
        $ai_form['notes'] = trim((string) ($_POST['ai_notes'] ?? ''));

        if (!is_logged_in($current_user)) {
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
                }
            } else {
                $ai_result = $result['drink'];
                $ai_ingredients_text = ingredients_to_text($ai_result['ingredients']);
                $create_form['name'] = $ai_result['name'];
                $create_form['description'] = $ai_result['description'];
                $create_form['instructions'] = $ai_result['instructions'];
                $create_form['quote'] = $ai_result['quote'];
                $create_form['ingredients'] = $ai_ingredients_text;
                $create_form['is_classic'] = '0';
            }
        }
    }

    if ($action === 'save_ai') {
        if (!is_logged_in($current_user)) {
            $flash = ['type' => 'error', 'message' => t('flash_login_required')];
        } else {
            $create_form['name'] = trim((string) ($_POST['ai_name'] ?? ''));
            $create_form['description'] = trim((string) ($_POST['ai_description'] ?? ''));
            $create_form['instructions'] = trim((string) ($_POST['ai_instructions'] ?? ''));
            $create_form['quote'] = trim((string) ($_POST['ai_quote'] ?? ''));
            $create_form['ingredients'] = trim((string) ($_POST['ai_ingredients'] ?? ''));

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
                try {
                    insert_drink($db, [
                        'name' => $create_form['name'],
                        'description' => $create_form['description'],
                        'instructions' => $create_form['instructions'],
                        'quote' => $create_form['quote'],
                        'is_classic' => 0,
                    ], $ingredients);

                    header('Location: index.php?added=1');
                    exit;
                } catch (mysqli_sql_exception $e) {
                    $flash = ['type' => 'error', 'message' => t('flash_db_error')];
                }
            }
        }
    }

    if ($action === 'approve_user' && is_admin($current_user)) {
        $user_id = (int) ($_POST['user_id'] ?? 0);
        if ($user_id > 0) {
            set_user_approved($db, $user_id, true);
            $flash = ['type' => 'success', 'message' => t('flash_admin_updated')];
        }
    }

    if ($action === 'toggle_admin' && is_admin($current_user)) {
        $user_id = (int) ($_POST['user_id'] ?? 0);
        $make_admin = (int) ($_POST['make_admin'] ?? 0) === 1;
        if ($user_id > 0) {
            set_user_admin($db, $user_id, $make_admin);
            $flash = ['type' => 'success', 'message' => t('flash_admin_updated')];
        }
    }

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
                    insert_drink($db, [
                        'name' => $create_form['name'],
                        'description' => $create_form['description'],
                        'instructions' => $create_form['instructions'],
                        'quote' => $create_form['quote'],
                        'is_classic' => $is_classic,
                    ], $ingredients);

                    header('Location: index.php?added=1');
                    exit;
                } catch (mysqli_sql_exception $e) {
                    $flash = ['type' => 'error', 'message' => t('flash_db_error')];
                }
            }
        }
    }
}

if (isset($_GET['added']) && $_GET['added'] === '1') {
    $flash = ['type' => 'success', 'message' => t('flash_saved')];
}

if (is_logged_in($current_user)) {
    $pantry_names = fetch_user_pantry($db, (int) $current_user['id']);
    if ($pantry_text === '') {
        $pantry_text = implode("\n", array_map('display_ingredient', $pantry_names));
    }
}

$q = trim((string) ($_GET['q'] ?? ''));

$all_drinks = fetch_drinks($db);
$drinks = $q !== '' ? filter_search($all_drinks, $q) : $all_drinks;

$classic_drinks = array_values(array_filter($drinks, static function (array $drink): bool {
    return (int) ($drink['is_classic'] ?? 0) === 1;
}));

$user_drinks = array_values(array_filter($drinks, static function (array $drink): bool {
    return (int) ($drink['is_classic'] ?? 0) !== 1;
}));

$pantry_norm = [];
if (is_logged_in($current_user)) {
    foreach ($pantry_names as $name) {
        $pantry_norm[] = normalize_ingredient($name);
    }
}
$possible = $pantry_norm !== [] ? filter_possible($all_drinks, $pantry_norm) : [];

$pending_users = is_admin($current_user) ? fetch_pending_users($db) : [];
$all_users = is_admin($current_user) ? fetch_all_users($db) : [];

$app_name = $config['app']['name'] ?? 'Barcraft';
$lang = i18n_lang($config, $current_user);
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($app_name) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="hero">
        <div class="hero-inner">
            <div>
                <p class="eyebrow"><?= e(t('app_eyebrow')) ?></p>
                <h1><?= e(t('hero_title')) ?></h1>
                <p class="lead"><?= e(t('hero_lead')) ?></p>
            </div>
            <div class="hero-card">
                <div class="hero-stats">
                    <div>
                        <span class="stat"><?= count($all_drinks) ?></span>
                        <span class="label"><?= e(t('stat_total')) ?></span>
                    </div>
                    <div>
                        <span class="stat"><?= count($classic_drinks) ?></span>
                        <span class="label"><?= e(t('stat_classics')) ?></span>
                    </div>
                    <div>
                        <span class="stat"><?= count($user_drinks) ?></span>
                        <span class="label"><?= e(t('stat_user')) ?></span>
                    </div>
                </div>
                <p class="hero-note"><?= e(t('hero_note')) ?></p>
            </div>
        </div>
    </header>

    <nav class="nav">
        <a href="#browse" class="nav-link"><?= e(t('nav_browse')) ?></a>
        <a href="#search" class="nav-link"><?= e(t('nav_search')) ?></a>
        <a href="#ai" class="nav-link"><?= e(t('nav_ai')) ?></a>
        <a href="#mix" class="nav-link"><?= e(t('nav_mix')) ?></a>
        <a href="#create" class="nav-link"><?= e(t('nav_create')) ?></a>
        <?php if (is_logged_in($current_user)): ?>
            <a href="#profile" class="nav-link"><?= e(t('nav_profile')) ?></a>
        <?php else: ?>
            <a href="#account" class="nav-link"><?= e(t('auth_title')) ?></a>
        <?php endif; ?>
        <?php if (is_admin($current_user)): ?>
            <a href="#admin" class="nav-link"><?= e(t('nav_admin')) ?></a>
        <?php endif; ?>
    </nav>

    <?php if (is_logged_in($current_user)): ?>
        <div class="auth-bar">
            <span><?= e(t('auth_logged_in_as', ['name' => (string) $current_user['name']])) ?></span>
            <form method="post" action="index.php" class="auth-inline">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-light"><?= e(t('auth_logout')) ?></button>
            </form>
        </div>
    <?php endif; ?>

    <main class="container">
        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <section id="browse" class="section">
            <div class="section-head">
                <h2><?= e(t('browse_title')) ?></h2>
                <p><?= $q !== '' ? e(t('browse_filter')) . ' ' . e($q) : e(t('browse_all')) ?></p>
            </div>

            <?php if ($classic_drinks !== []): ?>
                <h3 class="section-sub"><?= e(t('browse_classics')) ?></h3>
                <div class="grid">
                    <?php foreach ($classic_drinks as $drink) { render_drink_card($drink); } ?>
                </div>
            <?php endif; ?>

            <?php if ($user_drinks !== []): ?>
                <h3 class="section-sub"><?= e(t('browse_user')) ?></h3>
                <div class="grid">
                    <?php foreach ($user_drinks as $drink) { render_drink_card($drink); } ?>
                </div>
            <?php endif; ?>

            <?php if ($drinks === []): ?>
                <div class="empty"><?= e(t('browse_empty')) ?></div>
            <?php endif; ?>
        </section>
        <section id="search" class="section">
            <div class="section-head">
                <h2><?= e(t('search_title')) ?></h2>
                <p><?= e(t('search_hint')) ?></p>
            </div>
            <form class="form" method="get" action="index.php#search">
                <div class="field">
                    <label for="q"><?= e(t('search_label')) ?></label>
                    <input id="q" name="q" type="text" placeholder="<?= e(t('search_placeholder')) ?>" value="<?= e($q) ?>">
                </div>
                <button type="submit" class="btn"><?= e(t('search_button')) ?></button>
            </form>
            <?php if ($q !== ''): ?>
                <p class="result-note"><?= count($drinks) ?> <?= e(t('search_result')) ?> "<?= e($q) ?>".</p>
            <?php endif; ?>
        </section>

        <section id="ai" class="section">
            <div class="section-head">
                <h2><?= e(t('ai_title')) ?></h2>
                <p><?= e(t('ai_hint')) ?></p>
            </div>
            <?php if (!is_logged_in($current_user)): ?>
                <div class="empty"><?= e(t('ai_login')) ?></div>
            <?php elseif (trim((string) ($config['openai']['api_key'] ?? '')) === ''): ?>
                <div class="empty"><?= e(t('ai_missing_key')) ?></div>
            <?php else: ?>
                <form class="form" method="post" action="index.php#ai">
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
                <form class="form" method="post" action="index.php#ai">
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

        <section id="mix" class="section">
            <div class="section-head">
                <h2><?= e(t('mix_title')) ?></h2>
                <p><?= e(t('mix_hint')) ?></p>
            </div>
            <?php if (!is_logged_in($current_user)): ?>
                <div class="empty"><?= e(t('mix_login')) ?></div>
            <?php else: ?>
                <form class="form" method="post" action="index.php#mix">
                    <input type="hidden" name="action" value="save_pantry">
                    <div class="field">
                        <label for="pantry"><?= e(t('mix_label')) ?></label>
                        <textarea id="pantry" name="pantry" rows="4" placeholder="<?= e(t('mix_placeholder')) ?>"><?= e($pantry_text) ?></textarea>
                    </div>
                    <button type="submit" class="btn"><?= e(t('mix_button')) ?></button>
                </form>

                <?php if ($pantry_norm !== []): ?>
                    <p class="result-note"><?= count($possible) ?> <?= e(t('mix_count')) ?></p>
                    <?php if ($possible !== []): ?>
                        <div class="grid">
                            <?php foreach ($possible as $drink) { render_drink_card($drink); } ?>
                        </div>
                    <?php else: ?>
                        <div class="empty"><?= e(t('mix_none')) ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section id="create" class="section">
            <div class="section-head">
                <h2><?= e(t('create_title')) ?></h2>
                <p><?= e(t('create_hint')) ?></p>
            </div>
            <?php if (!is_logged_in($current_user)): ?>
                <div class="empty"><?= e(t('create_login')) ?></div>
            <?php else: ?>
                <form class="form" method="post" action="index.php#create">
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
        <?php if (!is_logged_in($current_user)): ?>
            <section id="account" class="section">
                <div class="section-head">
                    <h2><?= e(t('auth_title')) ?></h2>
                    <p><?= e(t('auth_login')) ?> / <?= e(t('auth_register')) ?></p>
                </div>
                <div class="grid">
                    <form class="form" method="post" action="index.php#account">
                        <input type="hidden" name="action" value="login">
                        <div class="field">
                            <label for="login_email"><?= e(t('auth_email')) ?></label>
                            <input id="login_email" name="email" type="email" value="<?= e($login_form['email']) ?>">
                        </div>
                        <div class="field">
                            <label for="login_password"><?= e(t('auth_password')) ?></label>
                            <input id="login_password" name="password" type="password">
                        </div>
                        <button type="submit" class="btn"><?= e(t('auth_login_btn')) ?></button>
                    </form>

                    <form class="form" method="post" action="index.php#account">
                        <input type="hidden" name="action" value="register">
                        <div class="field">
                            <label for="register_name"><?= e(t('auth_name')) ?></label>
                            <input id="register_name" name="name" type="text" value="<?= e($register_form['name']) ?>">
                        </div>
                        <div class="field">
                            <label for="register_email"><?= e(t('auth_email')) ?></label>
                            <input id="register_email" name="email" type="email" value="<?= e($register_form['email']) ?>">
                        </div>
                        <div class="field">
                            <label for="register_password"><?= e(t('auth_password')) ?></label>
                            <input id="register_password" name="password" type="password">
                        </div>
                        <button type="submit" class="btn"><?= e(t('auth_register_btn')) ?></button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <?php if (is_logged_in($current_user)): ?>
            <section id="profile" class="section">
                <div class="section-head">
                    <h2><?= e(t('profile_title')) ?></h2>
                </div>
                <div class="grid">
                    <form class="form" method="post" action="index.php#profile">
                        <input type="hidden" name="action" value="save_language">
                        <div class="field">
                            <label for="language"><?= e(t('profile_language')) ?></label>
                            <select id="language" name="language">
                                <?php foreach (supported_languages() as $code => $label): ?>
                                    <option value="<?= e($code) ?>" <?= $code === $lang ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn"><?= e(t('profile_language_btn')) ?></button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <?php if (is_admin($current_user)): ?>
            <section id="admin" class="section">
                <div class="section-head">
                    <h2><?= e(t('admin_title')) ?></h2>
                </div>

                <div class="admin-panel">
                    <h3><?= e(t('admin_pending')) ?></h3>
                    <?php if ($pending_users === []): ?>
                        <div class="empty"><?= e(t('admin_none')) ?></div>
                    <?php else: ?>
                        <?php foreach ($pending_users as $user_item): ?>
                            <div class="admin-row">
                                <div>
                                    <strong><?= e($user_item['name']) ?></strong>
                                    <span class="muted"><?= e($user_item['email']) ?></span>
                                </div>
                                <form method="post" action="index.php#admin" class="admin-actions">
                                    <input type="hidden" name="action" value="approve_user">
                                    <input type="hidden" name="user_id" value="<?= (int) $user_item['id'] ?>">
                                    <button type="submit" class="btn btn-small"><?= e(t('admin_approve')) ?></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="admin-panel">
                    <h3><?= e(t('admin_users')) ?></h3>
                    <?php foreach ($all_users as $user_item): ?>
                        <div class="admin-row">
                            <div>
                                <strong><?= e($user_item['name']) ?></strong>
                                <span class="muted"><?= e($user_item['email']) ?></span>
                            </div>
                            <div class="admin-actions">
                                <?php if ((int) $user_item['is_approved'] !== 1): ?>
                                    <span class="pill"><?= e(t('status_pending')) ?></span>
                                <?php elseif ((int) $user_item['is_admin'] === 1): ?>
                                    <form method="post" action="index.php#admin">
                                        <input type="hidden" name="action" value="toggle_admin">
                                        <input type="hidden" name="user_id" value="<?= (int) $user_item['id'] ?>">
                                        <input type="hidden" name="make_admin" value="0">
                                        <button type="submit" class="btn btn-small btn-light"><?= e(t('admin_remove_admin')) ?></button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="index.php#admin">
                                        <input type="hidden" name="action" value="toggle_admin">
                                        <input type="hidden" name="user_id" value="<?= (int) $user_item['id'] ?>">
                                        <input type="hidden" name="make_admin" value="1">
                                        <button type="submit" class="btn btn-small"><?= e(t('admin_make_admin')) ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
