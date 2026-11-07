"""asworktime (counts as worked: vacation, sick leave) and againstworktime (reduces time
to work: holidays). Ports timespan::setasworktime/setagainstworktime, the update/remove
functions in func_edit.php / func_remove.php and lookupsTable (func_misc.php)."""

import datetime as dt

from sqlalchemy import select
from sqlalchemy.orm import Session

from ..errors import DomainError, NotFound
from ..models import AgainstWorkTime, Lookup, User
from . import timeutil as tu
from .periods import record_deleted
from .serialize import absence_dict
from .stats import ABSENCE_MODELS, update_stats

KINDS = tuple(ABSENCE_MODELS)


def _model(kind: str):
    if kind not in ABSENCE_MODELS:
        raise NotFound(f"Unknown absence kind '{kind}'")
    return ABSENCE_MODELS[kind]


def lookups(db: Session, kind: str) -> list[dict]:
    _model(kind)
    rows = db.scalars(select(Lookup).where(Lookup.type == kind).order_by(Lookup.position, Lookup.code))
    return [{"code": r.code, "name": r.name, "position": r.position} for r in rows]


def _labels(db: Session, kind: str) -> dict[int, str]:
    return {r["code"]: r["name"] for r in lookups(db, kind)}


def _check_type(db: Session, kind: str, type_code: int) -> None:
    if type_code not in _labels(db, kind):
        raise DomainError(f"Unknown {kind} type {type_code}")


def _get(db: Session, user: User, kind: str, item_id: int):
    model = _model(kind)
    row = db.scalars(select(model).where(model.id == item_id, model.member_id == user.member_id)).first()
    if row is None:
        raise NotFound("Item not found")
    return row


def _update_day_stats(db: Session, user: User, day: dt.date) -> None:
    tz = tu.user_tz(user)
    update_stats(db, user, *tu.date_span(day, day, tz))


def list_absences(db: Session, user: User, kind: str, first: dt.date, last: dt.date) -> list[dict]:
    model = _model(kind)
    labels = _labels(db, kind)
    rows = db.scalars(
        select(model).where(model.member_id == user.member_id, model.date.between(first, last)).order_by(model.date)
    )
    return [absence_dict(kind, r, labels.get(r.type)) for r in rows]


def get_absence(db: Session, user: User, kind: str, item_id: int) -> dict:
    row = _get(db, user, kind, item_id)
    return absence_dict(kind, row, _labels(db, kind).get(row.type))


def add_range(
    db: Session,
    user: User,
    kind: str,
    first: dt.date,
    last: dt.date,
    type_code: int,
    time: int | None,
    comment: str | None = None,
) -> dict:
    """One row per weekday (Mon-Fri) in [first, last] that doesn't have one yet.
    time 0/None means a standard working day (dworkweek / daysinworkweek)."""
    model = _model(kind)
    if last < first:
        raise DomainError("End date must be on or after start date")
    _check_type(db, kind, type_code)
    seconds = time or round(user.day_length)
    existing = set(
        db.scalars(select(model.date).where(model.member_id == user.member_id, model.date.between(first, last)))
    )
    labels = _labels(db, kind)

    created, skipped = [], []
    day = first
    while day <= last:
        if day.isoweekday() >= 6:
            skipped.append({"date": day.isoformat(), "reason": "weekend"})
        elif day in existing:
            skipped.append({"date": day.isoformat(), "reason": "already registered"})
        else:
            row = model(member_id=user.member_id, date=day, time=seconds, type=type_code)
            if comment is not None and model is AgainstWorkTime:
                row.comment = comment
            db.add(row)
            created.append(row)
        day += dt.timedelta(days=1)
    db.flush()
    tz = tu.user_tz(user)
    update_stats(db, user, *tu.date_span(first, last, tz))
    db.commit()
    return {"created": [absence_dict(kind, r, labels.get(r.type)) for r in created], "skipped": skipped}


def update_absence(
    db: Session, user: User, kind: str, item_id: int, time: int | None, type_code: int | None, comment: str | None
) -> dict:
    row = _get(db, user, kind, item_id)
    if time is not None:
        if time < 0:
            raise DomainError("Time must be positive")
        row.time = time
    if type_code is not None:
        _check_type(db, kind, type_code)
        row.type = type_code
    if comment is not None and isinstance(row, AgainstWorkTime):
        row.comment = comment
    db.flush()
    _update_day_stats(db, user, row.date)
    db.commit()
    return absence_dict(kind, row, _labels(db, kind).get(row.type))


def delete_absence(db: Session, user: User, kind: str, item_id: int) -> None:
    row = _get(db, user, kind, item_id)
    day = row.date
    record_deleted(db, kind, row.id, user.member_id)
    db.delete(row)
    db.flush()
    _update_day_stats(db, user, day)
    db.commit()
