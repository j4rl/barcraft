<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/app.php';

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin($current_user)) {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve_user') {
        $user_id = (int) ($_POST['user_id'] ?? 0);
        if ($user_id > 0) {
            set_user_approved($db, $user_id, true);
            $flash = ['type' => 'success', 'message' => t('flash_admin_updated')];
        }
    }

    if ($action === 'toggle_admin') {
        $user_id = (int) ($_POST['user_id'] ?? 0);
        $make_admin = (int) ($_POST['make_admin'] ?? 0) === 1;
        if ($user_id > 0) {
            set_user_admin($db, $user_id, $make_admin);
            $flash = ['type' => 'success', 'message' => t('flash_admin_updated')];
        }
    }
}

$pending_users = is_admin($current_user) ? fetch_pending_users($db) : [];
$all_users = is_admin($current_user) ? fetch_all_users($db) : [];

$page_title = t('admin_title');
$nav_active = 'admin';
$body_class = 'page-admin';
require __DIR__ . '/partials/header.php';
?>

<main class="container">
    <section class="section">
        <div class="section-head">
            <h2><?= e(t('admin_title')) ?></h2>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (!is_admin($current_user)): ?>
            <div class="empty"><?= e(t('flash_login_required')) ?></div>
        <?php else: ?>
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
                            <form method="post" action="admin.php" class="admin-actions">
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
                                <form method="post" action="admin.php">
                                    <input type="hidden" name="action" value="toggle_admin">
                                    <input type="hidden" name="user_id" value="<?= (int) $user_item['id'] ?>">
                                    <input type="hidden" name="make_admin" value="0">
                                    <button type="submit" class="btn btn-small btn-light"><?= e(t('admin_remove_admin')) ?></button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="admin.php">
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
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
