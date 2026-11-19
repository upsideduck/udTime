import io
import warnings
from zoneinfo import ZoneInfo, ZoneInfoNotFoundError

from PIL import Image, ImageOps, UnidentifiedImageError

from sqlalchemy import select
from sqlalchemy.orm import Session

from ..errors import Conflict, DomainError
from ..models import User
from ..security import burn_password_check, hash_password, verify_password
from . import schedule
from . import timeutil as tu

# Largest accepted upload; it is shrunk to AVATAR_SIZE before it is stored
MAX_AVATAR_BYTES = 10 * 1024 * 1024
AVATAR_SIZE = 256  # pixels, square
AVATAR_FORMATS = ["JPEG", "PNG", "GIF", "WEBP"]
MAX_AVATAR_PIXELS = 50_000_000  # refuse decompression bombs
STORED_AVATAR_LIMIT = 65535  # userdb.avatar is a MySQL BLOB


def find_by_username(db: Session, username: str) -> User | None:
    return db.scalars(select(User).where(User.username == username)).first()


def authenticate(db: Session, username: str, password: str) -> User | None:
    user = find_by_username(db, username)
    if user is None:
        burn_password_check(password)
        return None
    valid, needs_rehash = verify_password(password, user.password)
    if not valid:
        return None
    if needs_rehash:
        user.password = hash_password(password)
        db.commit()
    return user


def update_profile(db: Session, user: User, changes: dict) -> None:
    if changes.get("timezone") is not None:
        try:
            ZoneInfo(changes["timezone"])
        except (ZoneInfoNotFoundError, ValueError):
            raise DomainError(f"Unknown timezone: {changes['timezone']}") from None
    for field in ("offset", "timezone"):
        if changes.get(field) is not None:
            setattr(user, field, changes[field])
    db.commit()


def change_password(db: Session, user: User, current: str, new: str) -> bool:
    """Returns False when the current password is wrong."""
    valid, _ = verify_password(current, user.password)
    if not valid:
        return False
    if new == current:
        raise DomainError("The new password must differ from the current one")
    user.password = hash_password(new)
    db.commit()
    return True


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
    db.flush()
    schedule.create_base_period(db, user)
    db.commit()
    return user


def shrink_avatar(data: bytes) -> bytes:
    """Crop to a centred square, scale down to AVATAR_SIZE and re-encode.

    Rotation from EXIF is applied (phone photos). Animated images keep their first frame.
    Images with transparency are stored as PNG, everything else as JPEG.
    """
    try:
        with warnings.catch_warnings():
            warnings.simplefilter("error", Image.DecompressionBombWarning)
            image = Image.open(io.BytesIO(data), formats=AVATAR_FORMATS)
            if image.width * image.height > MAX_AVATAR_PIXELS:
                raise DomainError("The image has too many pixels")
            image.draft("RGB", (AVATAR_SIZE * 2, AVATAR_SIZE * 2))  # fast JPEG decoding at a smaller scale
            image = ImageOps.exif_transpose(image)
            image.load()
    except (UnidentifiedImageError, Image.DecompressionBombError, Image.DecompressionBombWarning, OSError,
            SyntaxError, ValueError) as exc:
        raise DomainError("The file is not a supported image (JPEG, PNG, GIF or WebP)") from exc

    has_alpha = image.mode in ("RGBA", "LA", "PA") or (image.mode == "P" and "transparency" in image.info)
    image = image.convert("RGBA" if has_alpha else "RGB")
    side = min(AVATAR_SIZE, image.width, image.height)
    image = ImageOps.fit(image, (side, side), Image.Resampling.LANCZOS)

    out = io.BytesIO()
    if has_alpha:
        image.save(out, "PNG", optimize=True)
        if out.tell() <= STORED_AVATAR_LIMIT:
            return out.getvalue()
        flat = Image.new("RGB", image.size, "white")  # too big as PNG: flatten and use JPEG
        flat.paste(image, mask=image.getchannel("A"))
        image, out = flat, io.BytesIO()
    for quality in (85, 70, 50):
        out.seek(0)
        out.truncate()
        image.save(out, "JPEG", quality=quality, optimize=True)
        if out.tell() <= STORED_AVATAR_LIMIT:
            break
    return out.getvalue()


def set_avatar(db: Session, user: User, data: bytes) -> None:
    if not data:
        raise DomainError("No file uploaded")
    if len(data) > MAX_AVATAR_BYTES:
        raise DomainError(f"The image must be smaller than {MAX_AVATAR_BYTES // (1024 * 1024)} MB")
    user.avatar = shrink_avatar(data)
    db.commit()


def avatar_media_type(data: bytes) -> str:
    if data.startswith(b"\x89PNG"):
        return "image/png"
    if data[:6] in (b"GIF87a", b"GIF89a"):
        return "image/gif"
    return "image/jpeg"
