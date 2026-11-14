<?php
// Step 1 of resetting a password: ask for the email or phone, then email a 6-digit code.
require_once __DIR__ . '/includes/bootstrap.php';

$error = '';
$login = trim($_POST['login'] ?? $_GET['login'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $error = 'Session expired. Please try again.';
    } elseif ($login === '') {
        $error = 'Enter your email or phone number.';
    } else {
        $reuse = !empty($_SESSION['pw_reset']) && ($_SESSION['pw_reset']['login'] ?? '') === $login
              && (time() - ($_SESSION['pw_reset']['sent_at'] ?? 0)) < RESEND_SECONDS;
        if ($reuse) { header('Location: reset-password.php'); exit; }          // a code was just sent: do not spam
        try {
            $st = db()->prepare('SELECT id, first_name, email FROM users WHERE (email = ? OR phone = ?) AND is_verified = 1 AND email IS NOT NULL LIMIT 1');
            $st->execute([$login, $login]);
            $u = $st->fetch();
            $_SESSION['pw_reset'] = ['login' => $login, 'user_id' => $u ? (int)$u['id'] : 0, 'email' => $u['email'] ?? '', 'first_name' => $u['first_name'] ?? ''];
            if (send_password_reset_code()) { header('Location: reset-password.php'); exit; }
            unset($_SESSION['pw_reset']);
            $error = 'We could not send the email. Check includes/mail-config.php and try again.';
        } catch (PDOException $e) {
            $error = 'Database error. Is MySQL running and database.sql imported?';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot password | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <main class="auth-wrap">
    <form class="auth-card" method="post" novalidate>
      <h1>Forgot password?</h1>
      <p class="muted">Enter the email or phone number of your account. We will email a 6-digit code that expires in <?= CODE_MINUTES ?> minutes.</p>
      <?php if ($error): ?><p class="form-error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <label for="login">Email or phone</label>
      <input id="login" name="login" value="<?= htmlspecialchars($login) ?>" autocomplete="username" autofocus required>
      <button class="btn btn-primary btn-lg btn-block" type="submit">Send reset code</button>
      <p class="muted hint"><a href="signin.php">Back to sign in</a></p>
    </form>
  </main>
</body>
</html>
