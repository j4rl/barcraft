<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/app.php';

$flash = null;
$login_form = ['email' => ''];
$register_form = ['name' => '', 'email' => ''];
$login_failures = (int) ($_SESSION['login_failures'] ?? 0);
$last_action = '';

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $flash = ['type' => 'success', 'message' => t('auth_registered')];
}

$next = trim((string) ($_GET['next'] ?? ''));
if ($next !== '' && (!preg_match('/^[a-z0-9_-]+\.php(\?.*)?$/i', $next) || strpos($next, '://') !== false)) {
    $next = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $last_action = (string) $action;

    if ($action === 'login') {
        $login_form['email'] = str_lower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $post_next = trim((string) ($_POST['next'] ?? ''));

        if ($post_next !== '' && (!preg_match('/^[a-z0-9_-]+\.php(\?.*)?$/i', $post_next) || strpos($post_next, '://') !== false)) {
            $post_next = '';
        }

        if (!filter_var($login_form['email'], FILTER_VALIDATE_EMAIL) || $password === '') {
            $login_failures++;
            $_SESSION['login_failures'] = $login_failures;
            $flash = ['type' => 'error', 'message' => t('auth_invalid')];
        } else {
            $user = fetch_user_by_email($db, $login_form['email']);
            if (!$user || !password_verify($password, (string) $user['password_hash'])) {
                $login_failures++;
                $_SESSION['login_failures'] = $login_failures;
                $flash = ['type' => 'error', 'message' => t('auth_invalid')];
            } elseif ((int) $user['is_approved'] !== 1) {
                $flash = ['type' => 'error', 'message' => t('auth_pending')];
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['login_failures'] = 0;
                header('Location: ' . ($post_next !== '' ? $post_next : 'index.php'));
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
            $default_lang = i18n_lang($config, null);

            try {
                create_user($db, $register_form['name'], $register_form['email'], $password_hash, $default_lang);
                $_SESSION['login_failures'] = 0;
                header('Location: account.php?registered=1');
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
        $_SESSION['login_failures'] = 0;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        header('Location: index.php');
        exit;
    }

    if ($action === 'request_reset') {
        $email = str_lower(trim((string) ($_POST['reset_email'] ?? '')));
        $note = trim((string) ($_POST['reset_note'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash = ['type' => 'error', 'message' => t('error_email_invalid')];
        } else {
            try {
                create_password_reset_request($db, $email, $note);
                $flash = ['type' => 'success', 'message' => t('auth_reset_sent')];
            } catch (mysqli_sql_exception $e) {
                $flash = ['type' => 'error', 'message' => t('flash_db_error')];
            }
        }
    }
}

$show_register = $last_action === 'register';
$show_reset = $last_action === 'request_reset';

$page_title = t('auth_title');
$nav_active = 'account';
$body_class = 'page-account';
require __DIR__ . '/partials/header.php';
?>

<main class="container">
    <section class="section">
        <div class="section-head">
            <h2><?= e(t('auth_title')) ?></h2>
            <p><?= e(t('auth_intro')) ?></p>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <div class="account-grid">
            <form id="login" class="form" method="post" action="account.php">
                <input type="hidden" name="action" value="login">
                <?php if ($next !== ''): ?>
                    <input type="hidden" name="next" value="<?= e($next) ?>">
                <?php endif; ?>
                <div class="field">
                    <label for="login_email"><?= e(t('auth_email')) ?></label>
                    <input id="login_email" name="email" type="email" value="<?= e($login_form['email']) ?>">
                </div>
                <div class="field">
                    <label for="login_password"><?= e(t('auth_password')) ?></label>
                    <input id="login_password" name="password" type="password">
                </div>
                <button type="submit" class="btn"><?= e(t('auth_login_btn')) ?></button>
                <p class="form-note">
                    <?= e(t('auth_register_prompt')) ?>
                    <a href="#register"><?= e(t('auth_register_link')) ?></a>
                </p>
                <?php if ($login_failures >= 3): ?>
                    <div class="help-callout">
                        <strong><?= e(t('auth_need_help')) ?></strong>
                        <div class="help-actions">
                            <a class="btn btn-light btn-small" href="#register"><?= e(t('auth_register_link')) ?></a>
                            <a class="btn btn-light btn-small" href="#reset"><?= e(t('auth_reset_link')) ?></a>
                        </div>
                    </div>
                <?php endif; ?>
            </form>

            <form id="register" class="form register-panel<?= $show_register ? ' is-open' : '' ?>" method="post" action="account.php">
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
                <p class="form-note">
                    <a href="#login"><?= e(t('auth_back_to_login')) ?></a>
                </p>
            </form>
        </div>

        <div id="reset" class="card card-flat reset-card<?= ($show_reset || $login_failures >= 3) ? ' is-open' : '' ?>">
            <h3><?= e(t('auth_reset_title')) ?></h3>
            <p><?= e(t('auth_reset_hint')) ?></p>
            <form class="form form-compact" method="post" action="account.php">
                <input type="hidden" name="action" value="request_reset">
                <div class="field">
                    <label for="reset_email"><?= e(t('auth_reset_email')) ?></label>
                    <input id="reset_email" name="reset_email" type="email" placeholder="<?= e(t('auth_reset_email_placeholder')) ?>">
                </div>
                <div class="field">
                    <label for="reset_note"><?= e(t('auth_reset_note_label')) ?></label>
                    <textarea id="reset_note" name="reset_note" rows="2" placeholder="<?= e(t('auth_reset_note_placeholder')) ?>"></textarea>
                </div>
                <button type="submit" class="btn btn-light"><?= e(t('auth_reset_button')) ?></button>
            </form>
            <p class="muted"><?= e(t('auth_reset_note')) ?></p>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
