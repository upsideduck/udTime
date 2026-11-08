# udtime2-backend: FastAPI REST API
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
# UDTIME2_JWT_SECRET and UDTIME2_FRONTEND_SERVICE_SECRET have no default: the app refuses to start until
# they are set to random values (min 16 characters).
# UDTIME2_FORWARDED_ALLOW_IPS: proxies allowed to set X-Forwarded-* (private networks by default).
ENV UDTIME2_DATABASE_URL="mysql+pymysql://udtime:udtime@db:3306/udTime" \
    UDTIME2_JWT_SECRET="" \
    UDTIME2_JWT_EXPIRE_MINUTES="720" \
    UDTIME2_FRONTEND_SERVICE_SECRET="" \
    UDTIME2_ALLOW_REGISTRATION="false" \
    UDTIME2_DB_HAS_DELETE_TRIGGERS="false" \
    UDTIME2_DEFAULT_TIMEZONE="Europe/Stockholm" \
    UDTIME2_API_DOCS="false" \
    UDTIME2_FORWARDED_ALLOW_IPS="127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16"

EXPOSE 8000
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD python -c "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8000/health', timeout=4)"

# uvicorn only reads FORWARDED_ALLOW_IPS itself, so pass the prefixed setting explicitly
CMD ["sh", "-c", "exec uvicorn app.main:app --host 0.0.0.0 --port 8000 --proxy-headers --forwarded-allow-ips \"$UDTIME2_FORWARDED_ALLOW_IPS\""]
