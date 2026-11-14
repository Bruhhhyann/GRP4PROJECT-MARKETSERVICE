<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client', 'staff', 'admin');
ensure_shop_tables();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('Session expired. Please try again.'); header('Location: cart.php'); exit; }
    $act = $_POST['action'] ?? '';
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, min(99, (int)($_POST['quantity'] ?? 1)));
    $comment = mb_substr(trim($_POST['comment'] ?? ''), 0, 300);
    if (in_array($act, ['add', 'buy', 'checkout'], true) && !id_approved()) {
        flash(ID_BLOCK_MESSAGE);
        header('Location: ' . ($act === 'checkout' ? 'cart.php' : 'product.php?id=' . $pid)); exit;
    }

    $sizeLabel = ''; $colorLabel = '';
    if ($act === 'add' || $act === 'buy') {
        $st = db()->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1 AND stock > 0');
        $st->execute([$pid]);
        $prod = $st->fetch();
        if (!$prod) { flash('That product is not available.'); header('Location: shop.php'); exit; }
        if ((int)$prod['seller_id'] === (int)$uid) { flash("You can't buy your own product."); header('Location: product.php?id=' . $pid); exit; }
        [$sizeLabel, $sizeErr] = validate_size($prod['category'], trim($_POST['size'] ?? ''), trim($_POST['width'] ?? ''));
        if ($sizeErr) { flash($sizeErr); header('Location: product.php?id=' . $pid); exit; }
        [$colorLabel, $colorErr] = validate_color($prod, trim($_POST['color'] ?? ''));
        if ($colorErr) { flash($colorErr); header('Location: product.php?id=' . $pid); exit; }
    }
    if ($act === 'add') {
        db()->prepare('INSERT INTO cart_items (user_id, product_id, quantity, comment, size, color) VALUES (?,?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), comment = IF(VALUES(comment) <> "", VALUES(comment), comment)')
            ->execute([$uid, $pid, $qty, $comment ?: null, $sizeLabel, $colorLabel]);
        flash('Added to your cart.');
    } elseif ($act === 'buy') {
        [$n, $err] = place_order($uid, [['product_id' => $pid, 'qty' => $qty, 'comment' => $comment, 'size' => $sizeLabel, 'color' => $colorLabel]]);
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
        $rows = db()->prepare("SELECT product_id, quantity AS qty, comment, size, color FROM cart_items WHERE user_id = ? AND id IN ($in)");
        $rows->execute(array_merge([$uid], $ids));
        [$n, $err] = place_order($uid, $rows->fetchAll(), ['discount' => (int)($_POST['coupon_discount'] ?? 0), 'shipping' => (int)($_POST['coupon_shipping'] ?? 0)]);
        if ($err) { flash($err); header('Location: cart.php'); exit; }
        db()->prepare("DELETE FROM cart_items WHERE user_id = ? AND id IN ($in)")->execute(array_merge([$uid], $ids));
        flash($n === 1 ? 'Order sent. The shop needs to approve it.' : "Orders sent to $n shops. Each shop needs to approve its own order.");
        header('Location: orders.php'); exit;
    }
    header('Location: cart.php'); exit;
}

$st = db()->prepare('SELECT c.id AS item_id, c.quantity, c.comment AS note, c.size AS size, c.color AS color, pr.* FROM cart_items c
                     JOIN (' . product_sql() . ') pr ON pr.id = c.product_id WHERE c.user_id = ? ORDER BY pr.seller_id, c.id DESC');
$st->execute([$uid]);
$items = $st->fetchAll();
$groups = [];
foreach ($items as $i) { $groups[$i['seller_id']]['store'] = $i['store']; $groups[$i['seller_id']]['items'][] = $i; }
$wallet = my_coupons($uid);
$discV = array_values(array_filter($wallet, fn($v) => $v['type'] !== 'shipping'));
$shipV = array_values(array_filter($wallet, fn($v) => $v['type'] === 'shipping'));
$me = db()->prepare('SELECT * FROM users WHERE id = ?'); $me->execute([$uid]); $me = $me->fetch();

$panelTitle = 'My account';
$menu = buyer_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My cart | AIOS</title>
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
          <p><strong>Shipping:</strong> <?= money((float)SHIPPING_FEE) ?> for each shop. <span class="badge badge-confirmed">Free</span> when one shop's order is <?= money((float)FREE_SHIPPING_MIN) ?> or more. <a href="vouchers.php">My vouchers</a></p>
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
                <div class="cart-art"><?= product_art($i) ?></div>
                <div class="cart-info">
                  <strong><a class="plain" href="product.php?id=<?= (int)$i['id'] ?>"><?= htmlspecialchars($i['name']) ?></a></strong>
                  <span class="muted"><?= money((float)$i['price']) ?> each<?php if ($i['size']): ?> &middot; <strong>Size:</strong> <?= htmlspecialchars($i['size']) ?><?php endif; ?></span>
                  <?php if ($i['color']): ?><span class="muted"><strong>Color:</strong> <i class="dot" style="background:<?= color_hex($i['color']) ?>"></i><?= htmlspecialchars($i['color']) ?></span><?php endif; ?>
                  <div class="cart-edit">
                    <div class="stepper small">
                      <button type="button" class="step-btn" data-step="-1" aria-label="Decrease quantity">&minus;</button>
                      <input name="quantity" type="number" min="1" max="<?= max((int)$i['quantity'], min(20, (int)$i['stock'])) ?>" value="<?= (int)$i['quantity'] ?>" form="upd-<?= $id ?>" inputmode="numeric" aria-label="Quantity">
                      <button type="button" class="step-btn" data-step="1" aria-label="Increase quantity">+</button>
                    </div>
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
              <div class="summary">
                <?php if ($discV || $shipV): ?>
                  <div class="voucher-row">
                    <?php if ($discV): ?>
                      <label>&#127903;&#65039; Discount voucher
                        <select name="coupon_discount" form="co-<?= (int)$sid ?>">
                          <option value="0">None</option>
                          <?php foreach ($discV as $cv): ?>
                            <option value="<?= (int)$cv['id'] ?>" data-type="<?= $cv['type'] ?>" data-value="<?= $cv['value'] ?>" data-min="<?= $cv['min_spend'] ?>" data-max="<?= $cv['max_discount'] ?>"><?= htmlspecialchars($cv['label']) ?><?= (float)$cv['min_spend'] > 0 ? ' (min ' . money((float)$cv['min_spend']) . ')' : '' ?></option>
                          <?php endforeach; ?>
                        </select></label>
                    <?php endif; ?>
                    <?php if ($shipV): ?>
                      <label>&#128666; Free shipping voucher
                        <select name="coupon_shipping" form="co-<?= (int)$sid ?>">
                          <option value="0">None</option>
                          <?php foreach ($shipV as $cv): ?>
                            <option value="<?= (int)$cv['id'] ?>" data-type="shipping" data-value="0" data-min="<?= $cv['min_spend'] ?>"><?= htmlspecialchars($cv['label']) ?><?= (float)$cv['min_spend'] > 0 ? ' (min ' . money((float)$cv['min_spend']) . ')' : '' ?></option>
                          <?php endforeach; ?>
                        </select></label>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <p class="muted">No vouchers yet. <a href="vouchers.php">See how to earn them</a></p>
                <?php endif; ?>
                <div class="sum-lines">
                  <div><span>Selected items</span><span class="sel-count">0</span></div>
                  <div><span>Subtotal</span><span class="sel-sub">$0.00</span></div>
                  <div><span>Shipping</span><span class="sel-ship">$0.00</span></div>
                  <div class="disc"><span>Voucher discount</span><span class="sel-disc">-$0.00</span></div>
                  <div class="grand"><span>Total to pay (COD)</span><strong class="price-lg sel-total">$0.00</strong></div>
                </div>
                <p class="ship-hint muted"></p>
              </div>
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
    // + and - buttons for the quantity
    document.querySelectorAll('.stepper').forEach(st => {
      const q = st.querySelector('input');
      const clamp = v => Math.min(parseInt(q.max), Math.max(1, v || 1));
      st.querySelectorAll('.step-btn').forEach(b => b.addEventListener('click', () => { q.value = clamp((parseInt(q.value) || 1) + parseInt(b.dataset.step)); }));
      q.addEventListener('change', () => { q.value = clamp(parseInt(q.value)); });
    });
    // Per shop: only ticked items count. Shipping and vouchers are previewed here; the server works out the real amounts.
    const cur = <?= json_encode(CURRENCY) ?>, FEE = <?= (float)SHIPPING_FEE ?>, FREE_MIN = <?= (float)FREE_SHIPPING_MIN ?>;
    const fmt = n => cur + n.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.querySelectorAll('.shop-group').forEach(g => {
      const boxes = [...g.querySelectorAll('.pick')], all = g.querySelector('.pick-all');
      const dSel = g.querySelector('select[name=coupon_discount]'), sSel = g.querySelector('select[name=coupon_shipping]');
      const put = (cls, txt) => { g.querySelector(cls).textContent = txt; };
      const calc = () => {
        let sub = 0, n = 0;
        boxes.forEach(b => { if (b.checked) { sub += parseFloat(b.dataset.amount); n++; } });
        [dSel, sSel].forEach(sel => {      // a voucher needing a higher spend cannot be chosen yet
          if (!sel) return;
          [...sel.options].forEach(o => { if (o.value !== '0') o.disabled = sub < parseFloat(o.dataset.min); });
          if (sel.selectedOptions[0].disabled) sel.value = '0';
        });
        let ship = (n > 0 && sub < FREE_MIN) ? FEE : 0;
        if (ship > 0 && sSel && sSel.value !== '0') ship = 0;
        let disc = 0;
        if (dSel && dSel.value !== '0') {
          const o = dSel.selectedOptions[0], v = parseFloat(o.dataset.value);
          disc = o.dataset.type === 'percent' ? sub * v / 100 : v;
          if (o.dataset.max) disc = Math.min(disc, parseFloat(o.dataset.max));
          disc = Math.min(disc, sub);
        }
        put('.sel-count', n); put('.sel-sub', fmt(sub));
        put('.sel-ship', n > 0 && ship === 0 ? 'FREE' : fmt(ship));
        put('.sel-disc', '-' + fmt(disc)); put('.sel-total', fmt(sub + ship - disc));
        g.querySelector('.ship-hint').textContent = (n > 0 && sub < FREE_MIN) ? 'Spend ' + fmt(FREE_MIN - sub) + ' more from this shop for free shipping' : '';
        g.querySelector('.place-btn').disabled = (n === 0);
        all.checked = (n === boxes.length && n > 0);
      };
      boxes.forEach(b => b.addEventListener('change', calc));
      [dSel, sSel].forEach(sel => sel && sel.addEventListener('change', calc));
      all.addEventListener('change', () => { boxes.forEach(b => b.checked = all.checked); calc(); });
      calc();
    });
  </script>
</body>
</html>
