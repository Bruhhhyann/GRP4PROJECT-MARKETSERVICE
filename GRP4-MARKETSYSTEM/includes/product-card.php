<?php /* expects $p (a product row with 'store') */ ?>
<article class="product">
  <a class="product-img" href="product.php?id=<?= (int)$p['id'] ?>" aria-label="View <?= htmlspecialchars($p['name']) ?>">
    <?= product_art($p) ?>
    <?php if ($p['stock'] < 1): ?><em class="product-tag">Sold out</em><?php endif; ?>
  </a>
  <h3><a class="plain" href="product.php?id=<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></h3>
  <p class="muted">by <?= htmlspecialchars($p['store']) ?> &middot; <?= htmlspecialchars($p['category']) ?></p>
  <?php if ((int)($p['review_count'] ?? 0) > 0): ?><p class="rating-line"><?= stars_html((float)$p['avg_rating']) ?> <span class="muted"><?= number_format((float)$p['avg_rating'], 1) ?> (<?= (int)$p['review_count'] ?>)</span></p><?php endif; ?>
  <div class="card-foot">
    <strong class="price-lg"><?= money((float)$p['price']) ?></strong>
    <?php if ($p['stock'] > 0): ?>
      <div class="card-actions">
        <a class="btn btn-primary" href="product.php?id=<?= (int)$p['id'] ?>">Buy</a>
        <a class="btn btn-ghost" href="product.php?id=<?= (int)$p['id'] ?>#buy">Add to cart</a>
      </div>
    <?php endif; ?>
  </div>
</article>
