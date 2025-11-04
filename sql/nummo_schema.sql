-- Nummo DB Schema (Milestone 2)
-- Charset: utf8mb4 / Collation: utf8mb4_unicode_ci


-- Create database (run as admin once)
CREATE DATABASE IF NOT EXISTS nummo_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;


USE nummo_db;


-- Users
DROP TABLE IF EXISTS users;
CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
email VARCHAR(190) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL, -- store hashed passwords
role ENUM('user','admin') NOT NULL DEFAULT 'user',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- Categories (spending types)
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;


-- Merchants (optional on transactions)
DROP TABLE IF EXISTS merchants;
CREATE TABLE merchants (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(120) NOT NULL,
category VARCHAR(80) NULL,
contact_email VARCHAR(190) NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- Contacts (friend graph)
DROP TABLE IF EXISTS contacts;
CREATE TABLE contacts (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
friend_user_id INT NOT NULL,
status ENUM('pending','accepted','blocked') NOT NULL DEFAULT 'pending',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
CONSTRAINT fk_contacts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
CONSTRAINT fk_contacts_friend FOREIGN KEY (friend_user_id) REFERENCES users(id) ON DELETE CASCADE,
CONSTRAINT uq_contacts UNIQUE (user_id, friend_user_id)
) ENGINE=InnoDB;


-- Example transaction
INSERT INTO transactions (sender_id, receiver_id, amount, note, status, category_id)
SELECT u1.id, u2.id, 12.50, 'Lunch', 'completed', c.id
FROM users u1
JOIN users u2 ON u1.email='alice@example.com' AND u2.email='bob@example.com'
JOIN categories c ON c.name='Food'
WHERE u1.id IS NOT NULL AND u2.id IS NOT NULL AND c.id IS NOT NULL;
