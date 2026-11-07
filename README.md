# udTime 2.0

A time tracker for work periods, breaks, absences and projects, with week and month balances.

| Folder | What it is |
| --- | --- |
| [backend/](backend/) | Python REST API (FastAPI) on the existing MySQL schema in [udtime.sql](udtime.sql) |
| [frontend/](frontend/) | Python web GUI (Jinja2 + HTMX) that uses the backend API |
| [app/](app/) | The legacy PHP application, kept for reference |

## Quick start

```bash
cd backend && cp .env.example .env && uv sync && uv run uvicorn app.main:app --port 8000
cd frontend && cp .env.example .env && uv sync && uv run uvicorn app.main:app --port 8001
```

## Migrating from the PHP version

**Database.** The tables are not changed. The new code uses `lookups` and ignores the legacy `lookup` and `weeksdb` tables.

**Passwords.** md5 hashes are upgraded to argon2 at each user's next login. After that, the old PHP app can no longer log that user in.

**API.** The single `call_api.php?action=…` endpoint is replaced by REST endpoints under `/api/v1`, documented in [backend/README.md](backend/README.md) and at `/docs`. The old XML output is gone; everything is JSON.

**API keys.** Existing keys keep working, and their action-name scopes are mapped to the new scopes. Create new keys with `python -m app.cli create-apikey`.

**Delete tombstones.** The `deleted` table used to be filled by database triggers, which [udtime.sql](udtime.sql) does not contain. The API now writes these rows itself. If the triggers still exist in your database, set `DB_HAS_DELETE_TRIGGERS=true`.

**Statistics.** After deploying, an admin should press *Update stats* on the profile page once. The same is available as `python -m app.cli recompute-stats --user <name>`. The recompute applies the corrected timezone and ISO week handling to all cached week and month stats.

### Behaviour changes compared to PHP

- **Ownership checks.** Every update and delete checks that the row belongs to the logged-in user.
- **Timezones.** The user's timezone is applied everywhere, including API calls. Week and month boundaries are local midnights and use ISO years.
- **Registration.** It works without being logged in when `ALLOW_REGISTRATION=true`, and it sets every required column.
- **Project colours.** Projects get a colour, and it is shown in the GUI.
- **Removed.** XML output, the Redis publish, the long-poll `events.php`, the unused `edit.php` forms and the test scripts.
