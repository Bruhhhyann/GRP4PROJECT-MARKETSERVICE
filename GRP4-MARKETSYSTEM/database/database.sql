-- Import this in phpMyAdmin (http://localhost/phpmyadmin) > Import
CREATE DATABASE IF NOT EXISTS servicehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE servicehub;

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
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Accounts are only inserted AFTER the emailed code is verified, so there is no pending table.

-- Make someone admin or staff AFTER they register and verify:
-- UPDATE users SET role='admin' WHERE email='you@example.com';
-- UPDATE users SET role='staff', staff_type='service' WHERE email='worker@example.com';
-- UPDATE users SET role='staff', staff_type='product' WHERE email='seller@example.com';

-- Profile edits waiting for admin approval (the website also creates this automatically).
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

-- Clients applying to become a service or product provider (the website also creates this automatically).
CREATE TABLE IF NOT EXISTS provider_applications (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  type          ENUM('service','product') NOT NULL,
  business_name VARCHAR(120) NOT NULL,
  description   VARCHAR(600) NOT NULL,
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at    DATETIME NOT NULL,
  reviewed_at   DATETIME NULL,
  reviewed_by   INT UNSIGNED NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
