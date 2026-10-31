<?php /* expects $p (a product row with 'store') */ ?>
<article class="product">
  <a class="product-img" href="product.php?id=<?= (int)$p['id'] ?>" aria-label="View <?= htmlspecialchars($p['name']) ?>">
    <span><?= product_icon($p['category']) ?></span>
    <?php if ($p['stock'] < 1): ?><em class="product-tag">Sold out</em><?php endif; ?>
  </a>
  <h3><a class="plain" href="product.php?id=<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></h3>
  <p class="muted">by <?= htmlspecialchars($p['store']) ?> &middot; <?= htmlspecialchars($p['category']) ?></p>
  <div class="card-foot">
    <strong class="price-lg"><?= money((float)$p['price']) ?></strong>
    <?php if ($p['stock'] > 0): ?>
      <div class="card-actions">
        <a class="btn btn-primary" href="product.php?id=<?= (int)$p['id'] ?>">Buy</a>
        <?php if (size_config($p['category'])): ?>
          <a class="btn btn-ghost" href="product.php?id=<?= (int)$p['id'] ?>#buy">Add to cart</a>
        <?php else: ?>
        <form method="post" action="cart.php">
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <input type="hidden" name="quantity" value="1">
          <button class="btn btn-ghost" type="submit">Add to cart</button>
        </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</article>
