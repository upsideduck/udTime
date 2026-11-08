# udTime frontend

The web GUI for udTime. It is a FastAPI app that renders Jinja2 templates with HTMX, Bootstrap 5 and FullCalendar 6. It has no database access of its own: every read and write goes through the backend REST API.

## Run

```bash
cp .env.example .env        # set UDTIME2_BACKEND_URL, UDTIME2_SESSION_SECRET, UDTIME2_FRONTEND_SERVICE_SECRET
uv sync
uv run uvicorn app.main:app --port 8001
```

The backend must be running at `UDTIME2_BACKEND_URL`.

## Login

- **Login form:** `/login` checks the credentials against the backend. The access token is kept in a signed session cookie.
- **Single sign-on (OpenID Connect):** with `UDTIME2_OIDC_ENABLED=true` and `UDTIME2_OIDC_ISSUER`, `UDTIME2_OIDC_CLIENT_ID` and `UDTIME2_OIDC_CLIENT_SECRET` set, the login page shows a **Sign in with udauth** button (label from `UDTIME2_SSO_LABEL`).
  - `/sso` starts the Authorization Code + PKCE flow, and `/sso/callback` verifies the ID token (signature, issuer, audience, expiry, nonce, state).
  - The user is then signed in through the backend's service-secret protected trusted login.
  - Logout also ends the session at the identity provider.

  See [docs/authentik-nginx-proxy-manager.md](../docs/authentik-nginx-proxy-manager.md) for setting up Authentik and Nginx Proxy Manager.
- **Security:**
  - `UDTIME2_SESSION_SECRET` and `UDTIME2_FRONTEND_SERVICE_SECRET` must be random values of at least 16 characters, or the app refuses to start.
  - Responses carry a strict Content-Security-Policy (no inline scripts), `X-Frame-Options: DENY`, and HSTS when `UDTIME2_SESSION_HTTPS_ONLY=true`.

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
