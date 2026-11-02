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
| `user` | Manage own profile, contacts, payments and payment requests; view merchants and categories |
| `admin` | See all data, plus CRUD on users, merchants and categories (Admin menu in the navbar) |

Roles are checked twice: the backend `RoleMiddleware` returns `403`, and the frontend hides
admin pages and buttons from normal users.

## External API

The dashboard's **currency converter** uses the [Frankfurter API](https://frankfurter.dev)
(European Central Bank rates) to show how much an amount is worth in another currency.

- `ExchangeRateService` calls Frankfurter with a 5-second timeout (set in `config.php`).
- Backend endpoints: `GET /api/rates`, `GET /api/rates/convert`, `GET /api/rates/currencies`.
- If Frankfurter is slow or down, the failure is written to `backend/logs/error.log` and the
  client gets `503` with a friendly message. The rest of the app keeps working.

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
5. **Backend API** (from the repository root)
   ```bash
   php -S localhost:8080 -t backend/public backend/public/index.php
   ```
   API: http://localhost:8080/api/users · Swagger: http://localhost:8080/docs/
6. **Frontend** (in a second terminal)
   ```bash
   cd frontend && php -S localhost:8000
   ```
   Open http://localhost:8000. The API address is set in `frontend/js/config.js`.

On Apache, point the document root to `backend/public`; `.htaccess` sends every request to `index.php`.

## Frontend

Single-page app (`frontend/`), every page connected to the REST API — no hard-coded data.
It follows an MVC split: **views** (`views/*.html`) only hold HTML, **controllers** (`js/pages/`)
handle events and rendering, and **services** (`js/services/`) are the only code that talks to the
backend (through `js/api.js`). No view or controller calls `fetch()` directly.

```
js/config.js        API address
js/auth.js          token + logged-in user in localStorage
js/api.js           fetch wrapper: adds the JWT, handles 401/403 — the only code using fetch()
js/services/        one service object per resource (TransactionService, ContactService, ...)
js/pages/           one controller per view: loads data through services, renders, handles forms
js/router.js        hash router with route guards (guest / user / admin)
js/validation.js    client-side form validation
views/*.html        HTML templates
```

### Validation

Every form is validated twice:

- **Client-side** (`js/validation.js`): rules come from the HTML attributes (`required`,
  `type="email"`, `minlength`, `maxlength`, `min`, `max`, `step="0.01"`). Invalid fields are
  marked red with a message, and nothing is sent to the API.
- **Server-side** (`backend/utils/Validator.php` + services): the same rules plus business rules
  (unique email, enough balance, …). A request that skips the frontend still gets a `400`.

## Database and DAO layer

- `sql/nummo_db.sql` — 6 tables with primary and foreign keys, plus sample data.
- `backend/config/Database.php` — one shared PDO connection; credentials come from `config.php`.
- `backend/dao/BaseDao.php` — reusable `getAll`, `getById`, `insert`, `update`, `delete`
  with prepared statements. Only whitelisted columns can be written.
- One DAO per table: `UserDao`, `CategoryDao`, `MerchantDao`, `ContactDao`,
  `TransactionDao`, `PaymentRequestDao` — each adds the queries it needs (joins, filters).

## Authentication, middleware and logging

- **Register / log in:** `POST /api/auth/register`, `POST /api/auth/login`. Passwords are hashed
  with bcrypt. Login returns a JWT (HS256, valid 24 h) signed with `jwt_secret` from `config.php`.
- The frontend stores the token in `localStorage` and sends `Authorization: Bearer <token>` with
  every request (`frontend/js/api.js`). On `401` it logs out and opens the login page; on `403`
  it shows the error message.

Middleware (`backend/middleware/`), all registered in FlightPHP:

| Middleware | What it does |
|---|---|
| `AuthMiddleware` | Verifies the JWT on every protected route; `401` if missing, invalid or expired |
| `RoleMiddleware` | Reusable role check, e.g. `new RoleMiddleware('admin')`; `403` otherwise |
| `RequestLogger` | Writes every request to the info log (method, route, status, user, role, time) |
| `ErrorHandler` | Catches all exceptions, logs them, returns JSON without SQL, paths or stack traces |

Routes add them with `->addMiddleware(loggedIn())` or `->addMiddleware(adminOnly())`.

Logs (not committed):

- `backend/logs/info.log` — every request, logins, registrations, external API calls
  ```
  [2026-11-02 16:25:55] INFO GET /api/transactions?user_id=2 200 user=1 role=admin 2ms
  [2026-11-02 16:25:33] INFO Login: user=2 role=user
  ```
- `backend/logs/error.log` — unhandled exceptions, database errors, failed logins, 401/403, external API failures
  ```
  [2026-11-02 16:25:55] ERROR GET /api/transactions/4 403 user=2 role=user You do not have access to this transaction
  [2026-11-02 16:25:33] ERROR Failed login: email=amina@nummo.com
  ```

## REST API

Architecture: **route → service → DAO → database**. Routes only read the request and return JSON,
services hold validation and business rules, DAOs hold all SQL.

| Resource | Endpoints | Access |
|---|---|---|
| Auth | `POST /api/auth/register`, `POST /api/auth/login` | public |
| My profile | `GET/PUT /api/me`, `GET /api/me/spending` | logged in |
| Users | `GET/POST /api/users`, `GET/PUT/DELETE /api/users/{id}`, `GET /api/users/{id}/spending` | admin |
| Categories | `GET/POST /api/categories`, `GET/PUT/DELETE /api/categories/{id}` | read: logged in, write: admin |
| Merchants | `GET/POST /api/merchants`, `GET/PUT/DELETE /api/merchants/{id}` | read: logged in, write: admin |
| Contacts | `GET/POST /api/contacts`, `GET/PUT/DELETE /api/contacts/{id}` | own data (admin: all) |
| Transactions | `GET/POST /api/transactions`, `GET/PUT/DELETE /api/transactions/{id}` | own data (admin: all) |
| Payment requests | `GET/POST /api/payment-requests`, `GET/PUT/DELETE /api/payment-requests/{id}` | own data (admin: all) |
| Exchange rates | `GET /api/rates`, `GET /api/rates/convert`, `GET /api/rates/currencies` | public |

**Swagger documentation:** http://localhost:8080/docs/ (spec: `backend/docs/openapi.yaml`)

Status codes: `200` OK, `201` created, `400` invalid input, `401` not logged in,
`403` not allowed, `404` not found, `409` conflict
(duplicate, or changing a completed payment), `500` unexpected error, `503` exchange rate API down.
Errors look like `{"error": "message"}`; unexpected errors are logged but never show SQL or file paths.

### Business rules

- A payment goes to **either** a user or a merchant. Completed payments move money between balances
  inside one DB transaction; the sender needs enough balance.
- Only pending transactions can be changed; completed ones cannot be edited or deleted.
- Paying a payment request creates a completed transaction from the payer to the requester.
- Emails, category names and merchant names are unique; users cannot add themselves as contacts
  or send money to themselves.
- Users only see their own contacts, payments and requests. Only the payer can pay or decline a
  request; only the requester can change or delete it.

## Deployed application

- **Live app:** _add the link here after deploying (see below)_
- **Swagger:** _live link_ + `/docs/`
- Test accounts: see [Roles and test accounts](#roles-and-test-accounts).

## Deployment

The app runs as **one Docker container** (`Dockerfile`): Apache + PHP serve the frontend at `/`,
the REST API at `/api` and Swagger at `/docs/`. Because everything is on one domain, the frontend
uses the relative API address `/api` (`frontend/js/config.js`) and no CORS is needed.

On the server there is no `config.php`; settings come from environment variables:

| Variable | Example |
|---|---|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | MySQL connection (`DB_NAME=nummo_db`) |
| `JWT_SECRET` | a long random string (e.g. `openssl rand -hex 32`) |
| `RATES_API_URL`, `RATES_API_TIMEOUT` | optional, default Frankfurter and 5 s |
| `PORT` | set by the hosting platform; default 80 |

### Deploying on Railway

1. On [railway.com](https://railway.com) choose **New Project → Deploy from GitHub repo** and pick
   `nummo-webapp`. Railway finds the `Dockerfile` and builds it.
2. In the project, **New → Database → MySQL**.
3. In the app service → **Variables**, add:
   ```
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_USER=${{MySQL.MYSQLUSER}}
   DB_PASS=${{MySQL.MYSQLPASSWORD}}
   DB_NAME=nummo_db
   JWT_SECRET=<long random string>
   ```
4. Import the database. In the MySQL service → **Connect**, copy the public host and port, then:
   ```bash
   docker run --rm -i mysql:8.4 mysql -h <host> -P <port> -u root -p<password> < sql/nummo_db.sql
   ```
   (or `mysql -h <host> -P <port> -u root -p < sql/nummo_db.sql` with a local MySQL client).
5. App service → **Settings → Networking → Generate Domain**. Put that link at the top of this section.

Any other Docker host (DigitalOcean App Platform with the GitHub Student Pack, Render + an external
MySQL, a VPS) works the same way: build the `Dockerfile` and set the variables above.

### Running the production image locally

```bash
docker build -t nummo-app .
docker run -p 8090:80 -e DB_HOST=host.docker.internal -e DB_PORT=3306 -e DB_USER=root \
  -e DB_PASS=<password> -e DB_NAME=nummo_db -e JWT_SECRET=<secret> nummo-app
```
Open http://localhost:8090.

## Status

- [x] Milestone 1 — project structure, static SPA, ERD
- [x] Milestone 2 — database and DAO layer
- [x] Milestone 3 — services, routes, OpenAPI, external API
- [x] Milestone 4 — authentication, middleware, logging, connected frontend
- [x] Milestone 5 — frontend MVC, validations, deployment setup (add the live link after deploying)
