<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client', 'staff', 'admin');
ensure_shop_tables();
$uid = (int)$_SESSION['user_id'];

$pid = (int)($_GET['provider'] ?? $_POST['provider'] ?? 0);
$st = db()->prepare('SELECT * FROM (' . service_providers_sql() . ') t WHERE t.id = ?');
$st->execute([$pid]);
$sp = $st->fetch();
$me = db()->prepare('SELECT * FROM users WHERE id = ?'); $me->execute([$uid]); $me = $me->fetch();

$errors = []; $v = ['service' => '', 'date' => '', 'time' => '', 'address' => trim($me['address'] . ', ' . $me['city'] . ' ' . ($me['postal_code'] ?? '')), 'notes' => ''];
$cats = $sp ? provider_cats($sp) : [];
$slots = booking_slots();

if ($sp && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['service', 'date', 'time', 'address', 'notes'] as $k) { $v[$k] = trim($_POST[$k] ?? ''); }
    $d = DateTime::createFromFormat('!Y-m-d', $v['date']);
    $today = new DateTime('today');
    if (!csrf_ok())                                         $errors[] = 'Session expired. Please try again.';
    if ((int)$sp['id'] === $uid)                            $errors[] = "You can't book yourself.";
    if (!in_array($v['service'], $cats, true))              $errors[] = 'Choose one of the services this provider offers.';
    if (!$d || $d->format('Y-m-d') !== $v['date'])          $errors[] = 'Choose a date.';
    elseif ($d < $today)                                    $errors[] = 'The date cannot be in the past.';
    elseif ($d > (clone $today)->modify('+90 days'))        $errors[] = 'You can book up to 90 days ahead.';
    if (!isset($slots[$v['time']]))                         $errors[] = 'Choose a time.';
    elseif ($d && $d->format('Y-m-d') === date('Y-m-d') && $v['time'] <= date('H:i:s')) $errors[] = 'That time has already passed today. Pick a later time.';
    if (strlen($v['address']) < 5 || strlen($v['address']) > 300) $errors[] = 'Enter the address where the service should happen.';
    if (strlen($v['notes']) > 500)                          $errors[] = 'Notes are too long (500 characters max).';

    if (!$errors) {
        $busy = db()->prepare('SELECT id FROM bookings WHERE provider_id = ? AND booking_date = ? AND booking_time = ? AND status = "confirmed"');
        $busy->execute([$pid, $v['date'], $v['time']]);
        $dup = db()->prepare('SELECT id FROM bookings WHERE client_id = ? AND provider_id = ? AND booking_date = ? AND booking_time = ? AND status IN ("pending","confirmed")');
        $dup->execute([$uid, $pid, $v['date'], $v['time']]);
        if ($busy->fetch())     { $errors[] = 'This provider is already booked at that time. Please pick another time.'; }
        elseif ($dup->fetch())  { $errors[] = 'You already have a booking with this provider at that time.'; }
        else {
            db()->prepare('INSERT INTO bookings (client_id, provider_id, service, booking_date, booking_time, address, contact, notes, status, created_at) VALUES (?,?,?,?,?,?,?,?,"pending",?)')
                ->execute([$uid, $pid, $v['service'], $v['date'], $v['time'], $v['address'], $me['email'] ?: ($me['phone'] ?? ''), $v['notes'] ?: null, date('Y-m-d H:i:s')]);
            $bid = (int)db()->lastInsertId();
            notify($pid, "New booking request #$bid from {$me['first_name']}: {$v['service']} on " . date('D, M j', strtotime($v['date'])) . ' at ' . $slots[$v['time']] . '.', 'staff.php#jobs');
            flash('Booking request sent! ' . $sp['store'] . ' will confirm it. You will get a notification.');
            header('Location: bookings.php'); exit;
        }
    }
}
$h = fn($k) => htmlspecialchars($v[$k]);
$icons = category_options('service');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Book <?= $sp ? htmlspecialchars($sp['store']) : 'a service' ?> | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <main class="section shop-page book-page">
    <p><a href="providers.php">&larr; All service providers</a></p>
    <?php if (!$sp): ?>
      <h1>Provider not found</h1>
      <p class="muted">This provider may no longer be available. <a href="providers.php">See all providers</a>.</p>
    <?php else: ?>
      <div class="card provider-hero">
        <div class="avatar-lg"><?= avatar_art($sp['photo'] ?? '', $sp['store']) ?></div>
        <div>
          <h1><?= htmlspecialchars($sp['store']) ?></h1>
          <p class="chips-mini"><?php foreach ($cats as $pc): ?><span class="chip-mini"><?= $icons[$pc] ?? '' ?> <?= htmlspecialchars($pc) ?></span><?php endforeach; ?></p>
          <?php if ($sp['about']): ?><p><?= nl2br(htmlspecialchars($sp['about'])) ?></p><?php endif; ?>
          <p class="muted"><?= (int)$sp['done_jobs'] ?> job<?= (int)$sp['done_jobs'] === 1 ? '' : 's' ?> completed &middot;
            <?= $sp['service_rate'] !== null ? 'Starting from <strong>' . money((float)$sp['service_rate']) . '</strong>' : 'Price agreed with the provider' ?></p>
        </div>
      </div>

      <?php if ((int)$sp['id'] === $uid): ?>
        <p class="review-note">This is your own provider page, so you can't book yourself.</p>
      <?php else: ?>
        <h2 class="more-h">Request a booking</h2>
        <?php foreach ($errors as $e): ?><p class="form-error" role="alert"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        <form class="card dash-form book-form" method="post" novalidate>
          <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="provider" value="<?= (int)$sp['id'] ?>">
          <div class="form-grid">
            <div class="full"><label for="service">Service</label>
              <select id="service" name="service" required>
                <option value="">Choose a service</option>
                <?php foreach ($cats as $pc): ?><option value="<?= htmlspecialchars($pc) ?>" <?= $v['service'] === $pc ? 'selected' : '' ?>><?= htmlspecialchars($pc) ?></option><?php endforeach; ?>
              </select></div>
            <div><label for="date">Date</label>
              <input type="date" id="date" name="date" value="<?= $h('date') ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+90 days')) ?>" required></div>
            <div><label for="time">Time</label>
              <select id="time" name="time" required>
                <option value="">Choose a time</option>
                <?php foreach ($slots as $val => $lab): ?><option value="<?= $val ?>" <?= $v['time'] === $val ? 'selected' : '' ?>><?= $lab ?></option><?php endforeach; ?>
              </select></div>
            <div class="full"><label for="address">Where should the service happen?</label><input id="address" name="address" value="<?= $h('address') ?>" required></div>
            <div class="full"><label for="notes">Notes for the provider (optional)</label>
              <textarea id="notes" name="notes" rows="3" maxlength="500" placeholder="What do you need done? Anything they should bring or know?"><?= $h('notes') ?></textarea></div>
          </div>
          <p class="muted">The provider has to confirm your request. You can cancel it any time under My bookings.</p>
          <button class="btn btn-primary btn-lg" type="submit">Send booking request</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</body>
</html>
