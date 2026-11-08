import time
from typing import Annotated

from fastapi import APIRouter, Form, Request
from fastapi.responses import RedirectResponse

from ..api import Backend, BackendError
from ..config import settings
from ..oidc import OIDCError
from ..web import render, store_login

router = APIRouter()

OIDC_LOGIN_MAX_AGE = 10 * 60


async def _registration_open(request: Request) -> bool:
    try:
        return bool((await Backend(request.app.state.http).get("/auth/options"))["allow_registration"])
    except (BackendError, KeyError):
        return False


async def _login_page(request: Request, status_code: int = 200, **ctx):
    return render(request, "login.html", status_code=status_code,
                  registration_open=await _registration_open(request), **ctx)


@router.get("/login")
async def login_form(request: Request):
    if request.session.get("token"):
        return RedirectResponse("/", status_code=303)
    return await _login_page(request)


@router.post("/login")
async def login(request: Request, username: Annotated[str, Form()], password: Annotated[str, Form()]):
    client_ip = request.client.host if request.client else None
    try:
        result = await Backend(request.app.state.http).password_login(username, password, client_ip)
    except BackendError as exc:
        return await _login_page(request, status_code=exc.status_code, error=exc.detail, username=username)
    store_login(request, result, sso=False)
    return RedirectResponse("/", status_code=303)


# --- single sign-on (OpenID Connect) ------------------------------------------------------


def _redirect_uri(request: Request) -> str:
    return settings.oidc_redirect_uri or str(request.url_for("sso_callback"))


@router.get("/sso")
async def sso_start(request: Request):
    """Start sign-in with the identity provider ("Sign in with udauth")."""
    if not settings.oidc_enabled:
        return await _login_page(request, status_code=404, error="Single sign-on is not configured")
    oidc = request.app.state.oidc
    login = oidc.new_login()
    try:
        url = await oidc.authorization_url(login, _redirect_uri(request))
    except OIDCError as exc:
        return await _login_page(request, status_code=502, error=str(exc))
    request.session["oidc"] = login
    return RedirectResponse(url, status_code=303)


@router.get("/sso/callback", name="sso_callback")
async def sso_callback(
    request: Request, code: str = "", state: str = "", error: str = ""
):
    login = request.session.pop("oidc", None)
    if login is None and request.session.get("token"):
        return RedirectResponse("/", status_code=303)  # already signed in (e.g. reloaded callback)
    if error:
        # Only show the provider's error code (a short token), never free text from the URL
        code_text = error if error.replace("_", "").isalnum() and len(error) <= 40 else "unknown_error"
        return await _login_page(request, status_code=401, error=f"Sign-in was cancelled or failed ({code_text})")
    if not login or not state or state != login.get("state") or time.time() - login["started"] > OIDC_LOGIN_MAX_AGE:
        return await _login_page(request, status_code=400, error="Sign-in expired or was not started here. Try again.")
    oidc = request.app.state.oidc
    try:
        tokens = await oidc.exchange_code(code, _redirect_uri(request), login["verifier"])
        claims = await oidc.verify_id_token(tokens.get("id_token", ""), login["nonce"])
    except OIDCError as exc:
        return await _login_page(request, status_code=401, error=str(exc))
    username = claims.get(settings.oidc_username_claim)
    if not username:
        return await _login_page(request, status_code=401,
                                 error=f"The identity provider sent no '{settings.oidc_username_claim}' claim")
    try:
        result = await Backend(request.app.state.http).trusted_login(username)
    except BackendError:
        return await _login_page(request, status_code=403, error=f"{username} has no udTime account")
    store_login(request, result, sso=True, id_token=tokens.get("id_token"))
    return RedirectResponse("/", status_code=303)


@router.get("/logout")
async def logout(request: Request):
    was_sso = request.session.get("sso", False)
    id_token = request.session.get("id_token")
    request.session.clear()
    if was_sso and settings.oidc_enabled:
        url = await request.app.state.oidc.logout_url(id_token, str(request.url_for("login_form")))
        if url:
            return RedirectResponse(url, status_code=303)
    return render(request, "logged_out.html")


# --- registration -----------------------------------------------------------------------


@router.get("/register")
async def register_form(request: Request):
    if not await _registration_open(request):
        return RedirectResponse("/login", status_code=303)
    return render(request, "register.html", form={"timezone": settings.default_timezone, "workweek_hours": 40,
                                                  "daysinworkweek": 5})


@router.post("/register")
async def register(
    request: Request,
    username: Annotated[str, Form()],
    password: Annotated[str, Form()],
    cpassword: Annotated[str, Form()],
    workweek_hours: Annotated[float, Form()],
    daysinworkweek: Annotated[int, Form()] = 5,
    timezone: Annotated[str, Form()] = "",
    firstname: Annotated[str, Form()] = "",
    lastname: Annotated[str, Form()] = "",
):
    form = dict(username=username, workweek_hours=workweek_hours, daysinworkweek=daysinworkweek,
                timezone=timezone, firstname=firstname, lastname=lastname)
    if password != cpassword:
        return render(request, "register.html", status_code=400, error="Passwords do not match", form=form)
    try:
        await Backend(request.app.state.http).post(
            "/users", {**form, "password": password, "firstname": firstname or None, "lastname": lastname or None,
                       "timezone": timezone or None}
        )
    except BackendError as exc:
        return render(request, "register.html", status_code=exc.status_code, error=exc.detail, form=form)
    return render(request, "register_success.html", username=username)
