from typing import Annotated

from fastapi import APIRouter, Depends, Query, Response

from ..deps import DB, require, require_admin
from ..models import User
from ..schemas import Message
from ..services import export, stats, sync
from ..services.timeutil import NAMED_SPANS

router = APIRouter(tags=["stats"])

Reader = Annotated[User, Depends(require("stats:read"))]


@router.get("/stats/summary")
def summary(db: DB, user: Reader, types: str = Query("today,thisweek,thismonth", description=",".join(NAMED_SPANS))):
    return stats.summary(db, user, [t.strip() for t in types.split(",") if t.strip()])


@router.get("/stats/week/{year}/{week}")
def week_stats(year: int, week: int, db: DB, user: Reader):
    return stats.week_stats(db, user, year, week)


@router.get("/stats/month/{year}/{month}")
def month_stats(year: int, month: int, db: DB, user: Reader):
    return stats.month_stats(db, user, year, month)


@router.get("/stats/balance/weeks")
def balance_weeks(db: DB, user: Reader):
    """Per ISO week since statsstartdate: diff = worked + asworktime - towork + againstworktime,
    total = offset + running sum of diff. The current week is flagged `ongoing`."""
    return stats.balance_weeks(db, user)


@router.get("/stats/balance/months")
def balance_months(db: DB, user: Reader):
    return stats.balance_months(db, user)


@router.get("/stats/week-detail/{year}/{week}")
def week_detail(year: int, week: int, db: DB, user: Reader):
    return stats.week_detail(db, user, year, week)


@router.get("/stats/month-detail/{year}/{month}")
def month_detail(year: int, month: int, db: DB, user: Reader):
    return stats.month_detail(db, user, year, month)


@router.get("/sync/changes")
def changes(db: DB, user: Annotated[User, Depends(require("sync:read"))], since: int = 0):
    """Rows modified at or after `since` plus tombstones. Pass the returned cursor next time."""
    return sync.changes_since(db, user, since)


@router.get("/export/month/{year}/{month}.csv")
def month_csv(year: int, month: int, db: DB, user: Annotated[User, Depends(require("export:read"))]):
    return Response(
        export.month_csv(db, user, year, month),
        media_type="text/csv",
        headers={"Content-Disposition": f'attachment; filename="udtime-{year}-{month:02d}.csv"'},
    )


@router.post("/admin/recompute-stats", response_model=Message)
def recompute(db: DB, user: Annotated[User, Depends(require_admin)]):
    stats.recompute_all(db, user)
    return Message(message="Statistics recalculated")
