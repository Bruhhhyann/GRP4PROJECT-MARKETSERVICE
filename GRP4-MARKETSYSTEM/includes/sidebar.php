<?php /* expects $menu = ['Label' => '#anchor'], $panelTitle */ ?>
<aside class="sidebar">
  <h2><?= htmlspecialchars($panelTitle) ?></h2>
  <nav aria-label="Dashboard">
    <?php foreach ($menu as $menuLabel => $menuHref): ?>
      <a href="<?= $menuHref ?>"><?= htmlspecialchars($menuLabel) ?></a>
    <?php endforeach; ?>
    <a href="index.php">Back to shop</a>
  </nav>
</aside>
