<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client', 'staff', 'admin');
ensure_shop_tables();
$uid = $_SESSION['user_id'];

// The client confirms the order arrived -> the shop is told, and the client is asked to rate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'received') {
    $oid = (int)($_POST['order_id'] ?? 0);
    if (!csrf_ok()) {
        flash('Session expired. Please try again.');
    } else {
        $o = db()->prepare('SELECT * FROM orders WHERE id = ? AND buyer_id = ? AND status = "approved"');
        $o->execute([$oid, $uid]);
        $o = $o->fetch();
        if (!$o) {
            flash('That order is not waiting for delivery.');
        } else {
            db()->prepare('UPDATE orders SET status = "delivered", delivered_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $oid]);
            notify((int)$o['seller_id'], "Order #$oid was received by {$o['ship_name']}. It is now marked as delivered.", 'staff.php#orders');
            notify($uid, "Order #$oid is delivered. Please rate the products you received.", "orders.php#order-$oid");
            flash('Thanks! The shop was told your order arrived. You can now rate your products below.');
        }
    }
    header('Location: orders.php'); exit;
}

$st = db()->prepare('SELECT o.*, ' . store_expr('o.seller_id', 'u.first_name') . ' AS store FROM orders o JOIN users u ON u.id = o.seller_id
                     WHERE o.buyer_id = ? ORDER BY o.id DESC LIMIT 50');
$st->execute([$uid]);
$orders = $st->fetchAll();
$items = [];
if ($orders) {
    $ids = implode(',', array_map('intval', array_column($orders, 'id')));
    foreach (db()->query("SELECT * FROM order_items WHERE order_id IN ($ids) ORDER BY id") as $it) { $items[$it['order_id']][] = $it; }
}
$myRatings = db()->prepare('SELECT product_id, rating FROM reviews WHERE user_id = ?');
$myRatings->execute([$uid]);
$myRatings = $myRatings->fetchAll(PDO::FETCH_KEY_PAIR);

$statusLabel = ['pending' => 'Pending seller approval', 'approved' => 'Approved', 'delivered' => 'Delivered', 'rejected' => 'Rejected'];
$statusClass = ['pending' => 'badge-pending', 'approved' => 'badge-confirmed', 'delivered' => 'badge-delivered', 'rejected' => 'badge-rejected'];

// "Arrives in about 3 days (by Mon, Nov 9)"
function eta_text(array $o): string {
    if (empty($o['delivery_days'])) { return 'The shop has not set a delivery time yet.'; }
    $base = strtotime($o['approved_at'] ?: ($o['reviewed_at'] ?: $o['created_at']));
    $eta  = strtotime('+' . (int)$o['delivery_days'] . ' days', $base);
    $left = (int)ceil(($eta - time()) / 86400);
    $when = $left > 1 ? "Arrives in about $left days" : ($left === 1 ? 'Arrives in about 1 day' : 'Should arrive today');
    return $when . ' (expected by ' . date('D, M j', $eta) . ')';
}

$panelTitle = 'My account';
$menu = buyer_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My orders | AIOS</title>
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
        <div class="card order-card" id="order-<?= (int)$o['id'] ?>">
          <div class="order-head">
            <div><strong>Order #<?= (int)$o['id'] ?></strong> <span class="muted">from <?= htmlspecialchars($o['store']) ?> &middot; <?= htmlspecialchars(date('M j, g:i A', strtotime($o['created_at']))) ?></span></div>
            <span class="badge <?= $statusClass[$o['status']] ?>"><?= $statusLabel[$o['status']] ?></span>
          </div>

          <?php if ($o['status'] === 'approved'): ?>
            <div class="eta-box">
              <strong>&#128666; Approved by the shop.</strong> <?= htmlspecialchars(eta_text($o)) ?>
            </div>
          <?php elseif ($o['status'] === 'delivered'): ?>
            <div class="eta-box done">
              <strong>&#9989; Delivered</strong><?= $o['delivered_at'] ? ' on ' . htmlspecialchars(date('M j, g:i A', strtotime($o['delivered_at']))) : '' ?>. Please rate the products below.
            </div>
          <?php endif; ?>

          <ul class="order-lines">
            <?php foreach ($items[$o['id']] ?? [] as $it): $sum += $it['price'] * $it['quantity']; ?>
              <li><span><?= (int)$it['quantity'] ?> &times; <?= htmlspecialchars($it['name']) ?><?php if ($it['size']): ?> <span class="size-tag"><?= htmlspecialchars($it['size']) ?></span><?php endif; ?><?php if ($it['color']): ?> <span class="size-tag"><i class="dot" style="background:<?= color_hex($it['color']) ?>"></i><?= htmlspecialchars($it['color']) ?></span><?php endif; ?>
                <?php if ($it['comment']): ?><br><span class="muted">Your comment: <?= htmlspecialchars($it['comment']) ?></span><?php endif; ?>
                <?php if ($o['status'] === 'delivered' && $it['product_id']): ?>
                  <br><?php if (isset($myRatings[$it['product_id']])): ?>
                    <a class="rate-link" href="product.php?id=<?= (int)$it['product_id'] ?>#reviews"><?= stars_html((float)$myRatings[$it['product_id']]) ?> Your rating (edit)</a>
                  <?php else: ?>
                    <a class="btn btn-small" href="product.php?id=<?= (int)$it['product_id'] ?>#reviews">&#9733; Rate this product</a>
                  <?php endif; ?>
                <?php endif; ?></span>
                <strong><?= money($it['price'] * $it['quantity']) ?></strong></li>
            <?php endforeach; ?>
          </ul>
          <p class="order-foot"><span class="muted">Deliver to <?= htmlspecialchars($o['ship_address']) ?></span> <strong>Total <?= money($sum) ?></strong></p>
          <p class="muted">Payment: Cash on Delivery<?= $o['status'] === 'approved' ? '. Please prepare ' . money($sum) . ' for when it arrives.' : '.' ?></p>

          <?php if ($o['status'] === 'approved'): ?>
            <form method="post" class="received-form">
              <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="received">
              <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
              <button class="btn btn-primary" type="submit" onclick="return confirm('Did you receive this order?');">Order received</button>
              <span class="muted">Tap this when your order arrives. The shop will be told.</span>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </main>
  </div>
</body>
</html>
