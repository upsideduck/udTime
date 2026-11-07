"""The running timer: free -> work -> break -> work -> free.

State lives in userdb.activeperiod / activetype. Ports goWork/goOnBreak (func_start.php),
endWork/endBreak (func_end.php), api_currentperiod2.php and the "newperiod" action of
api_attachproject.php. Start/end calls are idempotent on the timestamp so offline
clients can safely resend.
"""

from sqlalchemy import func, select
from sqlalchemy.orm import Session

from ..errors import Conflict, DomainError
from ..models import Break, Project, User, Work, to_epoch
from . import timeutil as tu
from .periods import get_project
from .serialize import break_dict, project_dict, work_dict
from .stats import update_stats

KEEP_COMMENT = "NOCHANGE"


def _set_state(user: User, period_id: int | None, kind: str | None) -> None:
    user.activeperiod, user.activetype = period_id, kind


def _active_work(db: Session, user: User) -> Work | None:
    if user.activetype == "work" and user.activeperiod:
        return db.get(Work, user.activeperiod)
    if user.activetype == "break" and user.activeperiod:
        brk = db.get(Break, user.activeperiod)
        return db.get(Work, brk.parent_id) if brk else None
    return None


def _repair_state(db: Session, user: User) -> None:
    """Clear an active pointer that refers to a missing or finished row."""
    if not user.activetype:
        return
    model = Work if user.activetype == "work" else Break
    row = db.get(model, user.activeperiod) if user.activeperiod else None
    if row is None or row.member_id != user.member_id or row.endtime is not None:
        _set_state(user, None, None)
        db.commit()


def _apply_comment(target, comment: str | None) -> None:
    if comment is not None and comment != KEEP_COMMENT:
        target.comment = comment


def start_work(
    db: Session, user: User, ts: int, comment: str | None = None, project_id: int | None = None, resend_check=True
) -> str:
    _repair_state(db, user)
    if resend_check:
        existing = db.scalars(select(Work.id).where(Work.member_id == user.member_id, Work.starttime == ts)).first()
        active = _active_work(db, user)
        if existing and (active is None or active.id == existing):
            return "Work already registered (synced)"
    if user.activetype:
        raise Conflict("A period is already active")
    if project_id is not None:
        get_project(db, user, project_id)
    work = Work(member_id=user.member_id, starttime=ts, comment=comment or None, logged=0, project_id=project_id)
    db.add(work)
    db.flush()
    _set_state(user, work.id, "work")
    db.commit()
    return f"Work started {tu.local_dt(ts, tu.user_tz(user)):%H:%M:%S}"


def start_break(db: Session, user: User, ts: int, comment: str | None = None) -> str:
    if db.scalars(select(Break.id).where(Break.member_id == user.member_id, Break.starttime == ts)).first():
        return "Break already registered (synced)"
    _repair_state(db, user)
    if user.activetype == "break":
        raise Conflict("Already on a break")
    if user.activetype != "work":
        raise Conflict("No work period active")
    work = db.get(Work, user.activeperiod)
    if ts < work.starttime:
        raise DomainError("Break cannot start before the work period")
    if ts > tu.now_ts() + 60:
        raise DomainError("Break cannot start in the future")
    last_end = db.scalar(select(func.max(Break.endtime)).where(Break.parent_id == work.id))
    if last_end is not None and ts < last_end:
        raise DomainError("Break cannot start before the previous break ended")
    brk = Break(member_id=user.member_id, parent_id=work.id, starttime=ts, logged=0)
    db.add(brk)
    if comment:
        _apply_comment(work, comment)
    db.flush()
    _set_state(user, brk.id, "break")
    db.commit()
    return f"Break started {tu.local_dt(ts, tu.user_tz(user)):%H:%M:%S}"


def end_break(db: Session, user: User, ts: int, comment: str | None = None) -> str:
    if db.scalars(select(Break.id).where(Break.member_id == user.member_id, Break.endtime == ts)).first():
        return "Break end already registered (synced)"
    _repair_state(db, user)
    if user.activetype != "break":
        raise Conflict("Not on a break")
    brk = db.get(Break, user.activeperiod)
    if ts < brk.starttime:
        raise DomainError("Break cannot end before it started")
    brk.endtime = ts
    _apply_comment(brk, comment or None)
    _set_state(user, brk.parent_id, "work")
    db.commit()
    tz = tu.user_tz(user)
    return f"Break ended {tu.local_dt(brk.starttime, tz):%H:%M:%S} - {tu.local_dt(ts, tz):%H:%M:%S}"


def end_work(db: Session, user: User, ts: int, comment: str | None = None) -> str:
    """Ends the work period, ending an active break first (as call_api's endwork did)."""
    messages = []
    _repair_state(db, user)
    active = _active_work(db, user)
    # A resent end only matches when no period started at or before it is still running
    if (active is None or active.starttime > ts) and db.scalars(
        select(Work.id).where(Work.member_id == user.member_id, Work.endtime == ts)
    ).first():
        return "Work end already registered (synced)"
    if user.activetype == "break":
        messages.append(end_break(db, user, ts, KEEP_COMMENT))
    if user.activetype != "work":
        raise Conflict("No work period active")
    work = db.get(Work, user.activeperiod)
    if ts < work.starttime:
        raise DomainError("Work cannot end before it started")
    breaks = list(db.scalars(select(Break).where(Break.parent_id == work.id)))
    if any(b.endtime is None for b in breaks):
        raise DomainError("A break is still open")
    if any(b.endtime > ts for b in breaks):
        raise DomainError("Work cannot end before its breaks")
    work.endtime = ts
    _apply_comment(work, comment)
    _set_state(user, None, None)
    db.flush()
    update_stats(db, user, work.starttime, ts)
    db.commit()
    tz = tu.user_tz(user)
    messages.append(
        f"Work ended {tu.local_dt(work.starttime, tz):%H:%M:%S} - {tu.local_dt(ts, tz):%H:%M:%S}, "
        f"including {len(breaks)} breaks"
    )
    return ". ".join(messages)


def switch_project(db: Session, user: User, project_id: int | None) -> str:
    """End the active period now and start a new one with the given project."""
    _repair_state(db, user)
    if not user.activetype:
        raise Conflict("No active period")
    ts = tu.now_ts()
    message = end_work(db, user, ts, KEEP_COMMENT)
    start_work(db, user, ts, None, project_id, resend_check=False)
    return message + ". New period started"


def current_state(db: Session, user: User) -> dict:
    _repair_state(db, user)
    last_modified = max(
        to_epoch(db.scalar(select(func.max(Work.modified)).where(Work.member_id == user.member_id))),
        to_epoch(db.scalar(select(func.max(Break.modified)).where(Break.member_id == user.member_id))),
    )
    state = {
        "state": user.activetype or "free",
        "current_id": user.activeperiod,
        "workweek": user.dworkweek,
        "last_modified": last_modified,
        "server_time": tu.now_ts(),
    }
    work = _active_work(db, user)
    if work is None:
        return state | {"work": None, "project": None, "clock_start": None, "clock_offset": 0}

    breaks = list(db.scalars(select(Break).where(Break.parent_id == work.id).order_by(Break.starttime)))
    finished = sum(b.endtime - b.starttime for b in breaks if b.endtime is not None)
    project = db.get(Project, work.project_id) if work.project_id else None
    if user.activetype == "break":
        current_break = next(b for b in breaks if b.id == user.activeperiod)
        clock_start, clock_offset = current_break.starttime, 0
    else:
        clock_start, clock_offset = work.starttime, finished
    return state | {
        "work_id": work.id,
        "work": work_dict(work, breaks),
        "project": project_dict(project) if project else None,
        "all_break_time": finished,
        "clock_start": clock_start,
        "clock_offset": clock_offset,
        "breaks": [break_dict(b) for b in breaks],
    }
