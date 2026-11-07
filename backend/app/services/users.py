from sqlalchemy import select
from sqlalchemy.orm import Session

from ..errors import Conflict, DomainError
from ..models import User
from ..security import hash_password, verify_password
from . import timeutil as tu

MAX_AVATAR_BYTES = 20000


def find_by_username(db: Session, username: str) -> User | None:
    return db.scalars(select(User).where(User.username == username)).first()


def authenticate(db: Session, username: str, password: str) -> User | None:
    user = find_by_username(db, username)
    if user is None:
        return None
    valid, needs_rehash = verify_password(password, user.password)
    if not valid:
        return None
    if needs_rehash:
        user.password = hash_password(password)
        db.commit()
    return user


def register(
    db: Session,
    username: str,
    password: str,
    dworkweek: int,
    daysinworkweek: int = 5,
    timezone: str | None = None,
    firstname: str | None = None,
    lastname: str | None = None,
) -> User:
    username = username.strip()
    if not username or not password:
        raise DomainError("Username and password are required")
    if dworkweek <= 0 or not 1 <= daysinworkweek <= 7:
        raise DomainError("Invalid work week")
    if find_by_username(db, username):
        raise Conflict("Username already in use")
    now = tu.now_ts()
    user = User(
        username=username,
        password=hash_password(password),
        firstname=firstname,
        lastname=lastname,
        registerdate=now,
        dworkweek=dworkweek,
        daysinworkweek=daysinworkweek,
        offset=0,
        timezone=timezone or "",
        tempfiled=0,
        statsstartdate=now,
        admin=0,
    )
    user.timezone = tu.user_tz(user).key
    user.statsstartdate = tu.midnight(tu.local_date(now, tu.user_tz(user)), tu.user_tz(user))
    db.add(user)
    db.commit()
    return user


def set_avatar(db: Session, user: User, data: bytes) -> None:
    if not data:
        raise DomainError("No file uploaded")
    if len(data) > MAX_AVATAR_BYTES:
        raise DomainError(f"Avatar must be smaller than {MAX_AVATAR_BYTES} bytes")
    user.avatar = data
    db.commit()


def avatar_media_type(data: bytes) -> str:
    if data.startswith(b"\x89PNG"):
        return "image/png"
    if data[:6] in (b"GIF87a", b"GIF89a"):
        return "image/gif"
    return "image/jpeg"
