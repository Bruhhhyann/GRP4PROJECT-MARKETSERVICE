<?php
require_once __DIR__ . '/includes/bootstrap.php';
$q = trim($_GET['q'] ?? '');
$cat = $_GET['cat'] ?? '';
$icons = category_options('service');
$list = []; $dbError = false;
try {
    ensure_shop_tables();
    $sql = 'SELECT * FROM (' . service_providers_sql() . ') t WHERE 1 = 1'; $args = [];
    if ($q !== '') { $sql .= ' AND (t.store LIKE ? OR t.about LIKE ? OR COALESCE(t.cats, "") LIKE ?)'; $like = '%' . $q . '%'; array_push($args, $like, $like, $like); }
    if (isset($icons[$cat])) { $sql .= ' AND (t.cats IS NULL OR FIND_IN_SET(?, t.cats) > 0)'; $args[] = $cat; }
    $st = db()->prepare($sql . ' ORDER BY t.done_jobs DESC, t.id LIMIT 60');
    $st->execute($args);
    $list = $st->fetchAll();
} catch (PDOException $e) { $dbError = true; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Book a service | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <main class="section shop-page">
    <h1>Book a service</h1>
    <form class="search-bar" method="get" action="providers.php" role="search">
      <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search cleaning, repairs, tutoring..." aria-label="Search providers">
      <?php if ($cat): ?><input type="hidden" name="cat" value="<?= htmlspecialchars($cat) ?>"><?php endif; ?>
      <button class="btn btn-primary" type="submit">Search</button>
    </form>
    <div class="chips">
      <a class="chip <?= $cat === '' ? 'on' : '' ?>" href="providers.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>">All</a>
      <?php foreach ($icons as $cn => $ic): ?>
        <a class="chip <?= $cat === $cn ? 'on' : '' ?>" href="providers.php?cat=<?= urlencode($cn) ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"><?= $ic ?> <?= htmlspecialchars($cn) ?></a>
      <?php endforeach; ?>
    </div>
    <?php if ($m = flash()): ?><p class="form-ok"><?= htmlspecialchars($m) ?></p><?php endif; ?>
    <?php if ($dbError): ?>
      <p class="form-error">Could not load providers. Is MySQL running?</p>
    <?php elseif (!$list): ?>
      <p class="muted">No service providers found. Clients can apply under <strong>Become a provider</strong>, and an admin approves them.</p>
    <?php else: ?>
      <p class="muted"><?= count($list) ?> provider<?= count($list) === 1 ? '' : 's' ?> found</p>
      <div class="provider-grid">
        <?php foreach ($list as $sp) { include __DIR__ . '/includes/provider-card.php'; } ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
