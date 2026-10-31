<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client');
ensure_shop_tables();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('Session expired. Please try again.'); header('Location: cart.php'); exit; }
    $act = $_POST['action'] ?? '';
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, min(99, (int)($_POST['quantity'] ?? 1)));
    $comment = mb_substr(trim($_POST['comment'] ?? ''), 0, 300);

    $sizeLabel = '';
    if ($act === 'add' || $act === 'buy') {
        $st = db()->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1 AND stock > 0');
        $st->execute([$pid]);
        $prod = $st->fetch();
        if (!$prod) { flash('That product is not available.'); header('Location: shop.php'); exit; }
        [$sizeLabel, $sizeErr] = validate_size($prod['category'], trim($_POST['size'] ?? ''), trim($_POST['width'] ?? ''));
        if ($sizeErr) { flash($sizeErr); header('Location: product.php?id=' . $pid); exit; }
    }
    if ($act === 'add') {
        db()->prepare('INSERT INTO cart_items (user_id, product_id, quantity, comment, size) VALUES (?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), comment = IF(VALUES(comment) <> "", VALUES(comment), comment)')
            ->execute([$uid, $pid, $qty, $comment ?: null, $sizeLabel]);
        flash('Added to your cart.');
    } elseif ($act === 'buy') {
        [$n, $err] = place_order($uid, [['product_id' => $pid, 'qty' => $qty, 'comment' => $comment, 'size' => $sizeLabel]]);
        if ($err) { flash($err); header('Location: product.php?id=' . $pid); exit; }
        flash('Order sent! The seller needs to approve it. You can follow it here.');
        header('Location: orders.php'); exit;
    } elseif ($act === 'update') {
        db()->prepare('UPDATE cart_items SET quantity = ?, comment = ? WHERE id = ? AND user_id = ?')
            ->execute([$qty, $comment ?: null, (int)($_POST['item_id'] ?? 0), $uid]);
        flash('Cart updated.');
    } elseif ($act === 'remove') {
        db()->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?')->execute([(int)($_POST['item_id'] ?? 0), $uid]);
        flash('Item removed.');
    } elseif ($act === 'checkout') {
        // Only the items the client ticked are ordered (one order per shop).
        $ids = array_values(array_filter(array_map('intval', (array)($_POST['selected'] ?? []))));
        if (!$ids) { flash('Tick at least one item to order.'); header('Location: cart.php'); exit; }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = db()->prepare("SELECT product_id, quantity AS qty, comment, size FROM cart_items WHERE user_id = ? AND id IN ($in)");
        $rows->execute(array_merge([$uid], $ids));
        [$n, $err] = place_order($uid, $rows->fetchAll());
        if ($err) { flash($err); header('Location: cart.php'); exit; }
        db()->prepare("DELETE FROM cart_items WHERE user_id = ? AND id IN ($in)")->execute(array_merge([$uid], $ids));
        flash($n === 1 ? 'Order sent. The shop needs to approve it.' : "Orders sent to $n shops. Each shop needs to approve its own order.");
        header('Location: orders.php'); exit;
    }
    header('Location: cart.php'); exit;
}

$st = db()->prepare('SELECT c.id AS item_id, c.quantity, c.comment AS note, c.size AS size, pr.* FROM cart_items c
                     JOIN (' . product_sql() . ') pr ON pr.id = c.product_id WHERE c.user_id = ? ORDER BY pr.seller_id, c.id DESC');
$st->execute([$uid]);
$items = $st->fetchAll();
$groups = [];
foreach ($items as $i) { $groups[$i['seller_id']]['store'] = $i['store']; $groups[$i['seller_id']]['items'][] = $i; }
$me = db()->prepare('SELECT * FROM users WHERE id = ?'); $me->execute([$uid]); $me = $me->fetch();

$panelTitle = 'My account';
$menu = client_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My cart | ServiceHub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1>My cart</h1>
      <?php if ($m = flash()): ?><p class="form-ok"><?= htmlspecialchars($m) ?></p><?php endif; ?>

      <?php if (!$items): ?>
        <div class="card"><p>Your cart is empty.</p><a class="btn btn-primary" href="shop.php">Browse products</a></div>
      <?php else: ?>
        <div class="card cart-summary">
          <p><strong>Deliver to:</strong> <?= htmlspecialchars($me['address'] . ', ' . $me['city'] . ' ' . $me['postal_code']) ?> <a href="profile.php">(change)</a></p>
          <p><strong>Payment:</strong> <span class="badge badge-confirmed">Cash on Delivery (COD)</span> <span class="muted">Pay when your order arrives.</span></p>
          <p class="muted">Tick the items you want to buy. Items from the same shop are ordered together. Items from different shops are separate orders.</p>
        </div>

        <?php foreach ($groups as $sid => $g): ?>
          <section class="card shop-group">
            <div class="shop-head">
              <label class="shop-pick"><input type="checkbox" class="pick-all cart-check" aria-label="Select all items from <?= htmlspecialchars($g['store']) ?>">
                <strong>🏪 <?= htmlspecialchars($g['store']) ?></strong></label>
              <span class="muted"><?= count($g['items']) ?> item<?= count($g['items']) === 1 ? '' : 's' ?></span>
            </div>

            <?php foreach ($g['items'] as $i): $line = $i['price'] * $i['quantity']; $id = (int)$i['item_id']; ?>
              <div class="cart-line">
                <input type="checkbox" class="pick cart-check" name="selected[]" value="<?= $id ?>" form="co-<?= (int)$sid ?>"
                       data-amount="<?= number_format($line, 2, '.', '') ?>" aria-label="Select <?= htmlspecialchars($i['name']) ?>">
                <div class="cart-art" aria-hidden="true"><?= product_icon($i['category']) ?></div>
                <div class="cart-info">
                  <strong><a class="plain" href="product.php?id=<?= (int)$i['id'] ?>"><?= htmlspecialchars($i['name']) ?></a></strong>
                  <span class="muted"><?= money((float)$i['price']) ?> each<?php if ($i['size']): ?> &middot; <strong>Size:</strong> <?= htmlspecialchars($i['size']) ?><?php endif; ?></span>
                  <div class="cart-edit">
                    <label>Qty
                      <select name="quantity" form="upd-<?= $id ?>">
                        <?php for ($n = 1; $n <= max($i['quantity'], min(20, (int)$i['stock'])); $n++): ?><option value="<?= $n ?>" <?= $n == $i['quantity'] ? 'selected' : '' ?>><?= $n ?></option><?php endfor; ?>
                      </select></label>
                    <input class="note-input" name="comment" form="upd-<?= $id ?>" maxlength="300" placeholder="Comment for the seller" value="<?= htmlspecialchars((string)$i['note']) ?>" aria-label="Comment">
                  </div>
                  <?php if ($i['stock'] < $i['quantity']): ?><span class="form-error inline">Only <?= (int)$i['stock'] ?> in stock.</span><?php endif; ?>
                </div>
                <div class="cart-end">
                  <strong class="line-total"><?= money($line) ?></strong>
                  <div class="cart-btns">
                    <button class="btn btn-small" type="submit" form="upd-<?= $id ?>">Update</button>
                    <button class="btn btn-small btn-reject" type="submit" form="rm-<?= $id ?>">Remove</button>
                  </div>
                </div>
                <form id="upd-<?= $id ?>" method="post">
                  <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="item_id" value="<?= $id ?>">
                </form>
                <form id="rm-<?= $id ?>" method="post">
                  <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="item_id" value="<?= $id ?>">
                </form>
              </div>
            <?php endforeach; ?>

            <div class="shop-foot">
              <span>Selected: <strong class="sel-count">0</strong> &middot; Total <strong class="price-lg sel-total">$0.00</strong></span>
              <form id="co-<?= (int)$sid ?>" method="post">
                <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="checkout">
                <button class="btn btn-primary btn-lg place-btn" type="submit" disabled>Place order</button>
              </form>
            </div>
          </section>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
  </div>
  <script>
    // Per shop: only ticked items count toward the total and the order.
    const cur = <?= json_encode(CURRENCY) ?>;
    const fmt = n => cur + n.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.querySelectorAll('.shop-group').forEach(g => {
      const boxes = [...g.querySelectorAll('.pick')], all = g.querySelector('.pick-all');
      const calc = () => {
        let sum = 0, n = 0;
        boxes.forEach(b => { if (b.checked) { sum += parseFloat(b.dataset.amount); n++; } });
        g.querySelector('.sel-total').textContent = fmt(sum);
        g.querySelector('.sel-count').textContent = n;
        g.querySelector('.place-btn').disabled = (n === 0);
        all.checked = (n === boxes.length && n > 0);
      };
      boxes.forEach(b => b.addEventListener('change', calc));
      all.addEventListener('change', () => { boxes.forEach(b => b.checked = all.checked); calc(); });
      calc();
    });
  </script>
</body>
</html>
