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

## Running the frontend locally

```bash
cd frontend
php -S localhost:8000
```

Open http://localhost:8000.

## Status

- [x] Milestone 1 — project structure, static SPA, ERD
- [ ] Milestone 2 — database and DAO layer
- [ ] Milestone 3 — services, routes, OpenAPI, external API
- [ ] Milestone 4 — authentication, middleware, logging, connected frontend
- [ ] Milestone 5 — frontend MVC, validations, deployment
