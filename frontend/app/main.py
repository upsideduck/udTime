from contextlib import asynccontextmanager
from pathlib import Path

from fastapi import FastAPI, Request
from fastapi.responses import PlainTextResponse, HTMLResponse, RedirectResponse
from fastapi.staticfiles import StaticFiles
from starlette.middleware.sessions import SessionMiddleware

from .api import BackendError, NotLoggedIn, make_client
from .config import settings
from .oidc import OIDCClient
from .routes import auth, forms, home, profile, setup, summary
from .web import render, toast

# Scripts only from this site and jsDelivr (htmx, Bootstrap, FullCalendar); no inline scripts.
# Inline styles are needed by Bootstrap/FullCalendar/htmx.
CONTENT_SECURITY_POLICY = "; ".join([
    "default-src 'self'",
    "script-src 'self' https://cdn.jsdelivr.net",
    "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
    "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",  # data: for FullCalendar's icons
    "img-src 'self' data:",
    "connect-src 'self'",
    "object-src 'none'",
    "base-uri 'self'",
    "form-action 'self'",
    "frame-ancestors 'none'",
])

SECURITY_HEADERS = {
    "Content-Security-Policy": CONTENT_SECURITY_POLICY,
    "X-Frame-Options": "DENY",
    "X-Content-Type-Options": "nosniff",
    "Referrer-Policy": "same-origin",
    "Permissions-Policy": "camera=(), microphone=(), geolocation=()",
}


@asynccontextmanager
async def lifespan(app: FastAPI):
    app.state.http = make_client()
    app.state.oidc = OIDCClient(app.state.http)
    yield
    await app.state.http.aclose()


# Largest accepted request body (forms, JSON, the 20 kB avatar)
MAX_BODY_BYTES = 1024 * 1024


def create_app() -> FastAPI:
    app = FastAPI(title="udTime", lifespan=lifespan, docs_url=None, redoc_url=None, openapi_url=None)
    app.add_middleware(
        SessionMiddleware,
        secret_key=settings.session_secret,
        session_cookie="udtime_session",
        https_only=settings.session_https_only,
        same_site="lax",
        max_age=settings.session_max_age,
    )
    app.mount("/static", StaticFiles(directory=Path(__file__).parent / "static"), name="static")

    @app.middleware("http")
    async def security_headers(request: Request, call_next):
        response = await call_next(request)
        for name, value in SECURITY_HEADERS.items():
            response.headers.setdefault(name, value)
        if settings.session_https_only:
            response.headers.setdefault("Strict-Transport-Security", "max-age=31536000; includeSubDomains")
        return response

    @app.middleware("http")
    async def limit_body_size(request: Request, call_next):
        length = request.headers.get("content-length")
        if length and (not length.isdigit() or int(length) > MAX_BODY_BYTES):
            return PlainTextResponse("Request too large", status_code=413)
        return await call_next(request)

    @app.middleware("http")
    async def require_current_schema(request: Request, call_next):
        """Send everyone to the setup page while the database needs an update."""
        if not request.url.path.startswith(setup.EXEMPT_PREFIXES) and await setup.update_pending(request):
            if request.headers.get("HX-Request"):
                return HTMLResponse("", headers={"HX-Redirect": "/setup"})
            return RedirectResponse("/setup", status_code=303)
        return await call_next(request)

    @app.exception_handler(NotLoggedIn)
    async def not_logged_in(request: Request, _: NotLoggedIn):
        request.session.pop("token", None)
        if request.headers.get("HX-Request"):
            return HTMLResponse("", headers={"HX-Redirect": "/login"})
        return RedirectResponse("/login", status_code=303)

    @app.exception_handler(BackendError)
    async def backend_error(request: Request, exc: BackendError):
        if request.headers.get("HX-Request"):
            return HTMLResponse(toast(request, exc.detail, "danger"), headers={"HX-Reswap": "none"})
        return render(request, "error.html", status_code=exc.status_code, error=exc.detail)

    for module in (auth, home, summary, forms, profile, setup):
        app.include_router(module.router)
    return app


app = create_app()
