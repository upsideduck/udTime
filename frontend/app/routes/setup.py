"""Database update landing page. While the backend reports pending database patches, every page
redirects here; an admin reviews the patches and applies them."""

import time
from typing import Annotated

from fastapi import APIRouter, Form, Request
from fastapi.responses import RedirectResponse

from ..api import Backend, BackendError
from ..web import render

router = APIRouter()

# Pages that keep working while an update is pending
EXEMPT_PREFIXES = ("/setup", "/static", "/login", "/logout", "/sso", "/avatar")
CHECK_INTERVAL = 30  # seconds an "up to date" answer is trusted

_up_to_date_until = 0.0


async def schema_status(request: Request) -> dict | None:
    """The backend's schema status, or None if the backend can't be reached."""
    try:
        return await Backend(request.app.state.http).get("/schema")
    except (BackendError, OSError):
        return None


async def update_pending(request: Request) -> bool:
    global _up_to_date_until
    if time.monotonic() < _up_to_date_until:
        return False
    status = await schema_status(request)
    if status is None:
        return False  # let the page show the backend error itself
    if status["up_to_date"]:
        _up_to_date_until = time.monotonic() + CHECK_INTERVAL
    return not status["up_to_date"]


def _page(request: Request, status: dict, status_code: int = 200, **ctx):
    user = request.session.get("user")
    # me=None: render on the plain sign-in layout, without the app navigation
    return render(request, "setup.html", status_code=status_code, me=None, user=user, pending=status["pending"],
                  **ctx)


@router.get("/setup")
async def setup(request: Request):
    status = await schema_status(request)
    if status is None or status["up_to_date"]:
        return RedirectResponse("/", status_code=303)
    return _page(request, status)


@router.post("/setup/apply")
async def apply(request: Request, backup: Annotated[str, Form()] = ""):
    global _up_to_date_until
    status = await schema_status(request)
    if status is None or status["up_to_date"]:
        return RedirectResponse("/", status_code=303)
    user = request.session.get("user") or {}
    if not request.session.get("token") or not user.get("admin"):
        return _page(request, status, status_code=403, error="Sign in as an administrator to apply the update.")
    if not backup:
        return _page(request, status, status_code=400, error="Confirm that you have a backup of the database.")
    try:
        result = await Backend(request.app.state.http, request.session["token"]).post("/admin/schema/apply",
                                                                                      timeout=120)
    except BackendError as exc:
        return _page(request, status, status_code=exc.status_code, error=exc.detail)
    _up_to_date_until = 0.0
    return render(request, "setup.html", me=None, user=user, pending=[], applied=result["applied"])
