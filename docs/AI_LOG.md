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
