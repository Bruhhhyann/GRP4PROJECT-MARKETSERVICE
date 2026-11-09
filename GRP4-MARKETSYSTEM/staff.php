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
// Provider / shop photo (both product and service providers)
ensure_shop_tables();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['set_avatar', 'remove_avatar'], true) && csrf_ok()) {
    $old = db()->prepare('SELECT photo FROM users WHERE id = ?'); $old->execute([$uid]); $old = (string)$old->fetchColumn();
    if ($_POST['action'] === 'remove_avatar') {
        delete_avatar($old);
        db()->prepare('UPDATE users SET photo = "" WHERE id = ?')->execute([$uid]);
        $msg = 'Photo removed.';
    } else {
        [$path, $perr] = save_avatar($_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE], $uid);
        if ($perr)        { $msg = $perr; }
        elseif (!$path)   { $msg = 'Choose a photo first.'; }
        else {
            delete_avatar($old);
            db()->prepare('UPDATE users SET photo = ? WHERE id = ?')->execute([$path, $uid]);
            $msg = 'Photo updated.';
        }
    }
}
$myPhoto = db()->prepare('SELECT photo FROM users WHERE id = ?'); $myPhoto->execute([$uid]); $myPhoto = (string)$myPhoto->fetchColumn();

// Service provider: answer booking requests and edit service details
if ($type === 'service' && $_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $act = $_POST['action'] ?? '';
    if (in_array($act, ['booking_confirm', 'booking_decline', 'booking_complete'], true)) {
        $bk = db()->prepare('SELECT * FROM bookings WHERE id = ? AND provider_id = ?');
        $bk->execute([(int)($_POST['booking_id'] ?? 0), $uid]);
        $bk = $bk->fetch();
        $store = store_name($uid); $now = date('Y-m-d H:i:s');
        if (!$bk) {
            $msg = 'That booking was not found.';
        } elseif ($act === 'booking_confirm' && $bk['status'] === 'pending') {
            $busy = db()->prepare('SELECT id FROM bookings WHERE provider_id = ? AND booking_date = ? AND booking_time = ? AND status = "confirmed" AND id <> ?');
            $busy->execute([$uid, $bk['booking_date'], $bk['booking_time'], $bk['id']]);
            if ($busy->fetch()) { $msg = 'You already have a confirmed job at that time. Decline this one or ask the client to rebook.'; }
            else {
                db()->prepare('UPDATE bookings SET status = "confirmed", updated_at = ? WHERE id = ?')->execute([$now, $bk['id']]);
                notify((int)$bk['client_id'], "Your booking #{$bk['id']} with $store is confirmed for " . booking_when($bk) . '.', 'bookings.php');
                $msg = 'Booking confirmed. The client was told.';
            }
        } elseif ($act === 'booking_decline' && $bk['status'] === 'pending') {
            db()->prepare('UPDATE bookings SET status = "declined", updated_at = ? WHERE id = ?')->execute([$now, $bk['id']]);
            notify((int)$bk['client_id'], "Sorry, $store could not take booking #{$bk['id']}. You can pick another time or provider.", 'providers.php');
            $msg = 'Booking declined. The client was told.';
        } elseif ($act === 'booking_complete' && $bk['status'] === 'confirmed') {
            db()->prepare('UPDATE bookings SET status = "completed", updated_at = ? WHERE id = ?')->execute([$now, $bk['id']]);
            notify((int)$bk['client_id'], "Your booking #{$bk['id']} with $store was marked as completed.", 'bookings.php');
            $msg = 'Marked as completed.';
        } else {
            $msg = 'That booking cannot be changed any more.';
        }
    } elseif ($act === 'save_service') {
        $rate  = trim($_POST['service_rate'] ?? '');
        $about = mb_substr(trim($_POST['service_about'] ?? ''), 0, 600);
        if ($rate !== '' && (!is_numeric($rate) || (float)$rate < 0 || (float)$rate > 9999999)) { $msg = 'Enter a valid starting price, or leave it empty.'; }
        else {
            db()->prepare('UPDATE users SET service_rate = ?, service_about = ? WHERE id = ?')->execute([$rate === '' ? null : (float)$rate, $about, $uid]);
            $msg = 'Service details saved. Clients can now see them.';
        }
    }
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
                    $days = max(1, min(30, (int)($_POST['delivery_days'] ?? 3)));
                    $now  = date('Y-m-d H:i:s');
                    db()->prepare('UPDATE orders SET status = "approved", reviewed_at = ?, approved_at = ?, delivery_days = ? WHERE id = ?')->execute([$now, $now, $days, $oid]);
                    db()->commit();
                    $eta = date('D, M j', strtotime("+$days days"));
                    notify((int)$o['buyer_id'], 'Good news! Your order #' . $oid . ' from ' . store_name($uid) . ' was approved. It should arrive in about ' . $days . ' day' . ($days === 1 ? '' : 's') . ' (by ' . $eta . '). Tap "Order received" when it arrives.', 'orders.php#order-' . $oid);
                    $msg = 'Order #' . $oid . ' approved. The buyer was told it arrives in about ' . $days . ' day' . ($days === 1 ? '' : 's') . '. Stock was updated.';
                } else { db()->rollBack(); }
            } else {
                db()->prepare('UPDATE orders SET status = "rejected", reviewed_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $oid]);
                notify((int)$o['buyer_id'], 'Sorry, your order #' . $oid . ' from ' . store_name($uid) . ' was rejected by the shop.', 'orders.php#order-' . $oid);
                $msg = 'Order #' . $oid . ' rejected. The buyer was told.';
            }

        } elseif ($act === 'add_product') {
            $n = trim($_POST['name'] ?? ''); $c = $_POST['category'] ?? 'Other';
            $d = trim($_POST['description'] ?? ''); $price = (float)($_POST['price'] ?? 0); $stock = (int)($_POST['stock'] ?? 0);
            if ($n === '' || strlen($n) > 120)            $errors[] = 'Enter a product name (up to 120 characters).';
            if (!in_array($c, $myCats, true))              $errors[] = 'Choose one of your approved categories. Request new ones under My categories.';
            if ($price <= 0 || $price > 9999999)           $errors[] = 'Enter a price above 0.';
            if ($stock < 0 || $stock > 100000)             $errors[] = 'Stock must be 0 or more.';
            if (!$errors) {
                $pickedColors = implode(',', array_values(array_intersect((array)($_POST['colors'] ?? []), array_keys(color_map()))));
                db()->prepare('INSERT INTO products (seller_id, name, category, description, price, stock, colors, created_at) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$uid, $n, $c, mb_substr($d, 0, 2000), $price, $stock, $pickedColors, date('Y-m-d H:i:s')]);
                $newId = (int)db()->lastInsertId();
                [$photoPath, $photoErr] = save_product_image($_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE], $newId);
                if ($photoPath) { db()->prepare('UPDATE products SET image = ? WHERE id = ?')->execute([$photoPath, $newId]); }
                $msg = 'Product added. It now shows in the shop.' . ($photoErr ? ' But the photo was not saved: ' . $photoErr : '');
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

        } elseif ($act === 'set_photo' || $act === 'remove_photo') {
            $pid = (int)($_POST['product_id'] ?? 0);
            $row = db()->prepare('SELECT image FROM products WHERE id = ? AND seller_id = ?');
            $row->execute([$pid, $uid]);
            $row = $row->fetch();
            if (!$row) {
                $msg = 'Product not found.';
            } elseif ($act === 'remove_photo') {
                delete_product_image($row['image']);
                db()->prepare('UPDATE products SET image = "" WHERE id = ?')->execute([$pid]);
                $msg = 'Photo removed.';
            } else {
                [$path, $perr] = save_product_image($_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE], $pid);
                if ($perr)       { $msg = $perr; }
                elseif (!$path)  { $msg = 'Choose a photo first.'; }
                else {
                    delete_product_image($row['image']);
                    db()->prepare('UPDATE products SET image = ? WHERE id = ?')->execute([$path, $pid]);
                    $msg = 'Photo updated.';
                }
            }

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
    $sales = db()->prepare('SELECT COALESCE(SUM(i.price * i.quantity), 0) FROM order_items i JOIN orders o ON o.id = i.order_id WHERE o.seller_id = ? AND o.status IN ("approved", "delivered")');
    $sales->execute([$uid]);
    $sales = (float)$sales->fetchColumn();
}

if ($type === 'service') {
    $jobsQ = db()->prepare('SELECT b.*, u.first_name, u.last_name, u.email, u.phone FROM bookings b JOIN users u ON u.id = b.client_id
                            WHERE b.provider_id = ? ORDER BY (b.status = "pending") DESC, (b.status = "confirmed") DESC, b.booking_date DESC, b.booking_time DESC LIMIT 60');
    $jobsQ->execute([$uid]);
    $jobs = $jobsQ->fetchAll();
    $jobCount = fn(array $st) => count(array_filter($jobs, fn($j) => in_array($j['status'], $st, true)));
    $mySvc = db()->prepare('SELECT service_rate, service_about FROM users WHERE id = ?'); $mySvc->execute([$uid]); $mySvc = $mySvc->fetch();
}

$panelTitle = $type === 'product' ? 'Product seller' : 'Service provider';
$menu = $type === 'product'
    ? ['Overview' => '#overview', 'Orders to approve' => '#orders', 'Add a product' => '#add', 'My products' => '#products', 'My photo' => '#photo', 'My categories' => '#categories', 'Shop products' => 'shop.php', 'My cart' => 'cart.php', 'My purchases' => 'orders.php', 'Book a service' => 'providers.php', 'My bookings' => 'bookings.php']
    : ['Overview' => '#overview', 'My jobs' => '#jobs', 'My service details' => '#details', 'My photo' => '#photo', 'My categories' => '#categories', 'Shop products' => 'shop.php', 'My cart' => 'cart.php', 'My purchases' => 'orders.php', 'Book a service' => 'providers.php', 'My bookings' => 'bookings.php'];
$statusLabel = ['pending' => 'Pending', 'approved' => 'Approved', 'delivered' => 'Delivered', 'rejected' => 'Rejected'];
$statusClass = ['pending' => 'badge-pending', 'approved' => 'badge-confirmed', 'delivered' => 'badge-delivered', 'rejected' => 'badge-rejected'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Staff dashboard | AIOS</title>
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
          <div class="card"><span class="stat-value"><?= money($sales) ?></span><span>Sales (approved and delivered)</span></div>
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
                <li><span><?= (int)$it['quantity'] ?> &times; <?= htmlspecialchars($it['name']) ?><?php if ($it['size']): ?> <span class="size-tag"><?= htmlspecialchars($it['size']) ?></span><?php endif; ?><?php if ($it['color']): ?> <span class="size-tag"><i class="dot" style="background:<?= color_hex($it['color']) ?>"></i><?= htmlspecialchars($it['color']) ?></span><?php endif; ?>
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
                <label class="days-field">Delivery in <input type="number" name="delivery_days" min="1" max="30" value="3" aria-label="Delivery days"> day(s)</label>
                <button class="btn btn-small" name="decision" value="approve" type="submit">Approve</button>
                <button class="btn btn-small btn-reject" name="decision" value="reject" type="submit">Reject</button>
              </form>
            <?php elseif ($o['status'] === 'approved'): ?>
              <p class="eta-box">Approved. Delivery in about <?= (int)$o['delivery_days'] ?: '?' ?> day(s). Waiting for the buyer to tap "Order received".</p>
            <?php elseif ($o['status'] === 'delivered'): ?>
              <p class="eta-box done">&#9989; Delivered<?= $o['delivered_at'] ? ' on ' . htmlspecialchars(date('M j, g:i A', strtotime($o['delivered_at']))) : '' ?>. The buyer can now rate your product.</p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <h2 id="add">Add a product</h2>
        <?php foreach ($errors as $e): ?><p class="form-error" role="alert"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        <form class="card dash-form" method="post" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <input type="hidden" name="action" value="add_product">
          <div class="form-grid">
            <div class="full"><label for="pname">Product name</label><input id="pname" name="name" maxlength="120" required></div>
            <div><label for="pcat">Category</label>
              <select id="pcat" name="category"><?php foreach ($myCats as $c): ?><option><?= htmlspecialchars($c) ?></option><?php endforeach; ?></select></div>
            <div><label for="pstock">Stock (how many available)</label><input id="pstock" name="stock" type="number" min="0" value="10" required></div>
            <div><label for="pprice">Price</label><input id="pprice" name="price" type="number" min="0.01" step="0.01" required></div>
            <div class="full"><label for="pphoto">Product photo (JPG, PNG or WEBP, max 2 MB)</label>
              <input id="pphoto" name="photo" type="file" accept="image/jpeg,image/png,image/webp"></div>
            <div class="full"><label for="pdesc">Description</label><textarea id="pdesc" name="description" rows="3" maxlength="2000"></textarea></div>
            <div class="full"><span class="group-label">Colors available</span>
              <div class="opt-chips">
                <?php foreach (color_map() as $cn => $hex): ?>
                  <label class="opt-chip"><input type="checkbox" name="colors[]" value="<?= htmlspecialchars($cn) ?>"><span><i class="dot" style="background:<?= $hex ?>"></i><?= htmlspecialchars($cn) ?></span></label>
                <?php endforeach; ?>
              </div>
              <p class="muted">Tick the colors you sell. If you tick none, buyers see standard colors for this category.</p>
            </div>
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
          <thead><tr><th>Product</th><th>Photo</th><th>Price</th><th>Stock</th><th>Shown in shop</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($mine as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td>
                <div class="thumb"><?= product_art($p) ?></div>
                <form method="post" enctype="multipart/form-data" class="photo-form">
                  <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="set_photo">
                  <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                  <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" aria-label="New photo" required>
                  <button class="btn btn-small" type="submit">Change photo</button>
                </form>
                <?php if ($p['image']): ?>
                  <form method="post" class="photo-form">
                    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="remove_photo">
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn-small btn-reject" type="submit">Remove photo</button>
                  </form>
                <?php endif; ?>
              </td>
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
          <div class="card"><span class="stat-value"><?= $jobCount(['pending']) ?></span><span>New requests</span></div>
          <div class="card"><span class="stat-value"><?= $jobCount(['confirmed']) ?></span><span>Confirmed jobs</span></div>
          <div class="card"><span class="stat-value"><?= $jobCount(['completed']) ?></span><span>Completed jobs</span></div>
        </div>

        <h2 id="jobs">My jobs</h2>
        <?php if (!$jobs): ?><p class="muted">No booking requests yet. Clients can book you from the Book a service page.</p><?php endif; ?>
        <?php foreach ($jobs as $j): [$jl, $jc] = booking_badge($j['status'], true); ?>
          <div class="card order-card">
            <div class="order-head">
              <div><strong>#<?= (int)$j['id'] ?> &middot; <?= htmlspecialchars($j['service']) ?></strong>
                <span class="muted">for <?= htmlspecialchars(trim($j['first_name'] . ' ' . $j['last_name'])) ?></span></div>
              <span class="badge <?= $jc ?>"><?= $jl ?></span>
            </div>
            <p class="eta-box<?= $j['status'] === 'completed' ? ' done' : '' ?>">&#128197; <?= htmlspecialchars(booking_when($j)) ?></p>
            <p class="muted">&#128205; <?= htmlspecialchars($j['address']) ?> &middot; Contact: <?= htmlspecialchars($j['contact']) ?><?= $j['notes'] ? '<br>Client note: ' . htmlspecialchars($j['notes']) : '' ?></p>
            <?php if (in_array($j['status'], ['pending', 'confirmed'], true)): ?>
              <form method="post" class="row-actions">
                <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="booking_id" value="<?= (int)$j['id'] ?>">
                <?php if ($j['status'] === 'pending'): ?>
                  <button class="btn btn-small" name="action" value="booking_confirm" type="submit">Confirm</button>
                  <button class="btn btn-small btn-reject" name="action" value="booking_decline" type="submit">Decline</button>
                <?php else: ?>
                  <button class="btn btn-small" name="action" value="booking_complete" type="submit">Mark as completed</button>
                <?php endif; ?>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <h2 id="details">My service details</h2>
        <form class="card dash-form" method="post">
          <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="save_service">
          <div class="form-grid">
            <div><label for="service_rate">Starting price (optional)</label>
              <input id="service_rate" name="service_rate" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string)($mySvc['service_rate'] ?? '')) ?>" placeholder="e.g. 35"></div>
            <div class="full"><label for="service_about">About your service</label>
              <textarea id="service_about" name="service_about" rows="3" maxlength="600" placeholder="Tell clients what you do, your experience, and the areas you cover."><?= htmlspecialchars((string)($mySvc['service_about'] ?? '')) ?></textarea></div>
          </div>
          <p class="muted">Clients see this on the Book a service page, together with your photo and categories.</p>
          <button class="btn btn-primary" type="submit">Save details</button>
        </form>
      <?php endif; ?>

      <h2 id="photo">My photo</h2>
      <div class="card photo-card">
        <div class="avatar-lg"><?= avatar_art($myPhoto, $_SESSION['user']) ?></div>
        <div>
          <p class="muted"><?= $type === 'product' ? 'Shown next to your shop name on your product pages.' : 'Your service provider photo.' ?> JPG, PNG or WEBP, max 2 MB.</p>
          <form method="post" enctype="multipart/form-data" class="photo-form">
            <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="set_avatar">
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" aria-label="New photo" required>
            <button class="btn btn-small" type="submit">Change photo</button>
          </form>
          <?php if ($myPhoto): ?>
            <form method="post" class="photo-form">
              <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="remove_avatar">
              <button class="btn btn-small btn-reject" type="submit">Remove photo</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

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
