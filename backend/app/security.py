"""Password, token and API key handling.

Legacy rows store unsalted md5 hex digests (passwords and API keys). Passwords are
verified against md5 and transparently upgraded to argon2 on successful login. New API
keys are stored as sha256 hex (fits apikeys.key_hash char(64)); md5 is still accepted.
"""

import datetime as dt
import hashlib
import hmac
import re
import secrets

import jwt
from argon2 import PasswordHasher
from argon2.exceptions import InvalidHashError, VerificationError, VerifyMismatchError

from .config import settings

_hasher = PasswordHasher()
_MD5 = re.compile(r"^[0-9a-f]{32}$")


def hash_password(password: str) -> str:
    return _hasher.hash(password)


_DUMMY_HASH = _hasher.hash("udtime-dummy-password")


def burn_password_check(password: str) -> None:
    """Spend the same time as a real check so unknown usernames can't be detected by timing."""
    try:
        _hasher.verify(_DUMMY_HASH, password)
    except (VerifyMismatchError, VerificationError, InvalidHashError):
        pass


def verify_password(password: str, stored: str) -> tuple[bool, bool]:
    """Returns (valid, needs_rehash)."""
    if not stored:
        return False, False
    if _MD5.match(stored):
        digest = hashlib.md5(password.encode()).hexdigest()
        return hmac.compare_digest(digest, stored), True
    try:
        _hasher.verify(stored, password)
    except (VerifyMismatchError, VerificationError, InvalidHashError):
        return False, False
    return True, _hasher.check_needs_rehash(stored)


def create_access_token(member_id: int, sso: bool = False) -> str:
    now = dt.datetime.now(dt.timezone.utc)
    payload = {
        "sub": str(member_id),
        "sso": sso,
        "iat": now,
        "exp": now + dt.timedelta(minutes=settings.jwt_expire_minutes),
    }
    return jwt.encode(payload, settings.jwt_secret, algorithm="HS256")


def decode_access_token(token: str) -> int | None:
    try:
        payload = jwt.decode(token, settings.jwt_secret, algorithms=["HS256"])
        return int(payload["sub"])
    except (jwt.PyJWTError, KeyError, ValueError):
        return None


def generate_api_key() -> str:
    return "udt_" + secrets.token_urlsafe(32)


def hash_api_key(key: str) -> str:
    return hashlib.sha256(key.encode()).hexdigest()


def api_key_lookup_hashes(key: str) -> list[str]:
    return [hash_api_key(key), hashlib.md5(key.encode()).hexdigest()]


# Scopes. A `:write` scope implies the matching `:read` scope.
SCOPES = {
    "timer:read",
    "timer:write",
    "periods:read",
    "periods:write",
    "absences:read",
    "absences:write",
    "projects:read",
    "projects:write",
    "stats:read",
    "sync:read",
    "export:read",
    "profile:read",
    "profile:write",
}

# Old call_api.php action names (stored in apikeys.scopes) mapped onto the new scopes.
LEGACY_SCOPES = {
    "newperiod": {"timer:write"},
    "startwork": {"timer:write"},
    "endwork": {"timer:write"},
    "startbreak": {"timer:write"},
    "endbreak": {"timer:write"},
    "ongoingperiod": {"timer:write"},
    "currentperiod": {"timer:read"},
    "currentperiod2": {"timer:read"},
    "setwork": {"periods:write"},
    "setbreak": {"periods:write"},
    "updatework": {"periods:write"},
    "updatebreak": {"periods:write"},
    "removework": {"periods:write"},
    "removebreak": {"periods:write"},
    "attachproject": {"periods:write", "projects:read"},
    "fetchperiods": {"periods:read", "absences:read"},
    "setasworktime": {"absences:write"},
    "setagainstworktime": {"absences:write"},
    "updateasworktime": {"absences:write"},
    "updateagainstworktime": {"absences:write"},
    "removeasworktime": {"absences:write"},
    "removeagainstworktime": {"absences:write"},
    "newproject": {"projects:write"},
    "statistics": {"stats:read"},
    "weektotals": {"stats:read"},
    "monthtotals": {"stats:read"},
    "weekdetail": {"stats:read"},
    "serverupdates": {"sync:read"},
}


def expand_scopes(raw: str) -> set[str]:
    """Parse apikeys.scopes (';' or ',' separated, new or legacy names) into new scopes."""
    scopes: set[str] = set()
    for item in re.split(r"[;,\s]+", raw or ""):
        item = item.strip()
        if not item:
            continue
        if item == "*":
            scopes |= SCOPES
        elif item in SCOPES:
            scopes.add(item)
        else:
            scopes |= LEGACY_SCOPES.get(item, set())
    scopes |= {s.replace(":write", ":read") for s in scopes if s.endswith(":write")}
    return scopes
