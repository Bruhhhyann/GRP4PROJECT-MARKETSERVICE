<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'client', 'staff');
ensure_shop_tables();
$uid = $_SESSION['user_id'];

$st = db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 60');
$st->execute([$uid]);
$list = $st->fetchAll();
// They count as read once this page has been opened
db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$uid]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Notifications | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <main class="section shop-page notif-page">
    <p><a href="dashboard.php">&larr; Back to my dashboard</a></p>
    <h1>Notifications</h1>
    <?php if (!$list): ?>
      <div class="card"><p>No notifications yet. You will see order updates, delivery news and reviews here.</p></div>
    <?php endif; ?>
    <?php foreach ($list as $n): ?>
      <div class="card notif <?= $n['is_read'] ? '' : 'unread' ?>">
        <div>
          <p class="notif-text"><?= htmlspecialchars($n['message']) ?></p>
          <span class="muted"><?= htmlspecialchars(date('M j, g:i A', strtotime($n['created_at']))) ?></span>
        </div>
        <?php if ($n['link']): ?><a class="btn btn-small" href="<?= htmlspecialchars($n['link']) ?>">View</a><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </main>
</body>
</html>
