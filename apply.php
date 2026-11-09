<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client');
ensure_provider_applications_table();

$uid = $_SESSION['user_id'];
$errors = [];
$d = ['type' => '', 'business_name' => '', 'description' => '', 'categories' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d['type']          = $_POST['type'] ?? '';
    $d['business_name'] = trim($_POST['business_name'] ?? '');
    $d['description']   = trim($_POST['description'] ?? '');
    $picked = in_array($d['type'], ['service', 'product'], true)
        ? array_values(array_intersect((array)($_POST['categories'] ?? []), array_keys(category_options($d['type'])))) : [];
    $d['categories'] = implode(',', $picked);

    if (!in_array($d['type'], ['service', 'product'], true)) $errors[] = 'Choose whether you want to offer services or sell products.';
    if (!$picked && $d['type'] !== '')                       $errors[] = 'Pick at least one category you want to ' . ($d['type'] === 'product' ? 'sell' : 'offer') . '.';
    if ($d['business_name'] === '')                          $errors[] = 'Enter your business or shop name.';
    if (strlen($d['description']) < 20)                      $errors[] = 'Tell us a bit more about what you offer (at least 20 characters).';
    if (strlen($d['description']) > 600)                     $errors[] = 'Description is too long (600 characters max).';
    if (strlen($d['business_name']) > 120)                   $errors[] = 'Business name is too long.';

    if (!$errors) {
        db()->prepare('DELETE FROM provider_applications WHERE user_id = ? AND status = "pending"')->execute([$uid]);
        db()->prepare('INSERT INTO provider_applications (user_id, type, business_name, description, categories, status, created_at) VALUES (?,?,?,?,?,"pending",?)')
            ->execute([$uid, $d['type'], $d['business_name'], $d['description'], $d['categories'], date('Y-m-d H:i:s')]);
        header('Location: apply.php?sent=1'); exit;
    }
}

$last = db()->prepare('SELECT * FROM provider_applications WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$last->execute([$uid]);
$last = $last->fetch();
$pending = $last && $last['status'] === 'pending' ? $last : null;
$form = $errors ? $d : ($pending ?: $d);
$h = fn($k) => htmlspecialchars((string)($form[$k] ?? ''));
$checked = array_filter(explode(',', (string)($form['categories'] ?? '')));
$typeName = fn($t) => $t === 'product' ? 'Product provider (sell products)' : 'Service provider (offer services)';

$panelTitle = 'My account';
$menu = client_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Become a provider | ServiceHub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1>Become a provider</h1>
      <p class="muted">Offer your services or sell your products on ServiceHub. An admin reviews every application.</p>

      <?php if (isset($_GET['sent'])): ?><p class="form-ok">Application sent. An admin will review it.</p><?php endif; ?>
      <?php if ($last && $last['status'] === 'rejected'): ?><p class="form-error">Your last application was rejected. You can update it and apply again.</p><?php endif; ?>

      <?php if ($pending): ?>
        <h2>Your application <span class="badge badge-pending">Pending approval</span></h2>
        <div class="card profile">
          <p><strong>Applying as</strong><?= htmlspecialchars($typeName($pending['type'])) ?></p>
          <p><strong>Business</strong><?= htmlspecialchars($pending['business_name']) ?></p>
          <p><strong>Categories</strong><?= htmlspecialchars(str_replace(',', ', ', $pending['categories'])) ?></p>
          <p><strong>About</strong><?= htmlspecialchars($pending['description']) ?></p>
          <p class="muted">Waiting for an admin. Once approved, click the Dashboard button.</p>
        </div>
      <?php endif; ?>

      <h2><?= $pending ? 'Change your application' : 'Apply now' ?></h2>
      <?php foreach ($errors as $e): ?><p class="form-error" role="alert"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
      <form class="card dash-form" method="post" novalidate>
        <div class="choice-row">
          <label class="choice"><input type="radio" name="type" value="service" <?= $h('type') === 'service' ? 'checked' : '' ?>>
            <span><strong>Service provider</strong><br><span class="muted">Cleaning, repairs, design, tutoring and more.</span></span></label>
          <label class="choice"><input type="radio" name="type" value="product" <?= $h('type') === 'product' ? 'checked' : '' ?>>
            <span><strong>Product provider</strong><br><span class="muted">Shoes, shirts, bags and other items.</span></span></label>
        </div>

        <div class="cat-group" id="cats-product">
          <label class="group-label">What will you sell? Pick one or more.</label>
          <?php $pickerOptions = category_options('product'); $pickerChecked = $checked; include __DIR__ . '/includes/category-picker.php'; ?>
        </div>
        <div class="cat-group" id="cats-service">
          <label class="group-label">Which services will you offer? Pick one or more.</label>
          <?php $pickerOptions = category_options('service'); $pickerChecked = $checked; include __DIR__ . '/includes/category-picker.php'; ?>
        </div>
        <p class="muted" id="cats-note">Later, if you want to sell something you did not pick now, you apply for it and the admin approves it.</p>

        <label for="business_name">Business or shop name</label>
        <input id="business_name" name="business_name" maxlength="120" value="<?= $h('business_name') ?>" required>
        <label for="description">Tell us about what you offer</label>
        <textarea id="description" name="description" rows="4" maxlength="600" required><?= $h('description') ?></textarea>
        <button class="btn btn-primary btn-lg" type="submit"><?= $pending ? 'Update application' : 'Send application' ?></button>
      </form>
    </main>
  </div>
  <script>
    // Show the category tiles that match the chosen provider type.
    const radios = document.querySelectorAll('input[name="type"]');
    function sync() {
      const t = document.querySelector('input[name="type"]:checked')?.value || '';
      for (const g of ['product', 'service']) {
        const box = document.getElementById('cats-' + g);
        box.hidden = (t !== g);
        box.querySelectorAll('input').forEach(i => i.disabled = (t !== g));
      }
      document.getElementById('cats-note').hidden = (t === '');
    }
    radios.forEach(r => r.addEventListener('change', sync));
    sync();
  </script>
</body>
</html>
