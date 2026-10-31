<?php
require_once __DIR__ . '/includes/bootstrap.php';

$errors = []; $f = [];
$fields = ['first_name','middle_name','last_name','email','phone','address','city','postal_code'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $k) { $f[$k] = trim($_POST[$k] ?? ''); }
    $pass = $_POST['password'] ?? ''; $pass2 = $_POST['password2'] ?? '';

    if ($f['first_name'] === '' || $f['last_name'] === '') $errors[] = 'First name and last name are required.';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL))   $errors[] = 'Enter a valid email. We send your code there.';
    if ($f['phone'] !== '' && !preg_match('/^\+?[0-9]{7,15}$/', $f['phone'])) $errors[] = 'Phone: digits only, 7 to 15 numbers (a leading + is fine).';
    if ($f['address'] === '' || $f['city'] === '')          $errors[] = 'Address and city are required for deliveries and bookings.';
    if (strlen($pass) < 8)                                  $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2)                                   $errors[] = 'Passwords do not match.';

    if (!$errors) {
        try {
            // clear old never-verified rows from the earlier version, then check for real duplicates
            db()->prepare('DELETE FROM users WHERE is_verified = 0 AND (email = ? OR phone = ?)')->execute([$f['email'], $f['phone'] ?: null]);
            $st = db()->prepare('SELECT id FROM users WHERE email = ? OR (? <> "" AND phone = ?)');
            $st->execute([$f['email'], $f['phone'], $f['phone']]);
            if ($st->fetch()) {
                $errors[] = 'An account with that email or phone already exists.';
            } else {
                // Keep everything in the session. NOTHING is saved to the database until the code is verified.
                $f['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
                $_SESSION['pending_reg'] = ['data' => $f];
                if (send_registration_code()) { header('Location: verify.php'); exit; }
                unset($_SESSION['pending_reg']);
                $errors[] = 'We could not send the email. Check includes/mail-config.php and try again.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error. Is MySQL running and database.sql imported?';
        }
    }
}
$v = fn($k) => htmlspecialchars($f[$k] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register | ServiceHub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <main class="auth-wrap">
    <form class="auth-card wide" method="post" novalidate>
      <h1>Create your account</h1>
      <p class="muted">We'll send a verification code that expires in <?= CODE_MINUTES ?> minutes.</p>
      <?php foreach ($errors as $e): ?><p class="form-error" role="alert"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>

      <div class="form-grid">
        <div><label for="first_name">First name</label><input id="first_name" name="first_name" value="<?= $v('first_name') ?>" required></div>
        <div><label for="middle_name">Middle name (optional)</label><input id="middle_name" name="middle_name" value="<?= $v('middle_name') ?>"></div>
        <div class="full"><label for="last_name">Last name</label><input id="last_name" name="last_name" value="<?= $v('last_name') ?>" required></div>
        <div><label for="email">Email (we send your code here)</label><input type="email" id="email" name="email" value="<?= $v('email') ?>" required></div>
        <div><label for="phone">Phone number (optional)</label><input type="tel" id="phone" name="phone" value="<?= $v('phone') ?>"></div>
        <div class="full"><label for="address">Address</label><input id="address" name="address" value="<?= $v('address') ?>" required></div>
        <div><label for="city">City</label><input id="city" name="city" value="<?= $v('city') ?>" required></div>
        <div><label for="postal_code">Postal code</label><input id="postal_code" name="postal_code" value="<?= $v('postal_code') ?>"></div>
        <div><label for="password">Password</label><input type="password" id="password" name="password" autocomplete="new-password" required></div>
        <div><label for="password2">Confirm password</label><input type="password" id="password2" name="password2" autocomplete="new-password" required></div>
      </div>
      <p class="muted hint-left">Your account is only created after you enter the code we email you.</p>
      <button class="btn btn-primary btn-lg btn-block" type="submit">Register</button>
      <p class="muted hint">Already have an account? <a href="signin.php">Sign in</a></p>
    </form>
  </main>
</body>
</html>
