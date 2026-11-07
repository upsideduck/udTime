import datetime as dt
from typing import Annotated

from fastapi import APIRouter, Depends, Query, Response

from ..deps import DB, require
from ..errors import DomainError
from ..models import User
from ..schemas import BreakIn, PeriodPatch, ProjectAssignIn, WorkIn
from ..services import periods
from ..services import timeutil as tu
from ..services.serialize import break_dict

router = APIRouter(tags=["periods"])

Reader = Annotated[User, Depends(require("periods:read"))]
Writer = Annotated[User, Depends(require("periods:write"))]


def _span_from_query(user: User, year, week, month, start, end) -> tu.Span:
    tz = tu.user_tz(user)
    if year is not None and week is not None:
        return tu.week_span(year, week, tz)
    if year is not None and month is not None:
        return tu.month_span(year, month, tz)
    if start is not None and end is not None:
        return tu.date_span(start, end, tz)
    raise DomainError("Give year+week, year+month or from+to")


def _ts(value, user: User) -> int | None:
    return None if value is None else tu.parse_timestamp(value, tu.user_tz(user))


@router.get("/work")
def list_work(
    db: DB,
    user: Reader,
    year: int | None = None,
    week: int | None = None,
    month: int | None = None,
    start: Annotated[dt.date | None, Query(alias="from")] = None,
    end: Annotated[dt.date | None, Query(alias="to")] = None,
    include_open: bool = False,
):
    span = _span_from_query(user, year, week, month, start, end)
    return periods.list_work(db, user, *span, include_open=include_open)


@router.post("/work", status_code=201)
def add_work(body: WorkIn, db: DB, user: Writer):
    return periods.add_work(db, user, _ts(body.starttime, user), _ts(body.endtime, user), body.comment, body.project_id)


@router.get("/work/{work_id}")
def get_work(work_id: int, db: DB, user: Reader):
    return periods.work_detail(db, user, work_id)


@router.patch("/work/{work_id}")
def update_work(work_id: int, body: PeriodPatch, db: DB, user: Writer):
    return periods.update_work(db, user, work_id, _ts(body.starttime, user), _ts(body.endtime, user), body.comment)


@router.delete("/work/{work_id}", status_code=204)
def delete_work(work_id: int, db: DB, user: Writer):
    periods.delete_work(db, user, work_id)
    return Response(status_code=204)


@router.put("/work/{work_id}/project")
def set_project(work_id: int, body: ProjectAssignIn, db: DB, user: Writer):
    return periods.set_project(db, user, work_id, body.project_id)


@router.post("/breaks", status_code=201)
def add_break(body: BreakIn, db: DB, user: Writer):
    """The parent work period is the finished one that fully contains the break."""
    return periods.add_break(db, user, _ts(body.starttime, user), _ts(body.endtime, user), body.comment)


@router.get("/breaks/{break_id}")
def get_break(break_id: int, db: DB, user: Reader):
    return break_dict(periods.get_break(db, user, break_id))


@router.patch("/breaks/{break_id}")
def update_break(break_id: int, body: PeriodPatch, db: DB, user: Writer):
    return periods.update_break(db, user, break_id, _ts(body.starttime, user), _ts(body.endtime, user), body.comment)


@router.delete("/breaks/{break_id}", status_code=204)
def delete_break(break_id: int, db: DB, user: Writer):
    periods.delete_break(db, user, break_id)
    return Response(status_code=204)
