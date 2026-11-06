-- =====================================================================
-- ServiceHub: complete database
-- phpMyAdmin (http://localhost/phpmyadmin) > Import > choose this file > Go
-- Safe to import again: it only creates what is missing and never deletes data.
-- =====================================================================
CREATE DATABASE IF NOT EXISTS servicehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE servicehub;

-- ---------- Accounts (admin, client, staff) ----------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role          ENUM('admin','client','staff') NOT NULL DEFAULT 'client',
  staff_type    ENUM('service','product') NULL,          -- only used when role = 'staff'
  first_name    VARCHAR(60)  NOT NULL,
  middle_name   VARCHAR(60)  NULL,
  last_name     VARCHAR(60)  NOT NULL,
  email         VARCHAR(120) NULL UNIQUE,
  phone         VARCHAR(20)  NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  address       VARCHAR(255) NOT NULL,
  city          VARCHAR(80)  NOT NULL,
  postal_code   VARCHAR(12)  NULL,
  is_verified   TINYINT(1)   NOT NULL DEFAULT 0,
  photo         VARCHAR(200) NOT NULL DEFAULT '',        -- provider / shop photo (uploads/avatars/...)
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ---------- Profile edits waiting for admin approval ----------
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
);

-- ---------- Clients applying to become a service or product provider ----------
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
);

-- ---------- Categories each provider is approved (or waiting) to sell ----------
CREATE TABLE IF NOT EXISTS provider_categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  category    VARCHAR(60) NOT NULL,
  status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at  DATETIME NOT NULL,
  reviewed_at DATETIME NULL,
  UNIQUE KEY one_per_category (user_id, category),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- Products (owned by a product provider) ----------
CREATE TABLE IF NOT EXISTS products (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seller_id   INT UNSIGNED NOT NULL,
  name        VARCHAR(120) NOT NULL,
  category    VARCHAR(40)  NOT NULL DEFAULT 'Other',
  description TEXT NULL,
  price       DECIMAL(10,2) NOT NULL,
  stock       INT UNSIGNED NOT NULL DEFAULT 0,
  colors      VARCHAR(200) NOT NULL DEFAULT '',          -- comma separated; empty = standard colors for the category
  image       VARCHAR(200) NOT NULL DEFAULT '',          -- product photo (uploads/products/...)
  image       VARCHAR(200) NOT NULL DEFAULT '',          -- photo path, like uploads/products/p1-ab12cd34.jpg
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL,
  FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- Cart (one line per product + size + color) ----------
CREATE TABLE IF NOT EXISTS cart_items (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity   INT UNSIGNED NOT NULL DEFAULT 1,
  comment    VARCHAR(300) NULL,
  size       VARCHAR(60) NOT NULL DEFAULT '',
  color      VARCHAR(40) NOT NULL DEFAULT '',
  UNIQUE KEY one_per_variant (user_id, product_id, size, color),
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ---------- Orders (one per shop; the seller approves or rejects) ----------
CREATE TABLE IF NOT EXISTS orders (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  buyer_id       INT UNSIGNED NOT NULL,
  seller_id      INT UNSIGNED NOT NULL,
  status         ENUM('pending','approved','delivered','rejected') NOT NULL DEFAULT 'pending',
  payment_method VARCHAR(20) NOT NULL DEFAULT 'COD',     -- Cash on Delivery is the only method
  ship_name      VARCHAR(160) NOT NULL,
  ship_address   VARCHAR(300) NOT NULL,
  ship_contact   VARCHAR(140) NOT NULL,
  created_at     DATETIME NOT NULL,
  reviewed_at    DATETIME NULL,
  delivery_days  TINYINT UNSIGNED NULL,                  -- set by the seller when approving
  approved_at    DATETIME NULL,
  delivered_at   DATETIME NULL,                          -- set when the buyer taps "Order received"
  FOREIGN KEY (buyer_id)  REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id   INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  name       VARCHAR(120) NOT NULL,
  price      DECIMAL(10,2) NOT NULL,
  quantity   INT UNSIGNED NOT NULL,
  comment    VARCHAR(300) NULL,
  size       VARCHAR(60) NOT NULL DEFAULT '',
  color      VARCHAR(40) NOT NULL DEFAULT '',
  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- ---------- Product reviews (live right away; only buyers who received the product can post) ----------
CREATE TABLE IF NOT EXISTS reviews (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  order_id   INT UNSIGNED NULL,
  rating     TINYINT UNSIGNED NOT NULL,
  comment    VARCHAR(600) NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY one_review (product_id, user_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
);

-- ---------- Notifications (bell icon in the header) ----------
CREATE TABLE IF NOT EXISTS notifications (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  message    VARCHAR(300) NOT NULL,
  link       VARCHAR(200) NOT NULL DEFAULT '',
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  KEY by_user (user_id, is_read),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================================
-- OPTIONAL: make someone admin (after they register and verify):
--   UPDATE users SET role='admin' WHERE email='you@example.com';
-- Staff are made by approving a "Become a provider" application in the admin panel.
-- Sample products: run database/sample-products.sql after you have a product provider.
-- =====================================================================
