<?php
// A client uploads a (new) ID after it was rejected, or while it is still waiting.
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client');
ensure_shop_tables();
$uid = (int)$_SESSION['user_id'];
$me = db()->prepare('SELECT * FROM users WHERE id = ?'); $me->execute([$uid]); $me = $me->fetch();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $me['id_status'] !== 'approved') {
    $type = $_POST['id_type'] ?? '';
    if (!csrf_ok()) $errors[] = 'Session expired. Please try again.';
    if (!in_array($type, ['national_id', 'drivers_license'], true)) $errors[] = "Choose National ID or Driver's License.";
    [$ext, $err] = check_id_photo($_FILES['id_photo'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
    if ($err) { $errors[] = $err; }
    else {
        $ocrErr = check_id_text($_FILES['id_photo']['tmp_name'], $me['first_name'], $me['last_name']);
        if ($ocrErr) { $errors[] = $ocrErr; }
    }
    if (!$errors) {
        $file = save_id_photo($_FILES['id_photo'], $ext);
        if (!$file) { $errors[] = 'We could not save your ID photo. Please try again.'; }
        else {
            delete_id_photo((string)$me['id_doc_file']);
            db()->prepare('UPDATE users SET id_doc_type = ?, id_doc_file = ?, id_status = "pending", id_note = "", id_reviewed_at = NULL WHERE id = ?')->execute([$type, $file, $uid]);
            notify_admins("{$me['first_name']} {$me['last_name']} uploaded a new ID for review.", 'admin.php#id-review');
            flash('Your ID was sent. An admin will review it.');
            header('Location: id-upload.php'); exit;
        }
    }
}
$status = $me['id_status'];
$panelTitle = 'My account';
$menu = client_menu();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My ID | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
  <?php include __DIR__ . '/includes/head-extra.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1>My ID</h1>
      <?php if ($m = flash()): ?><p class="form-ok"><?= htmlspecialchars($m) ?></p><?php endif; ?>
      <div class="card"><?= id_progress_html($status) ?></div>
      <?php if ($me['id_doc_file']): ?>
        <div class="card"><p><strong>Your submitted document:</strong> <?= $me['id_doc_type'] === 'drivers_license' ? "Driver's License" : 'National ID' ?> <span class="muted">(only you and the admins can see it)</span></p>
          <img class="id-thumb big" src="my-id.php?v=<?= time() ?>" alt="Your submitted ID"></div>
      <?php endif; ?>
      <?php if ($status === 'approved'): ?>
        <div class="card"><p>&#9989; Your ID is approved. You can order, book services and apply as a provider.</p></div>
      <?php else: ?>
        <div class="card">
          <p><strong>Status:</strong> <?= $status === 'pending' ? '<span class="badge badge-pending">Being verified by an admin</span>' : '<span class="badge badge-rejected">Rejected</span>' ?></p>
          <?php if ($status === 'rejected' && $me['id_note']): ?><p>Reason: <?= htmlspecialchars($me['id_note']) ?></p><?php endif; ?>
          <p class="muted">Upload a clear photo of your own National ID or Driver's License. You can replace the photo while it is waiting.</p>
        </div>
        <?php foreach ($errors as $e): ?><p class="form-error" role="alert"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        <form class="card dash-form" method="post" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <div class="choice-row">
            <label class="choice"><input type="radio" name="id_type" value="national_id" <?= $me['id_doc_type'] !== 'drivers_license' ? 'checked' : '' ?>><span><strong>National ID</strong></span></label>
            <label class="choice"><input type="radio" name="id_type" value="drivers_license" <?= $me['id_doc_type'] === 'drivers_license' ? 'checked' : '' ?>><span><strong>Driver's License</strong></span></label>
          </div>
          <label for="id_photo">Photo of the document (JPG, PNG or WEBP, max 5 MB)</label>
          <input type="file" id="id_photo" name="id_photo" accept="image/jpeg,image/png,image/webp" required>
          <p class="muted">Good light, the whole card in view, every word readable.</p>
          <button class="btn btn-primary btn-lg" type="submit">Send ID for review</button>
        </form>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
