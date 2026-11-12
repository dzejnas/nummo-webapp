-- =========================================================
-- Nummo — database schema only (no data). Run it on an empty database:
--   mysql -u root -p -e "CREATE DATABASE nummo_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
--   mysql -u root -p nummo_db < sql/schema.sql
-- Demo data is opt-in: sql/demo_seed.sql. Upgrading a v1 database: sql/migrations/.
-- =========================================================

-- 1. Users (two roles: user, admin). Users are closed, never deleted, so history is kept.
CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(190) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,              -- bcrypt hash
  role       ENUM('user','admin') NOT NULL DEFAULT 'user',
  balance    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  currency   CHAR(3) NOT NULL DEFAULT 'EUR',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  closed_at  TIMESTAMP NULL DEFAULT NULL
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

-- 5. Transactions (user -> user, or user -> merchant). Always in EUR.
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
  CONSTRAINT fk_tx_sender   FOREIGN KEY (sender_id)   REFERENCES users(id)      ON DELETE RESTRICT,
  CONSTRAINT fk_tx_receiver FOREIGN KEY (receiver_id) REFERENCES users(id)      ON DELETE RESTRICT,
  CONSTRAINT fk_tx_merchant FOREIGN KEY (merchant_id) REFERENCES merchants(id)  ON DELETE SET NULL,
  CONSTRAINT fk_tx_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT chk_tx_amount CHECK (amount > 0)
) ENGINE=InnoDB;

-- 6. Payment requests (one user asks another to pay)
CREATE TABLE payment_requests (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  requester_id   INT NOT NULL,
  payer_id       INT NOT NULL,
  amount         DECIMAL(12,2) NOT NULL,
  note           VARCHAR(255) NULL,
  status         ENUM('pending','paid','declined') NOT NULL DEFAULT 'pending',
  transaction_id INT NULL,                         -- the payment that settled this request
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pr_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_pr_payer     FOREIGN KEY (payer_id)     REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT uq_pr_transaction UNIQUE (transaction_id),
  CONSTRAINT fk_pr_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE RESTRICT,
  CONSTRAINT chk_pr_amount CHECK (amount > 0)
) ENGINE=InnoDB;

-- 7. Idempotency keys: a retried "send money" request returns the first result instead of paying twice
CREATE TABLE idempotency_keys (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT NOT NULL,                     -- keys are scoped to the logged-in user
  idem_key       VARCHAR(100) NOT NULL,
  request_hash   CHAR(64) NOT NULL,                -- sha256 of the validated payment
  transaction_id INT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_idem_user_key UNIQUE (user_id, idem_key),
  CONSTRAINT fk_idem_user        FOREIGN KEY (user_id)        REFERENCES users(id)        ON DELETE CASCADE,
  CONSTRAINT fk_idem_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB;
