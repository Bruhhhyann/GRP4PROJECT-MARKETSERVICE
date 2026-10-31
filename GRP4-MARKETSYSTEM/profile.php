<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client');
ensure_profile_changes_table();

$uid = $_SESSION['user_id'];
$me = db()->prepare('SELECT * FROM users WHERE id = ?');
$me->execute([$uid]);
$me = $me->fetch();
$_SESSION['user'] = $me['first_name'];   // keeps the header name up to date after an approval

$cols = ['first_name', 'middle_name', 'last_name', 'phone', 'address', 'city', 'postal_code'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = [];
    foreach ($cols as $c) { $d[$c] = trim($_POST[$c] ?? ''); }
    if ($d['first_name'] === '' || $d['last_name'] === '') $errors[] = 'First name and last name are required.';
    if ($d['address'] === '' || $d['city'] === '')          $errors[] = 'Address and city are required.';
    if ($d['phone'] !== '' && !preg_match('/^\+?[0-9]{7,15}$/', $d['phone'])) $errors[] = 'Phone: digits only, 7 to 15 numbers (a leading + is fine).';
    if ($d['phone'] !== '') {
        $x = db()->prepare('SELECT id FROM users WHERE phone = ? AND id <> ?');
        $x->execute([$d['phone'], $uid]);
        if ($x->fetch()) $errors[] = 'That phone number is already used by another account.';
    }
    if (!$errors) {
        // Saved as a REQUEST only. The real profile changes when an admin approves it.
        db()->prepare('DELETE FROM profile_changes WHERE user_id = ? AND status = "pending"')->execute([$uid]);
        db()->prepare('INSERT INTO profile_changes (user_id, first_name, middle_name, last_name, phone, address, city, postal_code, status, created_at)
                       VALUES (?,?,?,?,?,?,?,?,"pending",?)')
            ->execute([$uid, $d['first_name'], $d['middle_name'] ?: null, $d['last_name'], $d['phone'] ?: null,
                       $d['address'], $d['city'], $d['postal_code'] ?: null, date('Y-m-d H:i:s')]);
        header('Location: profile.php?sent=1'); exit;
    }
}

$last = db()->prepare('SELECT * FROM profile_changes WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$last->execute([$uid]);
$last = $last->fetch();
$pending = $last && $last['status'] === 'pending' ? $last : null;

// Form shows what the user just typed, else the pending request, else the approved profile
$form = $errors ? $d : ($pending ?: $me);
$v = fn($k) => htmlspecialchars((string)($form[$k] ?? ''));
$show = fn($row) => htmlspecialchars(trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']));

$panelTitle = 'My account';
$menu = client_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My profile | ServiceHub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1>My profile</h1>

      <?php if (isset($_GET['sent'])): ?><p class="form-ok">Your changes were sent. They will show on your profile once an admin approves them.</p><?php endif; ?>
      <?php if ($last && $last['status'] === 'rejected'): ?><p class="form-error">Your last change request was rejected by the admin. You can edit and send it again.</p><?php endif; ?>
      <?php if ($last && $last['status'] === 'approved' && !isset($_GET['sent'])): ?><p class="form-ok">Your last change request was approved and saved.</p><?php endif; ?>

      <h2>Current information</h2>
      <div class="card profile">
        <p><strong>Name</strong><?= $show($me) ?></p>
        <p><strong>Email</strong><?= htmlspecialchars($me['email']) ?></p>
        <p><strong>Phone</strong><?= htmlspecialchars($me['phone'] ?: 'Not added') ?></p>
        <p><strong>Deliver to</strong><?= htmlspecialchars($me['address'] . ', ' . $me['city'] . ' ' . $me['postal_code']) ?></p>
      </div>

      <?php if ($pending): ?>
        <h2>Requested changes <span class="badge badge-pending">Pending approval</span></h2>
        <div class="card profile">
          <p><strong>Name</strong><?= $show($pending) ?></p>
          <p><strong>Phone</strong><?= htmlspecialchars($pending['phone'] ?: 'Not added') ?></p>
          <p><strong>Deliver to</strong><?= htmlspecialchars($pending['address'] . ', ' . $pending['city'] . ' ' . $pending['postal_code']) ?></p>
          <p class="muted">Waiting for an admin. Your profile above stays the same until it is approved.</p>
        </div>
      <?php endif; ?>

      <h2>Edit information</h2>
      <?php foreach ($errors as $e): ?><p class="form-error" role="alert"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
      <form class="card dash-form" method="post" novalidate>
        <div class="form-grid">
          <div><label for="first_name">First name</label><input id="first_name" name="first_name" value="<?= $v('first_name') ?>" required></div>
          <div><label for="middle_name">Middle name (optional)</label><input id="middle_name" name="middle_name" value="<?= $v('middle_name') ?>"></div>
          <div class="full"><label for="last_name">Last name</label><input id="last_name" name="last_name" value="<?= $v('last_name') ?>" required></div>
          <div><label for="email">Email (cannot be changed here)</label><input id="email" value="<?= htmlspecialchars($me['email']) ?>" disabled></div>
          <div><label for="phone">Phone number</label><input type="tel" id="phone" name="phone" value="<?= $v('phone') ?>"></div>
          <div class="full"><label for="address">Address</label><input id="address" name="address" value="<?= $v('address') ?>" required></div>
          <div><label for="city">City</label><input id="city" name="city" value="<?= $v('city') ?>" required></div>
          <div><label for="postal_code">Postal code</label><input id="postal_code" name="postal_code" value="<?= $v('postal_code') ?>"></div>
        </div>
        <p class="muted">Changes need admin approval before they are saved to your profile.</p>
        <button class="btn btn-primary btn-lg" type="submit">Send for approval</button>
      </form>
    </main>
  </div>
</body>
</html>
