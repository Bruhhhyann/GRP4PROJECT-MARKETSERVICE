<?php
require_once __DIR__ . '/includes/bootstrap.php';
$q = trim($_GET['q'] ?? '');
$cat = $_GET['cat'] ?? '';
$products = []; $dbError = false;
try {
    ensure_shop_tables();
    $sql = product_sql() . ' WHERE p.is_active = 1'; $args = [];
    if ($q !== '') { $sql .= ' AND (p.name LIKE ? OR p.description LIKE ? OR p.category LIKE ?)'; $like = '%' . $q . '%'; array_push($args, $like, $like, $like); }
    if (in_array($cat, product_categories(), true)) { $sql .= ' AND p.category = ?'; $args[] = $cat; }
    $st = db()->prepare($sql . ' ORDER BY (p.stock > 0) DESC, p.id DESC LIMIT 60');
    $st->execute($args);
    $products = $st->fetchAll();
} catch (PDOException $e) { $dbError = true; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Shop products | ServiceHub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <main class="section shop-page">
    <h1>Shop products</h1>
    <form class="search-bar" method="get" action="shop.php" role="search">
      <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search shoes, shirts, bags..." aria-label="Search products">
      <?php if ($cat): ?><input type="hidden" name="cat" value="<?= htmlspecialchars($cat) ?>"><?php endif; ?>
      <button class="btn btn-primary" type="submit">Search</button>
    </form>
    <div class="chips">
      <a class="chip <?= $cat === '' ? 'on' : '' ?>" href="shop.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>">All</a>
      <?php foreach (product_categories() as $c): ?>
        <a class="chip <?= $cat === $c ? 'on' : '' ?>" href="shop.php?cat=<?= urlencode($c) ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"><?= product_icon($c) ?> <?= htmlspecialchars($c) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if ($m = flash()): ?><p class="form-ok"><?= htmlspecialchars($m) ?></p><?php endif; ?>
    <?php if ($dbError): ?>
      <p class="form-error">Could not load products. Is MySQL running?</p>
    <?php elseif (!$products): ?>
      <p class="muted">No products found<?= $q !== '' ? ' for "' . htmlspecialchars($q) . '"' : '' ?>. Product providers can add items from their dashboard.</p>
    <?php else: ?>
      <p class="muted"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?> found</p>
      <div class="product-grid">
        <?php foreach ($products as $p) { include __DIR__ . '/includes/product-card.php'; } ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
