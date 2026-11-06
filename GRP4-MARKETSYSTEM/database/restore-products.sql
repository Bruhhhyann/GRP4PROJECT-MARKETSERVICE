-- RESTORE PRODUCTS (for testing). Use when the shop is empty or products vanished.
-- phpMyAdmin > servicehub > Import > choose this file > Go.   (Import database/database.sql first if tables are missing.)
-- It: (1) makes a product seller "OriShoes" if no product seller exists  (login orishoes@servicehub.test / 6969)
--     (2) approves all product categories for that seller  (3) un-hides products and refills empty stock
--     (4) adds the 35 sample products (skips any that already exist).
USE servicehub;

INSERT INTO users (role, staff_type, first_name, last_name, email, password_hash, address, city, is_verified)
SELECT 'staff', 'product', 'Ori', 'Shoes', 'orishoes@servicehub.test',
       '$2y$12$ARzD/OiUMHVYrCEb85wCSeh/99M0A7rIWN5Mvb1k0TVEAcEs5A/ci', 'Shop address', 'City', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'staff' AND staff_type = 'product');

INSERT INTO provider_applications (user_id, type, business_name, description, categories, status, created_at, reviewed_at)
SELECT s.id, 'product', 'OriShoes', 'Sample store for testing the shop.', 'Shoes,Shirts,Dresses,Bags,Caps & hats,Watches,Other', 'approved', NOW(), NOW()
FROM (SELECT id FROM users WHERE role = 'staff' AND staff_type = 'product' ORDER BY id LIMIT 1) s
WHERE NOT EXISTS (SELECT 1 FROM provider_applications a WHERE a.user_id = s.id AND a.status = 'approved');

INSERT IGNORE INTO provider_categories (user_id, category, status, created_at, reviewed_at)
SELECT s.id, c.category, 'approved', NOW(), NOW()
FROM (SELECT id FROM users WHERE role = 'staff' AND staff_type = 'product' ORDER BY id LIMIT 1) s
CROSS JOIN (SELECT 'Shoes' AS category UNION ALL SELECT 'Shirts' UNION ALL SELECT 'Dresses' UNION ALL SELECT 'Bags'
            UNION ALL SELECT 'Caps & hats' UNION ALL SELECT 'Watches' UNION ALL SELECT 'Other') c;

UPDATE products SET is_active = 1 WHERE is_active = 0;
UPDATE products SET stock = 20 WHERE stock = 0;

INSERT INTO products (seller_id, name, category, description, price, stock, created_at)
SELECT s.id, v.name, v.category, v.description, v.price, v.stock, NOW()
FROM (SELECT id FROM users WHERE role = 'staff' AND staff_type = 'product' ORDER BY id LIMIT 1) s
CROSS JOIN (
  SELECT 'Classic white sneakers' AS name, 'Shoes' AS category, 'Clean everyday sneakers with a cushioned sole. Sizes 38 to 45.' AS description, 55.00 AS price, 30 AS stock
  UNION ALL SELECT 'Running shoes', 'Shoes', 'Lightweight breathable running shoes for road and gym.', 68.00, 20
  UNION ALL SELECT 'Leather loafers', 'Shoes', 'Smooth leather slip-on loafers for work and dinners.', 79.00, 14
  UNION ALL SELECT 'Canvas slip-on shoes', 'Shoes', 'Easy casual slip-ons with a flexible rubber sole.', 32.00, 40
  UNION ALL SELECT 'Hiking boots', 'Shoes', 'Sturdy waterproof boots with strong grip for trails.', 94.00, 10
  UNION ALL SELECT 'Cotton crew-neck tee', 'Shirts', 'Soft 100% cotton t-shirt. Available in S, M, L, XL.', 14.00, 80
  UNION ALL SELECT 'Classic polo shirt', 'Shirts', 'Classic fit polo for work or weekends.', 22.00, 45
  UNION ALL SELECT 'Oxford button-down shirt', 'Shirts', 'Crisp cotton oxford shirt with a button-down collar.', 35.00, 25
  UNION ALL SELECT 'Graphic print tee', 'Shirts', 'Relaxed fit tee with a bold front print.', 18.00, 60
  UNION ALL SELECT 'Linen short-sleeve shirt', 'Shirts', 'Breathable linen shirt that stays cool in hot weather.', 29.00, 30
  UNION ALL SELECT 'Summer floral dress', 'Dresses', 'Light floral dress, perfect for warm days.', 38.00, 25
  UNION ALL SELECT 'Little black dress', 'Dresses', 'Simple, elegant black dress for any evening.', 52.00, 15
  UNION ALL SELECT 'Maxi wrap dress', 'Dresses', 'Flowy wrap-style maxi dress with an adjustable waist.', 46.00, 18
  UNION ALL SELECT 'Denim shirt dress', 'Dresses', 'Button-front denim dress with a belt.', 41.00, 20
  UNION ALL SELECT 'Casual midi dress', 'Dresses', 'Comfortable midi dress with short sleeves and pockets.', 36.00, 35
  UNION ALL SELECT 'Leather tote bag', 'Bags', 'Roomy tote with an inner zip pocket.', 72.00, 12
  UNION ALL SELECT 'Canvas backpack', 'Bags', 'Durable backpack with a padded laptop sleeve.', 39.00, 28
  UNION ALL SELECT 'Crossbody sling bag', 'Bags', 'Compact sling bag with an adjustable strap.', 28.00, 34
  UNION ALL SELECT 'Laptop messenger bag', 'Bags', 'Water-resistant messenger bag that fits a 15 inch laptop.', 58.00, 16
  UNION ALL SELECT 'Travel duffel bag', 'Bags', 'Large duffel bag for weekend trips and the gym.', 64.00, 13
  UNION ALL SELECT 'Baseball cap', 'Caps & hats', 'Adjustable cotton cap with a curved brim.', 9.00, 60
  UNION ALL SELECT 'Bucket hat', 'Caps & hats', 'Lightweight bucket hat with sun protection.', 12.00, 45
  UNION ALL SELECT 'Knit beanie', 'Caps & hats', 'Warm stretchy beanie for cool weather.', 11.00, 50
  UNION ALL SELECT 'Straw sun hat', 'Caps & hats', 'Wide-brim straw hat for the beach and outdoors.', 16.00, 22
  UNION ALL SELECT 'Snapback cap', 'Caps & hats', 'Flat-brim snapback with an embroidered logo.', 14.00, 38
  UNION ALL SELECT 'Minimal wrist watch', 'Watches', 'Slim analog watch with a steel strap.', 45.00, 15
  UNION ALL SELECT 'Sport digital watch', 'Watches', 'Water-resistant digital watch with stopwatch and alarm.', 34.00, 26
  UNION ALL SELECT 'Leather strap analog watch', 'Watches', 'Classic round-face watch with a genuine leather strap.', 62.00, 12
  UNION ALL SELECT 'Steel chronograph watch', 'Watches', 'Stainless steel chronograph with date display.', 89.00, 8
  UNION ALL SELECT 'Smart fitness band', 'Watches', 'Tracks steps, heart rate and sleep. Up to 7 days battery.', 49.00, 30
  UNION ALL SELECT 'Stainless water bottle', 'Other', 'Insulated 750 ml bottle that keeps drinks cold for 24 hours.', 18.00, 70
  UNION ALL SELECT 'Wireless earbuds', 'Other', 'Bluetooth earbuds with a charging case.', 42.00, 24
  UNION ALL SELECT 'Shockproof phone case', 'Other', 'Slim protective case with raised edges.', 8.00, 100
  UNION ALL SELECT 'Aviator sunglasses', 'Other', 'UV400 metal-frame aviator sunglasses.', 24.00, 36
  UNION ALL SELECT 'Leather bifold wallet', 'Other', 'Slim bifold wallet with card slots and a coin pocket.', 26.00, 32
) v
WHERE NOT EXISTS (SELECT 1 FROM products x WHERE x.seller_id = s.id AND x.name = v.name);
