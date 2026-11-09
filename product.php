<?php
require_once __DIR__ . '/includes/bootstrap.php';
ensure_shop_tables();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare(product_sql() . ' WHERE p.id = ? AND p.is_active = 1');
$st->execute([$id]);
$p = $st->fetch();

$others = [];
if ($p) {
    $o = db()->prepare(product_sql() . ' WHERE p.is_active = 1 AND p.stock > 0 AND p.id <> ? ORDER BY (p.category = ?) DESC, p.id DESC LIMIT 8');
    $o->execute([$id, $p['category']]);
    $others = $o->fetchAll();
}
$isClient = ($_SESSION['role'] ?? '') === 'client';
$mine = $p; // keep the main product while the card include reuses $p
$reviews = []; $eligibleOrder = 0; $myReview = null;
if ($p) {
    $rv = db()->prepare('SELECT r.*, u.first_name, u.last_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC LIMIT 50');
    $rv->execute([$id]);
    $reviews = $rv->fetchAll();
    if ($isClient) {
        $eligibleOrder = delivered_order_for((int)$_SESSION['user_id'], $id);   // 0 = never received it
        foreach ($reviews as $rev) { if ((int)$rev['user_id'] === (int)$_SESSION['user_id']) { $myReview = $rev; } }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $p ? htmlspecialchars($p['name']) : 'Product' ?> | ServiceHub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <main class="section shop-page">
    <p><a href="shop.php">&larr; Back to shop</a></p>
    <?php if (!$p): ?>
      <h1>Product not found</h1>
      <p class="muted">It may have been removed. <a href="shop.php">See all products</a>.</p>
    <?php else: ?>
      <?php if ($m = flash()): ?><p class="form-error"><?= htmlspecialchars($m) ?></p><?php endif; ?>
      <div class="product-page">
        <div class="detail-art"><?= product_art($p) ?></div>
        <div>
          <h1><?= htmlspecialchars($p['name']) ?></h1>
          <p class="muted seller-line"><span class="avatar-sm"><?= avatar_art($p['seller_photo'] ?? '', $p['store']) ?></span>
            Sold by <strong><?= htmlspecialchars($p['store']) ?></strong> &middot; <?= htmlspecialchars($p['category']) ?></p>
          <?php if ((int)$p['review_count'] > 0): ?>
            <p class="rating-line"><a class="plain" href="#reviews"><?= stars_html((float)$p['avg_rating']) ?> <strong><?= number_format((float)$p['avg_rating'], 1) ?></strong>
              <span class="muted">(<?= (int)$p['review_count'] ?> review<?= (int)$p['review_count'] === 1 ? '' : 's' ?>)</span></a></p>
          <?php endif; ?>
          <p class="price-xl"><?= money((float)$p['price']) ?></p>
          <p><?= $p['stock'] > 0 ? '<span class="badge badge-confirmed">' . (int)$p['stock'] . ' in stock</span>' : '<span class="badge badge-rejected">Sold out</span>' ?></p>
          <h2 class="small-h">About this product</h2>
          <p><?= nl2br(htmlspecialchars($p['description'] ?: 'No description yet.')) ?></p>

          <?php if ($p['stock'] > 0 && !is_signed_in()): ?>
            <p><a class="btn btn-primary btn-lg" href="signin.php">Sign in to buy</a></p>
          <?php elseif ($p['stock'] > 0 && !$isClient): ?>
            <p class="muted">Only client accounts can buy products.</p>
          <?php elseif ($p['stock'] > 0): ?>
            <form class="buy-box" id="buy" method="post" action="cart.php">
              <input type="hidden" name="csrf" value="<?= csrf() ?>">
              <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
              <?php $cfg = size_config($p['category']); ?>
              <?php if ($cfg): ?>
                <div class="opt-group">
                  <span class="opt-label"><?= htmlspecialchars($cfg['label']) ?></span>
                  <div class="opt-chips" role="radiogroup" aria-label="<?= htmlspecialchars($cfg['label']) ?>">
                    <?php foreach ($cfg['sizes'] as $sz => $info): ?>
                      <label class="opt-chip"><input type="radio" name="size" value="<?= htmlspecialchars($sz) ?>" data-info="<?= htmlspecialchars($info) ?>" required><span><?= htmlspecialchars($sz) ?></span></label>
                    <?php endforeach; ?>
                  </div>
                </div>
                <?php if (!empty($cfg['width'])): ?>
                  <div class="opt-group">
                    <span class="opt-label">Width</span>
                    <div class="opt-chips" role="radiogroup" aria-label="Width">
                      <?php foreach ($cfg['width'] as $w => $info): ?>
                        <label class="opt-chip"><input type="radio" name="width" value="<?= htmlspecialchars($w) ?>" data-info="<?= htmlspecialchars($info) ?>" required><span><?= htmlspecialchars($w) ?></span></label>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endif; ?>
                <p class="size-info" id="size-info">Choose your size to see its measurements.</p>
                <details class="size-guide">
                  <summary>Size guide</summary>
                  <table>
                    <thead><tr><th><?= htmlspecialchars($cfg['label']) ?></th><th>Measurements</th></tr></thead>
                    <tbody><?php foreach ($cfg['sizes'] as $sz => $info): ?><tr><td><?= htmlspecialchars($sz) ?></td><td><?= htmlspecialchars(ucfirst($info)) ?></td></tr><?php endforeach; ?></tbody>
                    <?php if (!empty($cfg['width'])): ?>
                      <thead><tr><th>Width</th><th>Fit</th></tr></thead>
                      <tbody><?php foreach ($cfg['width'] as $w => $info): ?><tr><td><?= htmlspecialchars($w) ?></td><td><?= htmlspecialchars(ucfirst($info)) ?></td></tr><?php endforeach; ?></tbody>
                    <?php endif; ?>
                  </table>
                  <p class="muted">Measurements are approximate and may differ by 1 to 2 cm.</p>
                </details>
              <?php endif; ?>

              <?php $colors = product_colors($p); ?>
              <div class="opt-group">
                <span class="opt-label">Color</span>
                <div class="opt-chips" role="radiogroup" aria-label="Color">
                  <?php foreach ($colors as $col): ?>
                    <label class="opt-chip"><input type="radio" name="color" value="<?= htmlspecialchars($col) ?>" required><span><i class="dot" style="background:<?= color_hex($col) ?>"></i><?= htmlspecialchars($col) ?></span></label>
                  <?php endforeach; ?>
                </div>
              </div>

              <span class="opt-label">Quantity</span>
              <div class="qty-row">
                <div class="stepper">
                  <button type="button" class="step-btn" data-step="-1" aria-label="Decrease quantity">&minus;</button>
                  <input id="quantity" name="quantity" type="number" min="1" max="<?= min(20, (int)$p['stock']) ?>" value="1" inputmode="numeric" aria-label="Quantity">
                  <button type="button" class="step-btn" data-step="1" aria-label="Increase quantity">+</button>
                </div>
                <span class="muted"><?= (int)$p['stock'] ?> available</span>
              </div>
              <label for="comment">Comment for the seller (optional)</label>
              <textarea id="comment" name="comment" rows="3" maxlength="300" placeholder="Size, color, delivery notes..."></textarea>
              <div class="row-actions">
                <button class="btn btn-primary btn-lg" name="action" value="buy" type="submit">Buy now</button>
                <button class="btn btn-ghost btn-lg" name="action" value="add" type="submit">Add to cart</button>
              </div>
              <p class="pay-note"><span class="badge badge-confirmed">Cash on Delivery (COD)</span> Pay when your order arrives. COD is the only payment method.</p>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <h2 class="more-h" id="reviews">Ratings and reviews</h2>
      <?php if (isset($_GET['reviewed'])): ?><p class="form-ok">Thanks! Your review is now live for other buyers to see.</p><?php endif; ?>
      <?php if ((int)$p['review_count'] > 0): ?>
        <p class="review-summary"><span class="big-rating"><?= number_format((float)$p['avg_rating'], 1) ?></span> <?= stars_html((float)$p['avg_rating']) ?>
          <span class="muted"><?= (int)$p['review_count'] ?> review<?= (int)$p['review_count'] === 1 ? '' : 's' ?></span></p>
      <?php else: ?>
        <p class="muted">No reviews yet.</p>
      <?php endif; ?>

      <?php if ($isClient && $eligibleOrder): ?>
        <form class="card dash-form review-form" method="post" action="review.php">
          <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <h3><?= $myReview ? 'Update your review' : 'Rate this product' ?></h3>
          <div class="star-input" role="radiogroup" aria-label="Your rating">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $myReview && (int)$myReview['rating'] === $i ? 'checked' : '' ?> required>
              <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i === 1 ? '' : 's' ?>">&#9733;</label>
            <?php endfor; ?>
          </div>
          <label for="rcomment">Your review (optional)</label>
          <textarea id="rcomment" name="comment" rows="3" maxlength="600" placeholder="How was the quality, fit and delivery?"><?= htmlspecialchars((string)($myReview['comment'] ?? '')) ?></textarea>
          <button class="btn btn-primary" type="submit"><?= $myReview ? 'Update review' : 'Post review' ?></button>
          <p class="muted">Your review is shown to other buyers right away.</p>
        </form>
      <?php elseif ($isClient): ?>
        <p class="review-note">&#128274; Only buyers who received this product can rate it. Buy it, then tap <strong>Order received</strong> when it arrives.</p>
      <?php endif; ?>

      <?php foreach ($reviews as $rev): ?>
        <div class="card review">
          <div class="review-head">
            <?= stars_html((float)$rev['rating']) ?>
            <strong><?= htmlspecialchars($rev['first_name'] . ' ' . mb_substr($rev['last_name'], 0, 1) . '.') ?></strong>
            <span class="badge badge-confirmed">Verified buyer</span>
            <span class="muted"><?= htmlspecialchars(date('M j, Y', strtotime($rev['created_at']))) ?></span>
          </div>
          <?php if ($rev['comment']): ?><p><?= nl2br(htmlspecialchars($rev['comment'])) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>

      <h2 class="more-h">Other available products</h2>
      <?php if (!$others): ?>
        <p class="muted">No other products right now.</p>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($others as $p) { include __DIR__ . '/includes/product-card.php'; } ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </main>
  <script>
    const form = document.getElementById('buy');
    if (form) {
      const q = form.querySelector('#quantity');
      const clamp = v => Math.min(parseInt(q.max), Math.max(1, v || 1));
      form.querySelectorAll('.step-btn').forEach(b => b.addEventListener('click', () => { q.value = clamp((parseInt(q.value) || 1) + parseInt(b.dataset.step)); }));
      q.addEventListener('change', () => { q.value = clamp(parseInt(q.value)); });
      const info = document.getElementById('size-info');
      const show = () => {
        const parts = [...form.querySelectorAll('input[type=radio]:checked')].filter(r => r.dataset.info).map(r => r.value + ': ' + r.dataset.info);
        if (info && parts.length) info.textContent = parts.join('  |  ');
      };
      form.querySelectorAll('input[type=radio]').forEach(r => r.addEventListener('change', show));
    }
  </script>
</body>
</html>
