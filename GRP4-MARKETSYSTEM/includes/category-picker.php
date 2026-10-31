<?php /* expects $pickerOptions (name => icon) and $pickerChecked (array of names) */ ?>
<div class="cat-picker">
  <?php foreach ($pickerOptions as $name => $icon): ?>
    <label class="cat-tile">
      <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($name) ?>" <?= in_array($name, $pickerChecked, true) ? 'checked' : '' ?>>
      <span class="cat-icon" aria-hidden="true"><?= $icon ?></span>
      <span class="cat-name"><?= htmlspecialchars($name) ?></span>
    </label>
  <?php endforeach; ?>
</div>
