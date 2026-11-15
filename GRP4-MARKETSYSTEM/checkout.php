<?php
// Confirm-your-order page (like Shopee): address, items, vouchers, shipping, payment and total BEFORE the order is placed.
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client', 'staff', 'admin');
ensure_shop_tables();
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: cart.php'); exit; }
$act  = $_POST['action'] ?? '';
$mode = $act === 'buy' ? 'buy' : ($act === 'checkout' ? 'cart' : (($_POST['mode'] ?? '') === 'buy' ? 'buy' : 'cart'));
$back = $mode === 'buy' ? 'product.php?id=' . (int)($_POST['product_id'] ?? 0) : 'cart.php';
if (!csrf_ok()) { flash('Session expired. Please try again.'); header('Location: ' . $back); exit; }
if (!id_approved()) { flash(ID_BLOCK_MESSAGE); header('Location: ' . $back); exit; }

// ---- what is being bought ----
$lines = []; $err = ''; $cartIds = []; $carry = [];
if ($mode === 'buy') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $q = db()->prepare(product_sql() . ' WHERE p.id = ? AND p.is_active = 1 AND p.stock > 0');
    $q->execute([$pid]);
    $p = $q->fetch();
    if (!$p)                                         { $err = 'That product is not available.'; }
    elseif ((int)$p['seller_id'] === $uid)           { $err = "You can't buy your own product."; }
    else {
        [$sizeLabel, $e1]  = validate_size($p['category'], trim($_POST['size'] ?? ''), trim($_POST['width'] ?? ''));
        [$colorLabel, $e2] = validate_color($p, trim($_POST['color'] ?? ''));
        $qty = max(1, min(99, (int)($_POST['quantity'] ?? 1)));
        $err = $e1 ?: $e2;
        if (!$err && $qty > (int)$p['stock']) { $err = 'Only ' . (int)$p['stock'] . ' left of ' . $p['name'] . '.'; }
        if (!$err) { $lines[] = ['p' => $p, 'qty' => $qty, 'comment' => mb_substr(trim($_POST['comment'] ?? ''), 0, 300), 'size' => $sizeLabel, 'color' => $colorLabel]; }
    }
    foreach (['product_id', 'quantity', 'size', 'width', 'color', 'comment'] as $k) { $carry[$k] = (string)($_POST[$k] ?? ''); }
} else {
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['selected'] ?? []))));
    if (!$ids) { $err = 'Tick at least one item in your cart first.'; }
    else {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $q = db()->prepare('SELECT c.id AS item_id, c.quantity AS cq, c.comment AS note, c.size AS csize, c.color AS ccolor, pr.* FROM cart_items c
                            JOIN (' . product_sql() . ') pr ON pr.id = c.product_id WHERE c.user_id = ? AND c.id IN (' . $in . ') ORDER BY pr.seller_id, c.id');
        $q->execute(array_merge([$uid], $ids));
        foreach ($q->fetchAll() as $r) {
            $cartIds[] = (int)$r['item_id'];
            if ((int)$r['stock'] < (int)$r['cq']) { $err = 'Only ' . (int)$r['stock'] . ' left of ' . $r['name'] . '. Update your cart first.'; }
            $lines[] = ['p' => $r, 'qty' => (int)$r['cq'], 'comment' => (string)$r['note'], 'size' => (string)$r['csize'], 'color' => (string)$r['ccolor']];
        }
        if (!$lines) { $err = 'Those items are no longer in your cart.'; }
    }
}

// ---- place the order ----
if (!$err && $act === 'confirm') {
    $pick = [];
    foreach ($lines as $l) {
        $sid = (int)$l['p']['seller_id'];
        $pick[$sid] = ['discount' => (int)($_POST['coupon_discount'][$sid] ?? 0), 'shipping' => (int)($_POST['coupon_shipping'][$sid] ?? 0)];
    }
    $placeLines = array_map(fn($l) => ['product_id' => $l['p']['id'], 'qty' => $l['qty'], 'comment' => $l['comment'], 'size' => $l['size'], 'color' => $l['color']], $lines);
    [$n, $e] = place_order($uid, $placeLines, $pick);
    if ($e) { $err = $e; }
    else {
        if ($mode === 'cart' && $cartIds) {
            $in = implode(',', array_fill(0, count($cartIds), '?'));
            db()->prepare("DELETE FROM cart_items WHERE user_id = ? AND id IN ($in)")->execute(array_merge([$uid], $cartIds));
        }
        flash($n === 1 ? 'Order placed! Now the shop needs to approve it. You will be notified.' : "Orders placed with $n shops. Each shop needs to approve its own order.");
        header('Location: orders.php'); exit;
    }
}

// ---- show the confirmation page ----
$groups = [];
foreach ($lines as $l) {
    $sid = (int)$l['p']['seller_id'];
    $groups[$sid]['store'] = $l['p']['store'];
    $groups[$sid]['lines'][] = $l;
    $groups[$sid]['sub'] = ($groups[$sid]['sub'] ?? 0) + (float)$l['p']['price'] * $l['qty'];
}
$wallet = my_coupons($uid);
$discV = array_values(array_filter($wallet, fn($v) => $v['type'] !== 'shipping'));
$shipV = array_values(array_filter($wallet, fn($v) => $v['type'] === 'shipping'));
$me = db()->prepare('SELECT * FROM users WHERE id = ?'); $me->execute([$uid]); $me = $me->fetch();
$fullName = trim($me['first_name'] . ' ' . ($me['middle_name'] ?? '') . ' ' . $me['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Checkout | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
  <?php include __DIR__ . '/includes/head-extra.php'; ?>
  <style>
    .co-page { max-width: 880px; margin: 0 auto; }
    .co-block { margin-bottom: 1rem; gap: .6rem; }
    .co-block h2 { font-size: 1.15rem; margin: 0; color: var(--navy); }
    .co-shop-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--line); padding-bottom: .6rem; }
    .co-item { display: flex; align-items: center; gap: 1rem; padding: .7rem 0; border-bottom: 1px solid var(--line); }
    .co-item:last-of-type { border-bottom: 0; }
    .co-art { width: 64px; height: 64px; border-radius: 12px; background: var(--mist); display: grid; place-items: center; font-size: 2rem; overflow: hidden; flex-shrink: 0; }
    .co-art img { width: 100%; height: 100%; object-fit: cover; }
    .co-item-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .15rem; }
    .co-voucher { display: flex; flex-wrap: wrap; gap: .8rem; padding: .8rem; background: #fffaf0; border: 1px dashed var(--marigold); border-radius: 12px; }
    .co-voucher label { flex: 1; min-width: 220px; display: flex; flex-direction: column; gap: .3rem; font-size: .85rem; font-weight: 500; }
    .co-voucher select { padding: .5rem .7rem; font: inherit; border: 1px solid #c3cfda; border-radius: 10px; background: #fff; }
    .co-lines { display: flex; flex-direction: column; gap: .3rem; max-width: 420px; margin-left: auto; width: 100%; }
    .co-lines > div { display: flex; justify-content: space-between; gap: 1rem; }
    .co-lines .disc { color: #3f8f5b; }
    .co-lines .free { color: #3f8f5b; font-weight: 700; }
    .co-lines .grand { padding-top: .5rem; border-top: 1px solid var(--line); font-weight: 800; font-size: 1.1rem; }
    .pay-option { display: flex; align-items: center; gap: .8rem; padding: .9rem 1rem; border: 2px solid var(--navy); border-radius: 12px; background: var(--mist); }
    .pay-option input { width: auto; margin: 0; }
    .co-actions { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: flex-end; }
    .co-hint { color: var(--teal); font-size: .88rem; margin: 0; min-height: 1.2em; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <main class="section shop-page co-page">
    <p><a href="<?= htmlspecialchars($back) ?>">&larr; Back</a></p>
    <h1>Checkout</h1>
    <ol class="id-steps" aria-label="Steps">
      <li class="done"><span>&#10003;</span> <?= $mode === 'buy' ? 'Choose item' : 'Cart' ?></li>
      <li class="current"><span>2</span> Confirm order</li>
      <li><span>3</span> Order placed</li>
    </ol>

    <?php if ($err): ?>
      <p class="form-error" role="alert"><?= htmlspecialchars($err) ?></p>
      <?php if (!$lines): ?><p><a class="btn btn-primary" href="<?= htmlspecialchars($back) ?>">Go back</a></p></main></body></html><?php exit; endif; ?>
    <?php endif; ?>

    <form method="post" action="checkout.php" id="checkoutForm">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <input type="hidden" name="action" value="confirm">
      <input type="hidden" name="mode" value="<?= $mode ?>">
      <?php foreach ($carry as $k => $v): ?><input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>"><?php endforeach; ?>
      <?php foreach ($cartIds as $cid): ?><input type="hidden" name="selected[]" value="<?= $cid ?>"><?php endforeach; ?>

      <section class="card co-block">
        <h2>&#128205; Delivery address</h2>
        <p><strong><?= htmlspecialchars($fullName) ?></strong> &middot; <?= htmlspecialchars($me['email'] ?: ($me['phone'] ?? '')) ?><br>
          <?= htmlspecialchars($me['address'] . ', ' . $me['city'] . ' ' . ($me['postal_code'] ?? '')) ?></p>
        <p class="muted">This is the address on your profile. <a href="profile.php">Edit profile</a> (address changes need admin approval).</p>
      </section>

      <?php foreach ($groups as $sid => $g): ?>
        <section class="card co-block co-shop" data-sub="<?= number_format($g['sub'], 2, '.', '') ?>">
          <div class="co-shop-head"><h2>&#127978; <?= htmlspecialchars($g['store']) ?></h2></div>
          <?php foreach ($g['lines'] as $l): $p = $l['p']; ?>
            <div class="co-item">
              <div class="co-art"><?= product_art($p) ?></div>
              <div class="co-item-info">
                <strong><?= htmlspecialchars($p['name']) ?></strong>
                <span class="muted"><?= $l['size'] ? htmlspecialchars($l['size']) : '' ?><?= $l['size'] && $l['color'] ? ' &middot; ' : '' ?><?php if ($l['color']): ?><i class="dot" style="background:<?= color_hex($l['color']) ?>"></i><?= htmlspecialchars($l['color']) ?><?php endif; ?></span>
                <?php if ($l['comment']): ?><span class="muted">Note: <?= htmlspecialchars($l['comment']) ?></span><?php endif; ?>
              </div>
              <span class="muted"><?= money((float)$p['price']) ?> &times; <?= (int)$l['qty'] ?></span>
              <strong><?= money((float)$p['price'] * $l['qty']) ?></strong>
            </div>
          <?php endforeach; ?>

          <div class="co-voucher">
            <?php if ($discV): ?>
              <label>&#127903;&#65039; Discount voucher
                <select name="coupon_discount[<?= $sid ?>]">
                  <option value="0">Don't use a voucher</option>
                  <?php foreach ($discV as $cv): ?>
                    <option value="<?= (int)$cv['id'] ?>" data-type="<?= $cv['type'] ?>" data-value="<?= $cv['value'] ?>" data-min="<?= $cv['min_spend'] ?>" data-max="<?= $cv['max_discount'] ?>"><?= htmlspecialchars($cv['label']) ?><?= (float)$cv['min_spend'] > 0 ? ' (min ' . money((float)$cv['min_spend']) . ')' : '' ?></option>
                  <?php endforeach; ?>
                </select></label>
            <?php endif; ?>
            <?php if ($shipV): ?>
              <label>&#128666; Free shipping voucher
                <select name="coupon_shipping[<?= $sid ?>]">
                  <option value="0">Don't use a voucher</option>
                  <?php foreach ($shipV as $cv): ?>
                    <option value="<?= (int)$cv['id'] ?>" data-type="shipping" data-value="0" data-min="<?= $cv['min_spend'] ?>"><?= htmlspecialchars($cv['label']) ?><?= (float)$cv['min_spend'] > 0 ? ' (min ' . money((float)$cv['min_spend']) . ')' : '' ?></option>
                  <?php endforeach; ?>
                </select></label>
            <?php endif; ?>
            <?php if (!$discV && !$shipV): ?><span class="muted">You have no vouchers right now. <a href="vouchers.php">See how to earn them</a></span><?php endif; ?>
          </div>

          <div class="co-lines">
            <div><span>Merchandise subtotal</span><span class="sh-sub"></span></div>
            <div><span>Shipping</span><span class="sh-ship"></span></div>
            <div class="disc"><span>Voucher discount</span><span class="sh-disc"></span></div>
            <div><span>Shop total</span><strong class="sh-total"></strong></div>
          </div>
          <p class="co-hint sh-hint"></p>
        </section>
      <?php endforeach; ?>

      <section class="card co-block">
        <h2>&#128179; Payment method</h2>
        <label class="pay-option"><input type="radio" name="pay" value="cod" checked> <span><strong>Cash on Delivery (COD)</strong><br><span class="muted">Pay in cash when your order arrives. COD is the only payment method.</span></span></label>
        <p class="muted">&#128230; The shop sets the delivery time when it approves your order. You can cancel the order from My orders until it is delivered.</p>
      </section>

      <section class="card co-block">
        <div class="co-lines">
          <div><span>Merchandise subtotal</span><span class="t-sub"></span></div>
          <div><span>Shipping total</span><span class="t-ship"></span></div>
          <div class="disc"><span>Voucher discount</span><span class="t-disc"></span></div>
          <div class="grand"><span>Total payment (COD)</span><strong class="t-total"></strong></div>
        </div>
        <div class="co-actions">
          <a class="btn btn-ghost btn-lg" href="<?= htmlspecialchars($back) ?>">Back</a>
          <button class="btn btn-primary btn-lg" type="submit">Place order</button>
        </div>
      </section>
    </form>
  </main>
  <script>
    // Preview of shipping and vouchers (the server works out the real amounts when you place the order).
    const cur = <?= json_encode(CURRENCY) ?>, FEE = <?= (float)SHIPPING_FEE ?>, FREE_MIN = <?= (float)FREE_SHIPPING_MIN ?>;
    const fmt = n => cur + n.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const shops = [...document.querySelectorAll('.co-shop')];
    const set = (root, cls, txt, free) => { const el = root.querySelector(cls); el.textContent = txt; if (free !== undefined) el.classList.toggle('free', free); };
    function calc() {
      const chosen = new Set();
      shops.forEach(s => s.querySelectorAll('select').forEach(sel => { if (sel.value !== '0') chosen.add(sel.value); }));
      let tSub = 0, tShip = 0, tDisc = 0;
      shops.forEach(s => {
        const sub = parseFloat(s.dataset.sub);
        const dSel = s.querySelector('select[name^=coupon_discount]'), sSel = s.querySelector('select[name^=coupon_shipping]');
        [dSel, sSel].forEach(sel => {
          if (!sel) return;
          [...sel.options].forEach(o => { if (o.value !== '0') o.disabled = sub < parseFloat(o.dataset.min) || (chosen.has(o.value) && sel.value !== o.value); });
          if (sel.selectedOptions[0].disabled) sel.value = '0';
        });
        let ship = sub < FREE_MIN ? FEE : 0;
        if (ship > 0 && sSel && sSel.value !== '0') ship = 0;
        let disc = 0;
        if (dSel && dSel.value !== '0') {
          const o = dSel.selectedOptions[0], v = parseFloat(o.dataset.value);
          disc = o.dataset.type === 'percent' ? sub * v / 100 : v;
          if (o.dataset.max) disc = Math.min(disc, parseFloat(o.dataset.max));
          disc = Math.min(disc, sub);
        }
        set(s, '.sh-sub', fmt(sub)); set(s, '.sh-ship', ship === 0 ? 'FREE' : fmt(ship), ship === 0);
        set(s, '.sh-disc', '-' + fmt(disc)); set(s, '.sh-total', fmt(sub + ship - disc));
        s.querySelector('.sh-hint').textContent = sub < FREE_MIN ? 'Spend ' + fmt(FREE_MIN - sub) + ' more from this shop for free shipping' : '';
        tSub += sub; tShip += ship; tDisc += disc;
      });
      const root = document.querySelector('.co-block:last-of-type') || document;
      set(document, '.t-sub', fmt(tSub)); set(document, '.t-ship', tShip === 0 ? 'FREE' : fmt(tShip), tShip === 0);
      set(document, '.t-disc', '-' + fmt(tDisc)); set(document, '.t-total', fmt(tSub + tShip - tDisc));
    }
    document.querySelectorAll('.co-shop select').forEach(sel => sel.addEventListener('change', calc));
    calc();
  </script>
</body>
</html>
