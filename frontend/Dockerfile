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
# Secrets below are placeholders only: always set real values at runtime.
# SESSION_HTTPS_ONLY=true needs https (the proxy); set it to false to test over plain http.
# FORWARDED_ALLOW_IPS: trust X-Forwarded-* from these addresses (the reverse proxy).
ENV BACKEND_URL="http://udtime2-backend:8000" \
    SESSION_SECRET="change-me" \
    SESSION_HTTPS_ONLY="true" \
    FRONTEND_SERVICE_SECRET="change-me-too" \
    TRUST_AUTHENTIK_HEADER="false" \
    AUTHENTIK_HEADER="X-Authentik-Username" \
    AUTHENTIK_LOGOUT_URL="/outpost.goauthentik.io/sign_out" \
    SSO_LABEL="udauth" \
    DEFAULT_TIMEZONE="Europe/Stockholm" \
    FORWARDED_ALLOW_IPS="*"

EXPOSE 8001
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD python -c "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8001/login', timeout=4)"

# Only publish this container behind the reverse proxy (Nginx Proxy Manager).
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8001", "--proxy-headers"]
