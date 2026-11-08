# Setting up udTime behind Nginx Proxy Manager, with Authentik sign-in

This guide publishes udTime on the internet behind Nginx Proxy Manager (NPM). Single sign-on uses Authentik ("udauth") over **OpenID Connect**. The result:

- **Login page:** has a **Sign in with udauth** button next to the normal password login.
- **NPM:** an ordinary https reverse proxy, with no forward-auth configuration.
- **Backend:** never reachable from the internet, unless you choose to expose the API (section 4).

```
Browser ──https──▶ Nginx Proxy Manager ──▶ udtime2-frontend :8001 ──▶ udtime2-backend :8000 ──▶ MySQL
   │                                              │
   └──── sign-in redirect ────▶ Authentik ◀───────┘ code exchange + token check (server to server)
```

## How sign-in works

1. The user clicks **Sign in with udauth**. The frontend (`/sso`) redirects to Authentik using the Authorization Code flow with PKCE, a one-time `state` and a `nonce`.
2. The user signs in to Authentik, which redirects back to `https://time.example.com/sso/callback?code=…`.
3. The frontend exchanges the code for an ID token, server to server. It then verifies the token's **signature** (Authentik's published keys), **issuer**, **audience** (your client ID), **expiry**, and the **nonce** and **state** from step 1.
4. The frontend reads the username from the `preferred_username` claim. It signs the user in through the backend's trusted login, which is protected by `FRONTEND_SERVICE_SECRET`.
5. **Sign out** clears the udTime session and redirects to Authentik's end-session endpoint, which signs the user out of Authentik too.

The **Authentik username must equal `userdb.username`** in udTime. A user without a matching account sees "… has no udTime account".

> **Important:** because accounts are matched by username, users must **not** be able to rename themselves in Authentik. Otherwise someone could rename their account to `johan` and sign in as johan. Check **System → Brands / Settings → "Allow users to change username"** (off by default) and keep it off. The same applies to enrollment flows that let people pick their own username.

---

## 1. Authentik

In the Authentik admin interface:

### 1.1 Provider

Go to **Applications → Providers → Create → OAuth2/OpenID Provider**.

| Field | Value |
| --- | --- |
| Name | `udTime` |
| Authorization flow | `default-provider-authorization-implicit-consent` (or explicit consent) |
| Client type | **Confidential** |
| Client ID / Client Secret | generated. Copy both. |
| Redirect URIs | `https://time.example.com/sso/callback` (strict) |
| Signing key | `authentik Self-signed Certificate` (RS256) |
| Scopes | `openid`, `profile`, `email` (the defaults) |
| Subject mode | Based on the User's hashed ID (default) |

`preferred_username` is part of the `profile` scope and holds the Authentik username.

### 1.2 Application

Go to **Applications → Applications → Create**.

| Field | Value |
| --- | --- |
| Name / Slug | `udTime` / `udtime` |
| Provider | `udTime` |
| Launch URL | `https://time.example.com/sso`, so the Authentik dashboard tile signs straight in |

Optional: bind a group (e.g. `udtime-users`) under **Policy / Group / User Bindings**, so only its members can use udTime.

The provider's **OpenID Configuration Issuer** is shown on the provider page, e.g. `https://auth.example.com/application/o/udtime/`. Use that value as `OIDC_ISSUER`.

---

## 2. udTime configuration

Put these in the root `.env` used by `docker-compose.yml` (see `.env.example`):

```ini
# Secrets: random values of at least 16 characters; the apps refuse to start otherwise.
# python3 -c "import secrets; print(secrets.token_urlsafe(48))"
JWT_SECRET=...
FRONTEND_SERVICE_SECRET=...
SESSION_SECRET=...

SESSION_HTTPS_ONLY=true

OIDC_ISSUER=https://auth.example.com/application/o/udtime/
OIDC_CLIENT_ID=<client id from 1.1>
OIDC_CLIENT_SECRET=<client secret from 1.1>
# Only needed if the public URL can't be derived from the proxy headers:
OIDC_REDIRECT_URI=https://time.example.com/sso/callback
SSO_LABEL=udauth
```

Leave `OIDC_ISSUER` empty to disable SSO. The button then disappears and only password login remains.

The frontend container must be able to reach Authentik's public URL, for the discovery document, the published keys and the token endpoint.

---

## 3. Nginx Proxy Manager

Create **Hosts → Proxy Hosts → Add Proxy Host**:

| Tab | Setting |
| --- | --- |
| Details | Domain `time.example.com`, scheme `http`, forward host = the frontend (container name `udtime2-frontend` if NPM shares its network, otherwise the server's IP), port `8001`. **Block Common Exploits** on. |
| SSL | Certificate (Let's Encrypt), **Force SSL**, **HTTP/2**, **HSTS** on |
| Advanced | see below (optional rate limit) |

```nginx
# Keep the original host and scheme for the app (redirect URIs, secure cookies)
proxy_set_header Host $host;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
```

udTime limits failed logins itself: 10 per username and 30 per IP within 15 minutes. If you want an extra limit at the proxy, add a `limit_req_zone` in NPM's custom http config and a `limit_req` for `location = /login`.

**Keep the frontend port private.** Only NPM should reach port 8001.
- **If NPM runs in Docker on the same host:** attach the frontend to NPM's network (see the comment in `docker-compose.yml`) and set `FRONTEND_BIND=127.0.0.1`, or remove the `ports:` mapping.
- **Otherwise:** firewall the port so only NPM's IP can connect.

`FORWARDED_ALLOW_IPS` defaults to private networks. Set it to NPM's IP if you can.

---

## 4. Exposing the API for scripts and apps (optional)

Browser users never need the backend directly. For scripts, mobile apps or API keys, publish the backend with a **second** proxy host, e.g. `time-api.example.com` → `udtime2-backend:8000`, with SSL. In its Advanced tab:

```nginx
# Only the frontend may use the trusted login
location = /api/v1/auth/trusted { return 404; }
location / {
    proxy_pass       $forward_scheme://$server:$port;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

Create API keys on the **Profile** page (API keys panel), or from the command line:

```bash
docker exec -it udtime2-backend python -m app.cli create-apikey --user <name> --scopes timer:write,sync:read --name phone
```

---

## 5. Testing

1. In a private window, open `https://time.example.com/login` and click **Sign in with udauth**. You should end up on the udTime home page with "Sign out (SSO)" in the sidebar.
2. **Sign out** should land on Authentik's signed-out page.
3. `curl -sI https://time.example.com/login` should show `strict-transport-security`, `content-security-policy` and `x-frame-options: DENY`.
4. `curl -s -o /dev/null -w '%{http_code}' https://time.example.com/sso/callback?code=x&state=forged` should return `400`.
5. From outside your network, port 8001 on the server must not answer.

## Troubleshooting

| Symptom | Cause / fix |
| --- | --- |
| "Single sign-on is not configured" | `OIDC_ISSUER` / `OIDC_CLIENT_ID` are empty. Restart after setting them. |
| "Could not reach the identity provider" | The frontend container can't resolve or reach `OIDC_ISSUER`. Check DNS and firewall from inside the container. |
| Authentik: "Redirect URI Error" | The redirect URI in the provider must exactly match `https://time.example.com/sso/callback`. Set `OIDC_REDIRECT_URI` if udTime builds an `http://` URL. |
| `http://` redirect URI / cookie not kept | NPM must send `X-Forwarded-Proto`, and NPM's IP must be covered by `FORWARDED_ALLOW_IPS`. |
| "Invalid sign-in token (Audience doesn't match)" | `OIDC_CLIENT_ID` differs from the provider's client ID. |
| "… has no udTime account" | The Authentik username doesn't match any `userdb.username`. |
| "The identity provider sent no 'preferred_username' claim" | Add the `profile` scope mapping to the provider, or change `OIDC_USERNAME_CLAIM`. |
| "Sign-in expired or was not started here" | The sign-in took more than 10 minutes, or the session cookie was lost. Check `SESSION_HTTPS_ONLY` against http/https. |
