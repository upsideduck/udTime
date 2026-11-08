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
  - Users manage their own keys on the **Profile** page (or `GET/POST /apikeys`, `PATCH/DELETE /apikeys/{id}`, which need a password/SSO login, not an API key). Admins can also create a key with `uv run python -m app.cli create-apikey --user <name> --scopes timer:write,sync:read --name phone`. The key is printed once and stored as sha256.
  - Legacy keys (md5 hashed) still work. Their old action-name scopes (`startwork`, `serverupdates`, …) are mapped to the new scopes; run `uv run python -m app.cli scopes` to see the mapping.
  - `is_active` and `expires_at` are enforced, and `last_used_at` is updated on every use.
- **Passwords:** legacy unsalted md5 hashes are accepted. On the first successful login, the hash is replaced with argon2.
- **Failed logins:** limited to 10 per username and 30 per client IP within 15 minutes, after which the server answers `429` with `Retry-After`.
- **Input limits:** timestamps must fall between 2000 and about a year ahead, timer actions can't be in the future, a work period can be at most 7 days, an absence range at most 366 days, and a duration at most 24 h.

Scopes are `timer`, `periods`, `absences`, `projects` and `profile` (each as `:read` / `:write`), plus `stats:read`, `sync:read` and `export:read`. A `:write` scope implies the matching `:read`.

## Endpoints (`/api/v1`)

Timestamps in request bodies can be any of:
- a unix timestamp
- `"HH:MM[:SS]"`, meaning today in the user's timezone
- an ISO datetime; naive values are interpreted in the user's timezone

| Area | Endpoints |
| --- | --- |
| Auth | `POST /auth/login`, `GET /auth/me`, `POST /users` (when `ALLOW_REGISTRATION=true`), `PATCH /users/me` (work week, days, offset, timezone), `PUT /users/me/password` (login token only), `GET/PUT /users/me/avatar` |
| Timer | `GET /timer`, `POST /timer/start-work`, `/timer/start-break`, `/timer/end-break`, `/timer/end-work`, `/timer/switch-project` |
| Work | `GET /work?year=&week=` (or `year=&month=`, or `from=&to=`), `POST /work`, `GET/PATCH/DELETE /work/{id}`, `PUT /work/{id}/project` |
| Breaks | `POST /breaks` (the parent is the work period containing it), `GET/PATCH/DELETE /breaks/{id}` |
| Absences | `GET /lookups/{kind}`, `GET /absences/{kind}?from=&to=`, `POST /absences/{kind}` (date range, weekdays only), `GET/PATCH/DELETE /absences/{kind}/{id}`. `kind` is `asworktime` or `againstworktime`. |
| Projects | `GET /projects`, `POST /projects` |
| API keys | `GET /apikeys`, `GET /apikeys/scopes`, `POST /apikeys` (returns the key once), `PATCH/DELETE /apikeys/{id}`. Login token only. |
| Stats | `GET /stats/summary?types=today,thisweek,…`, `/stats/week/{y}/{w}`, `/stats/month/{y}/{m}`, `/stats/balance/weeks`, `/stats/balance/months`, `/stats/week-detail/{y}/{w}`, `/stats/month-detail/{y}/{m}` |
| Sync | `GET /sync/changes?since=<cursor>`. Returns every row modified at or after the cursor, plus `deleted` tombstones and the next `cursor`. |
| Export | `GET /export/month/{y}/{m}.csv` |
| Schedules | `GET /schedules`, `POST /schedules/preview` (dry run of a create, update or delete), `POST /schedules`, `PATCH/DELETE /schedules/{id}`. Body: `valid_from`, `dworkweek` and `workdays` (e.g. `[1, 2, 4, 5]`; or `daysinworkweek` alone for the first N weekdays). A change recomputes the affected weeks and months. |
| Admin | `POST /admin/recompute-stats`. Admin users only, with a bearer token. |
| Schema | `GET /schema` (public: pending database patches with their SQL), `POST /admin/schema/apply` (admin, login token). |

The timer's start and end calls are idempotent on the timestamp. An offline client can resend the same call safely.

## Business rules

- **Worked time:** for each finished work period, its length minus its breaks. A period counts entirely towards the day it started.
- **Work schedule:** `workschedule` holds periods, each with `valid_from`, `dworkweek` and `workdays` (ISO weekday digits, e.g. `1245` = Mon, Tue, Thu, Fri; `daysinworkweek` is their count). A period applies until the next one starts, and days before the first period use the first one.
- **Target (`towork`):** each day on or after `statsstartdate` has a target. It is the standard day (`dworkweek / daysinworkweek`) of that day's period if the day is one of the period's workdays, and 0 otherwise. A week's or month's `towork` is the sum of its days. For a single 5-day schedule this equals the PHP formulas.
- **Absences:** a range only creates rows on workdays. An empty time means that day's standard day.
- **Balance:** `diff = worked + asworktime − towork + againstworktime`, and `total = userdb.offset + Σ diff`.
- **Cached stats:** `weekstats` and `monthstats` are recomputed for every week and month a change touches.

## Settings

| Variable | Meaning |
| --- | --- |
| `DATABASE_URL` | SQLAlchemy URL, e.g. `mysql+pymysql://user:pass@host:3306/udTime` |
| `JWT_SECRET` / `JWT_EXPIRE_MINUTES` | Signing secret (at least 16 random characters, required) and token lifetime (default 720 = 12 h) |
| `FRONTEND_SERVICE_SECRET` | Shared with the frontend; protects the trusted (SSO) login and the client-IP header used for rate limiting. At least 16 characters. |
| `ALLOW_REGISTRATION` | Allows `POST /users` |
| `DB_HAS_DELETE_TRIGGERS` | Set to `true` if the database still has the old `BEFORE DELETE` triggers that fill `deleted`. Otherwise the API writes the tombstones itself. |
| `DEFAULT_TIMEZONE` | Used when a user's `timezone` is empty or invalid |
| `API_DOCS` | Serve the interactive docs at `/docs` (default `true`; `false` in the Docker image) |
