<?php /* expects $fuId = id of the shop, provider or client to follow */
if (!is_signed_in()): ?>
  <a class="btn btn-small btn-primary" href="signin.php">+ Follow</a>
<?php elseif ((int)$_SESSION['user_id'] !== (int)$fuId): $fol = is_following((int)$fuId); ?>
  <form method="post" action="follow.php" class="follow-form">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <input type="hidden" name="user_id" value="<?= (int)$fuId ?>">
    <input type="hidden" name="do" value="<?= $fol ? 'unfollow' : 'follow' ?>">
    <input type="hidden" name="return" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'people.php') ?>">
    <button class="btn btn-small <?= $fol ? 'btn-light' : 'btn-primary' ?>" type="submit"><?= $fol ? 'Following &#10003;' : '+ Follow' ?></button>
  </form>
<?php endif; ?>
