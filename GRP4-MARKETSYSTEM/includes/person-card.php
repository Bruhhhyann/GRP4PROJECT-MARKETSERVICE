<?php /* expects $pp = a user row with store, role, staff_type, first_name, last_name, photo, id_status */ ?>
<article class="person-card">
  <a class="avatar-md" href="user.php?id=<?= (int)$pp['id'] ?>"><?= avatar_art($pp['photo'] ?? '', public_name($pp)) ?></a>
  <div class="person-info">
    <a class="plain" href="user.php?id=<?= (int)$pp['id'] ?>"><strong><?= htmlspecialchars(public_name($pp)) ?></strong><?= $pp['role'] === 'client' && ($pp['id_status'] ?? '') === 'approved' ? ' <span class="verified-check" title="ID verified">&#10003;</span>' : '' ?></a>
    <span class="muted"><?= htmlspecialchars(role_label($pp)) ?></span>
  </div>
  <?php $fuId = (int)$pp['id']; include __DIR__ . '/follow-button.php'; ?>
</article>
