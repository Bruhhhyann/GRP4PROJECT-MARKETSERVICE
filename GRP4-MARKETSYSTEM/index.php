<?php
require_once __DIR__ . '/includes/bootstrap.php';
$featured = [];
try {   // the home page still loads even if the database is off
    ensure_shop_tables();
    $featured = db()->query(product_sql() . ' WHERE p.is_active = 1 AND p.stock > 0 ORDER BY p.id DESC LIMIT 4')->fetchAll();
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ServiceHub | Shop with us. Grow with us.</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <!-- HEADER with Sign in button -->
  <header class="site-header">
    <a class="brand" href="index.php">
      <span class="brand-mark" aria-hidden="true">S</span>
      <?= SITE_NAME ?>
    </a>

    <nav class="main-nav" aria-label="Main">
      <a href="index.php">Dashboard</a>
      <a href="shop.php">Shop products</a>
      <a href="#categories">Book services</a>
      <a href="#providers">Providers</a>
    </nav>

    <div class="header-actions">
      <?php if (is_signed_in()): ?>
        <span class="greeting">Hi, <?= htmlspecialchars($_SESSION['user']) ?></span>
        <?php if (($_SESSION['role'] ?? '') === 'client'): ?><a class="btn btn-ghost" href="cart.php">Cart (<?= cart_count() ?>)</a><?php endif; ?>
        <a class="btn btn-primary" href="dashboard.php">Dashboard</a>
        <a class="btn btn-ghost" href="signin.php?action=signout">Sign out</a>
      <?php else: ?>
        <a class="btn btn-ghost" href="register.php">Register</a>
        <a class="btn btn-primary" href="signin.php">Sign in</a>
      <?php endif; ?>
    </div>
  </header>

  <main>
    <!-- HERO: tagline on the left, vertically centered. Dashboard preview on the right. -->
    <section class="hero">
      <div class="hero-copy">
        <h1>Shop with us.<br><span>Grow with us.</span></h1>
        <p>Buy shoes, shirts and more, or book trusted people for any job. Sell your products or offer your services and start earning.</p>
        <div class="hero-cta">
          <a class="btn btn-primary btn-lg" href="#products">Shop products</a>
          <a class="btn btn-outline btn-lg" href="#categories">Book a service</a>
        </div>
      </div>

      <figure class="hero-visual">
        <?php
        // Shows your own photo if you put one in the "images" folder
        // named worker.jpg, worker.jpeg, worker.png or worker.webp.
        // If there is no photo, the drawn worker is shown instead.
        $photo = null;
        foreach (['jpg', 'jpeg', 'png', 'webp', 'JPG', 'JPEG', 'PNG'] as $ext) {
            if (file_exists(__DIR__ . "/images/worker.$ext")) { $photo = "images/worker.$ext"; break; }
        }
        ?>
        <?php if ($photo): ?>
          <img src="<?= $photo ?>?v=<?= filemtime(__DIR__ . '/' . $photo) ?>" alt="Service worker">
        <?php else: ?>
          <?php include __DIR__ . '/includes/worker-art.php'; ?>
        <?php endif; ?>
      </figure>
    </section>

    <!-- PRODUCTS -->
    <section class="section" id="products">
      <h2>Shop products</h2>
      <form class="search-bar" method="get" action="shop.php" role="search">
        <input type="search" name="q" placeholder="Search shoes, shirts, bags..." aria-label="Search products">
        <button class="btn btn-primary" type="submit">Search</button>
      </form>
      <div class="category-grid">
        <?php foreach ($productCategories as $c): ?>
          <a class="category" href="shop.php?cat=<?= urlencode($c['name']) ?>">
            <span class="category-icon" aria-hidden="true"><?= $c['icon'] ?></span>
            <strong><?= htmlspecialchars($c['name']) ?></strong>
          </a>
        <?php endforeach; ?>
      </div>

      <h3 class="subhead">Available now</h3>
      <?php if (!$featured): ?>
        <p class="muted">No products yet. Product providers can add items from their dashboard.</p>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($featured as $p) { include __DIR__ . '/includes/product-card.php'; } ?>
        </div>
        <p><a class="btn btn-ghost" href="shop.php">See all products</a></p>
      <?php endif; ?>
    </section>

    <!-- SERVICE CATEGORIES -->
    <section class="section section-tint" id="categories">
      <h2>What service do you need?</h2>
      <div class="category-grid">
        <?php foreach ($categories as $c): ?>
          <a class="category" href="#">
            <span class="category-icon" aria-hidden="true"><?= $c['icon'] ?></span>
            <strong><?= htmlspecialchars($c['name']) ?></strong>
            <span class="muted"><?= $c['count'] ?> providers</span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- PROVIDERS -->
    <section class="section" id="providers">
      <h2>Top-rated providers</h2>
      <div class="provider-grid">
        <?php foreach ($providers as $p): ?>
          <article class="provider">
            <div class="avatar" aria-hidden="true"><?= strtoupper(substr($p['name'], 0, 1)) ?></div>
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <p class="muted"><?= htmlspecialchars($p['role']) ?></p>
            <p class="provider-meta">
              <span>★ <?= number_format($p['rating'], 1) ?></span>
              <span><?= $p['jobs'] ?> jobs done</span>
            </p>
            <div class="provider-foot">
              <span>From <strong><?= money($p['from']) ?></strong></span>
              <a class="btn btn-small" href="#">Book now</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <span>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Shop with us. Grow with us.</span>
  </footer>

</body>
</html>
