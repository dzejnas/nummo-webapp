# Nummo — Peer-to-Peer Payments

Nummo is a single-page web app for sending and requesting money between friends and paying
merchants. Users keep a balance, manage contacts, see their spending by category, and convert
amounts between currencies using live exchange rates. Admins manage users, merchants and categories.

IBU · IT 2001 Web Programming · Džejna Sejfić

## Tech stack

| Layer | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript, Bootstrap 5, Chart.js — single-page app |
| Backend | PHP 8.1+, FlightPHP 3, PDO |
| Database | MySQL 8 |
| API docs | OpenAPI 3 + Swagger UI |
| External API | [Frankfurter](https://frankfurter.dev) exchange rates |

## Entities and relationships

Six tables (full diagram in [docs/ERD.md](docs/ERD.md)):

- **users** — account, role (`user` / `admin`), balance and main currency
- **categories** — spending categories (Food, Transport, …)
- **merchants** — shops a user can pay; each belongs to a category
- **contacts** — links a user to another user (`pending`, `accepted`, `blocked`)
- **transactions** — a payment from a sender to a user **or** a merchant, with a category
- **payment_requests** — one user asks another user to pay an amount

## Roles

| Role | Can do |
|---|---|
| `user` | Manage own profile, contacts, transactions and payment requests; view merchants and categories |
| `admin` | Everything a user can, plus CRUD on users, merchants and categories |

## External API

The dashboard's **currency converter** uses the Frankfurter API (European Central Bank rates)
to show how much an amount is worth in another currency.

## Project structure

```
backend/    FlightPHP REST API (config, dao, services, routes, middleware, logs, docs, public)
frontend/   SPA (index.html, css, js, views, assets)
sql/        database schema + sample data
docs/       ERD and AI usage log
```

## Roles and test accounts

| Role | Email | Password |
|---|---|---|
| admin | admin@nummo.com | Admin123! |
| user | amina@nummo.com | User123! |
| user | tarik@nummo.com | User123! |

## Running locally

Requirements: PHP 8.1+, Composer, MySQL 8.

1. **Database** — import the schema and sample data (creates the `nummo_db` database):
   ```bash
   mysql -u root -p < sql/nummo_db.sql
   ```
2. **Configuration** — copy the template and fill in your database credentials:
   ```bash
   cp backend/config/config.example.php backend/config/config.php
   ```
3. **Dependencies**
   ```bash
   cd backend && composer install
   ```
4. **Check the DAO layer** — runs CRUD on every table and rolls it back:
   ```bash
   php backend/tests/dao_check.php
   ```
5. **Frontend**
   ```bash
   cd frontend && php -S localhost:8000
   ```
   Open http://localhost:8000.

## Database and DAO layer

- `sql/nummo_db.sql` — 6 tables with primary and foreign keys, plus sample data.
- `backend/config/Database.php` — one shared PDO connection; credentials come from `config.php`.
- `backend/dao/BaseDao.php` — reusable `getAll`, `getById`, `insert`, `update`, `delete`
  with prepared statements. Only whitelisted columns can be written.
- One DAO per table: `UserDao`, `CategoryDao`, `MerchantDao`, `ContactDao`,
  `TransactionDao`, `PaymentRequestDao` — each adds the queries it needs (joins, filters).

## Status

- [x] Milestone 1 — project structure, static SPA, ERD
- [x] Milestone 2 — database and DAO layer
- [ ] Milestone 3 — services, routes, OpenAPI, external API
- [ ] Milestone 4 — authentication, middleware, logging, connected frontend
- [ ] Milestone 5 — frontend MVC, validations, deployment
