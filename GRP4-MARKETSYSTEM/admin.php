<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));
$msg = '';
ensure_profile_changes_table();
ensure_provider_applications_table();
ensure_shop_tables();   // adds the ID columns if missing

// Approve or reject a profile change request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review') {
    $rid = (int)($_POST['request_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $req = db()->prepare('SELECT * FROM profile_changes WHERE id = ? AND status = "pending"');
    $req->execute([$rid]);
    $req = $req->fetch();
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $msg = 'Session expired. Refresh the page and try again.';
    } elseif (!$req) {
        $msg = 'That request was not found or was already reviewed.';
    } elseif ($decision === 'approve') {
        try {
            db()->beginTransaction();
            db()->prepare('UPDATE users SET first_name=?, middle_name=?, last_name=?, phone=?, address=?, city=?, postal_code=? WHERE id=?')
                ->execute([$req['first_name'], $req['middle_name'], $req['last_name'], $req['phone'], $req['address'], $req['city'], $req['postal_code'], $req['user_id']]);
            db()->prepare('UPDATE profile_changes SET status="approved", reviewed_at=?, reviewed_by=? WHERE id=?')
                ->execute([date('Y-m-d H:i:s'), $_SESSION['user_id'], $rid]);
            db()->commit();
            $msg = 'Approved. The changes are now saved to the client\'s profile.';
        } catch (PDOException $e) {
            db()->rollBack();
            $msg = 'Could not save. The phone number may already be used by another account.';
        }
    } elseif ($decision === 'reject') {
        db()->prepare('UPDATE profile_changes SET status="rejected", reviewed_at=?, reviewed_by=? WHERE id=?')
            ->execute([date('Y-m-d H:i:s'), $_SESSION['user_id'], $rid]);
        $msg = 'Request rejected. The client\'s profile was not changed.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'app_review') {
    // Approve or reject a service/product provider application
    $aid = (int)($_POST['app_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $app = db()->prepare('SELECT * FROM provider_applications WHERE id = ? AND status = "pending"');
    $app->execute([$aid]);
    $app = $app->fetch();
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $msg = 'Session expired. Refresh the page and try again.';
    } elseif (!$app) {
        $msg = 'That application was not found or was already reviewed.';
    } elseif ($decision === 'approve') {
        db()->beginTransaction();
        // Only a client can be promoted; the user becomes staff of the type they applied for.
        db()->prepare('UPDATE users SET role = "staff", staff_type = ? WHERE id = ? AND role = "client"')->execute([$app['type'], $app['user_id']]);
        foreach (array_filter(explode(',', (string)$app['categories'])) as $cat) {
            db()->prepare('INSERT INTO provider_categories (user_id, category, status, created_at, reviewed_at) VALUES (?,?,"approved",?,?)
                           ON DUPLICATE KEY UPDATE status = "approved", reviewed_at = VALUES(reviewed_at)')
                ->execute([$app['user_id'], $cat, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
        }
        db()->prepare('UPDATE provider_applications SET status="approved", reviewed_at=?, reviewed_by=? WHERE id=?')
            ->execute([date('Y-m-d H:i:s'), $_SESSION['user_id'], $aid]);
        db()->commit();
        $msg = 'Approved. The user is now a ' . $app['type'] . ' provider (staff).';
    } elseif ($decision === 'reject') {
        db()->prepare('UPDATE provider_applications SET status="rejected", reviewed_at=?, reviewed_by=? WHERE id=?')
            ->execute([date('Y-m-d H:i:s'), $_SESSION['user_id'], $aid]);
        $msg = 'Application rejected.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cat_review') {
    // Approve or reject a request to sell/offer another category
    $cid = (int)($_POST['cat_id'] ?? 0);
    $c = db()->prepare('SELECT * FROM provider_categories WHERE id = ? AND status = "pending"');
    $c->execute([$cid]);
    $c = $c->fetch();
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $msg = 'Session expired. Refresh the page and try again.';
    } elseif (!$c) {
        $msg = 'That request was not found or was already reviewed.';
    } else {
        $ok = ($_POST['decision'] ?? '') === 'approve';
        db()->prepare('UPDATE provider_categories SET status = ?, reviewed_at = ? WHERE id = ?')
            ->execute([$ok ? 'approved' : 'rejected', date('Y-m-d H:i:s'), $cid]);
        $msg = $ok ? 'Approved. The provider can now use "' . $c['category'] . '".' : 'Request rejected.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'id_review') {
    // Approve or reject a client's ID
    $cu = db()->prepare('SELECT * FROM users WHERE id = ? AND role = "client" AND id_status = "pending"');
    $cu->execute([(int)($_POST['user_id'] ?? 0)]);
    $cu = $cu->fetch();
    $note = mb_substr(trim($_POST['note'] ?? ''), 0, 200);
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $msg = 'Session expired. Refresh the page and try again.';
    } elseif (!$cu) {
        $msg = 'That ID was not found or was already reviewed.';
    } elseif (($_POST['decision'] ?? '') === 'approve') {
        db()->prepare('UPDATE users SET id_status = "approved", id_reviewed_at = ?, id_note = "" WHERE id = ?')->execute([date('Y-m-d H:i:s'), $cu['id']]);
        notify((int)$cu['id'], 'Your ID was approved. You can now order, book services and apply as a provider.', 'client.php');
        if (give_coupon((int)$cu['id'], ['type' => 'shipping', 'value' => 0, 'min_spend' => 0, 'max_discount' => null, 'label' => 'Welcome gift: free shipping', 'days' => 30], 'welcome', null, 'welcome')) {
            notify((int)$cu['id'], '🎁 Welcome gift: a free shipping voucher is in your wallet.', 'vouchers.php');
        }
        $msg = 'ID approved. The client can now order and book.';
    } else {
        db()->prepare('UPDATE users SET id_status = "rejected", id_reviewed_at = ?, id_note = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $note, $cu['id']]);
        notify((int)$cu['id'], 'Your ID was rejected' . ($note ? ': ' . $note : '.') . ' Please upload a clear photo of a valid National ID or Driver\'s License.', 'id-upload.php');
        $msg = 'ID rejected. The client was asked to upload a valid ID.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'code_create') {
    // Create a voucher code that clients can claim
    $code  = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['code'] ?? ''));
    $type  = $_POST['type'] ?? '';
    $value = (float)($_POST['value'] ?? 0);
    $min   = max(0, (float)($_POST['min_spend'] ?? 0));
    $max   = ($_POST['max_discount'] ?? '') === '' ? null : max(0, (float)$_POST['max_discount']);
    $days  = max(1, min(365, (int)($_POST['valid_days'] ?? 30)));
    $claims = ($_POST['max_claims'] ?? '') === '' ? null : max(1, (int)$_POST['max_claims']);
    $label = mb_substr(trim($_POST['label'] ?? ''), 0, 140);
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? ''))      { $msg = 'Session expired. Refresh the page and try again.'; }
    elseif (strlen($code) < 4 || strlen($code) > 20)                { $msg = 'The code must be 4 to 20 letters or numbers.'; }
    elseif (!in_array($type, ['percent', 'fixed', 'shipping'], true)) { $msg = 'Choose a voucher type.'; }
    elseif ($type !== 'shipping' && ($value <= 0 || ($type === 'percent' && $value > 100))) { $msg = 'Enter a valid discount value (percent must be 1 to 100).'; }
    elseif ($label === '')                                          { $msg = 'Enter a short name for the voucher, like "Summer sale 10% off".'; }
    else {
        try {
            db()->prepare('INSERT INTO coupon_codes (code, type, value, min_spend, max_discount, label, valid_days, max_claims, created_at) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$code, $type, $type === 'shipping' ? 0 : $value, $min, $type === 'percent' ? $max : null, $label, $days, $claims, date('Y-m-d H:i:s')]);
            $msg = "Voucher code $code created. Clients claim it under My vouchers.";
        } catch (PDOException $e) { $msg = 'That code already exists. Choose another.'; }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'code_toggle') {
    if (hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        db()->prepare('UPDATE coupon_codes SET active = 1 - active WHERE id = ?')->execute([(int)($_POST['code_id'] ?? 0)]);
        $msg = 'Voucher code updated.';
    }
// Change a user's role
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? '';
    $type = $_POST['staff_type'] ?? '';
    $cr = db()->prepare('SELECT role FROM users WHERE id = ?'); $cr->execute([$id]); $curRole = $cr->fetchColumn();
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $msg = 'Session expired. Refresh the page and try again.';
    } elseif ($id === $_SESSION['user_id']) {
        $msg = 'You cannot change your own role.';
    } elseif (!in_array($role, ['admin', 'client', 'staff'], true)) {
        $msg = 'Invalid role.';
    } elseif ($role === 'staff' && $curRole !== 'staff') {
        $msg = 'Clients become staff by applying under "Become a provider". Approve their application below.';
    } else {
        $type = ($role === 'staff' && in_array($type, ['service', 'product'], true)) ? $type : null;
        if ($role === 'staff' && !$type) { $type = 'service'; }
        db()->prepare('UPDATE users SET role = ?, staff_type = ? WHERE id = ?')->execute([$role, $type, $id]);
        $msg = 'Role updated.';
    }
}

$counts = ['admin' => 0, 'client' => 0, 'staff' => 0];
foreach (db()->query('SELECT role, COUNT(*) n FROM users GROUP BY role') as $r) { $counts[$r['role']] = (int)$r['n']; }
$unverified = (int)db()->query('SELECT COUNT(*) FROM users WHERE is_verified = 0')->fetchColumn();
$users = db()->query('SELECT id, first_name, last_name, email, phone, role, staff_type, is_verified, id_doc_type, id_doc_file, id_status, created_at FROM users ORDER BY id DESC LIMIT 50')->fetchAll();

$fieldLabels = ['first_name' => 'First name', 'middle_name' => 'Middle name', 'last_name' => 'Last name', 'phone' => 'Phone',
                'address' => 'Address', 'city' => 'City', 'postal_code' => 'Postal code'];
$requests = db()->query('SELECT pc.*, u.email, u.first_name o_first_name, u.middle_name o_middle_name, u.last_name o_last_name,
                                u.phone o_phone, u.address o_address, u.city o_city, u.postal_code o_postal_code
                         FROM profile_changes pc JOIN users u ON u.id = pc.user_id
                         WHERE pc.status = "pending" ORDER BY pc.id')->fetchAll();

$apps = db()->query('SELECT a.*, u.email, u.phone, u.first_name, u.last_name FROM provider_applications a
                     JOIN users u ON u.id = a.user_id WHERE a.status = "pending" ORDER BY a.id')->fetchAll();

$catReqs = db()->query('SELECT c.*, u.email, u.phone, u.first_name, u.last_name, u.staff_type FROM provider_categories c
                        JOIN users u ON u.id = c.user_id WHERE c.status = "pending" ORDER BY c.id')->fetchAll();

$idPending = db()->query('SELECT id, first_name, last_name, email, phone, id_doc_type, created_at FROM users
                         WHERE role = "client" AND id_status = "pending" AND id_doc_file <> "" ORDER BY id')->fetchAll();

$codes = db()->query('SELECT * FROM coupon_codes ORDER BY id DESC LIMIT 50')->fetchAll();

$panelTitle = 'Admin panel';
$menu = ['Overview' => '#overview', 'ID verification' => '#id-review', 'Vouchers' => '#vouchers', 'Profile requests' => '#requests', 'Provider applications' => '#applications', 'Category requests' => '#category-requests', 'Manage users' => '#users', 'Shop products' => 'shop.php', 'My cart' => 'cart.php', 'My orders' => 'orders.php', 'Book a service' => 'providers.php', 'My bookings' => 'bookings.php', 'Open on phone' => 'phone-access.php'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin dashboard | AIOS</title>
  <link rel="stylesheet" href="css/style.css">
  <?php include __DIR__ . '/includes/head-extra.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  <div class="dash">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="dash-main">
      <h1 id="overview">Admin overview</h1>
      <?php if ($msg): ?><p class="form-ok"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
      <div class="cards">
        <div class="card"><span class="stat-value"><?= array_sum($counts) ?></span><span>Total users</span></div>
        <div class="card"><span class="stat-value"><?= $counts['client'] ?></span><span>Clients</span></div>
        <div class="card"><span class="stat-value"><?= $counts['staff'] ?></span><span>Staff</span></div>
        <div class="card"><span class="stat-value"><?= $unverified ?></span><span>Not verified yet</span></div>
      </div>

      <h2 id="id-review">ID verification (<?= count($idPending) ?>)</h2>
      <?php if (!$idPending): ?>
        <p class="muted">No IDs waiting for review.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Client</th><th>Document</th><th>Decision</th></tr></thead>
          <tbody>
          <?php foreach ($idPending as $q): ?>
            <tr>
              <td><strong><?= htmlspecialchars($q['first_name'] . ' ' . $q['last_name']) ?></strong><br><span class="muted"><?= htmlspecialchars($q['email'] ?: $q['phone']) ?></span></td>
              <td><span class="muted"><?= $q['id_doc_type'] === 'drivers_license' ? "Driver's License" : 'National ID' ?></span><br>
                <a href="admin-id.php?user=<?= (int)$q['id'] ?>" target="_blank" rel="noopener"><img class="id-thumb" src="admin-id.php?user=<?= (int)$q['id'] ?>" alt="ID of <?= htmlspecialchars($q['first_name']) ?>"></a></td>
              <td>
                <form method="post" class="id-decide">
                  <input type="hidden" name="action" value="id_review"><input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>"><input type="hidden" name="user_id" value="<?= (int)$q['id'] ?>">
                  <input type="text" name="note" maxlength="200" placeholder="Reason if rejecting (optional)" aria-label="Reason">
                  <div class="row-actions">
                    <button class="btn btn-small" name="decision" value="approve" type="submit">Approve</button>
                    <button class="btn btn-small btn-reject" name="decision" value="reject" type="submit">Reject</button>
                  </div>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <h2 id="vouchers">Voucher codes</h2>
      <form class="card dash-form" method="post">
        <input type="hidden" name="action" value="code_create"><input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
        <div class="form-grid">
          <div><label for="vc_code">Code</label><input id="vc_code" name="code" maxlength="20" placeholder="e.g. SAVE10" required></div>
          <div class="full"><label for="vc_label">Name shown to clients</label><input id="vc_label" name="label" maxlength="140" placeholder="e.g. Summer sale: 10% off" required></div>
          <div><label for="vc_type">Type</label>
            <select id="vc_type" name="type"><option value="percent">Percent off</option><option value="fixed">Fixed amount off</option><option value="shipping">Free shipping</option></select></div>
          <div><label for="vc_value">Value (% or amount, not needed for free shipping)</label><input id="vc_value" name="value" type="number" step="0.01" min="0" value="10"></div>
          <div><label for="vc_min">Minimum spend</label><input id="vc_min" name="min_spend" type="number" step="0.01" min="0" value="0"></div>
          <div><label for="vc_max">Maximum discount (percent only, optional)</label><input id="vc_max" name="max_discount" type="number" step="0.01" min="0"></div>
          <div><label for="vc_days">Valid for (days after claiming)</label><input id="vc_days" name="valid_days" type="number" min="1" max="365" value="30"></div>
          <div><label for="vc_claims">Max claims (optional)</label><input id="vc_claims" name="max_claims" type="number" min="1"></div>
        </div>
        <button class="btn btn-primary" type="submit">Create voucher code</button>
      </form>
      <?php if ($codes): ?>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Code</th><th>Voucher</th><th>Claimed</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($codes as $vc): ?>
            <tr>
              <td><strong><?= htmlspecialchars($vc['code']) ?></strong></td>
              <td><?= htmlspecialchars($vc['label']) ?><br><span class="muted"><?= $vc['type'] === 'shipping' ? 'Free shipping' : ($vc['type'] === 'percent' ? (float)$vc['value'] . '% off' : money((float)$vc['value']) . ' off') ?> &middot; min <?= money((float)$vc['min_spend']) ?> &middot; <?= (int)$vc['valid_days'] ?> days</span></td>
              <td><?= (int)$vc['claims'] ?><?= $vc['max_claims'] !== null ? ' / ' . (int)$vc['max_claims'] : '' ?></td>
              <td><?= $vc['active'] ? '<span class="badge badge-confirmed">Active</span>' : '<span class="badge badge-rejected">Off</span>' ?></td>
              <td><form method="post"><input type="hidden" name="action" value="code_toggle"><input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>"><input type="hidden" name="code_id" value="<?= (int)$vc['id'] ?>">
                <button class="btn btn-small btn-light" type="submit"><?= $vc['active'] ? 'Turn off' : 'Turn on' ?></button></form></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <h2 id="requests">Profile change requests (<?= count($requests) ?>)</h2>
      <?php if (!$requests): ?>
        <p class="muted">No requests waiting for approval.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Client</th><th>Requested changes</th><th>Sent</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($requests as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['email']) ?></td>
              <td>
                <?php $changed = false; foreach ($fieldLabels as $col => $label):
                    $old = (string)($r['o_' . $col] ?? ''); $new = (string)($r[$col] ?? '');
                    if ($old !== $new): $changed = true; ?>
                  <div><strong><?= $label ?>:</strong> <span class="muted"><?= htmlspecialchars($old ?: 'empty') ?></span> &rarr; <?= htmlspecialchars($new ?: 'empty') ?></div>
                <?php endif; endforeach; if (!$changed): ?><span class="muted">No differences</span><?php endif; ?>
              </td>
              <td><?= htmlspecialchars(date('M j, g:i A', strtotime($r['created_at']))) ?></td>
              <td>
                <form method="post" class="row-actions">
                  <input type="hidden" name="action" value="review">
                  <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                  <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                  <button class="btn btn-small" name="decision" value="approve" type="submit">Approve</button>
                  <button class="btn btn-small btn-reject" name="decision" value="reject" type="submit">Reject</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <h2 id="applications">Provider applications (<?= count($apps) ?>)</h2>
      <?php if (!$apps): ?>
        <p class="muted">No applications waiting for approval.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Applicant</th><th>Applying as</th><th>Business and details</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($apps as $a): ?>
            <tr>
              <td><?= htmlspecialchars(trim($a['first_name'] . ' ' . $a['last_name'])) ?><br><span class="muted"><?= htmlspecialchars($a['email'] ?: $a['phone']) ?></span></td>
              <td><span class="badge badge-confirmed"><?= $a['type'] === 'product' ? 'Product provider' : 'Service provider' ?></span></td>
              <td><strong><?= htmlspecialchars($a['business_name']) ?></strong><br><span class="muted"><?= htmlspecialchars($a['description']) ?></span><br><span><strong>Wants to <?= $a['type'] === 'product' ? 'sell' : 'offer' ?>:</strong> <?= htmlspecialchars(str_replace(',', ', ', $a['categories'] ?: 'not specified')) ?></span></td>
              <td>
                <form method="post" class="row-actions">
                  <input type="hidden" name="action" value="app_review">
                  <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                  <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                  <button class="btn btn-small" name="decision" value="approve" type="submit">Approve</button>
                  <button class="btn btn-small btn-reject" name="decision" value="reject" type="submit">Reject</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <h2 id="category-requests">Category requests (<?= count($catReqs) ?>)</h2>
      <?php if (!$catReqs): ?>
        <p class="muted">No category requests waiting.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Provider</th><th>Wants to add</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($catReqs as $c): $icons = category_options($c['staff_type'] === 'service' ? 'service' : 'product'); ?>
            <tr>
              <td><?= htmlspecialchars(trim($c['first_name'] . ' ' . $c['last_name'])) ?><br><span class="muted"><?= htmlspecialchars($c['email'] ?: $c['phone']) ?></span></td>
              <td><span class="chip on"><?= $icons[$c['category']] ?? '' ?> <?= htmlspecialchars($c['category']) ?></span></td>
              <td>
                <form method="post" class="row-actions">
                  <input type="hidden" name="action" value="cat_review">
                  <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                  <input type="hidden" name="cat_id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-small" name="decision" value="approve" type="submit">Approve</button>
                  <button class="btn btn-small btn-reject" name="decision" value="reject" type="submit">Reject</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <h2 id="users">Manage users</h2>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Name</th><th>Contact</th><th>Verified</th><th>Role</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                <?php if ($u['id_doc_file']): ?><br><span class="muted"><?= $u['id_doc_type'] === 'drivers_license' ? "Driver's License" : 'National ID' ?> &middot; <a href="admin-id.php?user=<?= (int)$u['id'] ?>" target="_blank" rel="noopener">View ID</a> &middot; <?= htmlspecialchars($u['id_status'] ?? '') ?></span><?php endif; ?></td>
              <td><?= htmlspecialchars($u['email'] ?: $u['phone']) ?></td>
              <td><?= $u['is_verified'] ? 'Yes' : 'No' ?></td>
              <td>
                <form method="post" id="f<?= $u['id'] ?>">
                  <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                </form>
                <select name="role" form="f<?= $u['id'] ?>" aria-label="Role">
                  <?php foreach (($u['role'] === 'staff' ? ['client', 'staff', 'admin'] : ['client', 'admin']) as $r): ?>
                    <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if ($u['role'] === 'staff'): ?>
                  <select name="staff_type" form="f<?= $u['id'] ?>" aria-label="Provider type">
                    <option value="service" <?= $u['staff_type'] === 'service' ? 'selected' : '' ?>>Service</option>
                    <option value="product" <?= $u['staff_type'] === 'product' ? 'selected' : '' ?>>Product</option>
                  </select>
                <?php endif; ?>
              </td>
              <td><button class="btn btn-small" type="submit" form="f<?= $u['id'] ?>" <?= $u['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>Save</button></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>
