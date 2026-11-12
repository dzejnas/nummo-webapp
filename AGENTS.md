# AGENTS.md — Nummo

Context for AI coding assistants working in this repository. Read this before changing code.

## Project

Nummo is a peer-to-peer payments web app (like Venmo). Users keep a balance, add contacts,
send money to contacts or merchants, request money from other users, and see their spending
by category. Admins manage users, merchants and categories. The dashboard has a currency
converter that uses live exchange rates from the Frankfurter API.

This is a university project (IBU, IT 2001 Web Programming). The code must stay simple enough
that the author can explain every line at an oral defense.

## Tech stack

- Backend: PHP 8.1+, FlightPHP 3 (`flightphp/core`), `firebase/php-jwt`, PDO + MySQL 8. Composer for dependencies.
- Frontend: plain HTML, CSS, JavaScript; Bootstrap 5, Bootstrap Icons, Chart.js (CDN).
- API docs: OpenAPI 3 (`backend/docs/openapi.yaml`) shown with Swagger UI.
- External API: Frankfurter (`https://api.frankfurter.dev/v1`) for exchange rates.

## Folder structure

```
backend/
  config/       Settings.php (reads config.php), Database.php (PDO), config.example.php
  dao/          BaseDao + one DAO per table — the only place with SQL
  services/     One service per entity — validation and business rules
  routes/       FlightPHP routes, one file per entity — HTTP in/out only
  middleware/   AuthMiddleware (JWT), RoleMiddleware, RequestLogger, ErrorHandler, Guards (loggedIn/adminOnly)
  utils/        ApiException, Validator, Logger
  logs/         info.log, error.log (not committed)
  docs/         openapi.yaml
  public/       index.php (front controller), docs/ (Swagger UI)
  bin/          create_admin.php (create an admin / rotate a password)
  tests/        dao_check.php, payments_check.php + race_worker.php (races against real MySQL)
frontend/
  index.html    SPA shell (navbar + <main id="app">)
  js/api.js     the only code that calls fetch(); adds the JWT, handles 401
  js/services/  one object per API resource; pages call these, never Api directly
  js/pages/     one controller per view (Pages.<name>.init())
  js/router.js  hash router with guards (access: guest | user | admin)
  views/        HTML templates   css/   assets/
sql/            schema.sql (schema only), demo_seed.sql (opt-in demo data), migrations/ (+ .rollback.sql)
docs/           ERD, AI_LOG.md
Dockerfile      production image (Apache + PHP): frontend at /, API at /api, Swagger at /docs/
deploy/         apache.conf, api.php (entry for /api), start.sh (uses $PORT)
```

## Architecture rules

1. Request flow is always: **route → service → DAO → database**.
   - Routes never touch a DAO or SQL. They read the request, call a service, return JSON.
   - Services are the only layer that calls DAOs. All validation and business rules live here.
   - DAOs only run SQL. Every query uses PDO prepared statements; never put user input into SQL strings.
2. Services signal errors by throwing `ApiException($message, $httpStatus)`. The global error
   handler turns it into a JSON response. Do not echo errors from services or DAOs.
3. JSON responses: success returns the resource (or a list) directly with 200/201;
   errors return `{"error": "message"}` with 400/401/403/404/409/500/503.
   The error handler in `public/index.php` uses `Flight::jsonHalt()`, because a normal
   `Flight::json()` is not sent once an exception has been thrown (Flight v3).
4. Routes use `Flight::register()` to create one service instance per request.
   Route ids are restricted to numbers: `/api/users/@id:[0-9]+`.
   Every non-public route has `->addMiddleware(loggedIn())` or `->addMiddleware(adminOnly())`.
   The logged-in user is `Flight::get('user')` (id, name, email, role from the JWT); routes pass
   it to services, and services check ownership with `assertCanAccess()` (403).
5. Secrets (DB credentials, JWT secret, API URLs/keys) live only in `backend/config/config.php`,
   which is git-ignored, or in environment variables on the server (`Settings::fromEnvironment()`).
   Add any new setting to `config.example.php` and to the environment list in the README.
6. Frontend is a single-page app: views in `frontend/views/*.html` are loaded by
   `frontend/js/router.js`. No full page reloads. No PHP in the frontend.
   Put user data into HTML only through `UI.escape()`.
   Frontend MVC: views are HTML only; controllers in `js/pages/` never call `fetch()` or `Api`
   directly — they use the service objects in `js/services/`.
   Every form calls `Validation.check(form)` before sending; the backend validates again.
7. Money is stored as `DECIMAL(12,2)` in EUR and handled as exact decimal strings
   (`Validator::amount()` returns e.g. `"12.50"`); never use floats for money.
   Balances only change inside a DB transaction (`TransactionService::record()` expects the caller
   to have started one). Lock order: the row being changed (`getByIdForUpdate`), then users via
   `UserDao::lockForUpdate()` (sorted by id). Debits use `UserDao::debit()` (conditional UPDATE).
   Read state you are about to change only from a locked row, inside the transaction.
   Never throw after `commit()` inside a `try` whose `catch` calls `rollBack()`.
9. `POST /api/transactions` supports `Idempotency-Key` (table `idempotency_keys`, scoped to the
   logged-in user). Users are closed (`closed_at`), never deleted; history uses `ON DELETE RESTRICT`.
   Schema changes go in a new numbered file in `sql/migrations/` with a matching rollback, and
   `sql/schema.sql` is updated to the same result. Run `php backend/tests/payments_check.php`
   after any change to payments.
8. External API calls go through `ExchangeRateService` only: always with a timeout, and every
   failure is logged with `Logger::error()` and returned as a friendly 503.

## Naming conventions

- PHP classes: `PascalCase`, file name = class name (`UserDao.php`, `UserService.php`).
  Linux servers are case-sensitive, so these must match exactly.
- PHP methods and variables: `camelCase`. Database tables and columns: `snake_case`, plural tables.
- Route files: lowercase plural (`routes/users.php`). URLs: `/api/<plural>` and `/api/<plural>/{id}`.
- JS: `camelCase` functions, view files named after the route (`#contacts` → `views/contacts.html`).

## Rules for the AI

- Keep changes small and match the existing style. Do not add frameworks or build tools.
- Do not add AI attribution lines to commits or PR descriptions.
- Update `backend/docs/openapi.yaml` whenever an endpoint changes.
- Never commit `config.php`, logs or `vendor/`.
- Do not name a PHP class `Config` in `backend/config/`: on macOS/Windows `Config.php` and the
  secrets file `config.php` are the same file.
- Do not use `path` as a shell variable name in zsh scripts — it overwrites `$PATH`.
- Explain any non-obvious code in a short comment.
