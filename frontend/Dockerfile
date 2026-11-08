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
# SESSION_SECRET and FRONTEND_SERVICE_SECRET have no default: the app refuses to start until
# they are set to random values (min 16 characters).
# SESSION_HTTPS_ONLY=true needs https (the proxy); set it to false to test over plain http.
# OIDC_*: single sign-on with OpenID Connect (Authentik); empty OIDC_ISSUER disables it.
# FORWARDED_ALLOW_IPS: proxies allowed to set X-Forwarded-* (private networks by default).
ENV BACKEND_URL="http://udtime2-backend:8000" \
    SESSION_SECRET="" \
    SESSION_HTTPS_ONLY="true" \
    SESSION_MAX_AGE="43200" \
    FRONTEND_SERVICE_SECRET="" \
    DEFAULT_TIMEZONE="Europe/Stockholm" \
    OIDC_ISSUER="" \
    OIDC_CLIENT_ID="" \
    OIDC_CLIENT_SECRET="" \
    OIDC_REDIRECT_URI="" \
    OIDC_SCOPES="openid profile email" \
    OIDC_USERNAME_CLAIM="preferred_username" \
    SSO_LABEL="udauth" \
    FORWARDED_ALLOW_IPS="127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16"

EXPOSE 8001
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD python -c "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8001/login', timeout=4)"

# Only publish this container behind the reverse proxy (Nginx Proxy Manager).
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8001", "--proxy-headers"]
