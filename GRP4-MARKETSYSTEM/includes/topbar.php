<header class="site-header">
  <button class="menu-toggle" type="button"
          aria-label="Open menu" aria-expanded="false"
          aria-controls="siteNav">
    <span></span><span></span><span></span>
  </button>

  <a class="brand" href="index.php">
    <span class="brand-mark" aria-hidden="true">A</span><?= SITE_NAME ?>
  </a>

  <nav class="main-nav" id="siteNav" aria-label="Main menu" hidden>
    <a href="index.php">Home</a>
    <a href="shop.php">Shop products</a>
    <a href="providers.php">Book services</a>

    <?php if (is_signed_in()): ?>
      <a href="dashboard.php">Dashboard</a>
      <a href="notifications.php">
        Notifications
        <?php if (($nc = notif_count()) > 0): ?>
          <span class="menu-count"><?= $nc > 9 ? '9+' : $nc ?></span>
        <?php endif; ?>
      </a>
      <a href="cart.php">Cart (<?= cart_count() ?>)</a>
      <a href="signin.php?action=signout">Sign out</a>
    <?php else: ?>
      <a href="register.php">Register</a>
      <a href="signin.php">Sign in</a>
    <?php endif; ?>
  </nav>
</header>

<?= id_banner() ?>

<script>
(() => {
  const header = document.querySelector('.site-header');
  if (!header) return;

  const toggle = header.querySelector('.menu-toggle');
  const menu = header.querySelector('#siteNav');
  if (!toggle || !menu) return;

  const closeMenu = () => {
    menu.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open menu');
  };

  toggle.addEventListener('click', () => {
    const isOpen = toggle.getAttribute('aria-expanded') === 'true';
    menu.hidden = isOpen;
    toggle.setAttribute('aria-expanded', String(!isOpen));
    toggle.setAttribute('aria-label', isOpen ? 'Open menu' : 'Close menu');
  });

  menu.addEventListener('click', event => {
    if (event.target.closest('a')) closeMenu();
  });

  document.addEventListener('click', event => {
    if (!header.contains(event.target)) closeMenu();
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      closeMenu();
      toggle.focus();
    }
  });
})();
</script>