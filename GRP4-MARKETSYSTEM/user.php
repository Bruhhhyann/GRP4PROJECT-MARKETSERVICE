<?php
// Public page of a shop, provider or client (clients show first name + last initial only).
require_once __DIR__ . '/includes/bootstrap.php';
ensure_shop_tables();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT u.id, u.role, u.staff_type, u.first_name, u.last_name, u.photo, u.created_at, u.id_status, ' . store_expr('u.id', 'u.first_name') . ' AS store
                     FROM users u WHERE u.id = ? AND u.role IN ("client","staff") AND u.is_verified = 1');
$st->execute([$id]);
$pp = $st->fetch();
if ($pp && $pp['role'] === 'client' && !is_signed_in()) { header('Location: signin.php'); exit; }

$isMe = $pp && is_signed_in() && (int)$_SESSION['user_id'] === (int)$pp['id'];
$products = []; $sp = null; $reviewCount = 0;
if ($pp && $pp['role'] === 'staff' && $pp['staff_type'] === 'product') {
    $q = db()->prepare(product_sql() . ' WHERE p.seller_id = ? AND p.is_active = 1 ORDER BY (p.stock > 0) DESC, p.id DESC LIMIT 12');
    $q->execute([$id]);
    $products = $q->fetchAll();
} elseif ($pp && $pp['role'] === 'staff') {
    $q = db()->prepare('SELECT * FROM (' . service_providers_sql() . ') t WHERE t.id = ?');
    $q->execute([$id]);
    $sp = $q->fetch() ?: null;
} elseif ($pp) {
    $q = db()->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = ?');
    $q->execute([$id]);
    $reviewCount = (int)$q->fetchColumn();
}
$name = $pp ? public_name($pp) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $pp ? htmlspecialchars($name) : 'Profile' ?> | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
  <?php include __DIR__ . '/includes/head-extra.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <main class="section shop-page">
    <?php if (!$pp): ?>
      <h1>Profile not found</h1>
      <p class="muted">This account may no longer exist. <a href="people.php?tab=find">Find people</a>.</p>
    <?php else: ?>
      <div class="card profile-hero">
        <div class="avatar-lg"><?= avatar_art($pp['photo'] ?? '', $name) ?></div>
        <div class="hero-info">
          <h1><?= htmlspecialchars($name) ?><?= $pp['role'] === 'client' && $pp['id_status'] === 'approved' ? ' <span class="verified-check" title="ID verified">&#10003;</span>' : '' ?></h1>
          <p class="muted"><?= htmlspecialchars(role_label($pp)) ?> &middot; Member since <?= htmlspecialchars(date('F Y', strtotime($pp['created_at']))) ?></p>
          <p class="follow-line">&#128101; <strong><?= follower_count($id) ?></strong> follower<?= follower_count($id) === 1 ? '' : 's' ?> &middot; <strong><?= following_count($id) ?></strong> following</p>
          <?php if ($isMe): ?>
            <p><a class="btn btn-small" href="dashboard.php">My dashboard</a> <a class="btn btn-small btn-light" href="people.php?tab=followers">My followers</a></p>
          <?php else: $fuId = $id; include __DIR__ . '/includes/follow-button.php'; endif; ?>
        </div>
      </div>

      <?php if ($sp): $icons = category_options('service'); ?>
        <h2 class="more-h">Services</h2>
        <div class="card">
          <p class="chips-mini"><?php foreach (provider_cats($sp) as $pc): ?><span class="chip-mini"><?= $icons[$pc] ?? '' ?> <?= htmlspecialchars($pc) ?></span><?php endforeach; ?></p>
          <?php if ($sp['about']): ?><p><?= nl2br(htmlspecialchars($sp['about'])) ?></p><?php endif; ?>
          <p class="muted"><?= (int)$sp['done_jobs'] ?> job<?= (int)$sp['done_jobs'] === 1 ? '' : 's' ?> completed &middot; <?= $sp['service_rate'] !== null ? 'Starting from <strong>' . money((float)$sp['service_rate']) . '</strong>' : 'Price agreed with the provider' ?></p>
          <?php if (!$isMe): ?><p><a class="btn btn-primary" href="book.php?provider=<?= (int)$sp['id'] ?>">Book now</a></p><?php endif; ?>
        </div>
      <?php elseif ($pp['role'] === 'staff' && $pp['staff_type'] === 'product'): ?>
        <h2 class="more-h">Products</h2>
        <?php if (!$products): ?><p class="muted">No products yet.</p>
        <?php else: ?>
          <div class="product-grid"><?php foreach ($products as $p) { include __DIR__ . '/includes/product-card.php'; } ?></div>
        <?php endif; ?>
      <?php else: ?>
        <p class="muted"><?= $reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?> written</p>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</body>
</html>
