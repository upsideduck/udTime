from fastapi import FastAPI, Request
from fastapi.responses import JSONResponse, RedirectResponse

from .errors import DomainError
from .routers import absences, auth, periods, projects, stats, timer

API_PREFIX = "/api/v1"


def create_app() -> FastAPI:
    app = FastAPI(title="udTime API", version="2.0.0")

    @app.exception_handler(DomainError)
    async def domain_error(_: Request, exc: DomainError):
        return JSONResponse({"detail": exc.message}, status_code=exc.status_code)

    for module in (auth, timer, periods, absences, projects, stats):
        app.include_router(module.router, prefix=API_PREFIX)

    @app.get("/", include_in_schema=False)
    def root():
        return RedirectResponse("/docs")

    @app.get("/health", include_in_schema=False)
    def health():
        return {"status": "ok"}

    return app


app = create_app()
