<?php /* expects $sp (a row from service_providers_sql) */ $spCats = provider_cats($sp); $spIcons = category_options('service'); ?>
<article class="provider">
  <div class="avatar"><?= avatar_art($sp['photo'] ?? '', $sp['store']) ?></div>
  <h3><?= htmlspecialchars($sp['store']) ?></h3>
  <p class="chips-mini">
    <?php foreach (array_slice($spCats, 0, 3) as $pc): ?><span class="chip-mini"><?= $spIcons[$pc] ?? '' ?> <?= htmlspecialchars($pc) ?></span><?php endforeach; ?>
    <?php if (count($spCats) > 3): ?><span class="muted">+<?= count($spCats) - 3 ?> more</span><?php endif; ?>
  </p>
  <?php if (!empty($sp['about'])): ?><p class="muted about"><?= htmlspecialchars(mb_strimwidth($sp['about'], 0, 110, '...')) ?></p><?php endif; ?>
  <p class="provider-meta"><span><?= (int)$sp['done_jobs'] ?> job<?= (int)$sp['done_jobs'] === 1 ? '' : 's' ?> done</span></p>
  <div class="provider-foot">
    <span><?= $sp['service_rate'] !== null ? 'From <strong>' . money((float)$sp['service_rate']) . '</strong>' : '<span class="muted">Price on request</span>' ?></span>
    <a class="btn btn-small" href="book.php?provider=<?= (int)$sp['id'] ?>">Book now</a>
  </div>
</article>
