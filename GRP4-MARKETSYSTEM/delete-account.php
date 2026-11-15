<?php
// Clients and staff can delete their own account. Admin accounts cannot be deleted here.
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client', 'staff');
ensure_shop_tables();
$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];
$type = $_SESSION['staff_type'] ?? '';

$me = db()->prepare('SELECT * FROM users WHERE id = ?'); $me->execute([$uid]); $me = $me->fetch();
$blockers = account_blockers($uid);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok())                                              $errors[] = 'Session expired. Please try again.';
    if ($blockers)                                               $errors[] = 'Finish the items listed above first.';
    if (!password_verify($_POST['password'] ?? '', $me['password_hash'])) $errors[] = 'That password is not correct.';
    if (trim($_POST['confirm_text'] ?? '') !== 'DELETE')         $errors[] = 'Type DELETE (in capital letters) to confirm.';
    if (empty($_POST['understand']))                             $errors[] = 'Tick the box to confirm you understand this cannot be undone.';
    if (!$errors) {
        try {
            delete_account($uid);
            session_destroy();
            header('Location: index.php?deleted=1'); exit;
        } catch (Throwable $e) {
            error_log('delete_account failed: ' . $e->getMessage());
            $errors[] = 'We could not delete the account. Please try again, or ask an admin.';
        }
    }
}

$panelTitle = 'My account';
$menu = buyer_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Delete my account | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
  <?php include __DIR__ . '/includes/head-extra.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1>Delete my account</h1>

      <div class="card danger-zone">
        <p><strong>This cannot be undone.</strong> Deleting your account permanently removes:</p>
        <ul class="plain-list">
          <li>Your profile, address, photo and the ID you uploaded</li>
          <li>Your cart, notifications and the reviews you wrote</li>
          <li>Your order and booking history</li>
          <?php if ($role === 'staff' && $type === 'product'): ?>
            <li>All your products and their photos, and the orders your shop received</li>
          <?php elseif ($role === 'staff'): ?>
            <li>Your service provider page and the bookings you received</li>
          <?php endif; ?>
        </ul>
        <p class="muted">Orders or bookings still waiting for an answer are cancelled and the other person is told. You can register again later with the same email.</p>
      </div>

      <?php if ($blockers): ?>
        <div class="card">
          <p><strong>Before you can delete your account:</strong></p>
          <ul class="plain-list"><?php foreach ($blockers as $bl): ?><li><?= htmlspecialchars($bl) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <?php foreach ($errors as $e): ?><p class="form-error" role="alert"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
      <form class="card dash-form" method="post" novalidate>
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <label for="password">Your password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
        <label for="confirm_text">Type <strong>DELETE</strong> to confirm</label>
        <input id="confirm_text" name="confirm_text" autocomplete="off" required>
        <label class="check-line"><input type="checkbox" name="understand" value="1"> I understand my account and its data will be deleted for good.</label>
        <div class="row-actions">
          <button class="btn btn-reject btn-lg" type="submit" <?= $blockers ? 'disabled' : '' ?>>Delete my account</button>
          <a class="btn btn-ghost btn-lg" href="dashboard.php">Keep my account</a>
        </div>
      </form>
    </main>
  </div>
</body>
</html>
