<?php
// Following, followers, and a search to find shops, providers and clients.
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client', 'staff', 'admin');
ensure_shop_tables();
$uid = (int)$_SESSION['user_id'];
$tab = in_array($_GET['tab'] ?? '', ['following', 'followers', 'find'], true) ? $_GET['tab'] : 'following';
$q = trim($_GET['q'] ?? '');

$cols = 'u.id, u.role, u.staff_type, u.first_name, u.last_name, u.photo, u.id_status, ' . store_expr('u.id', 'u.first_name') . ' AS store';
$list = [];
if ($tab === 'following') {
    $s = db()->prepare("SELECT $cols FROM follows f JOIN users u ON u.id = f.followed_id WHERE f.follower_id = ? ORDER BY f.created_at DESC LIMIT 100");
    $s->execute([$uid]); $list = $s->fetchAll();
} elseif ($tab === 'followers') {
    $s = db()->prepare("SELECT $cols FROM follows f JOIN users u ON u.id = f.follower_id WHERE f.followed_id = ? ORDER BY f.created_at DESC LIMIT 100");
    $s->execute([$uid]); $list = $s->fetchAll();
} elseif (mb_strlen($q) >= 2) {
    $s = db()->prepare("SELECT * FROM (SELECT $cols FROM users u WHERE u.role IN ('client','staff') AND u.is_verified = 1) t
                        WHERE t.id <> ? AND (t.first_name LIKE ? OR t.last_name LIKE ? OR t.store LIKE ?) ORDER BY (t.role = 'staff') DESC, t.store LIMIT 40");
    $like = '%' . $q . '%';
    $s->execute([$uid, $like, $like, $like]); $list = $s->fetchAll();
}

$panelTitle = 'My account';
$menu = buyer_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>People | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
  <?php include __DIR__ . '/includes/head-extra.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1>People</h1>
      <div class="chips">
        <a class="chip <?= $tab === 'following' ? 'on' : '' ?>" href="people.php?tab=following">Following (<?= following_count($uid) ?>)</a>
        <a class="chip <?= $tab === 'followers' ? 'on' : '' ?>" href="people.php?tab=followers">Followers (<?= follower_count($uid) ?>)</a>
        <a class="chip <?= $tab === 'find' ? 'on' : '' ?>" href="people.php?tab=find">Find people</a>
      </div>

      <?php if ($tab === 'find'): ?>
        <form class="search-bar" method="get" action="people.php" role="search">
          <input type="hidden" name="tab" value="find">
          <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search a shop, provider or person by name" aria-label="Search people" autofocus>
          <button class="btn btn-primary" type="submit">Search</button>
        </form>
        <?php if (mb_strlen($q) < 2): ?><p class="muted">Type at least 2 letters of a name.</p><?php endif; ?>
      <?php endif; ?>

      <?php if (!$list): ?>
        <?php if ($tab === 'following'): ?><div class="card"><p>You are not following anyone yet. Follow shops and providers to see their new products here.</p><a class="btn btn-primary" href="people.php?tab=find">Find people</a></div>
        <?php elseif ($tab === 'followers'): ?><div class="card"><p>No followers yet.</p></div>
        <?php elseif (mb_strlen($q) >= 2): ?><p class="muted">No one found for "<?= htmlspecialchars($q) ?>".</p><?php endif; ?>
      <?php else: ?>
        <div class="people-list">
          <?php foreach ($list as $pp) { include __DIR__ . '/includes/person-card.php'; } ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
