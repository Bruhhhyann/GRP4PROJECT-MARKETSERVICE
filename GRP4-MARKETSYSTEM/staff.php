<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('staff');
$type = $_SESSION['staff_type'] ?? 'service';   // 'service' or 'product'
$uid  = $_SESSION['user_id'];
$msg = ''; $errors = [];

// Categories this provider is allowed to use
ensure_provider_applications_table();
ensure_provider_category_rows($uid, $type);
$opts = category_options($type);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_category' && csrf_ok()) {
    $have = provider_category_rows($uid); $sent = 0;
    foreach ((array)($_POST['categories'] ?? []) as $c) {
        if (!isset($opts[$c]) || in_array($have[$c] ?? '', ['approved', 'pending'], true)) { continue; }
        db()->prepare('INSERT INTO provider_categories (user_id, category, status, created_at) VALUES (?,?,"pending",?)
                       ON DUPLICATE KEY UPDATE status = "pending", created_at = VALUES(created_at), reviewed_at = NULL')
            ->execute([$uid, $c, date('Y-m-d H:i:s')]);
        $sent++;
    }
    $msg = $sent ? 'Request sent. An admin must approve it before you can use it.' : 'Pick at least one new category.';
}
$rowsCat = provider_category_rows($uid);
$myCats  = array_keys(array_filter($rowsCat, fn($x) => $x === 'approved'));

if ($type === 'product') {
    ensure_shop_tables();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $act = $_POST['action'] ?? '';
        if (!csrf_ok()) {
            $msg = 'Session expired. Refresh the page and try again.';

        } elseif ($act === 'order_review') {
            // The seller approves or rejects an order placed on their products
            $oid = (int)($_POST['order_id'] ?? 0);
            $o = db()->prepare('SELECT * FROM orders WHERE id = ? AND seller_id = ? AND status = "pending"');
            $o->execute([$oid, $uid]);
            $o = $o->fetch();
            if (!$o) {
                $msg = 'That order was not found or was already handled.';
            } elseif (($_POST['decision'] ?? '') === 'approve') {
                db()->beginTransaction();
                $ok = true;
                $its = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
                $its->execute([$oid]);
                foreach ($its->fetchAll() as $it) {
                    $r = db()->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
                    $r->execute([$it['quantity'], $it['product_id'], $it['quantity']]);
                    if ($r->rowCount() === 0) { $ok = false; $msg = 'Not enough stock for ' . $it['name'] . '. Update the stock or reject the order.'; break; }
                }
                if ($ok) {
                    db()->prepare('UPDATE orders SET status = "approved", reviewed_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $oid]);
                    db()->commit();
                    $msg = 'Order #' . $oid . ' approved. Stock was updated.';
                } else { db()->rollBack(); }
            } else {
                db()->prepare('UPDATE orders SET status = "rejected", reviewed_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $oid]);
                $msg = 'Order #' . $oid . ' rejected.';
            }

        } elseif ($act === 'add_product') {
            $n = trim($_POST['name'] ?? ''); $c = $_POST['category'] ?? 'Other';
            $d = trim($_POST['description'] ?? ''); $price = (float)($_POST['price'] ?? 0); $stock = (int)($_POST['stock'] ?? 0);
            if ($n === '' || strlen($n) > 120)            $errors[] = 'Enter a product name (up to 120 characters).';
            if (!in_array($c, $myCats, true))              $errors[] = 'Choose one of your approved categories. Request new ones under My categories.';
            if ($price <= 0 || $price > 9999999)           $errors[] = 'Enter a price above 0.';
            if ($stock < 0 || $stock > 100000)             $errors[] = 'Stock must be 0 or more.';
            if (!$errors) {
                db()->prepare('INSERT INTO products (seller_id, name, category, description, price, stock, created_at) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$uid, $n, $c, mb_substr($d, 0, 2000), $price, $stock, date('Y-m-d H:i:s')]);
                $msg = 'Product added. It now shows in the shop.';
            }

        } elseif ($act === 'toggle_product') {
            db()->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = ? AND seller_id = ?')->execute([(int)($_POST['product_id'] ?? 0), $uid]);
            $msg = 'Product updated.';

        } elseif ($act === 'load_samples') {
            // Testing helper: adds the sample products that fit this provider's approved categories
            $added = 0;
            foreach (require __DIR__ . '/includes/sample-products.php' as [$sn, $sc, $sd, $sp, $ss]) {
                if (!in_array($sc, $myCats, true)) { continue; }
                $ex = db()->prepare('SELECT id FROM products WHERE seller_id = ? AND name = ?');
                $ex->execute([$uid, $sn]);
                if ($ex->fetch()) { continue; }
                db()->prepare('INSERT INTO products (seller_id, name, category, description, price, stock, created_at) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$uid, $sn, $sc, $sd, $sp, $ss, date('Y-m-d H:i:s')]);
                $added++;
            }
            $msg = $added ? "Added $added sample products. Open the shop to see them." : 'No new sample products to add. They are already there, or your approved categories have none left.';

        } elseif ($act === 'set_stock') {
            db()->prepare('UPDATE products SET stock = ? WHERE id = ? AND seller_id = ?')
                ->execute([max(0, min(100000, (int)($_POST['stock'] ?? 0))), (int)($_POST['product_id'] ?? 0), $uid]);
            $msg = 'Stock updated.';
        }
    }

    $orders = db()->prepare('SELECT o.*, u.email, u.phone FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.seller_id = ?
                             ORDER BY (o.status = "pending") DESC, o.id DESC LIMIT 40');
    $orders->execute([$uid]);
    $orders = $orders->fetchAll();
    $lines = [];
    if ($orders) {
        $ids = implode(',', array_map('intval', array_column($orders, 'id')));
        foreach (db()->query("SELECT * FROM order_items WHERE order_id IN ($ids) ORDER BY id") as $it) { $lines[$it['order_id']][] = $it; }
    }
    $mine = db()->prepare('SELECT * FROM products WHERE seller_id = ? ORDER BY id DESC');
    $mine->execute([$uid]);
    $mine = $mine->fetchAll();
    $pendingCount = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
    $sales = db()->prepare('SELECT COALESCE(SUM(i.price * i.quantity), 0) FROM order_items i JOIN orders o ON o.id = i.order_id WHERE o.seller_id = ? AND o.status = "approved"');
    $sales->execute([$uid]);
    $sales = (float)$sales->fetchColumn();
}

$panelTitle = $type === 'product' ? 'Product seller' : 'Service provider';
$menu = $type === 'product'
    ? ['Overview' => '#overview', 'Orders to approve' => '#orders', 'Add a product' => '#add', 'My products' => '#products', 'My categories' => '#categories']
    : ['Overview' => '#overview', 'My jobs' => '#jobs', 'My categories' => '#categories'];
$statusLabel = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
$statusClass = ['pending' => 'badge-pending', 'approved' => 'badge-confirmed', 'rejected' => 'badge-rejected'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Staff dashboard | ServiceHub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1 id="overview"><?= $type === 'product' ? 'Product seller dashboard' : 'Service provider dashboard' ?></h1>
      <?php if ($msg): ?><p class="form-ok"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

      <?php if ($type === 'product'): ?>
        <div class="cards">
          <div class="card"><span class="stat-value"><?= $pendingCount ?></span><span>Orders to approve</span></div>
          <div class="card"><span class="stat-value"><?= count($mine) ?></span><span>Products listed</span></div>
          <div class="card"><span class="stat-value"><?= money($sales) ?></span><span>Approved sales</span></div>
        </div>

        <h2 id="orders">Orders to approve</h2>
        <?php if (!$orders): ?><p class="muted">No orders yet. They appear here when a client buys your products.</p><?php endif; ?>
        <?php foreach ($orders as $o): $sum = 0; ?>
          <div class="card order-card">
            <div class="order-head">
              <div><strong>Order #<?= (int)$o['id'] ?></strong> <span class="muted"><?= htmlspecialchars($o['ship_name']) ?> &middot; <?= htmlspecialchars(date('M j, g:i A', strtotime($o['created_at']))) ?></span></div>
              <span class="badge <?= $statusClass[$o['status']] ?>"><?= $statusLabel[$o['status']] ?></span>
            </div>
            <ul class="order-lines">
              <?php foreach ($lines[$o['id']] ?? [] as $it): $sum += $it['price'] * $it['quantity']; ?>
                <li><span><?= (int)$it['quantity'] ?> &times; <?= htmlspecialchars($it['name']) ?><?php if ($it['size']): ?> <span class="size-tag"><?= htmlspecialchars($it['size']) ?></span><?php endif; ?>
                  <?php if ($it['comment']): ?><br><span class="muted">Buyer's comment: <?= htmlspecialchars($it['comment']) ?></span><?php endif; ?></span>
                  <strong><?= money($it['price'] * $it['quantity']) ?></strong></li>
              <?php endforeach; ?>
            </ul>
            <p class="order-foot"><span class="muted">Deliver to <?= htmlspecialchars($o['ship_address']) ?> &middot; <?= htmlspecialchars($o['ship_contact']) ?></span> <strong>Total <?= money($sum) ?></strong></p>
            <p class="muted">Payment: Cash on Delivery. Collect <?= money($sum) ?> from the buyer when you deliver.</p>
            <?php if ($o['status'] === 'pending'): ?>
              <form method="post" class="row-actions">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <input type="hidden" name="action" value="order_review">
                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                <button class="btn btn-small" name="decision" value="approve" type="submit">Approve</button>
                <button class="btn btn-small btn-reject" name="decision" value="reject" type="submit">Reject</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <h2 id="add">Add a product</h2>
        <?php foreach ($errors as $e): ?><p class="form-error" role="alert"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        <form class="card dash-form" method="post" novalidate>
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <input type="hidden" name="action" value="add_product">
          <div class="form-grid">
            <div class="full"><label for="pname">Product name</label><input id="pname" name="name" maxlength="120" required></div>
            <div><label for="pcat">Category</label>
              <select id="pcat" name="category"><?php foreach ($myCats as $c): ?><option><?= htmlspecialchars($c) ?></option><?php endforeach; ?></select></div>
            <div><label for="pstock">Stock (how many available)</label><input id="pstock" name="stock" type="number" min="0" value="10" required></div>
            <div><label for="pprice">Price</label><input id="pprice" name="price" type="number" min="0.01" step="0.01" required></div>
            <div class="full"><label for="pdesc">Description</label><textarea id="pdesc" name="description" rows="3" maxlength="2000"></textarea></div>
          </div>
          <button class="btn btn-primary btn-lg" type="submit">Add product</button>
        </form>

        <h2 id="products">My products</h2>
        <form method="post" class="sample-form">
          <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="load_samples">
          <button class="btn btn-small btn-light" type="submit">Add sample products (for testing)</button>
          <span class="muted">Adds 5 example products for each category you are approved for.</span>
        </form>
        <?php if (!$mine): ?><p class="muted">You have not added any products yet.</p><?php else: ?>
        <div class="table-wrap"><table class="tbl">
          <thead><tr><th>Product</th><th>Price</th><th>Stock</th><th>Shown in shop</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($mine as $p): ?>
            <tr>
              <td><?= product_icon($p['category']) ?> <?= htmlspecialchars($p['name']) ?></td>
              <td><?= money((float)$p['price']) ?></td>
              <td>
                <form method="post" class="row-actions">
                  <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="set_stock">
                  <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                  <input class="stock-input" type="number" name="stock" min="0" value="<?= (int)$p['stock'] ?>" aria-label="Stock">
                  <button class="btn btn-small" type="submit">Save</button>
                </form>
              </td>
              <td><?= $p['is_active'] ? 'Yes' : 'Hidden' ?></td>
              <td>
                <form method="post">
                  <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="toggle_product">
                  <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                  <button class="btn btn-small btn-light" type="submit"><?= $p['is_active'] ? 'Hide' : 'Show' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody></table></div>
        <?php endif; ?>

      <?php else: ?>
        <div class="cards">
          <div class="card"><span class="stat-value">3</span><span>Jobs this week</span></div>
          <div class="card"><span class="stat-value"><?= money(920) ?></span><span>Earnings this month</span></div>
          <div class="card"><span class="stat-value">4.8</span><span>Average rating</span></div>
        </div>

        <h2 id="jobs">My jobs</h2>
        <ul class="booking-list card">
          <?php foreach ($bookings as $b): ?>
            <li>
              <div><strong><?= htmlspecialchars($b['service']) ?></strong>
                <span class="muted"><?= htmlspecialchars($b['date']) ?></span></div>
              <div class="booking-end"><span class="badge badge-<?= strtolower($b['status']) ?>"><?= $b['status'] ?></span>
                <span class="price"><?= money($b['price']) ?></span></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <h2 id="categories">My categories</h2>
      <div class="card">
        <p class="muted"><?= $type === 'product' ? 'You can only list products in categories the admin approved.' : 'These are the services you are approved to offer.' ?></p>
        <div class="chips">
          <?php foreach ($rowsCat as $c => $st): if (!isset($opts[$c])) { continue; } ?>
            <span class="chip <?= $st === 'approved' ? 'on' : '' ?>"><?= $opts[$c] ?> <?= htmlspecialchars($c) ?><?= $st === 'pending' ? ' (waiting for approval)' : ($st === 'rejected' ? ' (rejected)' : '') ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php $pickerOptions = array_filter($opts, fn($n) => !in_array($rowsCat[$n] ?? '', ['approved', 'pending'], true), ARRAY_FILTER_USE_KEY); ?>
      <?php if ($pickerOptions): $pickerChecked = []; ?>
        <h3 class="subhead">Want to <?= $type === 'product' ? 'sell' : 'offer' ?> something else?</h3>
        <form class="card dash-form" method="post">
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <input type="hidden" name="action" value="request_category">
          <label class="group-label">Pick the new categories, then send them for admin approval.</label>
          <?php include __DIR__ . '/includes/category-picker.php'; ?>
          <button class="btn btn-primary btn-lg" type="submit">Request approval</button>
        </form>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
