"""Month CSV export with the same column layout as proc/process_outputcsv.php:
day, weekday, start, break start, break end, end, againstworktime, asworktime.
Extra breaks continue on rows with empty leading columns."""

import csv
import datetime as dt
import io

from sqlalchemy.orm import Session

from ..models import User
from . import timeutil as tu
from .stats import finished_periods, update_month_stats


def month_csv(db: Session, user: User, year: int, month: int) -> str:
    tz = tu.user_tz(user)
    stats = update_month_stats(db, user, year, month)
    db.commit()
    start, end = tu.month_span(year, month, tz)

    by_day: dict[int, list] = {}
    for work, breaks in finished_periods(db, user.member_id, start, end):
        by_day.setdefault(tu.local_date(work.starttime, tz).day, []).append((work, breaks))

    def hm(ts: int | None) -> str:
        return tu.local_dt(ts, tz).strftime("%H:%M") if ts else ""

    def absence(kind: str, day: dt.date) -> str:
        item = stats[kind]["days"].get(day.isoformat())
        return tu.format_hms(item["time"]) if item and item["time"] else ""

    out = io.StringIO()
    writer = csv.writer(out, lineterminator="\n")
    for daynum in range(1, tu.month_last_day(year, month).day + 1):
        day = dt.date(year, month, daynum)
        free, asw = absence("againstworktime", day), absence("asworktime", day)
        periods = by_day.get(daynum)
        if not periods:
            writer.writerow([daynum, day.strftime("%a"), "", "", "", "", free, asw])
            continue
        for work, breaks in periods:
            if not breaks:
                writer.writerow([daynum, day.strftime("%a"), hm(work.starttime), "", "", hm(work.endtime), free, asw])
            for i, b in enumerate(breaks):
                if i == 0:
                    writer.writerow(
                        [daynum, day.strftime("%a"), hm(work.starttime), hm(b.starttime), hm(b.endtime),
                         hm(work.endtime), free, asw]
                    )
                else:
                    writer.writerow(["", "", "", hm(b.starttime), hm(b.endtime), "", "", ""])
    return out.getvalue()
