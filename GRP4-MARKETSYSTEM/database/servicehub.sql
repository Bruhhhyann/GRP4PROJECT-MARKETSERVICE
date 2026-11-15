-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 15, 2026 at 11:55 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `servicehub`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `provider_id` int(10) UNSIGNED NOT NULL,
  `service` varchar(60) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `address` varchar(300) NOT NULL,
  `contact` varchar(140) NOT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `status` enum('pending','confirmed','declined','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `comment` varchar(300) DEFAULT NULL,
  `size` varchar(60) NOT NULL DEFAULT '',
  `color` varchar(40) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `user_id`, `product_id`, `quantity`, `comment`, `size`, `color`) VALUES
(2, 2, 2, 1, NULL, '', '');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('percent','fixed','shipping') NOT NULL,
  `value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_spend` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `label` varchar(140) NOT NULL,
  `source` varchar(20) NOT NULL DEFAULT 'reward',
  `reward_key` varchar(40) DEFAULT NULL,
  `code_id` int(10) UNSIGNED DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `used_order_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_codes`
--

CREATE TABLE `coupon_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `type` enum('percent','fixed','shipping') NOT NULL,
  `value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_spend` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `label` varchar(140) NOT NULL,
  `valid_days` int(10) UNSIGNED NOT NULL DEFAULT 30,
  `max_claims` int(10) UNSIGNED DEFAULT NULL,
  `claims` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

CREATE TABLE `follows` (
  `follower_id` int(10) UNSIGNED NOT NULL,
  `followed_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` varchar(300) NOT NULL,
  `link` varchar(200) NOT NULL DEFAULT '',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 3, 'New order #2 from Bryan  Mendoza. Open Orders to approve it.', 'staff.php#orders', 1, '2026-11-06 03:12:44'),
(2, 2, 'Good news! Your order #2 from OriShoes was approved. It should arrive in about 2 days (by Sun, Nov 8). Tap \"Order received\" when it arrives.', 'orders.php#order-2', 1, '2026-11-06 03:13:11'),
(3, 3, 'Order #2 was received by Bryan  Mendoza. It is now marked as delivered.', 'staff.php#orders', 1, '2026-11-06 03:13:47'),
(4, 2, 'Order #2 is delivered. Please rate the products you received.', 'orders.php#order-2', 1, '2026-11-06 03:13:47'),
(5, 3, 'Order #1 was received by Bryan  Mendoza. It is now marked as delivered.', 'staff.php#orders', 1, '2026-11-09 05:24:12'),
(6, 2, 'Order #1 is delivered. Please rate the products you received.', 'orders.php#order-1', 1, '2026-11-09 05:24:12'),
(8, 2, 'Your booking #1 with Jack off all Trades is confirmed for Mon, Nov 9, 2026 at 1:00 PM.', 'bookings.php', 1, '2026-11-09 05:35:32'),
(9, 2, 'Your booking #1 with Jack off all Trades was marked as completed.', 'bookings.php', 1, '2026-11-09 05:35:37'),
(10, 4, 'New client Bryan Mendoza needs an ID review.', 'admin.php#id-review', 1, '2026-11-10 11:50:17'),
(12, 4, 'New client Bryan Mendoza needs an ID review.', 'admin.php#id-review', 1, '2026-11-10 11:56:31'),
(13, 7, 'Your ID was approved. You can now order, book services and apply as a provider.', 'client.php', 1, '2026-11-10 11:59:11'),
(14, 3, 'New order #3 from Bryan  Mendoza. Open Orders to approve it.', 'staff.php#orders', 1, '2026-11-14 23:22:02');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `buyer_id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','approved','delivered','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `ship_name` varchar(160) NOT NULL,
  `ship_address` varchar(300) NOT NULL,
  `ship_contact` varchar(140) NOT NULL,
  `created_at` datetime NOT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `payment_method` varchar(20) NOT NULL DEFAULT 'COD',
  `delivery_days` tinyint(3) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `voucher_note` varchar(200) NOT NULL DEFAULT '',
  `cancel_reason` varchar(160) NOT NULL DEFAULT '',
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `buyer_id`, `seller_id`, `status`, `ship_name`, `ship_address`, `ship_contact`, `created_at`, `reviewed_at`, `payment_method`, `delivery_days`, `approved_at`, `delivered_at`, `shipping_fee`, `discount`, `voucher_note`, `cancel_reason`, `cancelled_at`) VALUES
(1, 2, 3, 'delivered', 'Bryan  Mendoza', '12898, Santol ST. Homesite, Mabalacat City 2010', 'mendozabryan486@gmail.com', '2026-10-31 09:17:56', '2026-10-31 09:21:07', 'COD', NULL, NULL, '2026-11-09 05:24:12', 0.00, 0.00, '', '', NULL),
(2, 2, 3, 'delivered', 'Bryan  Mendoza', '12898, Santol ST. Homesite, Mabalacat City 2010', 'mendozabryan486@gmail.com', '2026-11-06 03:12:44', '2026-11-06 03:13:11', 'COD', 2, '2026-11-06 03:13:11', '2026-11-06 03:13:47', 0.00, 0.00, '', '', NULL),
(3, 2, 3, 'pending', 'Bryan  Mendoza', '12898, Santol ST. Homesite, Mabalacat City 2010', 'mendozabryan486@gmail.com', '2026-11-14 23:22:02', NULL, 'COD', NULL, NULL, NULL, 3.00, 0.00, '', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `comment` varchar(300) DEFAULT NULL,
  `size` varchar(60) NOT NULL DEFAULT '',
  `color` varchar(40) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `name`, `price`, `quantity`, `comment`, `size`, `color`) VALUES
(1, 1, 13, 'Maxi wrap dress', 46.00, 2, NULL, 'L', ''),
(2, 2, 34, 'Aviator sunglasses', 24.00, 1, NULL, '', 'White'),
(3, 3, 34, 'Aviator sunglasses', 24.00, 1, NULL, '', 'Black');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `category` varchar(40) NOT NULL DEFAULT 'Other',
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `colors` varchar(200) NOT NULL DEFAULT '',
  `image` varchar(200) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `name`, `category`, `description`, `price`, `stock`, `is_active`, `created_at`, `colors`, `image`) VALUES
(1, 3, 'Classic white sneakers', 'Shoes', 'Clean everyday sneakers with a cushioned sole. Sizes 38 to 45.', 55.00, 30, 1, '2026-10-31 16:00:31', '', 'uploads/products/p1-635357fe.jpg'),
(2, 3, 'Running shoes', 'Shoes', 'Lightweight breathable running shoes for road and gym.', 68.00, 20, 1, '2026-10-31 16:00:31', '', 'uploads/products/p2-d60e32a0.jpg'),
(3, 3, 'Leather loafers', 'Shoes', 'Smooth leather slip-on loafers for work and dinners.', 79.00, 14, 1, '2026-10-31 16:00:31', '', 'uploads/products/p3-5b105fae.jpg'),
(4, 3, 'Canvas slip-on shoes', 'Shoes', 'Easy casual slip-ons with a flexible rubber sole.', 32.00, 40, 1, '2026-10-31 16:00:31', '', 'uploads/products/p4-56ccf8b8.jpg'),
(5, 3, 'Hiking boots', 'Shoes', 'Sturdy waterproof boots with strong grip for trails.', 94.00, 10, 1, '2026-10-31 16:00:31', '', 'uploads/products/p5-a34f16ed.jpg'),
(6, 3, 'Cotton crew-neck tee', 'Shirts', 'Soft 100% cotton t-shirt. Available in S, M, L, XL.', 14.00, 80, 1, '2026-10-31 16:00:31', '', 'uploads/products/p6-280932a5.jpg'),
(7, 3, 'Classic polo shirt', 'Shirts', 'Classic fit polo for work or weekends.', 22.00, 45, 1, '2026-10-31 16:00:31', '', 'uploads/products/p7-d4189f93.jpg'),
(8, 3, 'Oxford button-down shirt', 'Shirts', 'Crisp cotton oxford shirt with a button-down collar.', 35.00, 25, 1, '2026-10-31 16:00:31', '', 'uploads/products/p8-65840ddb.jpg'),
(9, 3, 'Graphic print tee', 'Shirts', 'Relaxed fit tee with a bold front print.', 18.00, 60, 1, '2026-10-31 16:00:31', '', 'uploads/products/p9-9f11b01f.jpg'),
(10, 3, 'Linen short-sleeve shirt', 'Shirts', 'Breathable linen shirt that stays cool in hot weather.', 29.00, 30, 1, '2026-10-31 16:00:31', '', 'uploads/products/p10-fc9b49bc.jpg'),
(11, 3, 'Summer floral dress', 'Dresses', 'Light floral dress, perfect for warm days.', 38.00, 25, 1, '2026-10-31 16:00:31', '', 'uploads/products/p11-7a29e04a.jpg'),
(12, 3, 'Little black dress', 'Dresses', 'Simple, elegant black dress for any evening.', 52.00, 15, 1, '2026-10-31 16:00:31', '', 'uploads/products/p12-33c34e1c.jpg'),
(13, 3, 'Maxi wrap dress', 'Dresses', 'Flowy wrap-style maxi dress with an adjustable waist.', 46.00, 16, 1, '2026-10-31 16:00:31', '', 'uploads/products/p13-45dee218.jpg'),
(14, 3, 'Denim shirt dress', 'Dresses', 'Button-front denim dress with a belt.', 41.00, 20, 1, '2026-10-31 16:00:31', '', 'uploads/products/p14-1c60ebc5.jpg'),
(15, 3, 'Casual midi dress', 'Dresses', 'Comfortable midi dress with short sleeves and pockets.', 36.00, 35, 1, '2026-10-31 16:00:31', '', 'uploads/products/p15-a0e85e45.jpg'),
(16, 3, 'Leather tote bag', 'Bags', 'Roomy tote with an inner zip pocket.', 72.00, 12, 1, '2026-10-31 16:00:31', '', 'uploads/products/p16-7bbedeaf.jpg'),
(17, 3, 'Canvas backpack', 'Bags', 'Durable backpack with a padded laptop sleeve.', 39.00, 28, 1, '2026-10-31 16:00:31', '', 'uploads/products/p17-d138e509.jpg'),
(18, 3, 'Crossbody sling bag', 'Bags', 'Compact sling bag with an adjustable strap.', 28.00, 34, 1, '2026-10-31 16:00:31', '', 'uploads/products/p18-82986f3e.jpg'),
(19, 3, 'Laptop messenger bag', 'Bags', 'Water-resistant messenger bag that fits a 15 inch laptop.', 58.00, 16, 1, '2026-10-31 16:00:31', '', 'uploads/products/p19-15c2a269.jpg'),
(20, 3, 'Travel duffel bag', 'Bags', 'Large duffel bag for weekend trips and the gym.', 64.00, 13, 1, '2026-10-31 16:00:31', '', 'uploads/products/p20-1ce3a5de.jpg'),
(21, 3, 'Baseball cap', 'Caps & hats', 'Adjustable cotton cap with a curved brim.', 9.00, 60, 1, '2026-10-31 16:00:31', '', 'uploads/products/p21-62130a2a.jpg'),
(22, 3, 'Bucket hat', 'Caps & hats', 'Lightweight bucket hat with sun protection.', 12.00, 45, 1, '2026-10-31 16:00:31', '', 'uploads/products/p22-7de97807.jpg'),
(23, 3, 'Knit beanie', 'Caps & hats', 'Warm stretchy beanie for cool weather.', 11.00, 50, 1, '2026-10-31 16:00:31', '', 'uploads/products/p23-07a12522.jpg'),
(24, 3, 'Straw sun hat', 'Caps & hats', 'Wide-brim straw hat for the beach and outdoors.', 16.00, 22, 1, '2026-10-31 16:00:31', '', 'uploads/products/p24-59523628.jpg'),
(25, 3, 'Snapback cap', 'Caps & hats', 'Flat-brim snapback with an embroidered logo.', 14.00, 38, 1, '2026-10-31 16:00:31', '', 'uploads/products/p25-b06919d3.jpg'),
(26, 3, 'Minimal wrist watch', 'Watches', 'Slim analog watch with a steel strap.', 45.00, 15, 1, '2026-10-31 16:00:31', '', 'uploads/products/p26-9a7342e6.jpg'),
(27, 3, 'Sport digital watch', 'Watches', 'Water-resistant digital watch with stopwatch and alarm.', 34.00, 26, 1, '2026-10-31 16:00:31', '', 'uploads/products/p27-f6dac215.jpg'),
(28, 3, 'Leather strap analog watch', 'Watches', 'Classic round-face watch with a genuine leather strap.', 62.00, 12, 1, '2026-10-31 16:00:31', '', 'uploads/products/p28-4b783472.jpg'),
(29, 3, 'Steel chronograph watch', 'Watches', 'Stainless steel chronograph with date display.', 89.00, 8, 1, '2026-10-31 16:00:31', '', 'uploads/products/p29-c5641610.jpg'),
(30, 3, 'Smart fitness band', 'Watches', 'Tracks steps, heart rate and sleep. Up to 7 days battery.', 49.00, 30, 1, '2026-10-31 16:00:31', '', 'uploads/products/p30-a481163f.jpg'),
(31, 3, 'Stainless water bottle', 'Other', 'Insulated 750 ml bottle that keeps drinks cold for 24 hours.', 18.00, 70, 1, '2026-10-31 16:00:31', '', 'uploads/products/p31-1e8c2e7e.jpg'),
(32, 3, 'Wireless earbuds', 'Other', 'Bluetooth earbuds with a charging case.', 42.00, 24, 1, '2026-10-31 16:00:31', '', 'uploads/products/p32-8ce7dcdd.jpg'),
(33, 3, 'Shockproof phone case', 'Other', 'Slim protective case with raised edges.', 8.00, 100, 1, '2026-10-31 16:00:31', '', 'uploads/products/p33-7cdec52d.jpg'),
(34, 3, 'Aviator sunglasses', 'Other', 'UV400 metal-frame aviator sunglasses.', 24.00, 35, 1, '2026-10-31 16:00:31', '', 'uploads/products/p34-525c9fcc.jpg'),
(35, 3, 'Leather bifold wallet', 'Other', 'Slim bifold wallet with card slots and a coin pocket.', 26.00, 32, 1, '2026-10-31 16:00:31', '', 'uploads/products/p35-9abca9ca.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `profile_changes`
--

CREATE TABLE `profile_changes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(80) NOT NULL,
  `postal_code` varchar(12) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provider_applications`
--

CREATE TABLE `provider_applications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('service','product') NOT NULL,
  `business_name` varchar(120) NOT NULL,
  `description` varchar(600) NOT NULL,
  `categories` varchar(400) NOT NULL DEFAULT '',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provider_categories`
--

CREATE TABLE `provider_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category` varchar(60) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `provider_categories`
--

INSERT INTO `provider_categories` (`id`, `user_id`, `category`, `status`, `created_at`, `reviewed_at`) VALUES
(8, 3, 'Shoes', 'approved', '2026-11-06 04:42:03', '2026-11-06 04:42:03'),
(9, 3, 'Shirts', 'approved', '2026-11-06 04:42:03', '2026-11-06 04:42:03'),
(10, 3, 'Dresses', 'approved', '2026-11-06 04:42:03', '2026-11-06 04:42:03'),
(11, 3, 'Bags', 'approved', '2026-11-06 04:42:03', '2026-11-06 04:42:03'),
(12, 3, 'Caps & hats', 'approved', '2026-11-06 04:42:03', '2026-11-06 04:42:03'),
(13, 3, 'Watches', 'approved', '2026-11-06 04:42:03', '2026-11-06 04:42:03'),
(14, 3, 'Other', 'approved', '2026-11-06 04:42:03', '2026-11-06 04:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` varchar(600) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role` enum('admin','client','staff') NOT NULL DEFAULT 'client',
  `staff_type` enum('service','product') DEFAULT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(80) NOT NULL,
  `postal_code` varchar(12) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `photo` varchar(200) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_doc_type` varchar(20) NOT NULL DEFAULT '',
  `id_doc_file` varchar(200) NOT NULL DEFAULT '',
  `service_rate` decimal(10,2) DEFAULT NULL,
  `service_about` varchar(600) NOT NULL DEFAULT '',
  `id_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `id_reviewed_at` datetime DEFAULT NULL,
  `id_note` varchar(200) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `staff_type`, `first_name`, `middle_name`, `last_name`, `email`, `phone`, `password_hash`, `address`, `city`, `postal_code`, `is_verified`, `photo`, `created_at`, `id_doc_type`, `id_doc_file`, `service_rate`, `service_about`, `id_status`, `id_reviewed_at`, `id_note`) VALUES
(2, 'client', NULL, 'Bryan', NULL, 'Mendoza', 'mendozabryan486@gmail.com', NULL, '$2y$10$.FDE.3D5OFN/1IDDhT9pfucZXGsiRDhnd3wWJ6ZJtiu0Rywy.3Qru', '12898, Santol ST. Homesite', 'Mabalacat City', '2010', 1, '', '2026-10-31 01:17:11', '', '', NULL, '', 'approved', NULL, ''),
(3, 'staff', 'product', 'LII', NULL, 'YunXiao', 'bryanmendoza6629@gmail.com', NULL, '$2y$10$JI5tVOekHPRplxKSy5.uIOfctSKcQ8L9yopQHFHIkLBIVH5IePgpS', '12898, Santol ST. Homesite', 'Mabalacat City', '2010', 1, '', '2026-10-31 01:18:30', '', '', NULL, '', 'approved', NULL, ''),
(4, 'admin', NULL, '', NULL, '', '9999', NULL, '$2y$12$ARzD/OiUMHVYrCEb85wCSeh/99M0A7rIWN5Mvb1k0TVEAcEs5A/ci', '', '', NULL, 1, '', '2026-10-31 01:34:21', '', '', NULL, '', 'approved', NULL, ''),
(7, 'client', NULL, 'Bryan', NULL, 'Mendoza', 'Shiragakitomura123@gmail.com', NULL, '$2y$10$qkp15HWyIf3bXCrrhz7qqOfGIqLw23e.Vd4tmJxW0lYoflX1qV6q2', '12898, Santol ST. Homesite', 'Mabalacat City', '2010', 1, '', '2026-11-10 10:56:31', 'national_id', 'id-ddb5e7f5ec1bae9f50ca4e55.png', NULL, '', 'approved', '2026-11-10 11:59:11', '');

-- --------------------------------------------------------

--
-- Table structure for table `verification_codes`
--

CREATE TABLE `verification_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `channel` enum('email','phone') NOT NULL,
  `code_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `by_client` (`client_id`),
  ADD KEY `by_provider` (`provider_id`,`booking_date`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `one_per_variant` (`user_id`,`product_id`,`size`,`color`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `one_reward` (`user_id`,`reward_key`),
  ADD UNIQUE KEY `one_code` (`user_id`,`code_id`),
  ADD KEY `by_user` (`user_id`,`used_order_id`);

--
-- Indexes for table `coupon_codes`
--
ALTER TABLE `coupon_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`follower_id`,`followed_id`),
  ADD KEY `by_followed` (`followed_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `by_user` (`user_id`,`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `profile_changes`
--
ALTER TABLE `profile_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `provider_applications`
--
ALTER TABLE `provider_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `provider_categories`
--
ALTER TABLE `provider_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `one_per_category` (`user_id`,`category`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `one_review` (`product_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `verification_codes`
--
ALTER TABLE `verification_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon_codes`
--
ALTER TABLE `coupon_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `profile_changes`
--
ALTER TABLE `profile_changes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `provider_applications`
--
ALTER TABLE `provider_applications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `provider_categories`
--
ALTER TABLE `provider_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `verification_codes`
--
ALTER TABLE `verification_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupons`
--
ALTER TABLE `coupons`
  ADD CONSTRAINT `coupons_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `follows_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `follows_ibfk_2` FOREIGN KEY (`followed_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profile_changes`
--
ALTER TABLE `profile_changes`
  ADD CONSTRAINT `profile_changes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `provider_applications`
--
ALTER TABLE `provider_applications`
  ADD CONSTRAINT `provider_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `provider_categories`
--
ALTER TABLE `provider_categories`
  ADD CONSTRAINT `provider_categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
