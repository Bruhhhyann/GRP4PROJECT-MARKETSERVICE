<header class="site-header">
  <a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true">S</span><?= SITE_NAME ?></a>
  <nav class="main-nav" aria-label="Main">
    <a href="index.php">Home</a>
    <a href="shop.php">Shop products</a>
    <a href="index.php#categories">Book services</a>
  </nav>
  <div class="header-actions">
    <?php if (is_signed_in()): ?>
      <span class="greeting">Hi, <?= htmlspecialchars($_SESSION['user']) ?></span>
      <a class="btn btn-ghost bell" href="notifications.php" aria-label="Notifications">&#128276;<?php if (($nc = notif_count()) > 0): ?><span class="bell-count"><?= $nc > 9 ? '9+' : $nc ?></span><?php endif; ?></a>
      <?php if (($_SESSION['role'] ?? '') === 'client'): ?><a class="btn btn-ghost" href="cart.php">Cart (<?= cart_count() ?>)</a><?php endif; ?>
      <a class="btn btn-primary" href="dashboard.php">Dashboard</a>
      <a class="btn btn-ghost" href="signin.php?action=signout">Sign out</a>
    <?php else: ?>
      <a class="btn btn-ghost" href="register.php">Register</a>
      <a class="btn btn-primary" href="signin.php">Sign in</a>
    <?php endif; ?>
  </div>
</header>
