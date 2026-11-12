-- =========================================================
-- Rollback of 001_v2_payment_reliability.sql (back to the v1 schema). Deploy the v1 code with it.
-- Lost on rollback: idempotency keys, request -> transaction links, and the closed flag
-- (closed accounts can log in again in v1). Transactions and requests themselves are kept.
--   mysql -u root -p nummo_db < sql/migrations/001_v2_payment_reliability.rollback.sql
-- =========================================================

DROP TABLE IF EXISTS idempotency_keys;

ALTER TABLE payment_requests DROP FOREIGN KEY fk_pr_transaction;
ALTER TABLE payment_requests DROP INDEX uq_pr_transaction, DROP COLUMN transaction_id;

ALTER TABLE payment_requests DROP FOREIGN KEY fk_pr_requester, DROP FOREIGN KEY fk_pr_payer;
ALTER TABLE payment_requests
  ADD CONSTRAINT fk_pr_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_pr_payer     FOREIGN KEY (payer_id)     REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE transactions DROP FOREIGN KEY fk_tx_sender, DROP FOREIGN KEY fk_tx_receiver;
ALTER TABLE transactions
  ADD CONSTRAINT fk_tx_sender   FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_tx_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE users DROP COLUMN closed_at;
