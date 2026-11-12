-- =========================================================
-- Nummo — DEMO data (opt-in, local development only). Run on an empty database after schema.sql:
--   mysql -u root -p nummo_db < sql/demo_seed.sql
-- Never run this on a public deployment: these passwords are published in the README.
-- All balances are simulated demo money.
--
-- admin@nummo.com / Admin123!   (admin)
-- amina@nummo.com / User123!    (user)
-- tarik@nummo.com / User123!    (user)
-- =========================================================
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

-- The 'paid' request has no transaction_id, like requests paid before v2 (shown, no receipt link)
INSERT INTO payment_requests (requester_id, payer_id, amount, note, status) VALUES
(2, 3, 15.00, 'Pizza night', 'pending'),
(3, 2,  8.00, 'Taxi',        'paid');
