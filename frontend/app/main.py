from contextlib import asynccontextmanager
from pathlib import Path

from fastapi import FastAPI, Request
from fastapi.responses import HTMLResponse, RedirectResponse
from fastapi.staticfiles import StaticFiles
from starlette.middleware.sessions import SessionMiddleware

from .api import BackendError, NotLoggedIn, make_client
from .config import settings
from .routes import auth, forms, home, profile, summary
from .web import render, toast


@asynccontextmanager
async def lifespan(app: FastAPI):
    app.state.http = make_client()
    yield
    await app.state.http.aclose()


def create_app() -> FastAPI:
    app = FastAPI(title="udTime", lifespan=lifespan, docs_url=None, redoc_url=None, openapi_url=None)
    app.add_middleware(
        SessionMiddleware,
        secret_key=settings.session_secret,
        session_cookie="udtime_session",
        https_only=settings.session_https_only,
        same_site="lax",
        max_age=60 * 60 * 24 * 7,
    )
    app.mount("/static", StaticFiles(directory=Path(__file__).parent / "static"), name="static")

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

    for module in (auth, home, summary, forms, profile):
        app.include_router(module.router)
    return app


app = create_app()
