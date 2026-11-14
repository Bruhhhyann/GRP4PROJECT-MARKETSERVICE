<?php
// Follow or unfollow a shop, provider or client.
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client', 'staff', 'admin');
ensure_shop_tables();
$me = (int)$_SESSION['user_id'];

$ret = (string)($_POST['return'] ?? '');
if (!preg_match('#^/[^/\\\\]#', $ret) || strpbrk($ret, "\r\n") !== false) { $ret = 'people.php'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $target = (int)($_POST['user_id'] ?? 0);
    $t = db()->prepare('SELECT id FROM users WHERE id = ? AND role IN ("client","staff") AND is_verified = 1');
    $t->execute([$target]);
    if ($target !== $me && $t->fetch()) {
        if (($_POST['do'] ?? '') === 'unfollow') {
            db()->prepare('DELETE FROM follows WHERE follower_id = ? AND followed_id = ?')->execute([$me, $target]);
        } else {
            $ins = db()->prepare('INSERT IGNORE INTO follows (follower_id, followed_id, created_at) VALUES (?,?,?)');
            $ins->execute([$me, $target, date('Y-m-d H:i:s')]);
            if ($ins->rowCount() === 1) {
                $who = ($_SESSION['role'] === 'staff') ? store_name($me) : $_SESSION['user'];
                notify($target, "$who started following you.", 'user.php?id=' . $me);
            }
        }
    }
}
header('Location: ' . $ret); exit;
