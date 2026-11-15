<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client');
ensure_shop_tables();
$uid = (int)$_SESSION['user_id'];
$st = db()->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$uid]);
$me = $st->fetch();
$appQ = db()->prepare('SELECT type, status FROM provider_applications WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$appQ->execute([$uid]);
$app = $appQ->fetch() ?: [];
$canApply = ($me['id_status'] === 'approved');

$one = function (string $sql) use ($uid): int { $q = db()->prepare($sql); $q->execute([$uid]); return (int)$q->fetchColumn(); };
$ordersOpen    = $one('SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND status IN ("pending","approved")');
$bookingsAhead = $one('SELECT COUNT(*) FROM bookings WHERE client_id = ? AND status IN ("pending","confirmed") AND booking_date >= CURDATE()');
$inCart        = cart_count();

$bk = db()->prepare('SELECT b.*, ' . store_expr('b.provider_id', 'u.first_name') . ' AS store FROM bookings b JOIN users u ON u.id = b.provider_id
                     WHERE b.client_id = ? ORDER BY b.booking_date DESC, b.booking_time DESC LIMIT 5');
$bk->execute([$uid]);
$recent = $bk->fetchAll();

$fp = db()->prepare(product_sql() . ' WHERE p.is_active = 1 AND p.stock > 0 AND p.seller_id IN (SELECT followed_id FROM follows WHERE follower_id = ?) ORDER BY p.id DESC LIMIT 4');
$fp->execute([$uid]);
$followedPicks = $fp->fetchAll();
$picks = db()->query(product_sql() . ' WHERE p.is_active = 1 AND p.stock > 0 ORDER BY p.id DESC LIMIT 3')->fetchAll();

$panelTitle = 'My account';
$menu = client_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My account | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
  <?php include __DIR__ . '/includes/head-extra.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1>Welcome back, <?= htmlspecialchars($me['first_name']) ?><?php if ($me['id_status'] === 'approved'): ?> <span class="verified-check" title="ID verified" aria-label="ID verified">&#10003;</span><?php endif; ?></h1>
      <p class="follow-line">&#128101; <a href="people.php?tab=followers"><strong><?= follower_count($uid) ?></strong> follower<?= follower_count($uid) === 1 ? '' : 's' ?></a> &middot; <a href="people.php?tab=following"><strong><?= following_count($uid) ?></strong> following</a> &middot; <a href="people.php?tab=find">Find people</a></p>
      <?php $idst = $me['id_status']; ?>
      <?php if ($idst !== 'approved'): ?>
      <div class="card id-card <?= $idst ?>">
        <div class="id-card-head">
          <?php if ($idst === 'pending'): ?>
            <strong>&#128260; Your ID is being verified</strong><span class="badge badge-pending">In review</span>
          <?php elseif ($idst === 'approved'): ?>
            <strong>&#9989; Your ID is verified</strong><span class="badge badge-confirmed">Approved</span>
          <?php else: ?>
            <strong>&#10060; Your ID was rejected</strong><span class="badge badge-rejected">Rejected</span>
          <?php endif; ?>
        </div>
        <?= id_progress_html($idst) ?>
        <?php if ($idst === 'pending'): ?>
          <p class="muted">An admin is checking your <?= $me['id_doc_type'] === 'drivers_license' ? "Driver's License" : 'National ID' ?>. <a href="id-upload.php">See details</a></p>
        <?php elseif ($idst === 'rejected'): ?>
          <p><?= $me['id_note'] ? 'Reason: ' . htmlspecialchars($me['id_note']) . '.' : 'The admin could not accept this photo.' ?> <a class="btn btn-small" href="id-upload.php">Upload a new ID</a></p>
        <?php else: ?>
          <p class="muted">You can order products, book services and apply as a provider.</p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="cards">
        <a class="card stat-link" href="orders.php"><span class="stat-value"><?= $ordersOpen ?></span><span>Orders in progress</span></a>
        <a class="card stat-link" href="bookings.php"><span class="stat-value"><?= $bookingsAhead ?></span><span>Upcoming bookings</span></a>
        <a class="card stat-link" href="cart.php"><span class="stat-value"><?= $inCart ?></span><span>Items in your cart</span></a>
      </div>

      <h2 id="provider">Become a provider</h2>
      <?php if (($app['status'] ?? '') === 'pending'): ?>
        <div class="card"><p><span class="badge badge-pending">Pending</span> Your application to become a <strong><?= ($app['type'] ?? '') === 'product' ? 'product' : 'service' ?> provider</strong> is waiting for an admin.</p>
          <p><a class="btn btn-small" href="apply.php">View or change my application</a></p></div>
      <?php else: ?>
        <?php if (($app['status'] ?? '') === 'rejected'): ?><p class="form-error">Your last application was rejected. You can apply again.</p><?php endif; ?>
        <?php if (!$canApply): ?><p class="review-note">&#128274; You can register as a provider once your ID is approved.</p><?php endif; ?>
        <div class="cta-grid">
          <?php foreach ([['product', '🛍️', 'Sell products', 'Shoes, shirts, bags and more. List your products and receive orders.'],
                          ['service', '🔧', 'Offer services', 'Cleaning, repairs, design, tutoring and more. Get booked by clients.']] as [$ct, $ci, $ctitle, $cdesc]): ?>
            <a class="cta-tile<?= $canApply ? '' : ' locked' ?>" href="<?= $canApply ? 'apply.php?type=' . $ct : 'id-upload.php' ?>">
              <span class="cta-icon" aria-hidden="true"><?= $ci ?></span>
              <strong><?= $ctitle ?></strong>
              <span class="muted"><?= $cdesc ?></span>
              <span class="btn btn-primary btn-small"><?= $canApply ? 'Register as ' . $ct . ' provider' : 'Locked until your ID is approved' ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h2 id="bookings">My bookings</h2>
      <?php if (!$recent): ?>
        <div class="card"><p>You have not booked any service yet.</p><a class="btn btn-primary" href="providers.php">Find a service provider</a></div>
      <?php else: ?>
        <ul class="booking-list card">
          <?php foreach ($recent as $b): [$label, $class] = booking_badge($b['status']); ?>
            <li>
              <div><strong><?= htmlspecialchars($b['service']) ?></strong>
                <span class="muted"><?= htmlspecialchars($b['store']) ?> &middot; <?= htmlspecialchars(booking_when($b)) ?></span></div>
              <div class="booking-end"><span class="badge <?= $class ?>"><?= $label ?></span></div>
            </li>
          <?php endforeach; ?>
        </ul>
        <p><a class="btn btn-ghost" href="bookings.php">See all my bookings</a> <a class="btn btn-ghost" href="providers.php">Book another service</a></p>
      <?php endif; ?>

      <?php if ($followedPicks): ?>
        <h2 id="following">New from shops you follow</h2>
        <div class="product-grid">
          <?php foreach ($followedPicks as $p) { include __DIR__ . '/includes/product-card.php'; } ?>
        </div>
      <?php endif; ?>

      <h2 id="shop">Shop for you</h2>
      <?php if (!$picks): ?>
        <p class="muted">No products yet.</p>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($picks as $p) { include __DIR__ . '/includes/product-card.php'; } ?>
        </div>
        <p><a class="btn btn-ghost" href="shop.php">Browse all products</a></p>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
