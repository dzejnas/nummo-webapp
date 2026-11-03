-- =========================================================
-- Nummo — database schema + sample data
-- Import: mysql -u root -p < sql/nummo_db.sql
-- =========================================================
CREATE DATABASE IF NOT EXISTS nummo_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nummo_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS payment_requests, transactions, contacts, merchants, categories, users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users (two roles: user, admin)
CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(190) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,              -- bcrypt hash
  role       ENUM('user','admin') NOT NULL DEFAULT 'user',
  balance    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  currency   CHAR(3) NOT NULL DEFAULT 'EUR',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Categories (spending types)
CREATE TABLE categories (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 3. Merchants (shops a user can pay)
CREATE TABLE merchants (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL UNIQUE,
  category_id   INT NULL,
  contact_email VARCHAR(190) NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_merchants_category FOREIGN KEY (category_id)
    REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Contacts (who a user can send money to)
CREATE TABLE contacts (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT NOT NULL,
  friend_user_id INT NOT NULL,
  status         ENUM('pending','accepted','blocked') NOT NULL DEFAULT 'pending',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_contacts_user   FOREIGN KEY (user_id)        REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_contacts_friend FOREIGN KEY (friend_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT uq_contacts UNIQUE (user_id, friend_user_id)
) ENGINE=InnoDB;

-- 5. Transactions (user -> user, or user -> merchant)
CREATE TABLE transactions (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  sender_id   INT NOT NULL,
  receiver_id INT NULL,
  merchant_id INT NULL,
  category_id INT NULL,
  amount      DECIMAL(12,2) NOT NULL,
  currency    CHAR(3) NOT NULL DEFAULT 'EUR',
  note        VARCHAR(255) NULL,
  status      ENUM('pending','completed','failed') NOT NULL DEFAULT 'completed',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tx_sender   FOREIGN KEY (sender_id)   REFERENCES users(id)      ON DELETE CASCADE,
  CONSTRAINT fk_tx_receiver FOREIGN KEY (receiver_id) REFERENCES users(id)      ON DELETE CASCADE,
  CONSTRAINT fk_tx_merchant FOREIGN KEY (merchant_id) REFERENCES merchants(id)  ON DELETE SET NULL,
  CONSTRAINT fk_tx_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT chk_tx_amount CHECK (amount > 0)
) ENGINE=InnoDB;

-- 6. Payment requests (one user asks another to pay)
CREATE TABLE payment_requests (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  requester_id INT NOT NULL,
  payer_id     INT NOT NULL,
  amount       DECIMAL(12,2) NOT NULL,
  note         VARCHAR(255) NULL,
  status       ENUM('pending','paid','declined') NOT NULL DEFAULT 'pending',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pr_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_pr_payer     FOREIGN KEY (payer_id)     REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_pr_amount CHECK (amount > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Sample data
-- admin@nummo.com / Admin123!   (admin)
-- amina@nummo.com / User123!    (user)
-- tarik@nummo.com / User123!    (user)
-- ---------------------------------------------------------
INSERT INTO users (name, email, password, role, balance, currency) VALUES
('Nummo Admin', 'admin@nummo.com', '$2y$12$Hx4i94wQAEYrkLxFhyp2uucuo.SXOgXHHCinT6hLVw63XuhfNZ0y2', 'admin', 0.00,   'EUR'),
('Amina Hodzic', 'amina@nummo.com', '$2y$12$Cr4mxZM764fn3ye3uLCumeG0Nnw2BuhgPUTQDyuKotaeMVnk7rfCy', 'user', 250.00, 'EUR'),
('Tarik Begic',  'tarik@nummo.com', '$2y$12$Cr4mxZM764fn3ye3uLCumeG0Nnw2BuhgPUTQDyuKotaeMVnk7rfCy', 'user', 120.00, 'USD');

INSERT INTO categories (name) VALUES
('Food'), ('Transport'), ('Shopping'), ('Bills'), ('Entertainment'), ('Other');

INSERT INTO merchants (name, category_id, contact_email) VALUES
('Bingo Market', 3, 'info@bingo.example'),
('GRAS Sarajevo', 2, 'info@gras.example'),
('Cinema City',  5, 'hello@cinemacity.example');

INSERT INTO contacts (user_id, friend_user_id, status) VALUES
(2, 3, 'accepted'),
(3, 2, 'accepted');

INSERT INTO transactions (sender_id, receiver_id, merchant_id, category_id, amount, currency, note, status) VALUES
(2, 3,    NULL, 1, 12.50, 'EUR', 'Lunch',        'completed'),
(3, 2,    NULL, 5, 20.00, 'EUR', 'Concert ticket', 'completed'),
(2, NULL, 1,    3, 35.90, 'EUR', 'Groceries',    'completed'),
(3, NULL, 2,    2,  1.80, 'EUR', 'Bus ticket',   'completed'),
(2, 3,    NULL, 4, 40.00, 'EUR', 'Internet bill share', 'pending');

INSERT INTO payment_requests (requester_id, payer_id, amount, note, status) VALUES
(2, 3, 15.00, 'Pizza night', 'pending'),
(3, 2,  8.00, 'Taxi',        'paid');
