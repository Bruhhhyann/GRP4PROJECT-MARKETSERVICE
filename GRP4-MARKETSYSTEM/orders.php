<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client');
ensure_shop_tables();
$uid = $_SESSION['user_id'];

$st = db()->prepare('SELECT o.*, ' . store_expr('o.seller_id', 'u.first_name') . ' AS store FROM orders o JOIN users u ON u.id = o.seller_id
                     WHERE o.buyer_id = ? ORDER BY o.id DESC LIMIT 50');
$st->execute([$uid]);
$orders = $st->fetchAll();
$items = [];
if ($orders) {
    $ids = implode(',', array_map('intval', array_column($orders, 'id')));
    foreach (db()->query("SELECT * FROM order_items WHERE order_id IN ($ids) ORDER BY id") as $it) { $items[$it['order_id']][] = $it; }
}
$statusLabel = ['pending' => 'Pending seller approval', 'approved' => 'Approved', 'rejected' => 'Rejected'];
$statusClass = ['pending' => 'badge-pending', 'approved' => 'badge-confirmed', 'rejected' => 'badge-rejected'];

$panelTitle = 'My account';
$menu = client_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My orders | ServiceHub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1>My orders</h1>
      <?php if ($m = flash()): ?><p class="form-ok"><?= htmlspecialchars($m) ?></p><?php endif; ?>
      <?php if (!$orders): ?>
        <div class="card"><p>You have no orders yet.</p><a class="btn btn-primary" href="shop.php">Browse products</a></div>
      <?php endif; ?>
      <?php foreach ($orders as $o): $sum = 0; ?>
        <div class="card order-card">
          <div class="order-head">
            <div><strong>Order #<?= (int)$o['id'] ?></strong> <span class="muted">from <?= htmlspecialchars($o['store']) ?> &middot; <?= htmlspecialchars(date('M j, g:i A', strtotime($o['created_at']))) ?></span></div>
            <span class="badge <?= $statusClass[$o['status']] ?>"><?= $statusLabel[$o['status']] ?></span>
          </div>
          <ul class="order-lines">
            <?php foreach ($items[$o['id']] ?? [] as $it): $sum += $it['price'] * $it['quantity']; ?>
              <li><span><?= (int)$it['quantity'] ?> &times; <?= htmlspecialchars($it['name']) ?><?php if ($it['size']): ?> <span class="size-tag"><?= htmlspecialchars($it['size']) ?></span><?php endif; ?>
                <?php if ($it['comment']): ?><br><span class="muted">Your comment: <?= htmlspecialchars($it['comment']) ?></span><?php endif; ?></span>
                <strong><?= money($it['price'] * $it['quantity']) ?></strong></li>
            <?php endforeach; ?>
          </ul>
          <p class="order-foot"><span class="muted">Deliver to <?= htmlspecialchars($o['ship_address']) ?></span> <strong>Total <?= money($sum) ?></strong></p>
          <p class="muted">Payment: Cash on Delivery<?= $o['status'] === 'approved' ? '. Please prepare ' . money($sum) . ' for when it arrives.' : '.' ?></p>
        </div>
      <?php endforeach; ?>
    </main>
  </div>
</body>
</html>
