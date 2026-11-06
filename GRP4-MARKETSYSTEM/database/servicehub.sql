-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2026 at 04:14 AM
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
(2, 2, 2, 1, NULL, '', ''),
(4, 2, 32, 2, NULL, '', '');

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
(3, 3, 'Order #2 was received by Bryan  Mendoza. It is now marked as delivered.', 'staff.php#orders', 0, '2026-11-06 03:13:47'),
(4, 2, 'Order #2 is delivered. Please rate the products you received.', 'orders.php#order-2', 0, '2026-11-06 03:13:47');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `buyer_id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','approved','delivered','rejected') NOT NULL DEFAULT 'pending',
  `ship_name` varchar(160) NOT NULL,
  `ship_address` varchar(300) NOT NULL,
  `ship_contact` varchar(140) NOT NULL,
  `created_at` datetime NOT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `payment_method` varchar(20) NOT NULL DEFAULT 'COD',
  `delivery_days` tinyint(3) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `buyer_id`, `seller_id`, `status`, `ship_name`, `ship_address`, `ship_contact`, `created_at`, `reviewed_at`, `payment_method`, `delivery_days`, `approved_at`, `delivered_at`) VALUES
(1, 2, 3, 'approved', 'Bryan  Mendoza', '12898, Santol ST. Homesite, Mabalacat City 2010', 'mendozabryan486@gmail.com', '2026-10-31 09:17:56', '2026-10-31 09:21:07', 'COD', NULL, NULL, NULL),
(2, 2, 3, 'delivered', 'Bryan  Mendoza', '12898, Santol ST. Homesite, Mabalacat City 2010', 'mendozabryan486@gmail.com', '2026-11-06 03:12:44', '2026-11-06 03:13:11', 'COD', 2, '2026-11-06 03:13:11', '2026-11-06 03:13:47');

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
(2, 2, 34, 'Aviator sunglasses', 24.00, 1, NULL, '', 'White');

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
(1, 3, 'Classic white sneakers', 'Shoes', 'Clean everyday sneakers with a cushioned sole. Sizes 38 to 45.', 55.00, 30, 1, '2026-10-31 16:00:31', '', ''),
(2, 3, 'Running shoes', 'Shoes', 'Lightweight breathable running shoes for road and gym.', 68.00, 20, 1, '2026-10-31 16:00:31', '', ''),
(3, 3, 'Leather loafers', 'Shoes', 'Smooth leather slip-on loafers for work and dinners.', 79.00, 14, 1, '2026-10-31 16:00:31', '', ''),
(4, 3, 'Canvas slip-on shoes', 'Shoes', 'Easy casual slip-ons with a flexible rubber sole.', 32.00, 40, 1, '2026-10-31 16:00:31', '', ''),
(5, 3, 'Hiking boots', 'Shoes', 'Sturdy waterproof boots with strong grip for trails.', 94.00, 10, 1, '2026-10-31 16:00:31', '', ''),
(6, 3, 'Cotton crew-neck tee', 'Shirts', 'Soft 100% cotton t-shirt. Available in S, M, L, XL.', 14.00, 80, 1, '2026-10-31 16:00:31', '', ''),
(7, 3, 'Classic polo shirt', 'Shirts', 'Classic fit polo for work or weekends.', 22.00, 45, 1, '2026-10-31 16:00:31', '', ''),
(8, 3, 'Oxford button-down shirt', 'Shirts', 'Crisp cotton oxford shirt with a button-down collar.', 35.00, 25, 1, '2026-10-31 16:00:31', '', ''),
(9, 3, 'Graphic print tee', 'Shirts', 'Relaxed fit tee with a bold front print.', 18.00, 60, 1, '2026-10-31 16:00:31', '', ''),
(10, 3, 'Linen short-sleeve shirt', 'Shirts', 'Breathable linen shirt that stays cool in hot weather.', 29.00, 30, 1, '2026-10-31 16:00:31', '', ''),
(11, 3, 'Summer floral dress', 'Dresses', 'Light floral dress, perfect for warm days.', 38.00, 25, 1, '2026-10-31 16:00:31', '', ''),
(12, 3, 'Little black dress', 'Dresses', 'Simple, elegant black dress for any evening.', 52.00, 15, 1, '2026-10-31 16:00:31', '', ''),
(13, 3, 'Maxi wrap dress', 'Dresses', 'Flowy wrap-style maxi dress with an adjustable waist.', 46.00, 16, 1, '2026-10-31 16:00:31', '', ''),
(14, 3, 'Denim shirt dress', 'Dresses', 'Button-front denim dress with a belt.', 41.00, 20, 1, '2026-10-31 16:00:31', '', ''),
(15, 3, 'Casual midi dress', 'Dresses', 'Comfortable midi dress with short sleeves and pockets.', 36.00, 35, 1, '2026-10-31 16:00:31', '', ''),
(16, 3, 'Leather tote bag', 'Bags', 'Roomy tote with an inner zip pocket.', 72.00, 12, 1, '2026-10-31 16:00:31', '', ''),
(17, 3, 'Canvas backpack', 'Bags', 'Durable backpack with a padded laptop sleeve.', 39.00, 28, 1, '2026-10-31 16:00:31', '', ''),
(18, 3, 'Crossbody sling bag', 'Bags', 'Compact sling bag with an adjustable strap.', 28.00, 34, 1, '2026-10-31 16:00:31', '', ''),
(19, 3, 'Laptop messenger bag', 'Bags', 'Water-resistant messenger bag that fits a 15 inch laptop.', 58.00, 16, 1, '2026-10-31 16:00:31', '', ''),
(20, 3, 'Travel duffel bag', 'Bags', 'Large duffel bag for weekend trips and the gym.', 64.00, 13, 1, '2026-10-31 16:00:31', '', ''),
(21, 3, 'Baseball cap', 'Caps & hats', 'Adjustable cotton cap with a curved brim.', 9.00, 60, 1, '2026-10-31 16:00:31', '', ''),
(22, 3, 'Bucket hat', 'Caps & hats', 'Lightweight bucket hat with sun protection.', 12.00, 45, 1, '2026-10-31 16:00:31', '', ''),
(23, 3, 'Knit beanie', 'Caps & hats', 'Warm stretchy beanie for cool weather.', 11.00, 50, 1, '2026-10-31 16:00:31', '', ''),
(24, 3, 'Straw sun hat', 'Caps & hats', 'Wide-brim straw hat for the beach and outdoors.', 16.00, 22, 1, '2026-10-31 16:00:31', '', ''),
(25, 3, 'Snapback cap', 'Caps & hats', 'Flat-brim snapback with an embroidered logo.', 14.00, 38, 1, '2026-10-31 16:00:31', '', ''),
(26, 3, 'Minimal wrist watch', 'Watches', 'Slim analog watch with a steel strap.', 45.00, 15, 1, '2026-10-31 16:00:31', '', ''),
(27, 3, 'Sport digital watch', 'Watches', 'Water-resistant digital watch with stopwatch and alarm.', 34.00, 26, 1, '2026-10-31 16:00:31', '', ''),
(28, 3, 'Leather strap analog watch', 'Watches', 'Classic round-face watch with a genuine leather strap.', 62.00, 12, 1, '2026-10-31 16:00:31', '', ''),
(29, 3, 'Steel chronograph watch', 'Watches', 'Stainless steel chronograph with date display.', 89.00, 8, 1, '2026-10-31 16:00:31', '', ''),
(30, 3, 'Smart fitness band', 'Watches', 'Tracks steps, heart rate and sleep. Up to 7 days battery.', 49.00, 30, 1, '2026-10-31 16:00:31', '', ''),
(31, 3, 'Stainless water bottle', 'Other', 'Insulated 750 ml bottle that keeps drinks cold for 24 hours.', 18.00, 70, 1, '2026-10-31 16:00:31', '', ''),
(32, 3, 'Wireless earbuds', 'Other', 'Bluetooth earbuds with a charging case.', 42.00, 24, 1, '2026-10-31 16:00:31', '', ''),
(33, 3, 'Shockproof phone case', 'Other', 'Slim protective case with raised edges.', 8.00, 100, 1, '2026-10-31 16:00:31', '', ''),
(34, 3, 'Aviator sunglasses', 'Other', 'UV400 metal-frame aviator sunglasses.', 24.00, 35, 1, '2026-10-31 16:00:31', '', ''),
(35, 3, 'Leather bifold wallet', 'Other', 'Slim bifold wallet with card slots and a coin pocket.', 26.00, 32, 1, '2026-10-31 16:00:31', '', '');

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

--
-- Dumping data for table `profile_changes`
--

INSERT INTO `profile_changes` (`id`, `user_id`, `first_name`, `middle_name`, `last_name`, `phone`, `address`, `city`, `postal_code`, `status`, `created_at`, `reviewed_at`, `reviewed_by`) VALUES
(1, 3, 'LII', NULL, 'YunXiao', NULL, '12898, Santol ST. Homesite', 'Mabalacat City', '2010', 'approved', '2026-10-31 02:36:51', '2026-10-31 02:44:06', 4);

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
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `categories` varchar(400) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `provider_applications`
--

INSERT INTO `provider_applications` (`id`, `user_id`, `type`, `business_name`, `description`, `status`, `created_at`, `reviewed_at`, `reviewed_by`, `categories`) VALUES
(1, 3, 'product', 'OriShoes', 'Original branded shoes', 'approved', '2026-10-31 08:40:55', '2026-10-31 08:41:24', 4, '');

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
(1, 3, 'Shoes', 'approved', '2026-10-31 08:50:16', '2026-10-31 08:50:16'),
(2, 3, 'Shirts', 'approved', '2026-10-31 08:50:16', '2026-10-31 08:50:16'),
(3, 3, 'Dresses', 'approved', '2026-10-31 08:50:16', '2026-10-31 08:50:16'),
(4, 3, 'Bags', 'approved', '2026-10-31 08:50:16', '2026-10-31 08:50:16'),
(5, 3, 'Caps & hats', 'approved', '2026-10-31 08:50:16', '2026-10-31 08:50:16'),
(6, 3, 'Watches', 'approved', '2026-10-31 08:50:16', '2026-10-31 08:50:16'),
(7, 3, 'Other', 'approved', '2026-10-31 08:50:16', '2026-10-31 08:50:16');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo` varchar(200) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `staff_type`, `first_name`, `middle_name`, `last_name`, `email`, `phone`, `password_hash`, `address`, `city`, `postal_code`, `is_verified`, `created_at`, `photo`) VALUES
(2, 'client', NULL, 'Bryan', NULL, 'Mendoza', 'mendozabryan486@gmail.com', NULL, '$2y$10$.FDE.3D5OFN/1IDDhT9pfucZXGsiRDhnd3wWJ6ZJtiu0Rywy.3Qru', '12898, Santol ST. Homesite', 'Mabalacat City', '2010', 1, '2026-10-31 01:17:11', ''),
(3, 'staff', 'product', 'LII', NULL, 'YunXiao', 'bryanmendoza6629@gmail.com', NULL, '$2y$10$JI5tVOekHPRplxKSy5.uIOfctSKcQ8L9yopQHFHIkLBIVH5IePgpS', '12898, Santol ST. Homesite', 'Mabalacat City', '2010', 1, '2026-10-31 01:18:30', ''),
(4, 'admin', NULL, '', NULL, '', '9999', NULL, '$2y$12$ARzD/OiUMHVYrCEb85wCSeh/99M0A7rIWN5Mvb1k0TVEAcEs5A/ci', '', '', NULL, 1, '2026-10-31 01:34:21', '');

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
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `one_per_variant` (`user_id`,`product_id`,`size`,`color`),
  ADD KEY `product_id` (`product_id`);

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
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `provider_categories`
--
ALTER TABLE `provider_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `verification_codes`
--
ALTER TABLE `verification_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `verification_codes`
--
ALTER TABLE `verification_codes`
  ADD CONSTRAINT `verification_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
