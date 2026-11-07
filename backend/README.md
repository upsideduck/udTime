# udTime backend

JSON REST API for udTime (FastAPI + SQLAlchemy). It runs on the existing udTime MySQL/MariaDB
schema ([../udtime.sql](../udtime.sql)) without changing any tables.

## Run

```bash
cp .env.example .env        # set DATABASE_URL, JWT_SECRET, FRONTEND_SERVICE_SECRET
uv sync
uv run uvicorn app.main:app --port 8000
```

Interactive API docs are served at `http://localhost:8000/docs`.

## Tests

```bash
uv run pytest
```

The tests run on SQLite, with the tables built from [app/models.py](app/models.py).

## Authentication

- **Bearer token:** `POST /api/v1/auth/login` with `{"username", "password"}` returns `access_token`. Send it as `Authorization: Bearer <token>`. A token has full access.
- **API key:** send the key as `X-API-Key: <key>`. It is limited to the scopes stored in `apikeys.scopes`.
  - Create a key with `uv run python -m app.cli create-apikey --user <name> --scopes timer:write,sync:read --name phone`. The key is printed once and stored as sha256.
  - Legacy keys (md5 hashed) still work. Their old action-name scopes (`startwork`, `serverupdates`, …) are mapped to the new scopes; run `uv run python -m app.cli scopes` to see the mapping.
  - `is_active` and `expires_at` are enforced, and `last_used_at` is updated on every use.
- **Passwords:** legacy unsalted md5 hashes are accepted. On the first successful login, the hash is replaced with argon2.

Scopes are `timer`, `periods`, `absences`, `projects` and `profile` (each as `:read` / `:write`), plus `stats:read`, `sync:read` and `export:read`. A `:write` scope implies the matching `:read`.

## Endpoints (`/api/v1`)

Timestamps in request bodies can be any of:
- a unix timestamp
- `"HH:MM[:SS]"`, meaning today in the user's timezone
- an ISO datetime; naive values are interpreted in the user's timezone

| Area | Endpoints |
| --- | --- |
| Auth | `POST /auth/login`, `GET /auth/me`, `POST /users` (when `ALLOW_REGISTRATION=true`), `GET/PUT /users/me/avatar` |
| Timer | `GET /timer`, `POST /timer/start-work`, `/timer/start-break`, `/timer/end-break`, `/timer/end-work`, `/timer/switch-project` |
| Work | `GET /work?year=&week=` (or `year=&month=`, or `from=&to=`), `POST /work`, `GET/PATCH/DELETE /work/{id}`, `PUT /work/{id}/project` |
| Breaks | `POST /breaks` (the parent is the work period containing it), `GET/PATCH/DELETE /breaks/{id}` |
| Absences | `GET /lookups/{kind}`, `GET /absences/{kind}?from=&to=`, `POST /absences/{kind}` (date range, weekdays only), `GET/PATCH/DELETE /absences/{kind}/{id}`. `kind` is `asworktime` or `againstworktime`. |
| Projects | `GET /projects`, `POST /projects` |
| Stats | `GET /stats/summary?types=today,thisweek,…`, `/stats/week/{y}/{w}`, `/stats/month/{y}/{m}`, `/stats/balance/weeks`, `/stats/balance/months`, `/stats/week-detail/{y}/{w}`, `/stats/month-detail/{y}/{m}` |
| Sync | `GET /sync/changes?since=<cursor>`. Returns every row modified at or after the cursor, plus `deleted` tombstones and the next `cursor`. |
| Export | `GET /export/month/{y}/{m}.csv` |
| Admin | `POST /admin/recompute-stats`. Admin users only, with a bearer token. |

The timer's start and end calls are idempotent on the timestamp. An offline client can resend the same call safely.

## Business rules

These are ported unchanged from the PHP code:
- **Worked time:** for each finished work period, its length minus its breaks. A period counts entirely towards the day it started.
- **Week `towork`:** `daysinworkweek × dworkweek / daysinworkweek`. In the week containing `statsstartdate`, only `6 − ISO weekday` days count.
- **Month `towork`:** the number of Mon–Fri days from `max(1st, statsstartdate)` to the end of the month, × the standard day.
- **Balance:** `diff = worked + asworktime − towork + againstworktime`, and `total = userdb.offset + Σ diff`.
- **Cached stats:** `weekstats` and `monthstats` are recomputed for every week and month a change touches.

## Settings

| Variable | Meaning |
| --- | --- |
| `DATABASE_URL` | SQLAlchemy URL, e.g. `mysql+pymysql://user:pass@host:3306/udTime` |
| `JWT_SECRET` / `JWT_EXPIRE_MINUTES` | Signing secret and lifetime for access tokens |
| `FRONTEND_SERVICE_SECRET` | Shared with the frontend for Authentik (trusted header) logins |
| `ALLOW_REGISTRATION` | Allows `POST /users` |
| `DB_HAS_DELETE_TRIGGERS` | Set to `true` if the database still has the old `BEFORE DELETE` triggers that fill `deleted`. Otherwise the API writes the tombstones itself. |
| `DEFAULT_TIMEZONE` | Used when a user's `timezone` is empty or invalid |
