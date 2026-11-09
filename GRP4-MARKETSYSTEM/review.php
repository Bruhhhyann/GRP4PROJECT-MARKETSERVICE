<?php
// Saves a star rating + comment. Only a client who RECEIVED the product (order marked delivered) can rate it.
require_once __DIR__ . '/includes/bootstrap.php';
require_role('client', 'staff', 'admin');
ensure_shop_tables();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: shop.php'); exit; }
$pid = (int)($_POST['product_id'] ?? 0);
if (!csrf_ok()) { flash('Session expired. Please try again.'); header('Location: product.php?id=' . $pid); exit; }

$rating  = (int)($_POST['rating'] ?? 0);
$comment = mb_substr(trim($_POST['comment'] ?? ''), 0, 600);
$orderId = delivered_order_for($uid, $pid);

if (!$orderId) {
    flash('You can only rate products you bought and received.');
} elseif ($rating < 1 || $rating > 5) {
    flash('Choose 1 to 5 stars.');
} else {
    db()->prepare('INSERT INTO reviews (product_id, user_id, order_id, rating, comment, created_at) VALUES (?,?,?,?,?,?)
                   ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), order_id = VALUES(order_id), created_at = VALUES(created_at)')
        ->execute([$pid, $uid, $orderId, $rating, $comment ?: null, date('Y-m-d H:i:s')]);
    $p = db()->prepare('SELECT name, seller_id FROM products WHERE id = ?');
    $p->execute([$pid]);
    if ($p = $p->fetch()) {
        notify((int)$p['seller_id'], $_SESSION['user'] . ' rated "' . $p['name'] . '" ' . $rating . ' out of 5 stars.', 'product.php?id=' . $pid . '#reviews');
    }
    header('Location: product.php?id=' . $pid . '&reviewed=1#reviews'); exit;   // the review is live right away
}
header('Location: product.php?id=' . $pid . '#reviews'); exit;
