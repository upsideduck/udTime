from typing import Annotated

from fastapi import APIRouter, Form, Request
from fastapi.responses import RedirectResponse

from ..api import Backend, BackendError, NotLoggedIn
from ..config import settings
from ..web import get_backend, render, sso_username, store_login

router = APIRouter()


@router.get("/login")
async def login_form(request: Request):
    if request.session.get("token"):
        return RedirectResponse("/", status_code=303)
    return render(request, "login.html")


@router.get("/sso")
async def sso_login(request: Request):
    """Target of the "Sign in with udauth" button. The reverse proxy must protect this path,
    so the request arrives with the Authentik username header once the user has signed in."""
    if not settings.trust_authentik_header:
        return render(request, "login.html", status_code=400, error="Single sign-on is not enabled")
    if not sso_username(request):
        return render(request, "login.html", status_code=401,
                      error=f"No {settings.sso_label} sign-in was received. Is /sso protected by the proxy?")
    try:
        await get_backend(request)
    except NotLoggedIn:
        return render(request, "login.html", status_code=403,
                      error=f"{sso_username(request)} has no udTime account")
    return RedirectResponse("/", status_code=303)


@router.post("/login")
async def login(request: Request, username: Annotated[str, Form()], password: Annotated[str, Form()]):
    try:
        result = await Backend(request.app.state.http).post("/auth/login", {"username": username, "password": password})
    except BackendError as exc:
        return render(request, "login.html", status_code=401, error=exc.detail, username=username)
    store_login(request, result, sso=False)
    return RedirectResponse("/", status_code=303)


@router.get("/logout")
async def logout(request: Request):
    was_sso = request.session.get("sso", False)
    request.session.clear()
    if was_sso:
        return RedirectResponse(settings.authentik_logout_url, status_code=303)
    return render(request, "logged_out.html")


@router.get("/register")
async def register_form(request: Request):
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
