# udTime frontend

The web GUI for udTime. It is a FastAPI app that renders Jinja2 templates with HTMX, Bootstrap 5 and FullCalendar 6. It has no database access of its own: every read and write goes through the backend REST API.

## Run

```bash
cp .env.example .env        # set BACKEND_URL, SESSION_SECRET, FRONTEND_SERVICE_SECRET
uv sync
uv run uvicorn app.main:app --port 8001
```

The backend must be running at `BACKEND_URL`.

## Login

- **Login form:** `/login` checks the credentials against the backend. The access token is kept in a signed session cookie.
- **Authentik (SSO):** with `TRUST_AUTHENTIK_HEADER=true`, a request carrying `X-Authentik-Username` (configurable via `AUTHENTIK_HEADER`) is logged in as that user through the backend's trusted login. Logout then redirects to `AUTHENTIK_LOGOUT_URL`. Only enable this behind a proxy that strips the header from client requests.
- **"Sign in with udauth" button:** shown on the login page when `TRUST_AUTHENTIK_HEADER=true`. The label comes from `SSO_LABEL`. The button goes to `/sso`, which logs in from the header and redirects to `/`. `/sso` must be protected by Authentik in the reverse proxy, and the header must be stripped on every other path. See [docs/authentik-nginx-proxy-manager.md](../docs/authentik-nginx-proxy-manager.md) for the full Authentik and Nginx Proxy Manager setup.

## Pages

| Page | Route |
| --- | --- |
| Home: timer, user info and statistics, projects | `/` |
| Week / month totals with balance | `/summary/week-totals`, `/summary/month-totals` |
| Week / month views (edit, delete, add, CSV export) | `/summary/week`, `/summary/month` |
| Week / month calendars | `/calendar/week`, `/calendar/month` |
| Profile: avatar upload, admin stats recompute | `/profile` |
| Register, login, logout | `/register`, `/login`, `/logout` |

All add and edit forms open as modals; the routes are in [app/routes/forms.py](app/routes/forms.py). After a change, open views refresh through the `dataChanged` HTMX event.
