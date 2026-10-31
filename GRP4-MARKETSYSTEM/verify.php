<?php
require_once __DIR__ . '/includes/bootstrap.php';

$p = $_SESSION['pending_reg'] ?? null;
if (!$p) { header('Location: register.php'); exit; }

$error = ''; $notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'resend') {
        $wait = RESEND_SECONDS - (time() - $p['sent_at']);
        if ($wait > 0) {
            $error = "Please wait $wait seconds before asking for a new code.";
        } elseif (send_registration_code()) {
            $notice = 'A new code was sent. The old one no longer works.';
        } else {
            $error = 'We could not send the email. Check includes/mail-config.php and try again.';
        }
    } else {
        $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
        if (time() > $p['expires']) {
            $error = 'That code has expired. Tap "Resend code" for a new one.';
        } elseif ($p['tries'] >= 5) {
            $error = 'Too many wrong tries. Tap "Resend code" for a new one.';
        } elseif (!hash_equals($p['code_hash'], hash('sha256', $code))) {
            $_SESSION['pending_reg']['tries']++;
            $error = 'Wrong code. Check it and try again.';
        } else {
            // Code is correct: NOW the account is saved to the database.
            $d = $p['data'];
            try {
                $dup = db()->prepare('SELECT id FROM users WHERE email = ? OR (? <> "" AND phone = ?)');
                $dup->execute([$d['email'], $d['phone'], $d['phone']]);
                if ($dup->fetch()) { unset($_SESSION['pending_reg']); header('Location: signin.php'); exit; }
                db()->prepare('INSERT INTO users (role, first_name, middle_name, last_name, email, phone, password_hash, address, city, postal_code, is_verified)
                               VALUES ("client",?,?,?,?,?,?,?,?,?,1)')
                    ->execute([$d['first_name'], $d['middle_name'] ?: null, $d['last_name'], $d['email'], $d['phone'] ?: null,
                               $d['password_hash'], $d['address'], $d['city'], $d['postal_code'] ?: null]);
                $id = (int)db()->lastInsertId();
                $u = db()->query("SELECT * FROM users WHERE id = $id")->fetch();
                unset($_SESSION['pending_reg'], $_SESSION['dev_code']);
                sign_in_user($u);
                header('Location: dashboard.php'); exit;
            } catch (PDOException $e) {
                $error = 'Database error. Is MySQL running and database.sql imported?';
            }
        }
    }
    $p = $_SESSION['pending_reg'] ?? $p;
}

$secondsLeft = max(0, $p['expires'] - time());
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verify | ServiceHub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <main class="auth-wrap">
    <div class="auth-card">
      <h1>Check your email</h1>
      <p class="muted">We sent a 6-digit code to <strong><?= htmlspecialchars($p['data']['email']) ?></strong>. It expires in <span id="timer"><?= floor($secondsLeft / 60) ?>:<?= str_pad($secondsLeft % 60, 2, '0', STR_PAD_LEFT) ?></span>. Your account is created once you enter it.</p>

      <?php if (SMTP_USER === '' && !empty($_SESSION['dev_code'])): ?>
        <p class="dev-code">Test mode (email not set up yet): your code is <strong><?= htmlspecialchars($_SESSION['dev_code']) ?></strong></p>
      <?php endif; ?>
      <?php if ($error): ?><p class="form-error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <?php if ($notice): ?><p class="form-ok"><?= htmlspecialchars($notice) ?></p><?php endif; ?>

      <form method="post">
        <label for="code">Verification code</label>
        <input id="code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Verify and create account</button>
      </form>
      <form method="post">
        <input type="hidden" name="action" value="resend">
        <button class="btn btn-ghost btn-block" type="submit">Resend code</button>
      </form>
      <p class="muted hint"><a href="register.php">Wrong email? Start over</a></p>
    </div>
  </main>
  <script>
    let s = <?= (int)$secondsLeft ?>;
    const t = document.getElementById('timer');
    setInterval(() => { if (s > 0) s--; t.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); }, 1000);
  </script>
</body>
</html>
