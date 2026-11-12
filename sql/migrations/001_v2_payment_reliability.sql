-- =========================================================
-- Nummo v2 — forward migration for a database created with the v1 sql/nummo_db.sql.
-- Keeps all existing data. Back up first, then run it once:
--   mysqldump -u root -p nummo_db > nummo_db_before_v2.sql
--   mysql -u root -p nummo_db < sql/migrations/001_v2_payment_reliability.sql
-- Rollback: sql/migrations/001_v2_payment_reliability.rollback.sql
-- =========================================================

-- Users are closed instead of deleted, so payment history is kept
ALTER TABLE users ADD COLUMN closed_at TIMESTAMP NULL DEFAULT NULL;

-- Removing a user must never remove their transactions or payment requests
ALTER TABLE transactions DROP FOREIGN KEY fk_tx_sender, DROP FOREIGN KEY fk_tx_receiver;
ALTER TABLE transactions
  ADD CONSTRAINT fk_tx_sender   FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_tx_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE RESTRICT;

ALTER TABLE payment_requests DROP FOREIGN KEY fk_pr_requester, DROP FOREIGN KEY fk_pr_payer;
ALTER TABLE payment_requests
  ADD CONSTRAINT fk_pr_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_pr_payer     FOREIGN KEY (payer_id)     REFERENCES users(id) ON DELETE RESTRICT;

-- Link from a paid request to the transaction that settled it. Requests paid before v2 keep NULL
-- (there is no verified link for them) and are still shown.
ALTER TABLE payment_requests
  ADD COLUMN transaction_id INT NULL AFTER status,
  ADD CONSTRAINT uq_pr_transaction UNIQUE (transaction_id),
  ADD CONSTRAINT fk_pr_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE RESTRICT;

CREATE TABLE idempotency_keys (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT NOT NULL,
  idem_key       VARCHAR(100) NOT NULL,
  request_hash   CHAR(64) NOT NULL,
  transaction_id INT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_idem_user_key UNIQUE (user_id, idem_key),
  CONSTRAINT fk_idem_user        FOREIGN KEY (user_id)        REFERENCES users(id)        ON DELETE CASCADE,
  CONSTRAINT fk_idem_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB;
