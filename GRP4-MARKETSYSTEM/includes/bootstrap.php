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
    return 'SELECT p.*, ' . store_expr('p.seller_id', 'u.first_name') . ' AS store FROM products p JOIN users u ON u.id = p.seller_id';
}

function ensure_shop_tables(): void {
    if (!empty($_SESSION['shop_ready3'])) { return; }
    ensure_provider_applications_table();
    $sql = [
"CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, seller_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL, category VARCHAR(40) NOT NULL DEFAULT 'Other', description TEXT NULL,
  price DECIMAL(10,2) NOT NULL, stock INT UNSIGNED NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL, FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS cart_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1, comment VARCHAR(300) NULL, size VARCHAR(60) NOT NULL DEFAULT '', UNIQUE KEY one_per_product_size (user_id, product_id, size),
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
    $_SESSION['shop_ready3'] = true;
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
        $bySeller[$p['seller_id']][] = [$p, $qty, mb_substr(trim((string)($l['comment'] ?? '')), 0, 300), (string)($l['size'] ?? '')];
    }
    if (!$bySeller) { return [0, 'Nothing to order.']; }

    $name    = trim($buyer['first_name'] . ' ' . ($buyer['middle_name'] ?? '') . ' ' . $buyer['last_name']);
    $address = trim($buyer['address'] . ', ' . $buyer['city'] . ' ' . ($buyer['postal_code'] ?? ''));
    $contact = $buyer['email'] ?: ($buyer['phone'] ?? '');

    db()->beginTransaction();
    try {
        foreach ($bySeller as $sellerId => $items) {
            db()->prepare('INSERT INTO orders (buyer_id, seller_id, status, payment_method, ship_name, ship_address, ship_contact, created_at) VALUES (?,?,"pending","COD",?,?,?,?)')
                ->execute([$buyerId, $sellerId, $name, $address, $contact, date('Y-m-d H:i:s')]);
            $oid = (int)db()->lastInsertId();
            foreach ($items as [$p, $qty, $comment, $size]) {
                db()->prepare('INSERT INTO order_items (order_id, product_id, name, price, quantity, comment, size) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$oid, $p['id'], $p['name'], $p['price'], $qty, $comment ?: null, $size]);
            }
        }
        db()->commit();
    } catch (PDOException $e) {
        db()->rollBack();
        return [0, 'Could not place the order. Please try again.'];
    }
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
