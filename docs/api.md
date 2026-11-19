# udTime API (v1)

JSON REST API served by the backend (FastAPI). All endpoints below are relative to the base URL.

| Where | Base URL | Authentication |
| --- | --- | --- |
| Public, through the web app | `https://<udtime-host>/api/v1` | API key only |
| Backend directly (local dev, Docker network, or a separate API proxy host) | `http://localhost:8000/api/v1`, `http://backend:8000/api/v1`, … | API key or login token |

**Public base URL.** The web app forwards `/api/v1/*` to the backend when a request has an `X-API-Key` header (on by default; `UDTIME2_PUBLIC_API=false` turns it off). Only the API key, `Content-Type` and `Accept` are passed on; cookies and `Authorization` are dropped. A request without a key gets `401`. These endpoints answer `404` there, because they can't be used with an API key: `POST /auth/login`, `POST /users`, `PUT /users/me/password`, `/apikeys/*` and `/admin/*`.

When `UDTIME2_API_DOCS=true` the backend also serves the live OpenAPI spec at `/openapi.json`, with Swagger UI at `/docs` and ReDoc at `/redoc`. `GET /health` (outside the prefix) returns `{"status": "ok"}`. Neither is available through the public base URL.

---

## Conventions

### Authentication

Every endpoint needs one of these, unless it is marked **public**:

| Method | Header | Access |
| --- | --- | --- |
| Login token | `Authorization: Bearer <access_token>` | Full access for the user. Get it from `POST /auth/login`. Lifetime `UDTIME2_JWT_EXPIRE_MINUTES` (default 12 h). |
| API key | `X-API-Key: <key>` | Limited to the key's scopes. Inactive or expired keys get `401`. |

Some endpoints are marked **login only**: they refuse API keys with `403`, even keys that have every scope. **Admin** endpoints also need a login token, from a user with `admin = true`.

### Scopes

| Scope | Covers |
| --- | --- |
| `timer:read` / `timer:write` | `/timer` |
| `periods:read` / `periods:write` | `/work`, `/breaks` |
| `absences:read` / `absences:write` | `/absences`, `/lookups` |
| `projects:read` / `projects:write` | `/projects` |
| `profile:read` / `profile:write` | `/auth/me`, `/users/me`, avatar, `/schedules` |
| `stats:read` | `/stats/*` |
| `sync:read` | `/sync/changes` |
| `export:read` | `/export/*` |

A `:write` scope implies the matching `:read`. A key whose scopes are `*` has all of them. Old PHP-era scope names (`startwork`, `serverupdates`, …) are mapped to these; `uv run python -m app.cli scopes` prints the mapping.

### Times and durations

- **Timestamps in responses** are unix seconds (UTC). `modified` fields are unix seconds too.
- **Timestamps in requests** (`timestamp`, `starttime`, `endtime`) accept:
  - a unix timestamp, e.g. `1763539200`
  - `"HH:MM"` or `"HH:MM:SS"`, meaning today in the user's timezone
  - an ISO datetime; a value without an offset is read in the user's timezone
- **Dates** (`from`, `to`, `start_date`, `valid_from`, …) are `YYYY-MM-DD` in the user's local calendar.
- **Durations** are seconds. Where a request takes a duration it also accepts `"HH:MM[:SS]"`.
- Fields ending in `_hms` / `time` (e.g. `"07:42:10"`) and `dec` / `sum…` (e.g. `"7.70"`) are preformatted strings for display.
- Weeks are ISO weeks (`year` is the ISO week-year).

### Limits

- Timestamps must be from year 2000 up to about a year ahead. Timer actions can't be in the future.
- A work period is at most 7 days, an absence range at most 366 days, a duration at most 24 h.
- Comments are at most 1000 characters.
- Request bodies are at most 1 MB (`413` otherwise), except avatar uploads, which may be up to 10 MB.
- Failed logins: 10 per username and 30 per client IP within 15 minutes, then `429` with `Retry-After` (seconds).

### Errors

Errors are JSON with a `detail` field:

```json
{ "detail": "A period is already active" }
```

| Status | Meaning |
| --- | --- |
| `400` | Business rule violated (bad range, wrong password, …) |
| `401` | Missing, invalid or expired token / API key |
| `403` | API key lacks the scope (`"API key lacks scope 'timer:write'"`), login-only or admin-only endpoint, registration disabled |
| `404` | Row not found, or it belongs to another user |
| `409` | Conflict with the timer state (e.g. starting work while working) |
| `413` | Request body too large |
| `422` | Request validation failed (FastAPI's standard `detail` list) |
| `429` | Too many failed sign-in attempts; see `Retry-After` |
| `503` | Database unreachable, or `{"detail": …, "code": "schema_outdated"}` when database patches are pending (only `/auth/*`, `/schema` and `/admin/schema/*` work then) |

Every `/api/v1` response carries `Cache-Control: no-store`.

---

## Auth and profile

### `GET /auth/options` — public

```json
{ "allow_registration": false }
```

### `POST /auth/login` — public

```json
{ "username": "johan", "password": "secret" }
```

Response:

```json
{ "access_token": "eyJ…", "token_type": "bearer", "user": { …User } }
```

`401` on a wrong username or password, `429` when rate limited.

### `GET /auth/me` — `profile:read`

Returns the **User** object:

```json
{
  "member_id": 1,
  "username": "johan",
  "firstname": "Johan",
  "lastname": "A",
  "registerdate": 1420070400,
  "statsstartdate": 1420070400,
  "dworkweek": 144000,
  "daysinworkweek": 5,
  "offset": 0,
  "timezone": "Europe/Stockholm",
  "admin": false,
  "activeperiod": 1234,
  "activetype": "work"
}
```

`dworkweek` is seconds per week, `offset` the starting balance in seconds. `activetype` is `"work"`, `"break"` or `null`.

### `POST /users` — public, only when `UDTIME2_ALLOW_REGISTRATION=true`

| Field | Type | Notes |
| --- | --- | --- |
| `username` | string | required, 1–128 |
| `password` | string | required, at least 8 |
| `workweek_hours` | number | required, 0 < h ≤ 168 |
| `daysinworkweek` | int | 1–7, default 5 |
| `timezone` | string | IANA name, optional |
| `firstname`, `lastname` | string | optional |

`201` with the User object. `403` when registration is disabled.

### `PATCH /users/me` — `profile:write`

Any of:

| Field | Type | Notes |
| --- | --- | --- |
| `offset` | int | Starting balance in seconds |
| `timezone` | string | IANA name, e.g. `Europe/Stockholm` |

Returns the User object. The work week is changed through [`/schedules`](#schedules).

### `PUT /users/me/password` — login only

```json
{ "current_password": "old", "new_password": "at-least-8" }
```

Returns `{"message": "Password changed"}`. A wrong current password is `400` and counts towards the per-username sign-in limit.

### `GET /users/me/avatar` — `profile:read`

The image bytes with their media type, or `404` if none is set.

### `PUT /users/me/avatar` — `profile:write`

`multipart/form-data` with a `file` field: a JPEG, PNG, GIF or WebP image up to 10 MB. The server applies the EXIF rotation, crops it to a centred square and scales it down to at most 256×256. It stores a PNG if the image has transparency, otherwise a JPEG; an animated image keeps its first frame. Returns `{"message": "Avatar updated"}`. Other files, and images over 50 megapixels, get `400`.

---

## Timer

The timer is a per-user state machine: `free` → `work` ⇄ `break` → `free`.

### `GET /timer` — `timer:read`

```json
{
  "state": "work",
  "current_id": 1234,
  "workweek": 144000,
  "last_modified": 1763539200,
  "server_time": 1763541000,
  "work_id": 1234,
  "work": { …Work with breaks },
  "project": { …Project } ,
  "all_break_time": 900,
  "clock_start": 1763532000,
  "clock_offset": 900,
  "breaks": [ …Break ]
}
```

When `state` is `"free"`, `work`, `project` and `clock_start` are `null`, `clock_offset` is `0`, and `work_id`, `all_break_time` and `breaks` are absent.

To show a running clock: elapsed = `now − clock_start − clock_offset`. During work that is work time excluding finished breaks; during a break it is the length of the current break. `server_time` lets a client correct for clock skew.

### Actions — `timer:write`

| Endpoint | Body | Fails with `409` when |
| --- | --- | --- |
| `POST /timer/start-work` | `timestamp?`, `comment?`, `project_id?` | a period is already active |
| `POST /timer/start-break` | `timestamp?`, `comment?` | no work is active, or already on a break |
| `POST /timer/end-break` | `timestamp?`, `comment?` | not on a break |
| `POST /timer/end-work` | `timestamp?`, `comment?` | no work is active |
| `POST /timer/switch-project` | `project_id` (or `null`) | no work is active |

- `timestamp` empty means now.
- `comment` `null` or `"NOCHANGE"` keeps the current comment.
- `end-work` while on a break ends the break first. It fails with `400` if the timestamp is before a break's end.
- `end-break` fails with `400` if the timestamp is before the break started.
- `switch-project` ends the current period now and starts a new one with the given project.

Response:

```json
{ "message": "Work started 08:00:00", "timer": { …same as GET /timer } }
```

**Idempotency:** start and end calls are idempotent on the timestamp. If the same action with the same timestamp was already recorded, the call succeeds with a message like `"Work already registered (synced)"`, so an offline client can safely resend.

---

## Work and breaks

### Objects

**Work**

```json
{
  "id": 1234,
  "starttime": 1763532000,
  "endtime": 1763560800,
  "comment": "Release",
  "logged": false,
  "project_id": 3,
  "modified": 1763560800,
  "breaks": [ …Break ],
  "break_time": 1800,
  "worked": 27000,
  "project": { …Project }
}
```

`breaks`, `break_time` and `worked` are included in lists and detail views; `worked` only when the period has ended. `project` is only in the detail view. An unfinished period has `endtime: null`.

**Break**

```json
{ "id": 55, "parent_id": 1234, "starttime": 1763546400, "endtime": 1763548200, "comment": null, "logged": false, "modified": 1763548200 }
```

### `GET /work` — `periods:read`

Pick the range with one of:

| Query | Range |
| --- | --- |
| `year` + `week` | ISO week |
| `year` + `month` | calendar month |
| `from` + `to` | dates, inclusive |

`include_open=true` also returns the running period. Returns a list of Work (with breaks), oldest first. Missing parameters give `400`.

### `POST /work` — `periods:write`

```json
{ "starttime": "08:00", "endtime": "16:30", "comment": "Manual", "project_id": null }
```

`201` with the Work.

### `GET /work/{id}` — `periods:read`

Work with breaks and project.

### `PATCH /work/{id}` — `periods:write`

Any of `starttime`, `endtime`, `comment`. Returns the Work.

### `DELETE /work/{id}` — `periods:write`

`204`. Its breaks are deleted with it.

### `PUT /work/{id}/project` — `periods:write`

```json
{ "project_id": 3 }
```

`null` clears the project. Returns the Work.

### `POST /breaks` — `periods:write`

```json
{ "starttime": "12:00", "endtime": "12:30", "comment": "Lunch" }
```

The parent is the finished work period that fully contains the break; `400` if there is none. `201` with the Break.

### `GET /breaks/{id}` · `PATCH /breaks/{id}` · `DELETE /breaks/{id}`

Read needs `periods:read`, the others `periods:write`. `PATCH` takes any of `starttime`, `endtime`, `comment`. `DELETE` returns `204`.

Every change to work or breaks recomputes the cached week and month statistics it touches.

---

## Absences

There are two kinds, given as `{kind}` in the path:

| `kind` | Meaning | Effect on balance |
| --- | --- | --- |
| `asworktime` | Counts as worked time (vacation, sick leave, …) | added to worked |
| `againstworktime` | Reduces the target (time off, holidays, …) | subtracted from target |

**Absence**

```json
{
  "id": 77,
  "kind": "asworktime",
  "date": "2026-11-18",
  "time": 28800,
  "time_hms": "08:00:00",
  "type": 1,
  "typelabel": "Vacation",
  "modified": 1763400000,
  "comment": "Only on againstworktime"
}
```

### `GET /lookups/{kind}` — `absences:read`

The available types:

```json
[ { "code": 1, "name": "Vacation", "position": 1 } ]
```

### `GET /absences/{kind}?from=&to=` — `absences:read`

List of Absence in the date range (inclusive).

### `POST /absences/{kind}` — `absences:write`

| Field | Type | Notes |
| --- | --- | --- |
| `start_date` | date | required |
| `end_date` | date | inclusive, defaults to `start_date`, range ≤ 366 days |
| `type` | int | a lookup code, default `1` |
| `time` | int \| string | seconds or `"HH:MM[:SS]"` per day; empty means that day's standard day |
| `comment` | string | stored for `againstworktime` only |

One row is created per workday (by the schedule) that has none yet:

```json
{
  "created": [ …Absence ],
  "skipped": [ { "date": "2026-11-22", "reason": "not a workday" }, { "date": "2026-11-19", "reason": "already registered" } ]
}
```

### `GET /absences/{kind}/{id}` · `PATCH /absences/{kind}/{id}` · `DELETE /absences/{kind}/{id}`

`PATCH` takes any of `time`, `type`, `comment` and returns the Absence. `DELETE` returns `204`.

---

## Projects

**Project**: `{ "id": 3, "name": "Client A", "description": null, "color": "#4f46e5" }`

### `GET /projects` — `projects:read`

List of Project.

### `POST /projects` — `projects:write`

```json
{ "name": "Client A", "description": "optional", "color": "#4f46e5" }
```

`name` 1–100 characters, `color` `#RRGGBB`. `201` with the Project.

---

## Schedules

A user's work schedule is a list of periods. Each applies from `valid_from` until the next one starts; days before the first period use the first one.

**Schedule period**

```json
{
  "id": 2,
  "valid_from": "2026-01-01",
  "valid_to": null,
  "dworkweek": 115200,
  "daysinworkweek": 4,
  "workdays": [1, 2, 4, 5],
  "workdays_label": "Mon, Tue, Thu, Fri",
  "day_length": 28800,
  "current": true
}
```

`workdays` are ISO weekdays (1 = Monday … 7 = Sunday). `day_length` = `dworkweek / daysinworkweek`. `valid_to` is the day before the next period, or `null`.

### `GET /schedules` — `profile:read`

List of periods, oldest first.

### `POST /schedules` — `profile:write`

| Field | Type | Notes |
| --- | --- | --- |
| `valid_from` | date | required |
| `dworkweek` | int | required, seconds per week |
| `workdays` | int[] | ISO weekdays |
| `daysinworkweek` | int | without `workdays`: the first N weekdays |

Give `workdays` or `daysinworkweek` (if both, they must agree). `201` with the full list of periods.

### `PATCH /schedules/{id}` — `profile:write`

Any of the fields above. Returns the full list.

### `DELETE /schedules/{id}` — `profile:write`

Returns the full list.

Each change recomputes the cached statistics from the first affected day.

### `POST /schedules/preview` — `profile:read`

Dry run of a change; nothing is saved.

```json
{ "op": "update", "id": 2, "valid_from": "2026-03-01", "dworkweek": 144000, "workdays": [1, 2, 3, 4, 5] }
```

`op` is `create`, `update` or `delete` (`id` required for the latter two). Returns a [recompute preview](#recompute-preview).

---

## Statistics

Seconds unless noted. The balance formula is

```
diff  = worked + asworktime − towork + againstworktime
total = user.offset + Σ diff
```

### `GET /stats/summary?types=today,thisweek,thismonth` — `stats:read`

`types` is a comma list of `today`, `yesterday`, `thisweek`, `lastweek`, `thismonth`, `lastmonth`.

```json
{
  "today": { "start": 1763506800, "end": 1763593200, "worktime": 21600, "breaktime": 1800, "worktime_hms": "06:00:00", "breaktime_hms": "00:30:00" }
}
```

Only finished work periods are counted; a running period is not included (combine with `GET /timer` for a live figure). `worktime` already excludes breaks.

### `GET /stats/week/{year}/{week}` · `GET /stats/month/{year}/{month}` — `stats:read`

The **Totals** object plus `year` and `week` / `month`:

```json
{
  "year": 2026, "week": 47,
  "towork": 144000, "worked": 100800, "asworktime": 28800, "againstworktime": 0,
  "worked_incl_asworktime": 129600, "towork_incl_againstworktime": 144000, "diff": -14400,
  "sumworked": "28.00", "sumworkedinclvac": "36.00", "sumtowork": "40.00", "sumtoworkinclfree": "40.00",
  "sumasworktime": "8.00", "sumagainstworktime": "0.00", "sumdiff": "-4.00",
  "to_date": { "through": "2026-11-18", "days": 3, "towork_incl_againstworktime": 86400, "worked_incl_asworktime": 90000, "diff": 3600, "sumtoworkinclfree": "24.00", "sumworkedinclvac": "25.00", "sumdiff": "1.00" }
}
```

`sum…` fields are decimal hours. `to_date` covers completed days up to yesterday and is only set when today falls inside the period; otherwise `null`.

### `GET /stats/week-detail/{year}/{week}` · `GET /stats/month-detail/{year}/{month}` — `stats:read`

```json
{
  "year": 2026, "week": 47,
  "fromdate": "2026-11-16", "todate": "2026-11-22",
  "prev": { "year": 2026, "week": 46 },
  "next": { "year": 2026, "week": 48 },
  "totals": { …Totals },
  "days": [
    {
      "date": "2026-11-16", "label": "16-Nov", "weekday": "Mon", "isoweekday": 1, "day": 16,
      "workday": true, "target": 28800,
      "periods": [
        { "id": 1234, "start": "08:00:00", "end": "16:30:00", "starttime": 1763276400, "endtime": 1763307000,
          "comment": null, "project_id": null,
          "breaks": [ { "id": 55, "start": "12:00", "end": "12:30" } ],
          "break": "00:30:00", "worked": "08:00:00", "workeddec": "8.00", "worked_seconds": 28800 }
      ],
      "asworktime": null, "againstworktime": null,
      "worked": "08:00:00", "workeddec": "8.00"
    }
  ]
}
```

The month variant has `month` instead of `week`, and `prev` / `next` with `month`.

### `GET /stats/balance/weeks` · `GET /stats/balance/months` — `stats:read`

One entry per week (or month) since `statsstartdate`, oldest first:

```json
{
  "year": 2026, "week": 47,
  "towork": 144000, "worked": 100800, "asworktime": 28800, "againstworktime": 0,
  "diff": -14400, "total": 52200,
  "workedtime": "28:00:00", "difftime": "-04:00:00", "totaldifftime": "14:30:00",
  "modified": 1763560800,
  "ongoing": true
}
```

Months have `month` instead of `week`. `ongoing` marks the current period.

---

## Sync

### `GET /sync/changes?since=<cursor>` — `sync:read`

Everything the user changed at or after `since` (unix seconds, default `0` = everything):

```json
{
  "cursor": 1763560800,
  "work": [ …Work ],
  "break": [ …Break ],
  "asworktime": [ …Absence ],
  "againstworktime": [ …Absence ],
  "deleted": [ { "original_id": 1200, "type": "work", "modified": 1763550000 } ]
}
```

Store `cursor` and pass it as `since` next time. Because the boundary is inclusive, a client can receive a row twice and should upsert by `id`.

---

## Export

### `GET /export/month/{year}/{month}.csv` — `export:read`

`text/csv`, downloaded as `udtime-YYYY-MM.csv`.

---

## API keys — login only

**API key**

```json
{
  "id": 9, "name": "phone",
  "scopes": ["timer:read", "timer:write"],
  "is_active": true, "expires_on": "2027-01-31", "expired": false,
  "last_used_at": 1763560800, "created_at": 1760000000,
  "legacy": false
}
```

`legacy` marks keys from the PHP app (md5-hashed). `scopes` are shown expanded.

| Endpoint | Body | Response |
| --- | --- | --- |
| `GET /apikeys` | | list of API key |
| `GET /apikeys/scopes` | | sorted list of scope names |
| `POST /apikeys` | `name`, `scopes` (1–20), `expires_on?` | `201`, API key plus `"key"`: the plain key, **shown only this once** |
| `PATCH /apikeys/{id}` | any of `name`, `scopes`, `expires_on` (`null` removes it), `is_active` | API key |
| `DELETE /apikeys/{id}` | | `204` |

`expires_on` is the last valid day in the user's timezone. Keys are stored as sha256; an API key can never create or change keys.

---

## Admin

All need a login token from an admin user.

### Recompute preview

`GET /admin/recompute-stats/preview` recomputes every week and month since `statsstartdate` and reports differences against the cached rows, without writing:

```json
{
  "through": "2026-11-18",
  "weeks": {
    "checked": 150,
    "changes": [
      { "period": "2026-W46", "year": 2026, "number": 46, "status": "changed",
        "fields": { "towork": { "old": 144000, "new": 115200 } },
        "old_diff": -3600, "new_diff": 25200, "to_date": false }
    ],
    "balance_before": 48600, "balance_after": 77400,
    "pending": 0
  },
  "months": { …same shape, "period": "2026-11" }
}
```

`status` is `changed` or `missing` (no cached row). `to_date: true` marks the current period, compared through yesterday. `POST /schedules/preview` returns the same shape.

### `POST /admin/recompute-stats`

Rewrites all cached statistics. Returns `{"message": "Statistics recalculated"}`.

### `GET /schema` — public

```json
{ "up_to_date": false, "pending": [ { "id": "…", "title": "…", "description": "…", "sql": "ALTER TABLE …" } ] }
```

### `POST /admin/schema/apply`

Applies all pending database patches. Returns `{"applied": ["…"]}`, or `500` with the database error.

---

## Internal

`POST /auth/trusted` is used by the frontend after an OpenID Connect sign-in. It needs the `X-Service-Secret` header (`UDTIME2_FRONTEND_SERVICE_SECRET`) and is not part of the public API. The frontend also sends `X-Client-IP` with that secret, so the login rate limit sees the real client IP.
