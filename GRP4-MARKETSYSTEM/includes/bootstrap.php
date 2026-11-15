<?php
// includes/bootstrap.php
// Starts the session and holds sample data. Replace the arrays with MySQL queries later.

session_start();
require_once __DIR__ . '/mail-config.php';
if (is_file(__DIR__ . '/id-config.php')) { require_once __DIR__ . '/id-config.php'; }
if (!defined('TESSERACT_PATH')) { define('TESSERACT_PATH', ''); }
if (is_file(__DIR__ . '/shop-config.php')) { require_once __DIR__ . '/shop-config.php'; }
if (!defined('SHIPPING_FEE'))      { define('SHIPPING_FEE', 3.00); }
if (!defined('FREE_SHIPPING_MIN')) { define('FREE_SHIPPING_MIN', 50.00); }
if (!defined('REWARD_VALID_DAYS')) { define('REWARD_VALID_DAYS', 60); }

const SITE_NAME = 'AIOS';
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
const DB_HOST = 'sql311.infinityfree.com';
const DB_NAME = 'if0_43447497_db_marketplace';
const DB_USER = 'if0_43447497';
const DB_PASS = 'Bryan106'; 
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
    if (!empty($_SESSION['shop_ready12'])) { return; }
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
    add_column_if_missing('users', 'id_doc_type', "VARCHAR(20) NOT NULL DEFAULT ''");
    add_column_if_missing('users', 'id_doc_file', "VARCHAR(200) NOT NULL DEFAULT ''");
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
    // Service providers: price and "about" text, and the bookings table
    add_column_if_missing('users', 'service_rate', 'DECIMAL(10,2) NULL');
    add_column_if_missing('users', 'service_about', "VARCHAR(600) NOT NULL DEFAULT ''");
    db()->exec("CREATE TABLE IF NOT EXISTS bookings (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, client_id INT UNSIGNED NOT NULL, provider_id INT UNSIGNED NOT NULL,
      service VARCHAR(60) NOT NULL, booking_date DATE NOT NULL, booking_time TIME NOT NULL,
      address VARCHAR(300) NOT NULL, contact VARCHAR(140) NOT NULL, notes VARCHAR(500) NULL,
      status ENUM('pending','confirmed','declined','completed','cancelled') NOT NULL DEFAULT 'pending',
      created_at DATETIME NOT NULL, updated_at DATETIME NULL,
      KEY by_client (client_id), KEY by_provider (provider_id, booking_date),
      FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE)");
    // ID review: new clients start as "pending" until an admin approves the ID (older accounts count as approved)
    add_column_if_missing('users', 'id_status', "ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved'");
    add_column_if_missing('users', 'id_reviewed_at', 'DATETIME NULL');
    add_column_if_missing('users', 'id_note', "VARCHAR(200) NOT NULL DEFAULT ''");
    // Followers: who follows which shop, provider or client
    db()->exec("CREATE TABLE IF NOT EXISTS follows (
      follower_id INT UNSIGNED NOT NULL, followed_id INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
      PRIMARY KEY (follower_id, followed_id), KEY by_followed (followed_id),
      FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE)");
    // Shipping fee, voucher discount on each order, and the voucher tables
    add_column_if_missing('orders', 'shipping_fee', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
    add_column_if_missing('orders', 'discount', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
    add_column_if_missing('orders', 'voucher_note', "VARCHAR(200) NOT NULL DEFAULT ''");
    db()->exec("CREATE TABLE IF NOT EXISTS coupons (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL,
      type ENUM('percent','fixed','shipping') NOT NULL, value DECIMAL(10,2) NOT NULL DEFAULT 0,
      min_spend DECIMAL(10,2) NOT NULL DEFAULT 0, max_discount DECIMAL(10,2) NULL,
      label VARCHAR(140) NOT NULL, source VARCHAR(20) NOT NULL DEFAULT 'reward',
      reward_key VARCHAR(40) NULL, code_id INT UNSIGNED NULL, expires_at DATETIME NULL,
      used_order_id INT UNSIGNED NULL, created_at DATETIME NOT NULL,
      UNIQUE KEY one_reward (user_id, reward_key), UNIQUE KEY one_code (user_id, code_id), KEY by_user (user_id, used_order_id),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)");
    db()->exec("CREATE TABLE IF NOT EXISTS coupon_codes (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(30) NOT NULL UNIQUE,
      type ENUM('percent','fixed','shipping') NOT NULL, value DECIMAL(10,2) NOT NULL DEFAULT 0,
      min_spend DECIMAL(10,2) NOT NULL DEFAULT 0, max_discount DECIMAL(10,2) NULL, label VARCHAR(140) NOT NULL,
      valid_days INT UNSIGNED NOT NULL DEFAULT 30, max_claims INT UNSIGNED NULL, claims INT UNSIGNED NOT NULL DEFAULT 0,
      active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL)");
    // Buyers can cancel an order: new status and the reason
    $col2 = db()->query("SHOW COLUMNS FROM orders LIKE 'status'")->fetch();
    if ($col2 && !str_contains($col2['Type'], 'cancelled')) {
        db()->exec("ALTER TABLE orders MODIFY status ENUM('pending','approved','delivered','rejected','cancelled') NOT NULL DEFAULT 'pending'");
    }
    add_column_if_missing('orders', 'cancel_reason', "VARCHAR(160) NOT NULL DEFAULT ''");
    add_column_if_missing('orders', 'cancelled_at', 'DATETIME NULL');
    $_SESSION['shop_ready12'] = true;
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
function place_order(int $buyerId, array $lines, array $pick = []): array {
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
        if ((int)$p['seller_id'] === $buyerId) { return [0, "You can't buy your own product ({$p['name']})."]; }
        $qty = max(1, min(99, (int)$l['qty']));
        if ($p['stock'] < $qty) { return [0, $p['stock'] > 0 ? "Only {$p['stock']} left of {$p['name']}." : "{$p['name']} is sold out."]; }
        $bySeller[$p['seller_id']][] = [$p, $qty, mb_substr(trim((string)($l['comment'] ?? '')), 0, 300), (string)($l['size'] ?? ''), (string)($l['color'] ?? '')];
    }
    if (!$bySeller) { return [0, 'Nothing to order.']; }

    $name    = trim($buyer['first_name'] . ' ' . ($buyer['middle_name'] ?? '') . ' ' . $buyer['last_name']);
    $address = trim($buyer['address'] . ', ' . $buyer['city'] . ' ' . ($buyer['postal_code'] ?? ''));
    $contact = $buyer['email'] ?: ($buyer['phone'] ?? '');

    // Shipping fee and vouchers for each shop's order (worked out before anything is saved)
    $plans = []; $claimed = [];
    foreach ($bySeller as $sellerId => $items) {
        $sub = 0.0;
        foreach ($items as $it) { $sub += (float)$it[0]['price'] * $it[1]; }
        $ship = shipping_fee_for($sub); $disc = 0.0; $use = []; $notes = [];
        $pk = (isset($pick[$sellerId]) && is_array($pick[$sellerId])) ? $pick[$sellerId] : $pick;   // vouchers chosen for this shop
        foreach (['shipping', 'discount'] as $kind) {
            $cid = (int)($pk[$kind] ?? 0);
            if (!$cid || isset($claimed[$cid])) { continue; }
            $cs = db()->prepare('SELECT * FROM coupons WHERE id = ? AND user_id = ?');
            $cs->execute([$cid, $buyerId]);
            $c = $cs->fetch();
            $rightKind = $c && (($kind === 'shipping') === ($c['type'] === 'shipping'));
            if (!$c || !$rightKind || !coupon_usable($c, $sub)) {
                if (count($bySeller) === 1) { return [0, 'That voucher cannot be used for this order. You may need to spend more, or it expired.']; }
                continue;
            }
            if ($kind === 'shipping') { if ($ship <= 0) { continue; } $ship = 0.0; }
            else { $disc = coupon_discount($c, $sub); if ($disc <= 0) { continue; } }
            $use[] = (int)$c['id']; $notes[] = $c['label']; $claimed[$cid] = true;
        }
        $plans[$sellerId] = [$ship, $disc, $use, implode('; ', $notes)];
    }

    $newIds = [];
    db()->beginTransaction();
    try {
        foreach ($bySeller as $sellerId => $items) {
            [$ship, $disc, $use, $note] = $plans[$sellerId];
            db()->prepare('INSERT INTO orders (buyer_id, seller_id, status, payment_method, ship_name, ship_address, ship_contact, shipping_fee, discount, voucher_note, created_at) VALUES (?,?,"pending","COD",?,?,?,?,?,?,?)')
                ->execute([$buyerId, $sellerId, $name, $address, $contact, $ship, $disc, $note, date('Y-m-d H:i:s')]);
            $oid = (int)db()->lastInsertId();
            $newIds[$sellerId] = $oid;
            foreach ($items as [$p, $qty, $comment, $size, $color]) {
                db()->prepare('INSERT INTO order_items (order_id, product_id, name, price, quantity, comment, size, color) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$oid, $p['id'], $p['name'], $p['price'], $qty, $comment ?: null, $size, $color]);
            }
            foreach ($use as $cid) {
                $u = db()->prepare('UPDATE coupons SET used_order_id = ? WHERE id = ? AND used_order_id IS NULL');
                $u->execute([$oid, $cid]);
                if ($u->rowCount() !== 1) { throw new RuntimeException('voucher used'); }
            }
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) { db()->rollBack(); }
        return [0, $e->getMessage() === 'voucher used' ? 'That voucher was already used.' : 'Could not place the order. Please try again.'];
    }
    foreach ($newIds as $sid => $oid) { notify((int)$sid, "New order #$oid from $name. Open Orders to approve it.", 'staff.php#orders'); }
    return [count($bySeller), ''];
}

function client_menu(): array {
    return ['Dashboard' => 'client.php', 'Shop products' => 'shop.php', 'My cart' => 'cart.php', 'My orders' => 'orders.php',
            'Book a service' => 'providers.php', 'My bookings' => 'bookings.php', 'My vouchers' => 'vouchers.php', 'People' => 'people.php', 'My ID' => 'id-upload.php', 'My profile' => 'profile.php', 'Become a provider' => 'apply.php'];
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

// =====================================================================
// ID DOCUMENT CHECK (registration) + menu for buyers
// =====================================================================
const ID_BLUR_MIN  = 70;    // sharpness needed. Higher = stricter (rejects more photos), lower = more forgiving
const ID_MIN_LONG  = 640;   // photo must be at least 640 px on its long side
const ID_MIN_SHORT = 400;   // and 400 px on its short side

// Sharpness score of a photo (variance of the Laplacian). Blurry photos score low. null = could not measure.
function image_sharpness(string $path): ?float {
    if (!function_exists('imagecreatefromstring')) { return null; }          // GD extension not enabled
    $data = @file_get_contents($path);
    $src  = $data === false ? false : @imagecreatefromstring($data);
    if (!$src) { return null; }
    $w = imagesx($src); $h = imagesy($src);
    $scale = min(1, 800 / max($w, $h));                                      // measure at a fixed size (long side 800 px)
    $nw = max(3, (int)round($w * $scale)); $nh = max(3, (int)round($h * $scale));
    $img = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($img, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($src);
    $gray = [];
    for ($y = 0; $y < $nh; $y++) {
        for ($x = 0; $x < $nw; $x++) {
            $c = imagecolorat($img, $x, $y);
            $gray[$y * $nw + $x] = (int)(0.299 * (($c >> 16) & 255) + 0.587 * (($c >> 8) & 255) + 0.114 * ($c & 255));
        }
    }
    imagedestroy($img);
    $sum = 0; $sum2 = 0; $n = 0;
    for ($y = 1; $y < $nh - 1; $y++) {
        for ($x = 1; $x < $nw - 1; $x++) {
            $i = $y * $nw + $x;
            $lap = 4 * $gray[$i] - $gray[$i - 1] - $gray[$i + 1] - $gray[$i - $nw] - $gray[$i + $nw];
            $sum += $lap; $sum2 += $lap * $lap; $n++;
        }
    }
    if ($n === 0) { return null; }
    $mean = $sum / $n;
    return $sum2 / $n - $mean * $mean;
}

// Checks the uploaded ID photo. Returns [extension, error]. error '' = the photo is acceptable.
function check_id_photo(array $f): array {
    $err = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE) { return ['', "Upload a photo of your National ID or Driver's License."]; }
    if ($err !== UPLOAD_ERR_OK)      { return ['', 'The photo could not be uploaded. Try a smaller photo (under 5 MB).']; }
    if ($f['size'] > 5 * 1024 * 1024) { return ['', 'The photo is too big. Maximum is 5 MB.']; }
    $info  = @getimagesize($f['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) { return ['', 'The ID photo must be a JPG, PNG or WEBP image.']; }
    if (max($info[0], $info[1]) < ID_MIN_LONG || min($info[0], $info[1]) < ID_MIN_SHORT) {
        return ['', 'The photo is too small. Take a closer photo that shows the whole ID clearly.'];
    }
    $sharp = image_sharpness($f['tmp_name']);
    if ($sharp === null) { error_log('ID blur check skipped (enable the GD extension in php.ini).'); }
    elseif ($sharp < ID_BLUR_MIN) {
        return ['', 'The photo is blurry. Please upload it again: good light, hold the camera steady, and make sure all the text is readable.'];
    }
    return [$types[$info[2]], ''];
}

// Saves the ID photo in the private folder (not reachable by URL). Returns the file name, or '' on failure.
function save_id_photo(array $f, string $ext): string {
    $dir = __DIR__ . '/../private/ids';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) { return ''; }
    $name = 'id-' . bin2hex(random_bytes(12)) . '.' . $ext;
    return move_uploaded_file($f['tmp_name'], "$dir/$name") ? $name : '';
}
function delete_id_photo(string $name): void {
    if (preg_match('/^id-[a-f0-9]{24}\.(jpg|png|webp)$/', $name)) { @unlink(__DIR__ . '/../private/ids/' . $name); }
}

// Menu for pages every signed-in account can use (cart, orders)
function buyer_menu(): array {
    if (($_SESSION['role'] ?? '') === 'client') { return client_menu(); }
    return ['Dashboard' => 'dashboard.php', 'Shop products' => 'shop.php', 'My cart' => 'cart.php', 'My orders' => 'orders.php', 'Book a service' => 'providers.php', 'My bookings' => 'bookings.php', 'My vouchers' => 'vouchers.php', 'People' => 'people.php'];
}

// =====================================================================
// SERVICE PROVIDERS + BOOKINGS
// =====================================================================
// Every approved service provider (role staff, type service), with store name, about text, categories and jobs done.
function service_providers_sql(): string {
    $about = "(SELECT a.description FROM provider_applications a WHERE a.user_id = u.id AND a.status = 'approved' ORDER BY a.id DESC LIMIT 1)";
    return "SELECT u.id, u.first_name, u.photo, u.service_rate, " . store_expr('u.id', 'u.first_name') . " AS store,
            COALESCE(NULLIF(u.service_about, ''), $about, '') AS about,
            (SELECT GROUP_CONCAT(c.category ORDER BY c.category SEPARATOR ',') FROM provider_categories c WHERE c.user_id = u.id AND c.status = 'approved') AS cats,
            (SELECT COUNT(*) FROM bookings b WHERE b.provider_id = u.id AND b.status = 'completed') AS done_jobs
            FROM users u WHERE u.role = 'staff' AND u.staff_type = 'service'";
}
// The services a provider offers. Older providers without saved categories offer all of them.
function provider_cats(array $sp): array {
    $c = array_values(array_filter(explode(',', (string)($sp['cats'] ?? ''))));
    return $c ?: array_keys(category_options('service'));
}
function booking_slots(): array {
    $out = [];
    for ($h = 8; $h <= 18; $h++) { $out[sprintf('%02d:00:00', $h)] = date('g:i A', strtotime(sprintf('%02d:00', $h))); }
    return $out;
}
function booking_when(array $b): string {
    return date('D, M j, Y', strtotime($b['booking_date'])) . ' at ' . date('g:i A', strtotime($b['booking_time']));
}
// [label, css class]
function booking_badge(string $status, bool $forProvider = false): array {
    $map = ['pending' => [$forProvider ? 'New request' : 'Waiting for the provider', 'badge-pending'], 'confirmed' => ['Confirmed', 'badge-confirmed'],
            'completed' => ['Completed', 'badge-delivered'], 'declined' => ['Declined', 'badge-rejected'], 'cancelled' => ['Cancelled', 'badge-rejected']];
    return $map[$status] ?? [$status, 'badge-pending'];
}

// =====================================================================
// ID REVIEW: automatic text check (optional OCR) + admin approval gate
// =====================================================================
// Reads the text on a photo with Tesseract, if it is installed. null = OCR is not available.
function ocr_id_text(string $image): ?string {
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    if (!function_exists('shell_exec') || in_array('shell_exec', $disabled, true)) { return null; }
    $bin = TESSERACT_PATH !== '' ? TESSERACT_PATH : 'tesseract';
    $cmd = escapeshellarg($bin) . ' ' . escapeshellarg($image) . ' stdout --psm 11 2>&1';
    if (stripos(PHP_OS, 'WIN') === 0) { $cmd = '"' . $cmd . '"'; }
    $out = @shell_exec($cmd);
    if ($out === null || $out === false) { return null; }
    if (strlen($out) < 400 && preg_match('/not recognized|not found|cannot find|No such file/i', $out)) { return null; }
    return $out;
}

// Returns an error message, or '' when the photo is fine (or OCR is not installed, so the admin decides).
function check_id_text(string $path, string $first, string $last): string {
    $text = ocr_id_text($path);
    if ($text === null) { return ''; }
    $words = array_values(array_filter(preg_split('/\s+/', strtoupper(preg_replace('/[^A-Za-z0-9]+/', ' ', $text))), fn($w) => strlen($w) >= 2));
    if (count($words) < 8) { return "We could not read enough text on this photo. Upload a clear photo of your own National ID or Driver's License."; }
    $has = function (string $name) use ($words): bool {
        foreach (preg_split('/[\s\-]+/', strtoupper($name)) as $tok) {
            $tok = preg_replace('/[^A-Z0-9]/', '', $tok);
            if (strlen($tok) < 2) { continue; }
            foreach ($words as $w) {
                if (strlen($tok) === 2) { if ($w === $tok) { return true; } continue; }
                similar_text($w, $tok, $pct);
                if ($pct >= 80) { return true; }
            }
        }
        return false;
    };
    if (!$has($last) || !$has($first)) {
        return 'We could not find your name on this document. Use your own ID, make sure the name matches what you typed above, and that the text is clear.';
    }
    return '';
}

// Clients can order, book and apply only after an admin approved their ID. Staff and admin accounts are not blocked.
function id_approved(): bool {
    if (empty($_SESSION['user_id'])) { return false; }
    if (($_SESSION['role'] ?? '') !== 'client') { return true; }
    static $cache = [];
    $uid = (int)$_SESSION['user_id'];
    if (!isset($cache[$uid])) {
        ensure_shop_tables();
        $st = db()->prepare('SELECT id_status FROM users WHERE id = ?');
        $st->execute([$uid]);
        $cache[$uid] = (string)$st->fetchColumn();
    }
    return $cache[$uid] === 'approved';
}
const ID_BLOCK_MESSAGE = 'Your ID must be approved by an admin first. You can browse now, and order or book once it is approved.';

// The strip shown under the header while the ID is waiting or was rejected
function id_banner(): string {
    if (($_SESSION['role'] ?? '') !== 'client' || empty($_SESSION['user_id'])) { return ''; }
    try {
        ensure_shop_tables();
        $st = db()->prepare('SELECT id_status, id_note FROM users WHERE id = ?');
        $st->execute([$_SESSION['user_id']]);
        $r = $st->fetch();
        if ($r && $r['id_status'] === 'pending') {
            return '<div class="id-banner">&#128260; Your ID is waiting for an admin to review it. You can browse now, and order, book or apply once it is approved.</div>';
        }
        if ($r && $r['id_status'] === 'rejected') {
            return '<div class="id-banner bad">Your ID was rejected' . ($r['id_note'] ? ': ' . htmlspecialchars($r['id_note']) : '.')
                 . ' <a href="id-upload.php">Upload a valid ID</a> to continue.</div>';
        }
    } catch (Throwable $e) {}
    return '';
}

function notify_admins(string $message, string $link = ''): void {
    try {
        foreach (db()->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN) as $aid) { notify((int)$aid, $message, $link); }
    } catch (Throwable $e) {}
}

// =====================================================================
// DELETE ACCOUNT (clients and staff delete their own account; admins cannot)
// =====================================================================
// Things that must be finished first. An empty list means the account can be deleted.
function account_blockers(int $uid): array {
    $count = function (string $sql) use ($uid): int { $q = db()->prepare($sql); $q->execute([$uid]); return (int)$q->fetchColumn(); };
    $pl = fn(int $n, string $word) => $n . ' ' . $word . ($n === 1 ? '' : 's');
    $out = [];
    if ($n = $count('SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND status = "approved"'))     { $out[] = 'You have ' . $pl($n, 'order') . ' on the way. Tap "Order received" on My orders first.'; }
    if ($n = $count('SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status = "approved"'))    { $out[] = 'You have ' . $pl($n, 'approved order') . ' that ' . ($n === 1 ? 'is' : 'are') . ' not delivered yet. Finish delivering ' . ($n === 1 ? 'it' : 'them') . ' first.'; }
    if ($n = $count('SELECT COUNT(*) FROM bookings WHERE client_id = ? AND status = "confirmed"'))   { $out[] = 'You have ' . $pl($n, 'confirmed booking') . '. Cancel ' . ($n === 1 ? 'it' : 'them') . ' under My bookings first.'; }
    if ($n = $count('SELECT COUNT(*) FROM bookings WHERE provider_id = ? AND status = "confirmed"')) { $out[] = 'You have ' . $pl($n, 'confirmed job') . '. Mark ' . ($n === 1 ? 'it' : 'them') . ' as completed first.'; }
    return $out;
}

// Deletes the account and everything that belongs to it (cascades in the database), then removes its files.
// Waiting orders and bookings are cancelled and the other person is told.
function delete_account(int $uid): void {
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$uid]);
    $u = $st->fetch();
    if (!$u || $u['role'] === 'admin') { throw new RuntimeException('This account cannot be deleted here.'); }
    $name = trim($u['first_name'] . ' ' . $u['last_name']);

    $pi = db()->prepare('SELECT image FROM products WHERE seller_id = ? AND image <> ""');
    $pi->execute([$uid]);
    $productImages = $pi->fetchAll(PDO::FETCH_COLUMN);

    db()->beginTransaction();
    try {
        $rows = function (string $sql) use ($uid): array { $q = db()->prepare($sql); $q->execute([$uid]); return $q->fetchAll(); };
        foreach ($rows('SELECT id, seller_id AS other FROM orders WHERE buyer_id = ? AND status = "pending"') as $r) {
            notify((int)$r['other'], "Order #{$r['id']} was withdrawn because $name closed their account.", 'staff.php#orders');
        }
        foreach ($rows('SELECT id, buyer_id AS other FROM orders WHERE seller_id = ? AND status = "pending"') as $r) {
            notify((int)$r['other'], "Your order #{$r['id']} was cancelled because the shop closed its account.", 'orders.php');
        }
        foreach ($rows('SELECT id, provider_id AS other FROM bookings WHERE client_id = ? AND status = "pending"') as $r) {
            notify((int)$r['other'], "Booking request #{$r['id']} was withdrawn because $name closed their account.", 'staff.php#jobs');
        }
        foreach ($rows('SELECT id, client_id AS other FROM bookings WHERE provider_id = ? AND status = "pending"') as $r) {
            notify((int)$r['other'], "Your booking request #{$r['id']} was cancelled because the provider closed their account.", 'bookings.php');
        }
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) { db()->rollBack(); }
        throw $e;
    }
    delete_avatar((string)($u['photo'] ?? ''));
    delete_id_photo((string)($u['id_doc_file'] ?? ''));
    foreach ($productImages as $img) { delete_product_image((string)$img); }
}

// Three-step progress bar for the ID review: uploaded -> admin review -> approved
function id_progress_html(string $status): string {
    $s2 = $status === 'approved' ? 'done' : ($status === 'rejected' ? 'bad' : 'current');
    $s3 = $status === 'approved' ? 'done' : '';
    return '<ol class="id-steps" aria-label="ID verification progress">'
         . '<li class="done"><span>&#10003;</span> ID uploaded</li>'
         . '<li class="' . $s2 . '"><span>' . ($s2 === 'done' ? '&#10003;' : ($s2 === 'bad' ? '&#10007;' : '2')) . '</span> Admin review</li>'
         . '<li class="' . $s3 . '"><span>' . ($s3 === 'done' ? '&#10003;' : '3') . '</span> Approved</li></ol>';
}

// A small green check shown next to a verified client's name
function verified_badge(): string {
    return (($_SESSION['role'] ?? '') === 'client' && id_approved())
        ? ' <span class="verified-check" title="ID verified" aria-label="ID verified">&#10003;</span>' : '';
}

// =====================================================================
// FOLLOWING (shops, providers and clients can follow each other)
// =====================================================================
function is_following(int $otherId): bool {
    if (empty($_SESSION['user_id'])) { return false; }
    $st = db()->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?');
    $st->execute([$_SESSION['user_id'], $otherId]);
    return (bool)$st->fetchColumn();
}
function follower_count(int $uid): int  { $st = db()->prepare('SELECT COUNT(*) FROM follows WHERE followed_id = ?'); $st->execute([$uid]); return (int)$st->fetchColumn(); }
function following_count(int $uid): int { $st = db()->prepare('SELECT COUNT(*) FROM follows WHERE follower_id = ?'); $st->execute([$uid]); return (int)$st->fetchColumn(); }

// Shops and providers show their store name. Clients show first name and last initial only.
function public_name(array $u): string {
    if (($u['role'] ?? '') === 'staff') { return (string)($u['store'] ?? $u['first_name']); }
    return trim($u['first_name'] . ' ' . mb_substr((string)$u['last_name'], 0, 1)) . '.';
}
function role_label(array $u): string {
    if (($u['role'] ?? '') === 'staff') { return ($u['staff_type'] ?? '') === 'product' ? '🛍️ Product shop' : '🔧 Service provider'; }
    return 'Client';
}
function notify_followers(int $uid, string $message, string $link = ''): void {
    try {
        $st = db()->prepare('SELECT follower_id FROM follows WHERE followed_id = ?');
        $st->execute([$uid]);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $fid) { notify((int)$fid, $message, $link); }
    } catch (Throwable $e) {}
}

// =====================================================================
// SHIPPING + VOUCHERS (discount, free shipping, earned by buying a lot or spending a lot)
// =====================================================================
function shipping_fee_for(float $subtotal): float {
    return $subtotal >= (float)FREE_SHIPPING_MIN ? 0.0 : (float)SHIPPING_FEE;
}
function coupon_usable(array $c, float $sub): bool {
    if ($c['used_order_id'] !== null) { return false; }
    if ($c['expires_at'] && strtotime($c['expires_at']) < time()) { return false; }
    return $sub >= (float)$c['min_spend'];
}
function coupon_discount(array $c, float $sub): float {
    if ($c['type'] === 'percent') {
        $d = $sub * (float)$c['value'] / 100;
        if ($c['max_discount'] !== null) { $d = min($d, (float)$c['max_discount']); }
    } elseif ($c['type'] === 'fixed') {
        $d = (float)$c['value'];
    } else { return 0.0; }
    return round(min($d, $sub), 2);
}
// Vouchers in a user's wallet. $available = only the ones that can still be used.
function my_coupons(int $uid, bool $available = true): array {
    $sql = 'SELECT * FROM coupons WHERE user_id = ?'; $args = [$uid];
    if ($available) { $sql .= ' AND used_order_id IS NULL AND (expires_at IS NULL OR expires_at > ?)'; $args[] = date('Y-m-d H:i:s'); }
    $st = db()->prepare($sql . ' ORDER BY (used_order_id IS NULL) DESC, expires_at IS NULL, expires_at, id DESC');
    $st->execute($args);
    return $st->fetchAll();
}
// $c = ['type','value','min_spend','max_discount','label','days']. Returns true when a NEW voucher was given.
function give_coupon(int $uid, array $c, ?string $rewardKey = null, ?int $codeId = null, string $source = 'reward'): bool {
    $exp = !empty($c['days']) ? date('Y-m-d H:i:s', strtotime('+' . (int)$c['days'] . ' days')) : null;
    $st = db()->prepare('INSERT IGNORE INTO coupons (user_id, type, value, min_spend, max_discount, label, source, reward_key, code_id, expires_at, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $st->execute([$uid, $c['type'], $c['value'] ?? 0, $c['min_spend'] ?? 0, $c['max_discount'] ?? null, $c['label'], $source, $rewardKey, $codeId, $exp, date('Y-m-d H:i:s')]);
    return $st->rowCount() === 1;
}
// Rewards for buying a lot (orders, items) or spending a lot. Each reward is given once.
function reward_rules(): array {
    $d = (int)REWARD_VALID_DAYS;
    return [
        ['key' => 'orders_3',  'metric' => 'orders', 'target' => 3,   'type' => 'percent',  'value' => 10, 'min_spend' => 20,  'max_discount' => 10,   'days' => $d, 'label' => 'Loyal buyer: 10% off (up to ' . money(10) . ')'],
        ['key' => 'orders_10', 'metric' => 'orders', 'target' => 10,  'type' => 'percent',  'value' => 15, 'min_spend' => 30,  'max_discount' => 25,   'days' => $d, 'label' => 'Super buyer: 15% off (up to ' . money(25) . ')'],
        ['key' => 'items_10',  'metric' => 'items',  'target' => 10,  'type' => 'shipping', 'value' => 0,  'min_spend' => 0,   'max_discount' => null, 'days' => $d, 'label' => 'Bulk buyer: free shipping'],
        ['key' => 'items_30',  'metric' => 'items',  'target' => 30,  'type' => 'fixed',    'value' => 10, 'min_spend' => 50,  'max_discount' => null, 'days' => $d, 'label' => 'Bulk buyer: ' . money(10) . ' off'],
        ['key' => 'spend_100', 'metric' => 'spend',  'target' => 100, 'type' => 'fixed',    'value' => 8,  'min_spend' => 60,  'max_discount' => null, 'days' => $d, 'label' => 'Big spender: ' . money(8) . ' off'],
        ['key' => 'spend_300', 'metric' => 'spend',  'target' => 300, 'type' => 'fixed',    'value' => 25, 'min_spend' => 150, 'max_discount' => null, 'days' => $d, 'label' => 'Big spender: ' . money(25) . ' off'],
        ['key' => 'spend_500', 'metric' => 'spend',  'target' => 500, 'type' => 'shipping', 'value' => 0,  'min_spend' => 0,   'max_discount' => null, 'days' => $d, 'label' => 'VIP: free shipping'],
    ];
}
// What the buyer has completed so far (delivered orders only)
function reward_stats(int $uid): array {
    $st = db()->prepare('SELECT COUNT(DISTINCT o.id) AS orders, COALESCE(SUM(i.quantity), 0) AS items, COALESCE(SUM(i.price * i.quantity), 0) AS spend
                         FROM orders o JOIN order_items i ON i.order_id = o.id WHERE o.buyer_id = ? AND o.status = "delivered"');
    $st->execute([$uid]);
    return array_map('floatval', $st->fetch() ?: ['orders' => 0, 'items' => 0, 'spend' => 0]);
}
function award_rewards(int $uid): void {
    try {
        $stats = reward_stats($uid);
        foreach (reward_rules() as $r) {
            if ($stats[$r['metric']] >= $r['target'] && give_coupon($uid, $r, $r['key'])) {
                notify($uid, '🎁 You earned a voucher: ' . $r['label'] . '. Find it in My vouchers.', 'vouchers.php');
            }
        }
    } catch (Throwable $e) { error_log('award_rewards failed: ' . $e->getMessage()); }
}

// =====================================================================
// FORGOT PASSWORD
// =====================================================================
// Makes a new 6-digit reset code for the reset waiting in the session and emails it to the account.
// Unknown accounts get the same screens (user_id 0) but no email, so nobody can find out who is registered.
function send_password_reset_code(): bool {
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['pw_reset']['code_hash'] = hash('sha256', $code);
    $_SESSION['pw_reset']['expires']   = time() + CODE_MINUTES * 60;
    $_SESSION['pw_reset']['sent_at']   = time();
    $_SESSION['pw_reset']['tries']     = 0;
    unset($_SESSION['dev_reset_code']);
    $r = $_SESSION['pw_reset'];
    if (empty($r['user_id'])) { return true; }
    if (SMTP_USER === '') { $_SESSION['dev_reset_code'] = $code; return true; }       // test mode: show the code on screen
    return send_mail(
        $r['email'],
        'Your ' . SITE_NAME . ' password reset code',
        "Hi {$r['first_name']},\n\nYour password reset code is: $code\n\nIt expires in " . CODE_MINUTES . " minutes. "
        . "If you did not ask to reset your password, ignore this email and your password stays the same.\n\n- " . SITE_NAME
    );
}

// =====================================================================
// CANCEL ORDER (buyer, while the order is waiting or approved but not delivered yet)
// =====================================================================
function cancel_reasons(): array {
    return ['Changed my mind', 'Wrong size, color or item', 'Found a better price', 'Delivery takes too long', 'Ordered by mistake', 'Other'];
}
// Returns '' on success, or an error message.
function cancel_order(int $orderId, int $buyerId, string $reason): string {
    if (!in_array($reason, cancel_reasons(), true)) { return 'Choose a reason for cancelling.'; }
    $st = db()->prepare('SELECT * FROM orders WHERE id = ? AND buyer_id = ? AND status IN ("pending","approved")');
    $st->execute([$orderId, $buyerId]);
    $o = $st->fetch();
    if (!$o) { return 'This order can no longer be cancelled.'; }
    db()->beginTransaction();
    try {
        if ($o['status'] === 'approved') {            // stock was taken when the shop approved it: give it back
            $its = db()->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ? AND product_id IS NOT NULL');
            $its->execute([$orderId]);
            foreach ($its->fetchAll() as $it) {
                db()->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([$it['quantity'], $it['product_id']]);
            }
        }
        $u = db()->prepare('UPDATE orders SET status = "cancelled", cancel_reason = ?, cancelled_at = ? WHERE id = ? AND status IN ("pending","approved")');
        $u->execute([$reason, date('Y-m-d H:i:s'), $orderId]);
        if ($u->rowCount() !== 1) { throw new RuntimeException('changed'); }
        db()->prepare('UPDATE coupons SET used_order_id = NULL WHERE used_order_id = ?')->execute([$orderId]);   // vouchers come back
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) { db()->rollBack(); }
        return 'Could not cancel the order. Please try again.';
    }
    notify((int)$o['seller_id'], "Order #$orderId was cancelled by {$o['ship_name']}. Reason: $reason.", 'staff.php#orders');
    return '';
}
