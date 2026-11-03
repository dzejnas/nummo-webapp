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
- "Fix milestone 2 so it matches the requirements: MySQL schema with at least 5 entities and
  sample data, DAO layer with a base DAO, credentials from a config file."

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
