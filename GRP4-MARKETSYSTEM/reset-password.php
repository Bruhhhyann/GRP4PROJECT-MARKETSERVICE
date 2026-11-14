<?php
// Step 2: type the emailed code and choose a new password.
require_once __DIR__ . '/includes/bootstrap.php';

$r = $_SESSION['pw_reset'] ?? null;
if (!$r) { header('Location: forgot-password.php'); exit; }

$error = ''; $notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $error = 'Session expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'resend') {
        $wait = RESEND_SECONDS - (time() - $r['sent_at']);
        if ($wait > 0)                       { $error = "Please wait $wait seconds before asking for a new code."; }
        elseif (send_password_reset_code())  { $notice = 'A new code was sent. The old one no longer works.'; }
        else                                 { $error = 'We could not send the email. Check includes/mail-config.php and try again.'; }
    } else {
        $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
        $pass = $_POST['password'] ?? ''; $pass2 = $_POST['password2'] ?? '';
        if (time() > $r['expires'])                                   { $error = 'That code has expired. Tap "Resend code" for a new one.'; }
        elseif ($r['tries'] >= 5)                                     { $error = 'Too many wrong tries. Tap "Resend code" for a new one.'; }
        elseif (empty($r['user_id']) || !hash_equals($r['code_hash'], hash('sha256', $code))) {
            $_SESSION['pw_reset']['tries']++;
            $error = 'Wrong code. Check it and try again.';
        }
        elseif (strlen($pass) < 8)                                    { $error = 'Your new password must be at least 8 characters.'; }
        elseif ($pass !== $pass2)                                     { $error = 'The two passwords do not match.'; }
        else {
            try {
                ensure_shop_tables();
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $r['user_id']]);
                notify((int)$r['user_id'], 'Your password was changed. If this was not you, please contact an admin.', 'signin.php');
                unset($_SESSION['pw_reset'], $_SESSION['dev_reset_code']);
                flash('Your password was changed. Sign in with your new password.');
                header('Location: signin.php'); exit;
            } catch (PDOException $e) {
                $error = 'Database error. Is MySQL running?';
            }
        }
    }
    $r = $_SESSION['pw_reset'] ?? $r;
}
$secondsLeft = max(0, $r['expires'] - time());
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset password | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <main class="auth-wrap">
    <div class="auth-card">
      <h1>Reset your password</h1>
      <p class="muted">If an account exists for <strong><?= htmlspecialchars($r['login']) ?></strong>, we sent a 6-digit code to its email. It expires in <span id="timer"><?= floor($secondsLeft / 60) ?>:<?= str_pad($secondsLeft % 60, 2, '0', STR_PAD_LEFT) ?></span>.</p>

      <?php if (SMTP_USER === '' && !empty($_SESSION['dev_reset_code'])): ?>
        <p class="dev-code">Test mode (email not set up yet): your code is <strong><?= htmlspecialchars($_SESSION['dev_reset_code']) ?></strong></p>
      <?php endif; ?>
      <?php if ($error): ?><p class="form-error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <?php if ($notice): ?><p class="form-ok"><?= htmlspecialchars($notice) ?></p><?php endif; ?>

      <form method="post" novalidate>
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <label for="code">Verification code</label>
        <input id="code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus required>
        <label for="password">New password</label>
        <input type="password" id="password" name="password" autocomplete="new-password" required>
        <label for="password2">Confirm new password</label>
        <input type="password" id="password2" name="password2" autocomplete="new-password" required>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Change password</button>
      </form>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="resend">
        <button class="btn btn-ghost btn-block" type="submit">Resend code</button>
      </form>
      <p class="muted hint"><a href="forgot-password.php">Wrong email? Start over</a> &middot; <a href="signin.php">Back to sign in</a></p>
    </div>
  </main>
  <script>
    let s = <?= (int)$secondsLeft ?>;
    const t = document.getElementById('timer');
    setInterval(() => { if (s > 0) s--; t.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); }, 1000);
  </script>
</body>
</html>
