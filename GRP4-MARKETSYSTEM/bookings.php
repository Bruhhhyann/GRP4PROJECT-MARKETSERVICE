<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client', 'staff', 'admin');
ensure_shop_tables();
$uid = (int)$_SESSION['user_id'];

// Cancel a booking (waiting or confirmed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    if (!csrf_ok()) {
        flash('Session expired. Please try again.');
    } else {
        $b = db()->prepare('SELECT * FROM bookings WHERE id = ? AND client_id = ? AND status IN ("pending","confirmed")');
        $b->execute([(int)($_POST['booking_id'] ?? 0), $uid]);
        if ($b = $b->fetch()) {
            db()->prepare('UPDATE bookings SET status = "cancelled", updated_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $b['id']]);
            notify((int)$b['provider_id'], "{$_SESSION['user']} cancelled booking #{$b['id']} ({$b['service']}, " . booking_when($b) . ').', 'staff.php#jobs');
            flash('Booking cancelled.');
        } else {
            flash('That booking can no longer be cancelled.');
        }
    }
    header('Location: bookings.php'); exit;
}

$st = db()->prepare('SELECT b.*, ' . store_expr('b.provider_id', 'u.first_name') . ' AS store, u.photo FROM bookings b JOIN users u ON u.id = b.provider_id
                     WHERE b.client_id = ? ORDER BY b.booking_date DESC, b.booking_time DESC LIMIT 60');
$st->execute([$uid]);
$bookings = $st->fetchAll();

$panelTitle = 'My account';
$menu = buyer_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My bookings | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1>My bookings</h1>
      <?php if ($m = flash()): ?><p class="form-ok"><?= htmlspecialchars($m) ?></p><?php endif; ?>
      <?php if (!$bookings): ?>
        <div class="card"><p>You have not booked any service yet.</p><a class="btn btn-primary" href="providers.php">Find a service provider</a></div>
      <?php endif; ?>
      <?php foreach ($bookings as $b): [$label, $class] = booking_badge($b['status']); ?>
        <div class="card order-card">
          <div class="order-head">
            <div class="who"><span class="avatar-sm"><?= avatar_art($b['photo'] ?? '', $b['store']) ?></span>
              <div><strong><?= htmlspecialchars($b['service']) ?></strong> <span class="muted">with <?= htmlspecialchars($b['store']) ?> &middot; booking #<?= (int)$b['id'] ?></span></div></div>
            <span class="badge <?= $class ?>"><?= $label ?></span>
          </div>
          <p class="eta-box<?= $b['status'] === 'completed' ? ' done' : '' ?>">&#128197; <?= htmlspecialchars(booking_when($b)) ?></p>
          <p class="muted">&#128205; <?= htmlspecialchars($b['address']) ?><?= $b['notes'] ? ' &middot; Your note: ' . htmlspecialchars($b['notes']) : '' ?></p>
          <?php if (in_array($b['status'], ['pending', 'confirmed'], true)): ?>
            <form method="post" class="received-form" onsubmit="return confirm('Cancel this booking?');">
              <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="cancel"><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
              <button class="btn btn-small btn-reject" type="submit">Cancel booking</button>
              <?php if ($b['status'] === 'pending'): ?><span class="muted">Waiting for the provider to confirm.</span><?php endif; ?>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </main>
  </div>
</body>
</html>
