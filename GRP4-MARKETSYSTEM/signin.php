
<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (($_GET['action'] ?? '') === 'signout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

$error = '';
$login = '';
$step = 'email';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');

    if ($login === '') {
        $error = 'Enter your email or phone number.';
    } elseif (!isset($_POST['password'])) {
        $step = 'password';
    } else {
        $step = 'password';
        $pass = $_POST['password'];

        if ($pass === '') {
            $error = 'Enter your password.';
        } else {
            try {
                $st = db()->prepare(
                    'SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1'
                );
                $st->execute([$login, $login]);
                $u = $st->fetch();

                if (!$u || !password_verify($pass, $u['password_hash'])) {
                    $error = 'Wrong email/phone or password.';
                } elseif (!$u['is_verified']) {
                    $error = 'This account was never verified. Please register again.';
                } else {
                    sign_in_user($u);
                    header('Location: dashboard.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Database error. Is MySQL running and database.sql imported?';
            }
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
    <?php include __DIR__ . '/includes/head-extra.php'; ?>
</head>
<body>

<?php include __DIR__ . '/includes/topbar.php'; ?>

<main class="auth-wrap">
    <form class="auth-card" method="post" novalidate>

        <h1>Sign in</h1>

        <?php if ($step === 'email'): ?>

            

            <?php if ($m = flash()): ?>
                <p class="form-ok"><?= htmlspecialchars($m) ?></p>
            <?php endif; ?>

            <?php if ($error): ?>
                <p class="form-error" role="alert">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>

            <label for="login">Email or phone</label>

            <input
                id="login"
                name="login"
                type="text"
                value="<?= htmlspecialchars($login) ?>"
                autocomplete="username"
                autofocus
                required
            >

            <button class="btn btn-primary btn-lg btn-block" type="submit">
                Next
            </button>

        <?php else: ?>

            <?php if ($error): ?>
                <p class="form-error" role="alert">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>

            <input
                type="hidden"
                name="login"
                value="<?= htmlspecialchars($login) ?>"
            >

            <div class="signin-who">
                <span><?= htmlspecialchars($login) ?></span>
                <a href="signin.php">Change</a>
            </div>

            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                autofocus
                required
            >

            <p class="forgot-link">
                <a href="forgot-password.php?login=<?= urlencode($login) ?>">
                    Forgot password?
                </a>
            </p>

            <button class="btn btn-primary btn-lg btn-block" type="submit">
                Sign in
            </button>

        <?php endif; ?>

        <p class="muted hint">
            No account? <a href="register.php">Register</a>
        </p>

    </form>
</main>

</body>
</html>
