import logging
from collections.abc import Iterator
from contextlib import asynccontextmanager

from fastapi import FastAPI, Request
from fastapi.concurrency import run_in_threadpool
from fastapi.responses import PlainTextResponse, JSONResponse, RedirectResponse
from sqlalchemy.exc import SQLAlchemyError

from . import migrations
from .config import settings
from .db import get_db
from .errors import DomainError
from .routers import absences, apikeys, auth, periods, projects, schedules, schema, stats, timer

log = logging.getLogger("udtime")

API_PREFIX = "/api/v1"

SECURITY_HEADERS = {
    "X-Content-Type-Options": "nosniff",
    "X-Frame-Options": "DENY",
    "Referrer-Policy": "no-referrer",
}


# Largest accepted request body (forms, JSON, the 20 kB avatar)
MAX_BODY_BYTES = 1024 * 1024

# Usable while database patches are pending: sign-in and the schema endpoints
SCHEMA_EXEMPT = (f"{API_PREFIX}/auth/", f"{API_PREFIX}/schema", f"{API_PREFIX}/admin/schema/")


def _schema_pending(app: FastAPI) -> bool | None:
    """Whether database patches are pending (cached once known); None if the database is unreachable."""
    if getattr(app.state, "schema_pending", None) is None:
        sessions = app.dependency_overrides.get(get_db, get_db)()  # tests override it with a plain session
        try:
            db = next(sessions) if isinstance(sessions, Iterator) else sessions
            app.state.schema_pending = bool(migrations.pending(db.connection()))
        except SQLAlchemyError:
            return None
        finally:
            if isinstance(sessions, Iterator):
                sessions.close()
        if app.state.schema_pending:
            log.warning("The database needs to be updated; an admin can apply it on the frontend's setup page")
    return app.state.schema_pending


def create_app() -> FastAPI:
    docs = settings.api_docs

    @asynccontextmanager
    async def lifespan(app: FastAPI):
        await run_in_threadpool(_schema_pending, app)  # check (and log) at startup
        yield

    app = FastAPI(
        title="udTime API",
        version="2.0.0",
        lifespan=lifespan,
        docs_url="/docs" if docs else None,
        redoc_url="/redoc" if docs else None,
        openapi_url="/openapi.json" if docs else None,
    )

    @app.middleware("http")
    async def security_headers(request: Request, call_next):
        response = await call_next(request)
        for name, value in SECURITY_HEADERS.items():
            response.headers.setdefault(name, value)
        if request.url.path.startswith(API_PREFIX):
            response.headers.setdefault("Cache-Control", "no-store")
        return response

    @app.middleware("http")
    async def limit_body_size(request: Request, call_next):
        length = request.headers.get("content-length")
        if length and (not length.isdigit() or int(length) > MAX_BODY_BYTES):
            return PlainTextResponse("Request too large", status_code=413)
        return await call_next(request)

    @app.middleware("http")
    async def require_current_schema(request: Request, call_next):
        path = request.url.path
        if path.startswith(API_PREFIX) and not path.startswith(SCHEMA_EXEMPT):
            if await run_in_threadpool(_schema_pending, app):
                return JSONResponse(
                    {"detail": "The database must be updated before udTime can be used", "code": "schema_outdated"},
                    status_code=503,
                )
        return await call_next(request)

    @app.exception_handler(DomainError)
    async def domain_error(_: Request, exc: DomainError):
        return JSONResponse({"detail": exc.message}, status_code=exc.status_code)

    for module in (auth, timer, periods, absences, projects, stats, apikeys, schedules, schema):
        app.include_router(module.router, prefix=API_PREFIX)

    if docs:

        @app.get("/", include_in_schema=False)
        def root():
            return RedirectResponse("/docs")

    @app.get("/health", include_in_schema=False)
    def health():
        return {"status": "ok"}

    return app


app = create_app()
