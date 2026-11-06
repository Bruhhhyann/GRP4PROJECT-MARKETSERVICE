-- Repairs or creates the admin account:  login 9999  /  password 6969
-- phpMyAdmin > servicehub > Import > choose this file > Go   (or paste it in the SQL tab)
USE servicehub;

UPDATE users
SET role = 'admin', is_verified = 1,
    password_hash = '$2y$12$ARzD/OiUMHVYrCEb85wCSeh/99M0A7rIWN5Mvb1k0TVEAcEs5A/ci'
WHERE email = '9999' OR phone = '9999';

INSERT INTO users (role, first_name, last_name, email, password_hash, address, city, is_verified)
SELECT 'admin', 'Admin', 'User', '9999',
       '$2y$12$ARzD/OiUMHVYrCEb85wCSeh/99M0A7rIWN5Mvb1k0TVEAcEs5A/ci',
       'Admin address', 'City', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '9999' OR phone = '9999');
