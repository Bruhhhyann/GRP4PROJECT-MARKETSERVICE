<?php
// My vouchers: wallet, claim a code, and progress toward vouchers earned by buying a lot or spending a lot.
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client', 'staff', 'admin');
ensure_shop_tables();
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'claim') {
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['code'] ?? ''));
    $cc = db()->prepare('SELECT * FROM coupon_codes WHERE code = ? AND active = 1');
    $cc->execute([$code]);
    $cc = $cc->fetch();
    if (!csrf_ok())                                   { flash('Session expired. Please try again.'); }
    elseif (!$cc)                                     { flash('That code is not valid.'); }
    elseif ($cc['max_claims'] !== null && (int)$cc['claims'] >= (int)$cc['max_claims']) { flash('Sorry, this code has been fully claimed.'); }
    else {
        $given = give_coupon($uid, ['type' => $cc['type'], 'value' => $cc['value'], 'min_spend' => $cc['min_spend'], 'max_discount' => $cc['max_discount'], 'label' => $cc['label'], 'days' => $cc['valid_days']], null, (int)$cc['id'], 'code');
        if ($given) { db()->prepare('UPDATE coupon_codes SET claims = claims + 1 WHERE id = ?')->execute([$cc['id']]); flash('Voucher added to your wallet: ' . $cc['label']); }
        else        { flash('You already claimed this code.'); }
    }
    header('Location: vouchers.php'); exit;
}

$wallet = my_coupons($uid, false);
$now = time();
$active = array_filter($wallet, fn($c) => $c['used_order_id'] === null && (!$c['expires_at'] || strtotime($c['expires_at']) > $now));
$past   = array_filter($wallet, fn($c) => !in_array($c, $active, true));
$stats  = reward_stats($uid);
$unit   = ['orders' => ['delivered order', 'delivered orders'], 'items' => ['item bought', 'items bought'], 'spend' => null];
$next = [];   // the next reward to unlock for each metric
foreach (['orders', 'items', 'spend'] as $metric) {
    foreach (reward_rules() as $r) { if ($r['metric'] === $metric && $stats[$metric] < $r['target']) { $next[$metric] = $r; break; } }
}
$ruleText = fn($c) => $c['type'] === 'shipping' ? 'Free shipping' : ($c['type'] === 'percent' ? (float)$c['value'] . '% off' : money((float)$c['value']) . ' off');

$panelTitle = 'My account';
$menu = buyer_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My vouchers | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
  <?php include __DIR__ . '/includes/head-extra.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1>My vouchers</h1>
      <?php if ($m = flash()): ?><p class="form-ok"><?= htmlspecialchars($m) ?></p><?php endif; ?>

      <div class="card ship-card">
        <p><strong>&#128666; Shipping:</strong> <?= money((float)SHIPPING_FEE) ?> for each shop's order. <strong>Free</strong> when one shop's order is <?= money((float)FREE_SHIPPING_MIN) ?> or more.</p>
      </div>

      <h2>Have a voucher code?</h2>
      <form class="card dash-form claim-form" method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="claim">
        <input name="code" maxlength="30" placeholder="Enter code" aria-label="Voucher code" autocomplete="off" required>
        <button class="btn btn-primary" type="submit">Claim</button>
      </form>

      <h2>Available vouchers (<?= count($active) ?>)</h2>
      <?php if (!$active): ?><p class="muted">No vouchers right now.</p><?php endif; ?>
      <div class="ticket-grid">
        <?php foreach ($active as $c): ?>
          <div class="ticket <?= $c['type'] === 'shipping' ? 'ship' : '' ?>">
            <div class="ticket-main"><span class="ticket-rule"><?= $c['type'] === 'shipping' ? '&#128666; ' : '&#127903;&#65039; ' ?><?= htmlspecialchars($ruleText($c)) ?></span>
              <strong><?= htmlspecialchars($c['label']) ?></strong>
              <span class="muted"><?= (float)$c['min_spend'] > 0 ? 'Min spend ' . money((float)$c['min_spend']) : 'No minimum spend' ?><?= $c['max_discount'] !== null ? ' &middot; up to ' . money((float)$c['max_discount']) : '' ?></span></div>
            <span class="ticket-exp"><?= $c['expires_at'] ? 'Valid until<br>' . htmlspecialchars(date('M j, Y', strtotime($c['expires_at']))) : 'No expiry' ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($active): ?><p><a class="btn btn-primary" href="cart.php">Use in my cart</a></p><?php endif; ?>

      <h2>Earn more vouchers</h2>
      <div class="card earn-card">
        <div class="earn-grid">
          <?php foreach (['orders' => 'Orders delivered', 'items' => 'Items bought', 'spend' => 'Total spent'] as $metric => $title): $val = $stats[$metric]; $r = $next[$metric] ?? null; ?>
            <div class="earn-item">
              <strong><?= $title ?></strong>
              <span class="earn-num"><?= $metric === 'spend' ? money($val) : (int)$val ?></span>
              <?php if ($r): $pct = min(100, (int)round($val / $r['target'] * 100)); ?>
                <div class="bar" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?= $pct ?>%"></span></div>
                <span class="muted">Reach <?= $metric === 'spend' ? money((float)$r['target']) : (int)$r['target'] ?> to unlock: <?= htmlspecialchars($r['label']) ?></span>
              <?php else: ?><span class="muted">All rewards unlocked &#127881;</span><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if ($past): ?>
        <h2>Used or expired</h2>
        <div class="ticket-grid">
          <?php foreach (array_slice($past, 0, 12) as $c): ?>
            <div class="ticket gone"><div class="ticket-main"><strong><?= htmlspecialchars($c['label']) ?></strong>
              <span class="muted"><?= $c['used_order_id'] !== null ? 'Used on order #' . (int)$c['used_order_id'] : 'Expired' ?></span></div></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
