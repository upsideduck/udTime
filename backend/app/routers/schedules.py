from typing import Annotated

from fastapi import APIRouter, Depends

from ..deps import DB, require
from ..models import User
from ..schemas import ScheduleIn, SchedulePatch, SchedulePreviewIn
from ..services import stats
from ..services import timeutil as tu
from ..services.schedule import Change, Schedules, apply_change, list_periods

router = APIRouter(prefix="/schedules", tags=["schedules"])

Reader = Annotated[User, Depends(require("profile:read"))]
Writer = Annotated[User, Depends(require("profile:write"))]


def _apply(db, user: User, change: Change) -> list[dict]:
    """Store the change, then recalculate the weeks and months it affects."""
    sched, affected = apply_change(db, user, change)
    tz = tu.user_tz(user)
    start = user.statsstartdate if affected == affected.min else max(user.statsstartdate, tu.midnight(affected, tz))
    if start <= tu.now_ts():
        stats.update_stats(db, user, start, tu.now_ts(), sched=sched)
    db.commit()
    return list_periods(db, user)


@router.get("")
def get_schedules(db: DB, user: Reader):
    """Schedule periods, oldest first. `valid_to` is the day before the next period starts."""
    return list_periods(db, user)


@router.post("/preview")
def preview(body: SchedulePreviewIn, db: DB, user: Reader):
    """Dry run: how the cached week/month statistics would change. Saves nothing."""
    change = Change(body.op, body.id, body.valid_from, body.dworkweek, body.resolved_workdays())
    sched, affected = Schedules.load(db, user).with_change(change)
    return stats.preview_recompute(db, user, sched=sched, start_day=affected)


@router.post("", status_code=201)
def create(body: ScheduleIn, db: DB, user: Writer):
    return _apply(db, user, Change("create", None, body.valid_from, body.dworkweek, body.resolved_workdays()))


@router.patch("/{period_id}")
def update(period_id: int, body: SchedulePatch, db: DB, user: Writer):
    return _apply(db, user, Change("update", period_id, body.valid_from, body.dworkweek, body.resolved_workdays()))


@router.delete("/{period_id}")
def delete(period_id: int, db: DB, user: Writer):
    return _apply(db, user, Change("delete", period_id))
