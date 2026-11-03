# AI usage log

AI tool used throughout: **Claude Code** (Anthropic, model Claude Opus 5.5) in the Claude desktop app.
I gave it the course requirements PDF and my existing repository, reviewed what it produced,
ran it locally, and committed the result myself.

## Milestone 1 — Setup, static frontend, ERD

**What the AI was used for**
- Reviewing my existing repository against the Milestone 1 requirements and listing the gaps.
- Removing dead files (old `api/`, `model/`, `views/` folders, a second `composer.json` that
  required the wrong Flight package, screenshots).
- Rewriting the frontend as one SPA with a single hash router (`js/router.js`) and static views
  for every page: login, register, dashboard, transactions, payment requests, contacts,
  merchants, categories, users, profile.
- Drafting the ERD (`docs/erd.dbml`, `docs/ERD.md`), `AGENTS.md` and the README.

**Most important prompts**
- "Based on this [requirements PDF], can we fix milestone 1 and milestone 2 and continue until
  the end of milestone 3?"
- "Make sure we push all the milestones today with the code."

**Cases where the AI was wrong or produced poor code**
1. *Lost track of staged deletions.* While cleaning the repo, the AI ran `git rm --cached -r backend`
   followed by `git reset`, which unstaged all the deletions it had just made. Nothing was lost,
   but `git status` showed a confusing mix of deleted and untracked files. I noticed it in the
   status output, and the deletions were staged again explicitly before committing.
2. *Duplicate relationship in the ERD.* The first Mermaid ERD had two separate USERS–CONTACTS
   lines ("has" and "is friend in"), which rendered as a cluttered diagram and looked like two
   different relationships. I removed the duplicate and explained the two foreign keys in the
   relationships table instead.

**What I checked myself**
- Every route in the navbar opens its view without a page reload, and the layout works at phone width.

## Milestone 2 — Database and DAO layer

**What the AI was used for**
- Rewriting `sql/nummo_db.sql`: the old file inserted into a `transactions` table it never
  created and had no sample users. The new file has 6 tables with PKs/FKs and sample data
  (test passwords hashed with PHP `password_hash`).
- Creating `config.example.php`, `Settings.php` and `Database.php` so credentials are read from the
  git-ignored `config.php` instead of being hard-coded.
- Rewriting `BaseDao` (column whitelist, prepared statements, DB transactions) and one DAO per table.
- Writing `backend/tests/dao_check.php`, which runs CRUD on every DAO inside a rolled-back transaction.

**Most important prompts**
- "Based on this [requirements PDF], can we fix milestone 1 and milestone 2 and continue until
  the end of milestone 3?" — the AI then planned the schema and DAO layer from the Milestone 2
  requirements (5+ entities with sample data, base DAO, credentials from a config file).

**Cases where the AI was wrong or produced poor code**
1. *CHECK constraint MySQL rejects.* The AI first wanted
   `CHECK (receiver_id IS NOT NULL OR merchant_id IS NOT NULL)` on `transactions`. I tested it on
   MySQL 8.4 and it fails with error 3823, because `merchant_id` has an `ON DELETE SET NULL`
   foreign key and MySQL does not allow such columns in CHECK constraints. The rule
   "a payment goes to a user or a merchant, not both" now lives in `TransactionService`.
2. *File name clash on macOS.* The AI named the settings class `Config.php` in the same folder
   as the secrets file `config.php`. macOS file names are case-insensitive, so writing one file
   overwrote the other. I renamed the class to `Settings` (`Settings.php`).

**What I checked myself**
- Imported `sql/nummo_db.sql` into a clean MySQL 8.4 database.
- `php backend/tests/dao_check.php` — all 30 checks pass.

## Milestone 3 — Services, routes, OpenAPI, external API

**What the AI was used for**
- `utils/`: `ApiException` (error + HTTP status), `Validator` (input checks), `Logger` (info/error log).
- One service per entity on top of a `BaseService`, with the business rules: payments go to a user
  or a merchant, balances move inside a DB transaction, insufficient balance is rejected, completed
  payments cannot be edited or deleted, paying a payment request creates a transaction.
- FlightPHP routes for full CRUD on all six entities, a global error handler and JSON 404.
- `ExchangeRateService` for the Frankfurter API (timeout, error logging, friendly 503) and the
  `/api/rates` endpoints; the dashboard currency converter now calls the backend.
- `backend/docs/openapi.yaml` (34 operations) and the Swagger UI page at `/docs/`.

**Most important prompts**
- "Please make sure we push all the milestones today with the code."
- The AI worked from the Milestone 3 requirements in the PDF: a service per entity, CRUD routes
  with correct status codes, OpenAPI + Swagger, and an external API with timeout and error handling.

**Cases where the AI was wrong or produced poor code**
1. *Error responses were empty.* The first error handler used `Flight::json()`. Every error
   (404, 400, 409) came back as an empty `200` response. Testing with curl showed it; reading
   `Engine.php` in Flight v3 explained why: the response is only sent when a route finishes
   normally. The fix was `Flight::jsonHalt()`, which sends the response immediately.
2. *Swagger page showed API JSON.* With PHP's built-in server, the front controller only let real
   *files* through, so `/docs/` (a folder) was routed to Flight and returned the API root JSON.
   I saw it in the browser and changed the check to also allow folders.
3. *Broken shell script.* While generating route files, the AI used `path` as a loop variable in
   zsh, which overwrote `$PATH`, so `cat` and `ls` stopped working halfway through. The files
   were regenerated with a different variable name and checked.

**What I checked myself**
- Called every endpoint with curl: success cases, 400 validation errors, 404, 409, and that
  balances change correctly (and are rolled back when a payment request cannot be paid).
- Stopped the database: the API returns a generic 500 and the real error is in `error.log`.
- Pointed the rates API at an unreachable address: after the 2-second timeout the API returns 503,
  the failure is in `error.log`, and the other endpoints keep working.
- A script compared the routes in `routes/*.php` with `openapi.yaml`: all 34 are documented.
- Swagger UI loads with no spec errors; the dashboard converter shows live rates.

## Milestone 4 — Authentication, middleware, logging, connected frontend

**What the AI was used for**
- `AuthService` (register, login, JWT with `firebase/php-jwt`), `/api/auth/*` and `/api/me` routes.
- Middleware: `AuthMiddleware` (401), `RoleMiddleware` (403), `RequestLogger` (info log for every
  request) and `ErrorHandler` (JSON errors, error log). Ownership checks in the services so users
  only see and change their own contacts, payments and requests.
- Rewriting the frontend so every page uses the API: `api.js` (JWT, 401/403 handling), one service
  object per resource, one controller per page, route guards and a role-based navbar.
- Updating the OpenAPI spec (bearer auth, 401/403 responses, new endpoints — 39 operations).

**Most important prompts**
- "Please continue with milestone 4 and 5 … let's finish a whole project."

**Cases where the AI was wrong or produced poor code**
1. *Test script, not the code, was wrong.* The first role test showed `401` for every request even
   with a valid token. Calling one endpoint by hand with curl worked, so the problem was the test
   helper: in zsh, `${TOKEN:+-H "Authorization: Bearer $TOKEN"}` is passed as one argument instead
   of two, so curl never sent the header. The tests were rewritten as a bash script with an
   argument array. Lesson: when a test fails, check the test first, not only the code.
2. *Too many error messages after the session expired.* When the token was invalid, every request
   on the page showed its own toast ("Invalid token", "You need to log in first", ...), up to four
   at once. Now `api.js` logs out and shows one "session expired" message, and marks the error as
   handled so `UI.error()` does not show it again.
3. *`Pages` defined too late.* The page controller files are loaded before `router.js`, but the
   `Pages` object was first declared in `router.js`, so `Pages.dashboard = ...` would have failed.
   I moved the declaration to `ui.js`, which loads first.

**What I checked myself**
- curl with three real tokens (admin, two users): 401 without/with a bad token, 403 for admin-only
  routes and other users' data, users cannot change their own role or balance, an admin cannot
  delete their own account. Both logs contain user id and role.
- In the browser: login, dashboard with real data, sending money, adding a contact, requesting
  money, paying a request as the other user, admin pages, the Admin menu hidden for users, and
  being sent to the login page with an expired token.
