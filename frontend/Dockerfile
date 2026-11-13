# udtime2-frontend: web GUI (Jinja2 + HTMX), talks to udtime2-backend
FROM python:3.13-slim

COPY --from=ghcr.io/astral-sh/uv:0.5.11 /uv /bin/uv

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    UV_COMPILE_BYTECODE=1 \
    UV_LINK_MODE=copy \
    UV_PYTHON_DOWNLOADS=never \
    PATH="/app/.venv/bin:$PATH"

WORKDIR /app

# Dependencies first, for layer caching
COPY pyproject.toml uv.lock ./
RUN uv sync --frozen --no-dev --no-install-project

COPY app ./app

RUN useradd --no-create-home --uid 10001 udtime && chown -R udtime /app
USER udtime

# --- Runtime configuration (override with `docker run -e` / compose `environment:`) ---
# UDTIME2_SESSION_SECRET and UDTIME2_FRONTEND_SERVICE_SECRET have no default: the app refuses to start until
# they are set to random values (min 16 characters).
# UDTIME2_SESSION_HTTPS_ONLY=true needs https (the proxy); set it to false to test over plain http.
# UDTIME2_OIDC_*: single sign-on with OpenID Connect (Authentik); switched on with UDTIME2_OIDC_ENABLED=true.
# UDTIME2_FORWARDED_ALLOW_IPS: proxies allowed to set X-Forwarded-* (private networks by default).
ENV UDTIME2_BACKEND_URL="http://udtime2-backend:8000" \
    UDTIME2_SESSION_SECRET="" \
    UDTIME2_SESSION_HTTPS_ONLY="true" \
    UDTIME2_SESSION_MAX_AGE="43200" \
    UDTIME2_FRONTEND_SERVICE_SECRET="" \
    UDTIME2_DEFAULT_TIMEZONE="Europe/Stockholm" \
    UDTIME2_OIDC_ENABLED="false" \
    UDTIME2_OIDC_ISSUER="" \
    UDTIME2_OIDC_CLIENT_ID="" \
    UDTIME2_OIDC_CLIENT_SECRET="" \
    UDTIME2_OIDC_REDIRECT_URI="" \
    UDTIME2_OIDC_SCOPES="openid profile email" \
    UDTIME2_OIDC_USERNAME_CLAIM="preferred_username" \
    UDTIME2_SSO_LABEL="udauth" \
    UDTIME2_FORWARDED_ALLOW_IPS="127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16"

# Build time (ISO 8601 UTC), shown in the footer of the Today page. Set late so it does not
# invalidate the cached layers above.
ARG BUILD_DATE=""
ENV UDTIME2_BUILD_DATE="$BUILD_DATE"

EXPOSE 8001
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD python -c "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8001/login', timeout=4)"

# Only publish this container behind the reverse proxy (Nginx Proxy Manager).
# uvicorn only reads FORWARDED_ALLOW_IPS itself, so pass the prefixed setting explicitly
CMD ["sh", "-c", "exec uvicorn app.main:app --host 0.0.0.0 --port 8001 --proxy-headers --forwarded-allow-ips \"$UDTIME2_FORWARDED_ALLOW_IPS\""]
