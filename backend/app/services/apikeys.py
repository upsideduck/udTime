"""API keys managed by their owner. The plain key is returned once, on creation."""

import datetime as dt

from sqlalchemy import func, select
from sqlalchemy.orm import Session

from ..errors import DomainError, NotFound
from ..models import ApiKey, User, to_epoch, utcnow
from ..security import SCOPES, expand_scopes, generate_api_key, hash_api_key
from . import timeutil as tu

MAX_KEYS_PER_USER = 50


def key_dict(key: ApiKey, user: User) -> dict:
    expires_on = None
    if key.expires_at is not None and to_epoch(key.expires_at):
        # expires_at is the first moment after the last valid day
        expires_on = tu.local_date(to_epoch(key.expires_at) - 1, tu.user_tz(user)).isoformat()
    return {
        "id": key.id,
        "name": key.name,
        "scopes": sorted(expand_scopes(key.scopes)),
        "is_active": bool(key.is_active),
        "expires_on": expires_on,
        "expired": key.expires_at is not None and bool(to_epoch(key.expires_at)) and key.expires_at < utcnow(),
        "last_used_at": to_epoch(key.last_used_at) or None,
        "created_at": to_epoch(key.created_at) or None,
        "legacy": len(key.key_hash) == 32,  # md5 hash from the PHP app
    }


def _check_scopes(scopes: list[str]) -> str:
    unknown = sorted(set(scopes) - SCOPES)
    if unknown:
        raise DomainError(f"Unknown scope(s): {', '.join(unknown)}")
    if not scopes:
        raise DomainError("Choose at least one scope")
    return ";".join(sorted(set(scopes)))


def _expires_at(expires_on: dt.date | None, user: User) -> dt.datetime | None:
    if expires_on is None:
        return None
    tz = tu.user_tz(user)
    if expires_on < dt.datetime.now(tz).date():
        raise DomainError("The expiry date is in the past")
    tu.check_year(expires_on.year)
    end = dt.datetime.combine(expires_on + dt.timedelta(days=1), dt.time(), tz)
    return end.astimezone(dt.timezone.utc).replace(tzinfo=None)


def _get(db: Session, user: User, key_id: int) -> ApiKey:
    key = db.get(ApiKey, key_id)
    if key is None or key.member_id != user.member_id:
        raise NotFound("API key not found")
    return key


def list_keys(db: Session, user: User) -> list[dict]:
    rows = db.scalars(select(ApiKey).where(ApiKey.member_id == user.member_id).order_by(ApiKey.id))
    return [key_dict(k, user) for k in rows]


def create_key(db: Session, user: User, name: str, scopes: list[str], expires_on: dt.date | None) -> dict:
    count = db.scalar(select(func.count()).select_from(ApiKey).where(ApiKey.member_id == user.member_id))
    if count >= MAX_KEYS_PER_USER:
        raise DomainError(f"You can have at most {MAX_KEYS_PER_USER} API keys")
    name = name.strip()
    if not name:
        raise DomainError("Name is required")
    plain = generate_api_key()
    key = ApiKey(
        member_id=user.member_id,
        key_hash=hash_api_key(plain),
        scopes=_check_scopes(scopes),
        name=name,
        expires_at=_expires_at(expires_on, user),
        is_active=1,
        created_at=utcnow(),
    )
    db.add(key)
    db.commit()
    return {**key_dict(key, user), "key": plain}


def update_key(db: Session, user: User, key_id: int, changes: dict) -> dict:
    key = _get(db, user, key_id)
    if "name" in changes and changes["name"] is not None:
        if not changes["name"].strip():
            raise DomainError("Name is required")
        key.name = changes["name"].strip()
    if "scopes" in changes and changes["scopes"] is not None:
        key.scopes = _check_scopes(changes["scopes"])
    if "expires_on" in changes:
        key.expires_at = _expires_at(changes["expires_on"], user)
    if "is_active" in changes and changes["is_active"] is not None:
        key.is_active = int(changes["is_active"])
    db.commit()
    return key_dict(key, user)


def delete_key(db: Session, user: User, key_id: int) -> None:
    db.delete(_get(db, user, key_id))
    db.commit()
