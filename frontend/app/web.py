"""Shared web helpers: templates, session login, rendering HTMX fragments."""

import datetime as dt
import hashlib
import json
from pathlib import Path
from typing import Annotated
from zoneinfo import ZoneInfo, ZoneInfoNotFoundError

from fastapi import Depends, Request
from fastapi.responses import HTMLResponse
from fastapi.templating import Jinja2Templates

from .api import Backend, BackendError, NotLoggedIn
from .config import settings

templates = Jinja2Templates(directory=Path(__file__).parent / "templates")


# --- formatting -----------------------------------------------------------------------


def format_hms(seconds) -> str:
    if seconds in (None, ""):
        return ""
    seconds = int(seconds)
    sign = "-" if seconds < 0 else ""
    seconds = abs(seconds)
    return f"{sign}{seconds // 3600:02d}:{seconds % 3600 // 60:02d}:{seconds % 60:02d}"


def format_hm(seconds) -> str:
    return format_hms(seconds)[:-3] if seconds not in (None, "") else ""


def localtime(ts, tz: ZoneInfo, fmt: str = "%Y-%m-%d %H:%M") -> str:
    if ts in (None, "", 0):
        return ""
    return dt.datetime.fromtimestamp(int(ts), tz).strftime(fmt)


def input_value(ts, tz: ZoneInfo) -> str:
    """Value for the date-and-time picker (data-picker="datetime"): ISO with a space separator."""
    return localtime(ts, tz, "%Y-%m-%d %H:%M:%S")


def sign_class(value) -> str:
    text = str(value or "")
    if text.startswith("-"):
        return "neg"
    return "pos" if text.strip("+0:.") else ""


templates.env.filters.update(hms=format_hms, hm=format_hm, localtime=localtime, sign_class=sign_class)

STATIC_DIR = Path(__file__).parent / "static"
_static_versions: dict[str, tuple[float, str]] = {}


def static_url(name: str) -> str:
    """/static/<name>?v=<content hash>, so browsers fetch a changed file instead of a cached one."""
    path = STATIC_DIR / name
    mtime = path.stat().st_mtime
    cached = _static_versions.get(name)
    if cached is None or cached[0] != mtime:
        cached = (mtime, hashlib.sha256(path.read_bytes()).hexdigest()[:10])
        _static_versions[name] = cached
    return f"/static/{name}?v={cached[1]}"


templates.env.globals["static_url"] = static_url


# --- session / auth -------------------------------------------------------------------


def session_tz(request: Request) -> ZoneInfo:
    name = (request.session.get("user") or {}).get("timezone") or settings.default_timezone
    try:
        return ZoneInfo(name)
    except (ZoneInfoNotFoundError, ValueError):
        return ZoneInfo(settings.default_timezone)


def store_login(request: Request, token_response: dict, sso: bool, id_token: str | None = None) -> None:
    user = token_response["user"]
    request.session.clear()
    request.session.update(
        token=token_response["access_token"],
        sso=sso,
        user={k: user.get(k) for k in ("member_id", "username", "firstname", "lastname", "timezone", "admin")},
    )
    if id_token:
        request.session["id_token"] = id_token


async def get_backend(request: Request) -> Backend:
    token = request.session.get("token")
    if not token:
        raise NotLoggedIn()
    return Backend(request.app.state.http, token)


Api = Annotated[Backend, Depends(get_backend)]


# --- rendering ------------------------------------------------------------------------


def context(request: Request, **ctx) -> dict:
    return {
        "request": request,
        "me": request.session.get("user"),
        "sso": request.session.get("sso", False),
        "tz": session_tz(request),
        "sso_enabled": settings.oidc_enabled,
        "sso_label": settings.sso_label,
        **ctx,
    }


def render(request: Request, name: str, status_code: int = 200, headers: dict | None = None, **ctx) -> HTMLResponse:
    return templates.TemplateResponse(request, name, context(request, **ctx), status_code=status_code, headers=headers)


def fragment(request: Request, name: str, **ctx) -> str:
    return templates.get_template(name).render(context(request, **ctx))


def toast(request: Request, message: str, level: str = "success") -> str:
    return fragment(request, "partials/toast.html", message=message, level=level)


def htmx_response(
    *parts: str, trigger: dict | None = None, trigger_after_swap: dict | None = None, reswap: str | None = None
) -> HTMLResponse:
    headers = {}
    if trigger:
        headers["HX-Trigger"] = json.dumps(trigger)
    if trigger_after_swap:
        headers["HX-Trigger-After-Swap"] = json.dumps(trigger_after_swap)
    if reswap:
        headers["HX-Reswap"] = reswap
    return HTMLResponse("".join(parts), headers=headers)


def changed(request: Request, message: str) -> HTMLResponse:
    """Success response for modal forms and deletes: toast, refresh views, close modal."""
    return htmx_response(toast(request, message), trigger={"dataChanged": True, "closeModal": True}, reswap="none")


def failed(request: Request, error: BackendError) -> HTMLResponse:
    return htmx_response(toast(request, error.detail, "danger"), reswap="none")
