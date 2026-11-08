import hmac
from typing import Annotated

from fastapi import APIRouter, Depends, File, Header, HTTPException, Request, Response, UploadFile, status

from ..config import settings
from ..deps import DB, require, require_login
from ..models import User
from ..ratelimit import by_ip, by_username
from ..schemas import LoginIn, Message, PasswordChangeIn, ProfilePatch, RegisterIn, TokenOut, TrustedLoginIn
from ..security import create_access_token
from ..services import users
from ..services.serialize import user_dict

router = APIRouter(tags=["auth"])


def _valid_service_secret(value: str | None) -> bool:
    return bool(value) and hmac.compare_digest(value, settings.frontend_service_secret)


@router.get("/auth/options")
def auth_options():
    """Public: which sign-in options the server offers."""
    return {"allow_registration": settings.allow_registration}


@router.post("/auth/login", response_model=TokenOut)
def login(
    body: LoginIn,
    db: DB,
    request: Request,
    x_service_secret: Annotated[str | None, Header()] = None,
    x_client_ip: Annotated[str | None, Header()] = None,
):
    """Failed attempts are limited per username and per client IP. The frontend passes the
    real client IP in X-Client-IP, which is only trusted together with the service secret."""
    client_ip = x_client_ip if x_client_ip and _valid_service_secret(x_service_secret) else None
    client_ip = client_ip or (request.client.host if request.client else "unknown")
    user_key, ip_key = f"user:{body.username.strip().lower()}", f"ip:{client_ip}"
    wait = max(by_username.retry_after(user_key), by_ip.retry_after(ip_key))
    if wait:
        raise HTTPException(
            status.HTTP_429_TOO_MANY_REQUESTS,
            f"Too many failed sign-in attempts. Try again in {(wait + 59) // 60} minutes.",
            headers={"Retry-After": str(wait)},
        )
    user = users.authenticate(db, body.username, body.password)
    if user is None:
        by_username.fail(user_key)
        by_ip.fail(ip_key)
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Invalid username or password")
    by_username.reset(user_key)
    return TokenOut(access_token=create_access_token(user.member_id), user=user_dict(user))


@router.post("/auth/trusted", response_model=TokenOut, include_in_schema=False)
def trusted_login(body: TrustedLoginIn, db: DB, x_service_secret: Annotated[str | None, Header()] = None):
    """Used by the frontend after it has verified an OpenID Connect sign-in (udauth)."""
    if not _valid_service_secret(x_service_secret):
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Invalid service secret")
    user = users.find_by_username(db, body.username)
    if user is None:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "No account for this user")
    return TokenOut(access_token=create_access_token(user.member_id, sso=True), user=user_dict(user))


@router.get("/auth/me")
def me(user: Annotated[User, Depends(require("profile:read"))]):
    return user_dict(user)


@router.post("/users", status_code=201)
def register(body: RegisterIn, db: DB):
    if not settings.allow_registration:
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Registration is disabled")
    user = users.register(
        db,
        body.username,
        body.password,
        round(body.workweek_hours * 3600),
        body.daysinworkweek,
        body.timezone,
        body.firstname,
        body.lastname,
    )
    return user_dict(user)


@router.patch("/users/me")
def update_me(body: ProfilePatch, db: DB, user: Annotated[User, Depends(require("profile:write"))]):
    """Change the starting offset or timezone. The work week is changed through /schedules."""
    users.update_profile(db, user, body.model_dump(exclude_unset=True))
    return user_dict(user)


@router.put("/users/me/password", response_model=Message)
def change_password(body: PasswordChangeIn, db: DB, user: Annotated[User, Depends(require_login)]):
    """Needs the current password; wrong guesses count towards the sign-in limit for the user."""
    user_key = f"user:{user.username.strip().lower()}"
    wait = by_username.retry_after(user_key)
    if wait:
        raise HTTPException(
            status.HTTP_429_TOO_MANY_REQUESTS,
            f"Too many wrong passwords. Try again in {(wait + 59) // 60} minutes.",
            headers={"Retry-After": str(wait)},
        )
    if not users.change_password(db, user, body.current_password, body.new_password):
        by_username.fail(user_key)
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "The current password is wrong")
    by_username.reset(user_key)
    return Message(message="Password changed")


@router.get("/users/me/avatar")
def get_avatar(user: Annotated[User, Depends(require("profile:read"))]):
    if not user.avatar:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "No avatar")
    return Response(user.avatar, media_type=users.avatar_media_type(user.avatar))


@router.put("/users/me/avatar", response_model=Message)
async def put_avatar(
    db: DB, user: Annotated[User, Depends(require("profile:write"))], file: Annotated[UploadFile, File()]
):
    users.set_avatar(db, user, await file.read(users.MAX_AVATAR_BYTES + 1))
    return Message(message="Avatar updated")
