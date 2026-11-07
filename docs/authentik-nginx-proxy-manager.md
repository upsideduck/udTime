# Setting up udTime behind Authentik and Nginx Proxy Manager

This guide puts the udTime frontend behind Nginx Proxy Manager (NPM), using Authentik as the single sign-on provider ("udauth"). After setup:

- **Login page:** the "Sign in with udauth" button signs users in through Authentik.
- **Password login:** still works. This is optional, see [Option A](#option-a-sso-only).
- **Backend:** never directly reachable from the browser, or exposed separately for API clients only.

```
Browser ──https──▶ Nginx Proxy Manager ──▶ udTime frontend :8001 ──▶ udTime backend :8000 ──▶ MySQL
                        │  auth_request
                        ▼
                  Authentik outpost (/outpost.goauthentik.io)
```

## How SSO works in udTime

1. The user clicks **Sign in with udauth**. This links to `/sso` on the frontend.
2. NPM asks the Authentik outpost whether the request is authenticated. If it isn't, the user is redirected to Authentik to sign in, then sent back to `/sso`.
3. When the request reaches the frontend, NPM adds the header `X-Authentik-Username: <username>`.
4. The frontend calls the backend's trusted login (`POST /api/v1/auth/trusted`), authenticated with the shared `FRONTEND_SERVICE_SECRET`. It stores the returned token in its session cookie and redirects to `/`.
5. **Sign out** clears the udTime session and sends the user to `/outpost.goauthentik.io/sign_out`, which ends the Authentik session too.

**Requirements:**
- The **Authentik username must equal `userdb.username`** in udTime. An SSO user without a matching account sees "… has no udTime account".
- **Security:** the frontend trusts the header blindly, so NPM must always overwrite it. Clients must never be able to send their own `X-Authentik-Username`. The configuration below does this.

---

## 1. udTime configuration

`backend/.env`:

```ini
DATABASE_URL=mysql+pymysql://udtime:<password>@<db-host>:3306/udTime
JWT_SECRET=<long random string>
FRONTEND_SERVICE_SECRET=<long random string, shared with the frontend>
```

`frontend/.env`:

```ini
BACKEND_URL=http://<backend-host>:8000       # internal address, not the public one
SESSION_SECRET=<long random string>
SESSION_HTTPS_ONLY=true                      # the site is served over https by NPM
FRONTEND_SERVICE_SECRET=<same value as the backend>
TRUST_AUTHENTIK_HEADER=true                  # enables /sso and the udauth button
AUTHENTIK_HEADER=X-Authentik-Username
AUTHENTIK_LOGOUT_URL=/outpost.goauthentik.io/sign_out
SSO_LABEL=udauth
```

Generate the secrets with `python3 -c "import secrets; print(secrets.token_urlsafe(48))"`.

Run both apps so they are reachable from the NPM container but **not** from the internet. Bind them to an internal Docker network, or to the LAN IP with a firewall in front:

```bash
# backend
cd backend && uv run uvicorn app.main:app --host 0.0.0.0 --port 8000
# frontend: trust X-Forwarded-* from NPM so redirects use https
cd frontend && uv run uvicorn app.main:app --host 0.0.0.0 --port 8001 \
    --proxy-headers --forwarded-allow-ips "<NPM IP or *>"
```

---

## 2. Authentik configuration

All of this is done in the Authentik admin interface.

### 2.1 Create the provider

**Applications → Providers → Create → Proxy Provider**

| Field | Value |
| --- | --- |
| Name | `udTime` |
| Authorization flow | `default-provider-authorization-implicit-consent` (or your own) |
| Mode | **Forward auth (single application)** |
| External host | `https://time.example.com` (the public URL of the frontend) |
| Token validity | as you like, e.g. `hours=24` |

Leave **Unauthenticated paths** empty. Which paths require udauth is decided in NPM (step 3).

> Unauthenticated paths are Go regular expressions, and Go doesn't support negative lookahead such as `(?!sso)`. Restricting SSO to `/sso` is easier in nginx.

### 2.2 Create the application

**Applications → Applications → Create**

| Field | Value |
| --- | --- |
| Name | `udTime` |
| Slug | `udtime` |
| Provider | `udTime` (from 2.1) |
| Launch URL | `https://time.example.com/sso`, so the Authentik dashboard tile signs straight in |

Optional: under **Policy / Group / User Bindings**, bind a group (e.g. `udtime-users`) so only its members can sign in.

### 2.3 Add it to the outpost

**Applications → Outposts → authentik Embedded Outpost → Edit**, then add `udTime` to **Applications** and save.

If you run a separate outpost container instead of the embedded one, add it there. Then use that container's address in the nginx config below.

---

## 3. Nginx Proxy Manager configuration

Create **Hosts → Proxy Hosts → Add Proxy Host**:

**Details tab**

| Field | Value |
| --- | --- |
| Domain Names | `time.example.com` |
| Scheme | `http` |
| Forward Hostname / IP | the frontend host (container name or IP) |
| Forward Port | `8001` |
| Block Common Exploits | on |
| Websockets Support | off (not needed) |

**SSL tab:** request or select a certificate, and enable **Force SSL** and **HTTP/2**.

**Custom Locations tab:** leave it empty.

**Advanced tab:** paste one of the two options below. Replace `authentik.example.com` with the address NPM can reach Authentik on: `http://authentik-server:9000` for the embedded outpost on the same Docker network, or the outpost container for a manual outpost.

NPM skips its own generated `location /` when the Advanced config defines one, so it's fine that both options do.

### Option B: password login plus the "Sign in with udauth" button (recommended)

Only `/sso` goes through Authentik. Every other path is proxied directly, with the SSO header removed so clients can't fake it.

```nginx
# Authentik sends large headers/cookies
proxy_buffers 8 16k;
proxy_buffer_size 32k;
port_in_redirect off;

# Everything except /sso: no Authentik, and never pass a client-supplied SSO header
location / {
    proxy_pass       $forward_scheme://$server:$port;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-authentik-username "";
}

# /sso: must be signed in to Authentik; forwards the username to udTime
location = /sso {
    auth_request        /outpost.goauthentik.io/auth/nginx;
    error_page          401 = @goauthentik_proxy_signin;
    auth_request_set    $auth_cookie $upstream_http_set_cookie;
    add_header          Set-Cookie $auth_cookie;

    auth_request_set    $authentik_username $upstream_http_x_authentik_username;
    proxy_set_header    X-authentik-username $authentik_username;

    proxy_pass          $forward_scheme://$server:$port;
    proxy_set_header    Host $host;
    proxy_set_header    X-Forwarded-Proto $scheme;
    proxy_set_header    X-Forwarded-For $proxy_add_x_forwarded_for;
}

# Authentik outpost endpoints (sign-in callback, sign-out); must not require auth
location /outpost.goauthentik.io {
    proxy_pass              http://authentik.example.com:9000/outpost.goauthentik.io;
    proxy_set_header        Host $host;
    proxy_set_header        X-Original-URL $scheme://$http_host$request_uri;
    add_header              Set-Cookie $auth_cookie;
    auth_request_set        $auth_cookie $upstream_http_set_cookie;
    proxy_pass_request_body off;
    proxy_set_header        Content-Length "";
}

# Not signed in to Authentik: start the udauth sign-in, then come back to /sso
location @goauthentik_proxy_signin {
    internal;
    add_header Set-Cookie $auth_cookie;
    return 302 /outpost.goauthentik.io/start?rd=$scheme://$http_host$request_uri;
}
```

### Option A: SSO only

Every page requires udauth, and users are signed in automatically on their first visit. The password form is never shown in practice. Use the same snippet, but make `location /` require Authentik as well:

```nginx
proxy_buffers 8 16k;
proxy_buffer_size 32k;
port_in_redirect off;

location / {
    auth_request        /outpost.goauthentik.io/auth/nginx;
    error_page          401 = @goauthentik_proxy_signin;
    auth_request_set    $auth_cookie $upstream_http_set_cookie;
    add_header          Set-Cookie $auth_cookie;

    auth_request_set    $authentik_username $upstream_http_x_authentik_username;
    proxy_set_header    X-authentik-username $authentik_username;

    proxy_pass          $forward_scheme://$server:$port;
    proxy_set_header    Host $host;
    proxy_set_header    X-Forwarded-Proto $scheme;
    proxy_set_header    X-Forwarded-For $proxy_add_x_forwarded_for;
}

# ... plus the same `location /outpost.goauthentik.io` and
# `location @goauthentik_proxy_signin` blocks as in Option B
```

---

## 4. Exposing the API for scripts and apps (optional)

Browser users never need the backend directly. If you want API access for scripts, mobile apps or API keys, create a **second** proxy host without Authentik. Those clients authenticate with `X-API-Key` or a bearer token.

| Field | Value |
| --- | --- |
| Domain Names | `time-api.example.com` |
| Forward Hostname / IP / Port | backend host, `8000` |
| SSL | certificate + Force SSL |
| Advanced | see below |

```nginx
# The trusted login is only meant for the frontend on the internal network
location = /api/v1/auth/trusted { return 404; }

location / {
    proxy_pass       $forward_scheme://$server:$port;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

`/api/v1/auth/trusted` also requires `FRONTEND_SERVICE_SECRET`, so blocking it is a second layer of protection.

Create API keys with:

```bash
cd backend && uv run python -m app.cli create-apikey --user <username> --scopes timer:write,sync:read --name phone
```

---

## 5. Testing the setup

1. **Header spoofing must fail.** This must **not** sign you in:
   ```bash
   curl -si https://time.example.com/sso -H "X-Authentik-Username: johan" | head -1
   ```
   With Option B you should get a `302` to `/outpost.goauthentik.io/start…`. You must not get a `303` to `/`.
2. **Same for the other pages:**
   ```bash
   curl -si https://time.example.com/ -H "X-Authentik-Username: johan" | grep -i location
   ```
   This should redirect to `/login`.
3. **In a private browser window**, open `https://time.example.com/login` and click **Sign in with udauth**. You should land on Authentik, sign in, and arrive on the udTime home page with "Sign out (SSO)" in the sidebar.
4. **Sign out** should return you to Authentik's signed-out page, and `/` should ask you to sign in again.

## Troubleshooting

| Symptom | Cause / fix |
| --- | --- |
| "No udauth sign-in was received. Is /sso protected by the proxy?" | `/sso` didn't go through `auth_request`. Check the `location = /sso` block and that the proxy host uses it. |
| "&lt;user&gt; has no udTime account" | The Authentik username doesn't match any `userdb.username`. Rename one of them, or register the user in udTime. |
| Redirect loop between `/sso` and Authentik | The application isn't added to the outpost (2.3), or the `/outpost.goauthentik.io` `proxy_pass` points to the wrong host or port. |
| 502 on `/outpost.goauthentik.io/...` | NPM can't reach Authentik. Use the container name or IP and port `9000` that NPM can resolve. |
| "Single sign-on is not enabled" | `TRUST_AUTHENTIK_HEADER` isn't `true` in `frontend/.env`. Restart the frontend after changing it. |
| Signed in, but redirected to `http://` URLs | Start the frontend with `--proxy-headers --forwarded-allow-ips`, and keep `X-Forwarded-Proto` in the nginx config. |
| "upstream sent too big header" in the NPM logs | Keep the `proxy_buffers` / `proxy_buffer_size` lines. |
