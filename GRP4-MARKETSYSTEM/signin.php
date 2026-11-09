<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (($_GET['action'] ?? '') === 'signout') { session_destroy(); header('Location: index.php'); exit; }

$error = ''; $login = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($login === '' || $pass === '') {
        $error = 'Enter your email or phone and your password.';
    } else {
        try {
            $st = db()->prepare('SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1');
            $st->execute([$login, $login]);
            $u = $st->fetch();
            if (!$u || !password_verify($pass, $u['password_hash'])) {
                $error = 'Wrong email/phone or password.';
            } elseif (!$u['is_verified']) {
                $error = 'This account was never verified. Please register again.';
            } else {
                sign_in_user($u);
                header('Location: dashboard.php'); exit;
            }
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
  <title>Sign in | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <main class="auth-wrap">
    <form class="auth-card" method="post" novalidate>
      <h1>Sign in</h1>
      <p class="muted">Use the email or phone number you registered with.</p>
      <?php if ($error): ?><p class="form-error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <label for="login">Email or phone</label>
      <input id="login" name="login" value="<?= htmlspecialchars($login) ?>" autocomplete="username" required>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="current-password" required>
      <button class="btn btn-primary btn-lg btn-block" type="submit">Sign in</button>
      <p class="muted hint">No account? <a href="register.php">Register</a></p>
    </form>
  </main>
</body>
</html>
