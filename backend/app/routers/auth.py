import hmac
from typing import Annotated

from fastapi import APIRouter, Depends, File, Header, HTTPException, Response, UploadFile, status

from ..config import settings
from ..deps import DB, require
from ..models import User
from ..schemas import LoginIn, Message, RegisterIn, TokenOut, TrustedLoginIn
from ..security import create_access_token
from ..services import users
from ..services.serialize import user_dict

router = APIRouter(tags=["auth"])


@router.post("/auth/login", response_model=TokenOut)
def login(body: LoginIn, db: DB):
    user = users.authenticate(db, body.username, body.password)
    if user is None:
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Invalid username or password")
    return TokenOut(access_token=create_access_token(user.member_id), user=user_dict(user))


@router.post("/auth/trusted", response_model=TokenOut, include_in_schema=False)
def trusted_login(body: TrustedLoginIn, db: DB, x_service_secret: Annotated[str | None, Header()] = None):
    """Used by the frontend when a reverse proxy (Authentik) has authenticated the user."""
    if not x_service_secret or not hmac.compare_digest(x_service_secret, settings.frontend_service_secret):
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
