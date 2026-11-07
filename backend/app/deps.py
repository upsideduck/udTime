from dataclasses import dataclass
from typing import Annotated

from fastapi import Depends, HTTPException, Security, status
from fastapi.security import APIKeyHeader, HTTPAuthorizationCredentials, HTTPBearer
from sqlalchemy import select
from sqlalchemy.orm import Session

from .db import get_db
from .models import ApiKey, User, utcnow
from .security import api_key_lookup_hashes, decode_access_token, expand_scopes

DB = Annotated[Session, Depends(get_db)]


@dataclass
class Principal:
    user: User
    scopes: set[str] | None  # None: logged in with password/SSO, full access


def _unauthorized(detail: str) -> HTTPException:
    return HTTPException(status.HTTP_401_UNAUTHORIZED, detail, headers={"WWW-Authenticate": "Bearer"})


bearer_scheme = HTTPBearer(auto_error=False, description="Token from POST /api/v1/auth/login")
api_key_scheme = APIKeyHeader(name="X-API-Key", auto_error=False, description="API key (scoped)")


def get_principal(
    db: DB,
    bearer: Annotated[HTTPAuthorizationCredentials | None, Security(bearer_scheme)] = None,
    x_api_key: Annotated[str | None, Security(api_key_scheme)] = None,
) -> Principal:
    if x_api_key:
        key = db.scalars(
            select(ApiKey).where(ApiKey.key_hash.in_(api_key_lookup_hashes(x_api_key)))
        ).first()
        if key is None or not key.is_active:
            raise _unauthorized("Invalid API key")
        if key.expires_at is not None and key.expires_at < utcnow():
            raise _unauthorized("API key has expired")
        user = db.get(User, key.member_id)
        if user is None:
            raise _unauthorized("Invalid API key")
        key.last_used_at = utcnow()
        db.commit()
        return Principal(user, expand_scopes(key.scopes))

    if bearer and bearer.credentials:
        member_id = decode_access_token(bearer.credentials)
        user = db.get(User, member_id) if member_id is not None else None
        if user is None:
            raise _unauthorized("Invalid or expired token")
        return Principal(user, None)

    raise _unauthorized("Not authenticated")


def require(scope: str | None = None):
    """Dependency returning the current user, checking the API key scope if given."""

    def dependency(principal: Annotated[Principal, Depends(get_principal)]) -> User:
        if scope and principal.scopes is not None and scope not in principal.scopes:
            raise HTTPException(status.HTTP_403_FORBIDDEN, f"API key lacks scope '{scope}'")
        return principal.user

    return dependency


def require_admin(principal: Annotated[Principal, Depends(get_principal)]) -> User:
    if principal.scopes is not None or not principal.user.admin:
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Admin only")
    return principal.user
