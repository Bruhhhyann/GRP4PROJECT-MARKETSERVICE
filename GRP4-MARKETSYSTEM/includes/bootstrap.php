<?php
// includes/bootstrap.php
// Starts the session and holds sample data. Replace the arrays with MySQL queries later.

session_start();
require_once __DIR__ . '/mail-config.php';

const SITE_NAME = 'ServiceHub';
const CURRENCY  = '$'; // change to your own currency symbol

function is_signed_in(): bool {
    return !empty($_SESSION['user_id']);
}

function money(float $amount): string {
    return CURRENCY . number_format($amount, 2);
}

$stats = [
    ['label' => 'Orders & bookings', 'value' => '128',    'note' => '+12 this week'],
    ['label' => 'Earnings',        'value' => money(4820), 'note' => 'Up 8% from last month'],
    ['label' => 'Avg. rating',     'value' => '4.8',    'note' => 'From 312 reviews'],
];

$bookings = [
    ['service' => 'Aircon cleaning',    'provider' => 'CoolCare Services', 'date' => 'Today, 2:00 PM',    'status' => 'Confirmed', 'price' => 45],
    ['service' => 'Home deep clean',    'provider' => 'Sparkle Crew',      'date' => 'Tomorrow, 9:00 AM', 'status' => 'Pending',   'price' => 80],
    ['service' => 'Logo design',        'provider' => 'Mara Santos',       'date' => 'Nov 3, 11:00 AM',   'status' => 'Confirmed', 'price' => 120],
    ['service' => 'Plumbing repair',    'provider' => 'FlowFix Pros',      'date' => 'Nov 4, 3:30 PM',    'status' => 'Completed', 'price' => 60],
];

$categories = [
    ['name' => 'Home repair',   'count' => 240, 'icon' => '🔧'],
    ['name' => 'Cleaning',      'count' => 180, 'icon' => '🧹'],
    ['name' => 'Design & tech', 'count' => 150, 'icon' => '💻'],
    ['name' => 'Beauty & care', 'count' => 120, 'icon' => '💇'],
    ['name' => 'Tutoring',      'count' => 95,  'icon' => '📚'],
    ['name' => 'Events',        'count' => 70,  'icon' => '🎉'],
];

$providers = [
    ['name' => 'CoolCare Services', 'role' => 'Aircon & appliance repair', 'rating' => 4.9, 'jobs' => 214, 'from' => 35],
    ['name' => 'Sparkle Crew',      'role' => 'Home & office cleaning',     'rating' => 4.8, 'jobs' => 342, 'from' => 50],
    ['name' => 'Mara Santos',       'role' => 'Brand & logo designer',      'rating' => 5.0, 'jobs' => 89,  'from' => 90],
    ['name' => 'FlowFix Pros',      'role' => 'Plumbing & pipe repair',     'rating' => 4.7, 'jobs' => 176, 'from' => 40],
];

// ---------- Products (shoes, shirts, etc.) ----------
$productCategories = [
    ['name' => 'Shoes',       'count' => 320, 'icon' => '👟'],
    ['name' => 'Shirts',      'count' => 410, 'icon' => '👕'],
    ['name' => 'Dresses',     'count' => 190, 'icon' => '👗'],
    ['name' => 'Bags',        'count' => 150, 'icon' => '👜'],
    ['name' => 'Caps & hats', 'count' => 85,  'icon' => '🧢'],
    ['name' => 'Watches',     'count' => 60,  'icon' => '⌚'],
];

$products = [
    ['name' => 'Classic white sneakers', 'seller' => 'StepUp Shoes',   'price' => 55, 'rating' => 4.8, 'sold' => 640, 'icon' => '👟', 'tag' => 'Best seller'],
    ['name' => 'Cotton crew-neck tee',   'seller' => 'Threadwork',     'price' => 14, 'rating' => 4.7, 'sold' => 1210, 'icon' => '👕', 'tag' => 'New'],
    ['name' => 'Leather tote bag',       'seller' => 'Carry & Co.',    'price' => 72, 'rating' => 4.9, 'sold' => 215, 'icon' => '👜', 'tag' => ''],
    ['name' => 'Summer floral dress',    'seller' => 'Bloom Boutique', 'price' => 38, 'rating' => 4.6, 'sold' => 380, 'icon' => '👗', 'tag' => 'Sale'],
];

// ---------- Database + auth (localhost XAMPP defaults) ----------
const DB_HOST = 'localhost';
const DB_NAME = 'servicehub';
const DB_USER = 'root';
const DB_PASS = '';            // XAMPP default is empty
const CODE_MINUTES   = 15;     // verification code lifetime
const RESEND_SECONDS = 30;     // wait before a new code can be sent

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function require_role(string ...$roles): void {
    if (!is_signed_in()) {
        header('Location: signin.php');
        exit;
    }
    // Always use the role saved in the database (it changes when an admin approves an application).
    $st = db()->prepare('SELECT role, staff_type FROM users WHERE id = ?');
    $st->execute([$_SESSION['user_id']]);
    $row = $st->fetch();
    if (!$row) { session_destroy(); header('Location: signin.php'); exit; }
    $_SESSION['role'] = $row['role'];
    $_SESSION['staff_type'] = $row['staff_type'];
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        // Signed in, but this page is for another role: say so instead of silently bouncing back.
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>No access</title>'
           . '<link rel="stylesheet" href="css/style.css"></head><body><main class="auth-wrap"><div class="auth-card">'
           . '<h1>No access</h1><p class="muted">This page is for: ' . htmlspecialchars(implode(', ', $roles))
           . '. Your account role is <strong>' . htmlspecialchars((string)($_SESSION['role'] ?? 'none')) . '</strong>.</p>'
           . '<a class="btn btn-primary btn-block" href="dashboard.php">Go to my dashboard</a>'
           . '<a class="btn btn-ghost btn-block" href="signin.php?action=signout">Sign out</a></div></main></body></html>';
        exit;
    }
}

function sign_in_user(array $u): void {
    session_regenerate_id(true);
    unset($_SESSION['pending_user']);
    $_SESSION['user']       = trim((string)$u['first_name']) !== '' ? $u['first_name'] : ucfirst($u['role']);
    $_SESSION['user_id']    = (int)$u['id'];
    $_SESSION['role']       = $u['role'];
    $_SESSION['staff_type'] = $u['staff_type'];
}

// Sends a plain-text email through SMTP (SSL, port 465). Returns true on success.
function send_mail(string $to, string $subject, string $body): bool {
    $fp = @stream_socket_client('ssl://' . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 15);
    if (!$fp) { error_log("SMTP connect failed: $errstr"); return false; }
    stream_set_timeout($fp, 15);
    $read = function () use ($fp): string {
        $r = '';
        while (($l = fgets($fp, 515)) !== false) { $r .= $l; if (isset($l[3]) && $l[3] === ' ') break; }
        return $r;
    };
    $cmd = function (string $c, string $ok) use ($fp, $read): bool {
        fwrite($fp, $c . "\r\n");
        $r = $read();
        if (!str_starts_with($r, $ok)) { error_log("SMTP error after [" . substr($c, 0, 12) . "]: $r"); return false; }
        return true;
    };
    $read(); // server greeting
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $body = preg_replace('/^\./m', '..', $body);
    $msg  = "From: =?UTF-8?B?" . base64_encode(MAIL_FROM_NAME) . "?= <" . SMTP_USER . ">\r\n"
          . "To: <$to>\r\n"
          . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
          . "Date: " . date('r') . "\r\n"
          . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n"
          . str_replace("\n", "\r\n", $body);
    $ok = $cmd('EHLO localhost', '250')
       && $cmd('AUTH LOGIN', '334')
       && $cmd(base64_encode(SMTP_USER), '334')
       && $cmd(base64_encode(SMTP_PASS), '235')
       && $cmd('MAIL FROM:<' . SMTP_USER . '>', '250')
       && $cmd("RCPT TO:<$to>", '250')
       && $cmd('DATA', '354')
       && $cmd($msg . "\r\n.", '250');
    fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return $ok;
}

// Makes a new 6-digit code for the registration waiting in the session and emails it.
// Nothing is saved in the database until the code is entered correctly.
// Returns true if sent (or shown in test mode), false if the email failed.
function send_registration_code(): bool {
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['pending_reg']['code_hash'] = hash('sha256', $code);
    $_SESSION['pending_reg']['expires']   = time() + CODE_MINUTES * 60;
    $_SESSION['pending_reg']['sent_at']   = time();
    $_SESSION['pending_reg']['tries']     = 0;
    unset($_SESSION['dev_code']);

    if (SMTP_USER === '') { $_SESSION['dev_code'] = $code; return true; }   // test mode

    return send_mail(
        $_SESSION['pending_reg']['data']['email'],
        'Your ' . SITE_NAME . ' verification code',
        "Hi " . $_SESSION['pending_reg']['data']['first_name'] . ",\n\nYour verification code is: $code\n\n"
        . "It expires in " . CODE_MINUTES . " minutes. If you did not try to register, ignore this email.\n\n- " . SITE_NAME
    );
}

// Table for profile edits waiting for admin approval (created automatically if missing).
function ensure_profile_changes_table(): void {
    db()->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS profile_changes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  first_name  VARCHAR(60)  NOT NULL,
  middle_name VARCHAR(60)  NULL,
  last_name   VARCHAR(60)  NOT NULL,
  phone       VARCHAR(20)  NULL,
  address     VARCHAR(255) NOT NULL,
  city        VARCHAR(80)  NOT NULL,
  postal_code VARCHAR(12)  NULL,
  status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at  DATETIME NOT NULL,
  reviewed_at DATETIME NULL,
  reviewed_by INT UNSIGNED NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
SQL);
}

// Clients who ask to become a service or product provider (created automatically if missing).
function ensure_provider_applications_table(): void {
    if (!empty($_SESSION['prov_ready'])) { return; }
    db()->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS provider_applications (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  type          ENUM('service','product') NOT NULL,
  business_name VARCHAR(120) NOT NULL,
  description   VARCHAR(600) NOT NULL,
  categories    VARCHAR(400) NOT NULL DEFAULT '',
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at    DATETIME NOT NULL,
  reviewed_at   DATETIME NULL,
  reviewed_by   INT UNSIGNED NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
SQL);
    // older installs: add the categories column if it is missing
    if (!db()->query("SHOW COLUMNS FROM provider_applications LIKE 'categories'")->fetch()) {
        db()->exec("ALTER TABLE provider_applications ADD COLUMN categories VARCHAR(400) NOT NULL DEFAULT ''");
    }
    db()->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS provider_categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  category    VARCHAR(60) NOT NULL,
  status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at  DATETIME NOT NULL,
  reviewed_at DATETIME NULL,
  UNIQUE KEY one_per_category (user_id, category),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
SQL);
    $_SESSION['prov_ready'] = true;
}

// =====================================================================
// SHOP: products, cart, orders (tables are created automatically)
// =====================================================================
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_ok(): bool { return hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? ''); }

// flash('text') stores a one-time message; flash() returns it once.
function flash(?string $msg = null): ?string {
    if ($msg !== null) { $_SESSION['flash'] = $msg; return null; }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

function product_categories(): array { return ['Shoes', 'Shirts', 'Dresses', 'Bags', 'Caps & hats', 'Watches', 'Other']; }
function product_icon(string $cat): string {
    return ['Shoes' => '👟', 'Shirts' => '👕', 'Dresses' => '👗', 'Bags' => '👜', 'Caps & hats' => '🧢', 'Watches' => '⌚'][$cat] ?? '🛍️';
}

// Store name = approved business name, else the seller's first name.
function store_expr(string $idCol, string $nameCol): string {
    return "COALESCE((SELECT a.business_name FROM provider_applications a WHERE a.user_id = $idCol AND a.status = 'approved' ORDER BY a.id DESC LIMIT 1), NULLIF($nameCol, ''), 'Store')";
}
function product_sql(): string {
    return 'SELECT p.*, ' . store_expr('p.seller_id', 'u.first_name') . ' AS store, u.photo AS seller_photo,
                   (SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.product_id = p.id) AS avg_rating,
                   (SELECT COUNT(*) FROM reviews r2 WHERE r2.product_id = p.id) AS review_count
            FROM products p JOIN users u ON u.id = p.seller_id';
}

function ensure_shop_tables(): void {
    if (!empty($_SESSION['shop_ready6'])) { return; }
    ensure_provider_applications_table();
    $sql = [
"CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, seller_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL, category VARCHAR(40) NOT NULL DEFAULT 'Other', description TEXT NULL,
  price DECIMAL(10,2) NOT NULL, stock INT UNSIGNED NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL, FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS cart_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1, comment VARCHAR(300) NULL, size VARCHAR(60) NOT NULL DEFAULT '', color VARCHAR(40) NOT NULL DEFAULT '', UNIQUE KEY one_per_variant (user_id, product_id, size, color),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, buyer_id INT UNSIGNED NOT NULL, seller_id INT UNSIGNED NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  ship_name VARCHAR(160) NOT NULL, ship_address VARCHAR(300) NOT NULL, ship_contact VARCHAR(140) NOT NULL,
  created_at DATETIME NOT NULL, reviewed_at DATETIME NULL,
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL, price DECIMAL(10,2) NOT NULL, quantity INT UNSIGNED NOT NULL, comment VARCHAR(300) NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL)",
    ];
    foreach ($sql as $s) { db()->exec($s); }
    // Only payment method is Cash on Delivery. Older installs get the column added once.
    if (!db()->query("SHOW COLUMNS FROM orders LIKE 'payment_method'")->fetch()) {
        db()->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) NOT NULL DEFAULT 'COD'");
    }
    // Size/width choice is saved on cart lines and order lines (older installs get the column added once).
    foreach (['cart_items', 'order_items'] as $t) {
        if (!db()->query("SHOW COLUMNS FROM $t LIKE 'size'")->fetch()) {
            db()->exec("ALTER TABLE $t ADD COLUMN size VARCHAR(60) NOT NULL DEFAULT ''");
        }
    }
    // Cart: one line per product AND size (old rule was one line per product).
    if (db()->query("SHOW INDEX FROM cart_items WHERE Key_name = 'one_per_product'")->fetch()) {
        db()->exec('ALTER TABLE cart_items ADD UNIQUE KEY one_per_product_size (user_id, product_id, size), DROP INDEX one_per_product');
    }
    // Colors: which colors a product comes in, and the color chosen on cart and order lines.
    if (!db()->query("SHOW COLUMNS FROM products LIKE 'colors'")->fetch()) {
        db()->exec("ALTER TABLE products ADD COLUMN colors VARCHAR(200) NOT NULL DEFAULT ''");
    }
    foreach (['cart_items', 'order_items'] as $t) {
        if (!db()->query("SHOW COLUMNS FROM $t LIKE 'color'")->fetch()) {
            db()->exec("ALTER TABLE $t ADD COLUMN color VARCHAR(40) NOT NULL DEFAULT ''");
        }
    }
    // Cart: one line per product + size + color
    if (db()->query("SHOW INDEX FROM cart_items WHERE Key_name = 'one_per_product_size'")->fetch()) {
        db()->exec('ALTER TABLE cart_items ADD UNIQUE KEY one_per_variant (user_id, product_id, size, color), DROP INDEX one_per_product_size');
    }
    if (!db()->query("SHOW COLUMNS FROM products LIKE 'image'")->fetch()) {
        db()->exec("ALTER TABLE products ADD COLUMN image VARCHAR(200) NOT NULL DEFAULT ''");
    }
    // Delivery flow: status "delivered", delivery days, and the dates
    $col = db()->query("SHOW COLUMNS FROM orders LIKE 'status'")->fetch();
    if ($col && !str_contains($col['Type'], 'delivered')) {
        db()->exec("ALTER TABLE orders MODIFY status ENUM('pending','approved','delivered','rejected') NOT NULL DEFAULT 'pending'");
    }
    add_column_if_missing('orders', 'delivery_days', 'TINYINT UNSIGNED NULL');
    add_column_if_missing('orders', 'approved_at', 'DATETIME NULL');
    add_column_if_missing('orders', 'delivered_at', 'DATETIME NULL');
    add_column_if_missing('users', 'photo', "VARCHAR(200) NOT NULL DEFAULT ''");
    // Reviews (live as soon as they are posted) and notifications
    db()->exec("CREATE TABLE IF NOT EXISTS reviews (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL, order_id INT UNSIGNED NULL,
      rating TINYINT UNSIGNED NOT NULL, comment VARCHAR(600) NULL, created_at DATETIME NOT NULL,
      UNIQUE KEY one_review (product_id, user_id),
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)");
    db()->exec("CREATE TABLE IF NOT EXISTS notifications (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, message VARCHAR(300) NOT NULL,
      link VARCHAR(200) NOT NULL DEFAULT '', is_read TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL,
      KEY by_user (user_id, is_read), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)");
    $_SESSION['shop_ready6'] = true;
}

function cart_count(): int {
    if (empty($_SESSION['user_id'])) { return 0; }
    try {
        ensure_shop_tables();
        $st = db()->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
        $st->execute([$_SESSION['user_id']]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) { return 0; }
}

// $lines = [['product_id' => 1, 'qty' => 2, 'comment' => '...'], ...]
// Makes one PENDING order per seller. Returns [number_of_orders, error_message].
function place_order(int $buyerId, array $lines): array {
    $b = db()->prepare('SELECT * FROM users WHERE id = ?');
    $b->execute([$buyerId]);
    $buyer = $b->fetch();
    if (!$buyer) { return [0, 'Account not found.']; }

    $bySeller = [];
    foreach ($lines as $l) {
        $st = db()->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1');
        $st->execute([(int)$l['product_id']]);
        $p = $st->fetch();
        if (!$p) { continue; }
        $qty = max(1, min(99, (int)$l['qty']));
        if ($p['stock'] < $qty) { return [0, $p['stock'] > 0 ? "Only {$p['stock']} left of {$p['name']}." : "{$p['name']} is sold out."]; }
        $bySeller[$p['seller_id']][] = [$p, $qty, mb_substr(trim((string)($l['comment'] ?? '')), 0, 300), (string)($l['size'] ?? ''), (string)($l['color'] ?? '')];
    }
    if (!$bySeller) { return [0, 'Nothing to order.']; }

    $name    = trim($buyer['first_name'] . ' ' . ($buyer['middle_name'] ?? '') . ' ' . $buyer['last_name']);
    $address = trim($buyer['address'] . ', ' . $buyer['city'] . ' ' . ($buyer['postal_code'] ?? ''));
    $contact = $buyer['email'] ?: ($buyer['phone'] ?? '');

    $newIds = [];
    db()->beginTransaction();
    try {
        foreach ($bySeller as $sellerId => $items) {
            db()->prepare('INSERT INTO orders (buyer_id, seller_id, status, payment_method, ship_name, ship_address, ship_contact, created_at) VALUES (?,?,"pending","COD",?,?,?,?)')
                ->execute([$buyerId, $sellerId, $name, $address, $contact, date('Y-m-d H:i:s')]);
            $oid = (int)db()->lastInsertId();
            $newIds[$sellerId] = $oid;
            foreach ($items as [$p, $qty, $comment, $size, $color]) {
                db()->prepare('INSERT INTO order_items (order_id, product_id, name, price, quantity, comment, size, color) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$oid, $p['id'], $p['name'], $p['price'], $qty, $comment ?: null, $size, $color]);
            }
        }
        db()->commit();
    } catch (PDOException $e) {
        db()->rollBack();
        return [0, 'Could not place the order. Please try again.'];
    }
    foreach ($newIds as $sid => $oid) { notify((int)$sid, "New order #$oid from $name. Open Orders to approve it.", 'staff.php#orders'); }
    return [count($bySeller), ''];
}

function client_menu(): array {
    return ['Dashboard' => 'client.php', 'Shop products' => 'shop.php', 'My cart' => 'cart.php', 'My orders' => 'orders.php',
            'My profile' => 'profile.php', 'Become a provider' => 'apply.php'];
}

// ---------- Provider categories ----------
// name => icon. Products and services have their own lists.
function category_options(string $type): array {
    return $type === 'service'
        ? ['Home repair' => '🔧', 'Cleaning' => '🧹', 'Design & tech' => '💻', 'Beauty & care' => '💇', 'Tutoring' => '📚', 'Events' => '🎉']
        : ['Shoes' => '👟', 'Shirts' => '👕', 'Dresses' => '👗', 'Bags' => '👜', 'Caps & hats' => '🧢', 'Watches' => '⌚', 'Other' => '🛍️'];
}

// category => status for one provider
function provider_category_rows(int $uid): array {
    $st = db()->prepare('SELECT category, status FROM provider_categories WHERE user_id = ?');
    $st->execute([$uid]);
    return $st->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Providers approved before categories existed get all categories of their type, once.
function ensure_provider_category_rows(int $uid, string $type): void {
    if (provider_category_rows($uid)) { return; }
    $now = date('Y-m-d H:i:s');
    foreach (array_keys(category_options($type)) as $c) {
        db()->prepare('INSERT IGNORE INTO provider_categories (user_id, category, status, created_at, reviewed_at) VALUES (?,?,"approved",?,?)')->execute([$uid, $c, $now, $now]);
    }
}

function approved_categories(int $uid): array {
    return array_keys(array_filter(provider_category_rows($uid), fn($s) => $s === 'approved'));
}

// ---------- Sizes ----------
// Which products need a size, with the measurements shown to the buyer. null = no size needed.
function size_config(string $category): ?array {
    $shoes = [];
    foreach ([6 => 24.0, 7 => 24.8, 8 => 25.7, 9 => 26.5, 10 => 27.3, 11 => 28.2, 12 => 29.1, 13 => 30.0] as $us => $cm) {
        $shoes["US $us"] = "foot length about $cm cm";
    }
    return match ($category) {
        'Shoes' => ['label' => 'US size', 'sizes' => $shoes,
                    'width' => ['Narrow' => 'snug fit, for slim feet', 'Regular' => 'standard fit', 'Wide' => 'roomy fit, for broad feet']],
        'Shirts' => ['label' => 'Size', 'sizes' => [
            'XS' => 'chest width 48 cm, length 66 cm', 'S' => 'chest width 51 cm, length 68 cm', 'M' => 'chest width 54 cm, length 70 cm',
            'L' => 'chest width 57 cm, length 72 cm', 'XL' => 'chest width 60 cm, length 74 cm', 'XXL' => 'chest width 63 cm, length 76 cm']],
        'Dresses' => ['label' => 'Size', 'sizes' => [
            'XS' => 'bust 82 cm, length 100 cm', 'S' => 'bust 86 cm, length 102 cm', 'M' => 'bust 90 cm, length 104 cm',
            'L' => 'bust 96 cm, length 106 cm', 'XL' => 'bust 102 cm, length 108 cm']],
        'Caps & hats' => ['label' => 'Size', 'sizes' => [
            'S/M' => 'head 54 to 57 cm', 'L/XL' => 'head 58 to 61 cm', 'One size' => 'adjustable strap']],
        default => null,
    };
}

// Returns [label, error]. label is what gets saved, like "US 9, Regular width" or "M".
function validate_size(string $category, string $size, string $width): array {
    $cfg = size_config($category);
    if (!$cfg) { return ['', '']; }
    if (!isset($cfg['sizes'][$size])) { return ['', 'Please choose a size.']; }
    $label = $size;
    if (isset($cfg['width'])) {
        if (!isset($cfg['width'][$width])) { return ['', 'Please choose a width.']; }
        $label .= ', ' . $width . ' width';
    }
    return [$label, ''];
}

// ---------- Colors ----------
function color_map(): array {
    return ['White' => '#ffffff', 'Black' => '#1b2430', 'Navy' => '#163a5f', 'Gray' => '#8a96a3', 'Silver' => '#c0c7cf',
            'Red' => '#d64545', 'Pink' => '#e88fb0', 'Orange' => '#ee8a33', 'Yellow' => '#f5b83d', 'Gold' => '#d4a940',
            'Green' => '#3f8f5b', 'Blue' => '#3b82c4', 'Purple' => '#7b5ea7', 'Brown' => '#7a4b2a', 'Tan' => '#c9a77c',
            'Beige' => '#e3d3b8', 'Khaki' => '#bfae7f', 'Rose gold' => '#d8a496'];
}
function color_hex(string $name): string { return color_map()[$name] ?? '#c3cfda'; }

// Used when a seller did not pick colors for a product.
function default_colors(string $category): array {
    return match ($category) {
        'Shoes'       => ['White', 'Black', 'Navy', 'Brown'],
        'Shirts'      => ['White', 'Black', 'Navy', 'Gray', 'Red'],
        'Dresses'     => ['Black', 'Red', 'Navy', 'Beige', 'Green'],
        'Bags'        => ['Black', 'Brown', 'Tan', 'Navy'],
        'Caps & hats' => ['Black', 'White', 'Navy', 'Khaki'],
        'Watches'     => ['Silver', 'Black', 'Gold', 'Brown'],
        default       => ['Black', 'White', 'Silver'],
    };
}
function product_colors(array $p): array {
    $own = array_values(array_filter(array_map('trim', explode(',', (string)($p['colors'] ?? '')))));
    return $own ?: default_colors((string)$p['category']);
}
// Returns [color, error]
function validate_color(array $p, string $color): array {
    return in_array($color, product_colors($p), true) ? [$color, ''] : ['', 'Please choose a color.'];
}

// ---------- Product photos ----------
// The photo if the seller uploaded one, otherwise the category emoji.
function product_art(array $p): string {
    if (!empty($p['image'])) {
        return '<img src="' . htmlspecialchars($p['image']) . '" alt="' . htmlspecialchars((string)($p['name'] ?? '')) . '" loading="lazy">';
    }
    return '<span>' . product_icon((string)$p['category']) . '</span>';
}

// Saves an uploaded photo into uploads/products. Returns [path, error]; path is '' when no file was chosen.
function save_product_image(array $f, int $productId): array {
    $err = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE) { return ['', '']; }
    if ($err !== UPLOAD_ERR_OK) { return ['', 'The photo could not be uploaded. It may be bigger than 2 MB.']; }
    if ($f['size'] > 2 * 1024 * 1024) { return ['', 'The photo is too big. Maximum is 2 MB.']; }
    $info  = @getimagesize($f['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) { return ['', 'The photo must be a JPG, PNG or WEBP image.']; }
    $dir = __DIR__ . '/../uploads/products';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) { return ['', 'Could not create the uploads folder.']; }
    $name = 'p' . $productId . '-' . bin2hex(random_bytes(4)) . '.' . $types[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) { return ['', 'Could not save the photo.']; }
    return ['uploads/products/' . $name, ''];
}

function delete_product_image(string $path): void {
    if (str_starts_with($path, 'uploads/products/')) {
        $file = __DIR__ . '/../' . $path;
        if (is_file($file)) { @unlink($file); }
    }
}

// =====================================================================
// NOTIFICATIONS, REVIEWS, PROVIDER PHOTOS
// =====================================================================
function add_column_if_missing(string $table, string $col, string $ddl): void {
    if (!db()->query("SHOW COLUMNS FROM `$table` LIKE '$col'")->fetch()) {
        db()->exec("ALTER TABLE `$table` ADD COLUMN `$col` $ddl");
    }
}

function notify(int $uid, string $message, string $link = ''): void {
    try {
        db()->prepare('INSERT INTO notifications (user_id, message, link, created_at) VALUES (?,?,?,?)')
            ->execute([$uid, mb_substr($message, 0, 300), $link, date('Y-m-d H:i:s')]);
    } catch (Throwable $e) { error_log('notify failed: ' . $e->getMessage()); }
}

function notif_count(): int {
    if (empty($_SESSION['user_id'])) { return 0; }
    try {
        ensure_shop_tables();
        $st = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $st->execute([$_SESSION['user_id']]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) { return 0; }
}

function store_name(int $sellerId): string {
    $st = db()->prepare('SELECT ' . store_expr('u.id', 'u.first_name') . ' FROM users u WHERE u.id = ?');
    $st->execute([$sellerId]);
    return (string)($st->fetchColumn() ?: 'the shop');
}

// The id of a delivered order in which this client bought this product (0 = never received it, so no rating allowed).
function delivered_order_for(int $uid, int $productId): int {
    $st = db()->prepare('SELECT o.id FROM orders o JOIN order_items i ON i.order_id = o.id
                         WHERE o.buyer_id = ? AND i.product_id = ? AND o.status = "delivered" ORDER BY o.id DESC LIMIT 1');
    $st->execute([$uid, $productId]);
    return (int)$st->fetchColumn();
}

function stars_html(float $rating): string {
    $full = max(0, min(5, (int)round($rating)));
    return '<span class="stars" role="img" aria-label="' . number_format($rating, 1) . ' out of 5 stars">'
         . str_repeat('★', $full) . '<span class="off">' . str_repeat('★', 5 - $full) . '</span></span>';
}

// ----- provider / shop photo (users.photo) -----
function avatar_art(?string $path, string $name = ''): string {
    if ($path) { return '<img src="' . htmlspecialchars($path) . '" alt="' . htmlspecialchars($name) . '" loading="lazy">'; }
    return '<span>' . htmlspecialchars(strtoupper(mb_substr(trim($name) !== '' ? $name : 'S', 0, 1))) . '</span>';
}

function save_avatar(array $f, int $uid): array {
    $err = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE) { return ['', '']; }
    if ($err !== UPLOAD_ERR_OK) { return ['', 'The photo could not be uploaded. It may be bigger than 2 MB.']; }
    if ($f['size'] > 2 * 1024 * 1024) { return ['', 'The photo is too big. Maximum is 2 MB.']; }
    $info  = @getimagesize($f['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) { return ['', 'The photo must be a JPG, PNG or WEBP image.']; }
    $dir = __DIR__ . '/../uploads/avatars';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) { return ['', 'Could not create the uploads folder.']; }
    $name = 'u' . $uid . '-' . bin2hex(random_bytes(4)) . '.' . $types[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) { return ['', 'Could not save the photo.']; }
    return ['uploads/avatars/' . $name, ''];
}

function delete_avatar(string $path): void {
    if (str_starts_with($path, 'uploads/avatars/')) {
        $file = __DIR__ . '/../' . $path;
        if (is_file($file)) { @unlink($file); }
    }
}
