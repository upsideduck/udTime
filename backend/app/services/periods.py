"""Work periods and breaks: list, add, update, delete, attach project.

Ports addPeriod/updatePeriod (func_edit.php), removeWork/removeBreak (func_remove.php),
attachProject (func_projects.php) and fetchPeriodsArray/fetchWorkPeriod (func_fetch.php).
Every lookup is scoped to the member.
"""

from sqlalchemy import delete, select
from sqlalchemy.orm import Session

from ..config import settings
from ..errors import DomainError, NotFound
from ..models import Break, Deleted, Project, User, Work
from .serialize import break_dict, work_dict
from .stats import update_stats


def record_deleted(db: Session, kind: str, original_id: int, member_id: int) -> None:
    if not settings.db_has_delete_triggers:
        db.add(Deleted(type=kind, original_id=original_id, member_id=member_id))


def get_work(db: Session, user: User, work_id: int) -> Work:
    work = db.scalars(select(Work).where(Work.id == work_id, Work.member_id == user.member_id)).first()
    if work is None:
        raise NotFound("Work period not found")
    return work


def get_break(db: Session, user: User, break_id: int) -> Break:
    brk = db.scalars(select(Break).where(Break.id == break_id, Break.member_id == user.member_id)).first()
    if brk is None:
        raise NotFound("Break not found")
    return brk


def breaks_of(db: Session, work_id: int) -> list[Break]:
    return list(db.scalars(select(Break).where(Break.parent_id == work_id).order_by(Break.starttime)))


def work_detail(db: Session, user: User, work_id: int) -> dict:
    work = get_work(db, user, work_id)
    project = db.get(Project, work.project_id) if work.project_id else None
    return work_dict(work, breaks_of(db, work.id), project)


def list_work(db: Session, user: User, start: int, end: int, include_open: bool = False) -> list[dict]:
    query = select(Work).where(Work.member_id == user.member_id, Work.starttime.between(start, end))
    if not include_open:
        query = query.where(Work.endtime.is_not(None))
    works = list(db.scalars(query.order_by(Work.starttime)))
    breaks: dict[int, list[Break]] = {w.id: [] for w in works}
    if works:
        for b in db.scalars(select(Break).where(Break.parent_id.in_(breaks)).order_by(Break.starttime)):
            breaks[b.parent_id].append(b)
    return [work_dict(w, breaks[w.id]) for w in works]


def _check_span(start: int, end: int | None) -> None:
    if end is not None and end < start:
        raise DomainError("End time must be after start time")


def add_work(db: Session, user: User, start: int, end: int, comment: str | None, project_id: int | None) -> dict:
    _check_span(start, end)
    if project_id is not None:
        get_project(db, user, project_id)
    work = Work(member_id=user.member_id, starttime=start, endtime=end, comment=comment, logged=0, project_id=project_id)
    db.add(work)
    db.flush()
    update_stats(db, user, start, end)
    db.commit()
    return work_dict(work, [])


def add_break(db: Session, user: User, start: int, end: int, comment: str | None) -> dict:
    _check_span(start, end)
    parent = db.scalars(
        select(Work).where(
            Work.member_id == user.member_id,
            Work.starttime <= start,
            Work.endtime >= end,
        )
    ).first()
    if parent is None:
        raise DomainError("Break not within any work period")
    brk = Break(member_id=user.member_id, parent_id=parent.id, starttime=start, endtime=end, comment=comment, logged=0)
    db.add(brk)
    db.flush()
    update_stats(db, user, parent.starttime, parent.endtime)
    db.commit()
    return break_dict(brk)


def update_work(
    db: Session, user: User, work_id: int, start: int | None, end: int | None, comment: str | None
) -> dict:
    work = get_work(db, user, work_id)
    old_start, old_end = work.starttime, work.endtime
    new_start = work.starttime if start is None else start
    new_end = work.endtime if end is None else end
    if work.endtime is None and end is not None:
        raise DomainError("Period is active; end it from the timer")
    _check_span(new_start, new_end)

    breaks = breaks_of(db, work.id)
    if breaks:
        first_break = min(b.starttime for b in breaks)
        last_break = max((b.endtime or b.starttime) for b in breaks)
        if new_start > first_break or (new_end is not None and new_end < last_break):
            raise DomainError("Work period must contain all of its breaks")

    work.starttime, work.endtime, work.logged = new_start, new_end, 0
    if comment is not None:
        work.comment = comment
    db.flush()
    if new_end is not None:
        update_stats(db, user, new_start, new_end)
    if old_end is not None and (old_start, old_end) != (new_start, new_end):
        update_stats(db, user, old_start, old_end)
    db.commit()
    return work_dict(work, breaks)


def update_break(
    db: Session, user: User, break_id: int, start: int | None, end: int | None, comment: str | None
) -> dict:
    brk = get_break(db, user, break_id)
    parent = get_work(db, user, brk.parent_id)
    new_start = brk.starttime if start is None else start
    new_end = brk.endtime if end is None else end
    if brk.endtime is None and end is not None:
        raise DomainError("Break is active; end it from the timer")
    _check_span(new_start, new_end)
    if new_start < parent.starttime or (
        parent.endtime is not None and new_end is not None and new_end > parent.endtime
    ):
        raise DomainError("Break must be within its work period")

    brk.starttime, brk.endtime, brk.logged = new_start, new_end, 0
    if comment is not None:
        brk.comment = comment
    db.flush()
    if parent.endtime is not None:
        update_stats(db, user, parent.starttime, parent.endtime)
    db.commit()
    return break_dict(brk)


def delete_work(db: Session, user: User, work_id: int) -> None:
    work = get_work(db, user, work_id)
    breaks = breaks_of(db, work.id)
    active_ids = {work.id} if user.activetype == "work" else {b.id for b in breaks}
    if user.activetype and user.activeperiod in active_ids:
        user.activeperiod, user.activetype = None, None
    for b in breaks:
        record_deleted(db, "break", b.id, user.member_id)
    record_deleted(db, "work", work.id, user.member_id)
    db.execute(delete(Break).where(Break.parent_id == work.id))
    span = (work.starttime, work.endtime)
    db.delete(work)
    db.flush()
    if span[1] is not None:
        update_stats(db, user, *span)
    db.commit()


def delete_break(db: Session, user: User, break_id: int) -> None:
    brk = get_break(db, user, break_id)
    parent = db.get(Work, brk.parent_id)
    if user.activetype == "break" and user.activeperiod == brk.id:
        user.activeperiod, user.activetype = brk.parent_id, "work"
    record_deleted(db, "break", brk.id, user.member_id)
    db.delete(brk)
    db.flush()
    if parent is not None and parent.endtime is not None:
        update_stats(db, user, parent.starttime, parent.endtime)
    db.commit()


def get_project(db: Session, user: User, project_id: int) -> Project:
    project = db.scalars(
        select(Project).where(Project.id == project_id, Project.member_id == user.member_id)
    ).first()
    if project is None:
        raise NotFound("Project not found")
    return project


def set_project(db: Session, user: User, work_id: int, project_id: int | None) -> dict:
    work = get_work(db, user, work_id)
    project = get_project(db, user, project_id) if project_id is not None else None
    work.project_id = project.id if project else None
    db.commit()
    return work_dict(work, None, project)
