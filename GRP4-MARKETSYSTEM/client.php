<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client');
$st = db()->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$_SESSION['user_id']]);
$me = $st->fetch();

ensure_shop_tables();
$picks = db()->query(product_sql() . ' WHERE p.is_active = 1 AND p.stock > 0 ORDER BY p.id DESC LIMIT 3')->fetchAll();

$panelTitle = 'My account';
$menu = client_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My account | ServiceHub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1>Welcome back, <?= htmlspecialchars($me['first_name']) ?></h1>
      <div class="cards">
        <div class="card"><span class="stat-value">2</span><span>Orders on the way</span></div>
        <div class="card"><span class="stat-value">3</span><span>Upcoming bookings</span></div>
        <div class="card"><span class="stat-value">5</span><span>Saved items</span></div>
      </div>

      <h2 id="bookings">My bookings</h2>
      <ul class="booking-list card">
        <?php foreach ($bookings as $b): ?>
          <li>
            <div><strong><?= htmlspecialchars($b['service']) ?></strong>
              <span class="muted"><?= htmlspecialchars($b['provider']) ?> &middot; <?= htmlspecialchars($b['date']) ?></span></div>
            <div class="booking-end"><span class="badge badge-<?= strtolower($b['status']) ?>"><?= $b['status'] ?></span>
              <span class="price"><?= money($b['price']) ?></span></div>
          </li>
        <?php endforeach; ?>
      </ul>

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
