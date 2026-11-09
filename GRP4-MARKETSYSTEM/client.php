<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client');
ensure_shop_tables();
$uid = (int)$_SESSION['user_id'];
$st = db()->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$uid]);
$me = $st->fetch();

$one = function (string $sql) use ($uid): int { $q = db()->prepare($sql); $q->execute([$uid]); return (int)$q->fetchColumn(); };
$ordersOpen    = $one('SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND status IN ("pending","approved")');
$bookingsAhead = $one('SELECT COUNT(*) FROM bookings WHERE client_id = ? AND status IN ("pending","confirmed") AND booking_date >= CURDATE()');
$inCart        = cart_count();

$bk = db()->prepare('SELECT b.*, ' . store_expr('b.provider_id', 'u.first_name') . ' AS store FROM bookings b JOIN users u ON u.id = b.provider_id
                     WHERE b.client_id = ? ORDER BY b.booking_date DESC, b.booking_time DESC LIMIT 5');
$bk->execute([$uid]);
$recent = $bk->fetchAll();

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
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1>Welcome back, <?= htmlspecialchars($me['first_name']) ?></h1>
      <div class="cards">
        <a class="card stat-link" href="orders.php"><span class="stat-value"><?= $ordersOpen ?></span><span>Orders in progress</span></a>
        <a class="card stat-link" href="bookings.php"><span class="stat-value"><?= $bookingsAhead ?></span><span>Upcoming bookings</span></a>
        <a class="card stat-link" href="cart.php"><span class="stat-value"><?= $inCart ?></span><span>Items in your cart</span></a>
      </div>

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
