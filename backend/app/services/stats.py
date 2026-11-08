"""Worked time, cached week/month statistics and balances.

Ports fetchWorkAndBreakTime (func_fetch.php), timespan::updateWeekStats/updateMonthStats
(timespan.class.php), workingdaysmonth (func_misc.php) and balanceUptoWeek/Month
(func_stats.php). Targets are counted per day with the work schedule valid on that day
(services/schedule.py); with one 5-day schedule they equal the PHP formulas.
"""

import datetime as dt

from sqlalchemy import select
from sqlalchemy.orm import Session

from ..models import AgainstWorkTime, AsWorkTime, Break, Lookup, MonthStats, User, WeekStats, Work, to_epoch
from . import timeutil as tu
from .schedule import Schedules
from .serialize import absence_dict

ABSENCE_MODELS = {"asworktime": AsWorkTime, "againstworktime": AgainstWorkTime}


# --- raw data -------------------------------------------------------------------------


def finished_periods(db: Session, member_id: int, start: int, end: int) -> list[tuple[Work, list[Break]]]:
    """Finished work periods whose start lies in [start, end], each with its breaks.
    A period counts entirely towards the span it started in (as in the PHP version)."""
    works = db.scalars(
        select(Work)
        .where(
            Work.member_id == member_id,
            Work.starttime.between(start, end),
            Work.endtime.is_not(None),
        )
        .order_by(Work.starttime)
    ).all()
    breaks_by_parent: dict[int, list[Break]] = {w.id: [] for w in works}
    if works:
        for b in db.scalars(
            select(Break).where(Break.parent_id.in_(breaks_by_parent)).order_by(Break.starttime)
        ):
            breaks_by_parent[b.parent_id].append(b)
    return [(w, breaks_by_parent[w.id]) for w in works]


def worked_and_break(db: Session, member_id: int, start: int, end: int) -> tuple[int, int]:
    worktime = breaktime = 0
    for work, breaks in finished_periods(db, member_id, start, end):
        worktime += work.endtime - work.starttime
        breaktime += sum(b.endtime - b.starttime for b in breaks if b.endtime is not None)
    return worktime - breaktime, breaktime


def absence_rows(db: Session, kind: str, member_id: int, first: dt.date, last: dt.date):
    """(row, typelabel) for absences in [first, last]. Inner join on lookups like the PHP."""
    model = ABSENCE_MODELS[kind]
    return db.execute(
        select(model, Lookup.name)
        .join(Lookup, (Lookup.code == model.type) & (Lookup.type == kind))
        .where(model.member_id == member_id, model.date.between(first, last))
        .order_by(model.date)
    ).all()


def absence_summary(db: Session, kind: str, member_id: int, first: dt.date, last: dt.date) -> dict:
    rows = absence_rows(db, kind, member_id, first, last)
    return {
        "sum": sum(row.time for row, _ in rows),
        "days": {row.date.isoformat(): absence_dict(kind, row, label) for row, label in rows},
    }


# --- cached stats ---------------------------------------------------------------------


def _stats_window(user: User, start: int, end: int, tz) -> tuple[int, int, dt.date, dt.date]:
    if start < user.statsstartdate:
        start = user.statsstartdate
    return start, end, tu.local_date(start, tz), tu.local_date(end, tz)


def _target(user: User, sched: Schedules, first: dt.date, last: dt.date) -> int:
    """Target time for the days in [first, last] on or after the statistics start."""
    first = max(first, tu.local_date(user.statsstartdate, tu.user_tz(user)))
    return round(sched.target(first, last))


def update_week_stats(
    db: Session, user: User, iso_year: int, week: int, persist: bool = True, sched: Schedules | None = None
) -> dict:
    """Compute a week's stats and store them in weekstats (unless persist=False)."""
    tz = tu.user_tz(user)
    sched = sched or Schedules.load(db, user)
    start, end = tu.week_span(iso_year, week, tz)
    monday = tu.week_monday(iso_year, week)
    towork = _target(user, sched, monday, monday + dt.timedelta(days=6))

    start, end, first, last = _stats_window(user, start, end, tz)
    worked = worked_and_break(db, user.member_id, start, end)[0] if start <= end else 0
    asworktime = absence_summary(db, "asworktime", user.member_id, first, last)
    againstworktime = absence_summary(db, "againstworktime", user.member_id, first, last)

    if persist:
        _upsert(
            db,
            WeekStats,
            dict(member_id=user.member_id, year=iso_year, week=week),
            dict(towork=towork, worked=worked, asworktime=asworktime["sum"], againstworktime=againstworktime["sum"]),
        )
    return {"towork": towork, "worked": worked, "asworktime": asworktime, "againstworktime": againstworktime}


def update_month_stats(
    db: Session, user: User, year: int, month: int, persist: bool = True, sched: Schedules | None = None
) -> dict:
    """Compute a month's stats and store them in monthstats (unless persist=False)."""
    tz = tu.user_tz(user)
    sched = sched or Schedules.load(db, user)
    start, end = tu.month_span(year, month, tz)
    towork = _target(user, sched, dt.date(year, month, 1), tu.month_last_day(year, month))

    start, end, first, last = _stats_window(user, start, end, tz)
    worked = worked_and_break(db, user.member_id, start, end)[0] if start <= end else 0
    asworktime = absence_summary(db, "asworktime", user.member_id, first, last)
    againstworktime = absence_summary(db, "againstworktime", user.member_id, first, last)

    if persist:
        _upsert(
            db,
            MonthStats,
            dict(member_id=user.member_id, year=year, month=month),
            dict(towork=towork, worked=worked, asworktime=asworktime["sum"], againstworktime=againstworktime["sum"]),
        )
    return {"towork": towork, "worked": worked, "asworktime": asworktime, "againstworktime": againstworktime}


def _upsert(db: Session, model, key: dict, values: dict) -> None:
    row = db.scalars(select(model).filter_by(**key).order_by(model.id.desc())).first()
    if row is None:
        db.add(model(**key, **values))
    else:
        for name, value in values.items():
            setattr(row, name, value)
    db.flush()


def update_stats(db: Session, user: User, start: int, end: int, sched: Schedules | None = None) -> None:
    """Recompute every week and month touched by [start, end]."""
    tz = tu.user_tz(user)
    sched = sched or Schedules.load(db, user)
    for year, week in tu.weeks_in_span(start, end, tz):
        update_week_stats(db, user, year, week, sched=sched)
    for year, month in tu.months_in_span(start, end, tz):
        update_month_stats(db, user, year, month, sched=sched)


def recompute_all(db: Session, user: User) -> None:
    update_stats(db, user, user.statsstartdate, tu.now_ts())
    db.commit()


STAT_FIELDS = ("towork", "worked", "asworktime", "againstworktime")


def _future_target(user: User, sched: Schedules, first: dt.date, last: dt.date) -> int:
    """Target time for the workdays after today in [first, last] (not yet due)."""
    tomorrow = tu.local_date(tu.now_ts(), tu.user_tz(user)) + dt.timedelta(days=1)
    return _target(user, sched, max(first, tomorrow), last)


def _preview_rows(
    user: User, existing: dict, computed: list[tuple[tuple[int, int], dict]], label, future: dict, carried: int = 0
) -> dict:
    """Compare freshly computed stats with the stored rows (the row recompute would update).
    The balances run up to today: the target of workdays still to come in the current
    period (`future`) is left out, so week and month balances are comparable."""
    changes = []
    before = after = user.offset
    for key, new in computed:
        new_values = {f: new[f]["sum"] if isinstance(new[f], dict) else new[f] for f in STAT_FIELDS}
        old = existing.get(key)
        old_values = {f: int(getattr(old, f)) for f in STAT_FIELDS} if old is not None else None
        new_diff = new_values["worked"] + new_values["asworktime"] - new_values["towork"] + new_values["againstworktime"]
        old_diff = (
            old_values["worked"] + old_values["asworktime"] - old_values["towork"] + old_values["againstworktime"]
            if old_values
            else 0
        )
        before += old_diff + (future.get(key, 0) if old_values else 0)
        after += new_diff + future.get(key, 0)
        if old_values == new_values:
            continue
        changes.append({
            "period": label(*key),
            "year": key[0],
            "number": key[1],
            "status": "missing" if old_values is None else "changed",
            "fields": {
                f: {"old": old_values[f] if old_values else None, "new": new_values[f]}
                for f in STAT_FIELDS
                if old_values is None or old_values[f] != new_values[f]
            },
            "old_diff": old_diff if old_values else None,
            "new_diff": new_diff,
        })
    return {
        "checked": len(computed) - carried,
        "changes": changes,
        "balance_before": before,
        "balance_after": after,
        "future_target": sum(future.values()),
    }


def preview_recompute(
    db: Session, user: User, sched: Schedules | None = None, start_day: dt.date | None = None
) -> dict:
    """Dry run of recompute_all: what would change in weekstats/monthstats. Writes nothing.
    With `sched` the stats are computed for a proposed schedule; with `start_day` only weeks and
    months from that day on are recomputed (earlier stored rows still count in the balances)."""
    tz = tu.user_tz(user)
    sched = sched or Schedules.load(db, user)
    start, end = user.statsstartdate, tu.now_ts()
    if start_day is not None and start_day > dt.date.min:  # dt.date.min: the whole history
        start = max(start, tu.midnight(start_day, tz))

    def latest_rows(model, number_field):
        rows = {}
        for row in db.scalars(select(model).where(model.member_id == user.member_id).order_by(model.id)):
            rows[(row.year, getattr(row, number_field))] = row  # recompute updates the newest duplicate
        return rows

    def carried(existing: dict, first_key, computed: list) -> list:
        """Stored rows between the statistics start and the recomputed range, unchanged."""
        if not computed:
            return []
        return [(key, {f: int(getattr(row, f)) for f in STAT_FIELDS})
                for key, row in sorted(existing.items()) if first_key <= key < computed[0][0]]

    weeks_rows, months_rows = latest_rows(WeekStats, "week"), latest_rows(MonthStats, "month")
    stats_start = tu.local_date(user.statsstartdate, tz)
    weeks = [((y, w), update_week_stats(db, user, y, w, persist=False, sched=sched))
             for y, w in tu.weeks_in_span(start, end, tz)]
    months = [((y, m), update_month_stats(db, user, y, m, persist=False, sched=sched))
              for y, m in tu.months_in_span(start, end, tz)]
    week_carried = carried(weeks_rows, stats_start.isocalendar()[:2], weeks)
    month_carried = carried(months_rows, (stats_start.year, stats_start.month), months)

    today = tu.local_date(end, tz)
    iso_year, iso_week, _ = today.isocalendar()
    monday = tu.week_monday(iso_year, iso_week)
    week_future = {(iso_year, iso_week): _future_target(user, sched, monday, monday + dt.timedelta(days=6))}
    month_future = {
        (today.year, today.month): _future_target(
            user, sched, dt.date(today.year, today.month, 1), tu.month_last_day(today.year, today.month)
        )
    }
    return {
        "through": today.isoformat(),
        "weeks": _preview_rows(user, weeks_rows, week_carried + weeks, lambda y, w: f"{y}-W{w:02d}", week_future,
                               carried=len(week_carried)),
        "months": _preview_rows(user, months_rows, month_carried + months, lambda y, m: f"{y}-{m:02d}",
                                month_future, carried=len(month_carried)),
    }


# --- balances -------------------------------------------------------------------------


def _diff(row) -> int:
    return int(row.asworktime) + int(row.worked) - int(row.towork) + int(row.againstworktime)


def _balance_entry(row, diff: int, total: int) -> dict:
    return {
        "year": row.year,
        "towork": row.towork,
        "worked": row.worked,
        "asworktime": row.asworktime,
        "againstworktime": row.againstworktime,
        "diff": diff,
        "total": total,
        "workedtime": tu.format_hms(row.worked),
        "difftime": tu.format_signed_hms(diff),
        "totaldifftime": tu.format_hms(total),
        "modified": to_epoch(row.modified),
    }


def balance_weeks(db: Session, user: User, upto: tuple[int, int] | None = None) -> list[dict]:
    tz = tu.user_tz(user)
    first = tu.local_date(user.statsstartdate, tz).isocalendar()[:2]
    current = tu.local_date(tu.now_ts(), tz).isocalendar()[:2]
    upto = upto or current
    rows: dict[tuple[int, int], WeekStats] = {}
    for row in db.scalars(select(WeekStats).where(WeekStats.member_id == user.member_id).order_by(WeekStats.id)):
        if first <= (row.year, row.week) <= upto:
            rows[(row.year, row.week)] = row  # later rows win if duplicates exist

    result, total = [], user.offset
    for key in sorted(rows):
        row = rows[key]
        diff = _diff(row)
        total += diff
        entry = _balance_entry(row, diff, total)
        entry["week"] = row.week
        entry["ongoing"] = key == current
        result.append(entry)
    return result


def balance_months(db: Session, user: User, upto: tuple[int, int] | None = None) -> list[dict]:
    tz = tu.user_tz(user)
    stats_start = tu.local_date(user.statsstartdate, tz)
    today = tu.local_date(tu.now_ts(), tz)
    first, current = (stats_start.year, stats_start.month), (today.year, today.month)
    upto = upto or current
    rows: dict[tuple[int, int], MonthStats] = {}
    for row in db.scalars(select(MonthStats).where(MonthStats.member_id == user.member_id).order_by(MonthStats.id)):
        if first <= (row.year, row.month) <= upto:
            rows[(row.year, row.month)] = row

    result, total = [], user.offset
    for key in sorted(rows):
        row = rows[key]
        diff = _diff(row)
        total += diff
        entry = _balance_entry(row, diff, total)
        entry["month"] = row.month
        entry["ongoing"] = key == current
        result.append(entry)
    return result


# --- summaries and detail views -------------------------------------------------------


def summary(db: Session, user: User, names: list[str]) -> dict:
    tz = tu.user_tz(user)
    result = {}
    for name in names:
        start, end = tu.named_span(name, tz)
        worktime, breaktime = worked_and_break(db, user.member_id, start, end)
        result[name] = {
            "start": start,
            "end": end,
            "worktime": worktime,
            "breaktime": breaktime,
            "worktime_hms": tu.format_hms(worktime),
            "breaktime_hms": tu.format_hms(breaktime),
        }
    return result


def _totals(stats: dict) -> dict:
    asw, agw = stats["asworktime"]["sum"], stats["againstworktime"]["sum"]
    worked_incl = stats["worked"] + asw
    towork_incl = stats["towork"] - agw
    return {
        "towork": stats["towork"],
        "worked": stats["worked"],
        "asworktime": asw,
        "againstworktime": agw,
        "worked_incl_asworktime": worked_incl,
        "towork_incl_againstworktime": towork_incl,
        "diff": worked_incl - towork_incl,
        "sumworked": tu.format_dec(stats["worked"]),
        "sumworkedinclvac": tu.format_dec(worked_incl),
        "sumtowork": tu.format_dec(stats["towork"]),
        "sumtoworkinclfree": tu.format_dec(towork_incl),
        "sumasworktime": tu.format_dec(asw),
        "sumagainstworktime": tu.format_dec(agw),
        "sumdiff": tu.format_dec(worked_incl - towork_incl),
    }


def _to_date(user: User, sched: Schedules, rows: list[dict]) -> dict | None:
    """Totals for the days up to and including today, when today lies inside the period.
    The target counts each day's scheduled time on or after statsstartdate, matching how the
    week/month target is built."""
    tz = tu.user_tz(user)
    today = tu.local_date(tu.now_ts(), tz)
    first, last = dt.date.fromisoformat(rows[0]["date"]), dt.date.fromisoformat(rows[-1]["date"])
    if not first <= today <= last:
        return None
    stats_start = tu.local_date(user.statsstartdate, tz)
    towork = worked = asw = agw = 0.0
    for row in rows:
        day = dt.date.fromisoformat(row["date"])
        if day > today or day < stats_start:
            continue
        towork += sched.day_target(day)
        worked += sum(p["worked_seconds"] for p in row["periods"])
        asw += row["asworktime"]["time"] if row["asworktime"] else 0
        agw += row["againstworktime"]["time"] if row["againstworktime"] else 0
    towork_incl, worked_incl = round(towork - agw), round(worked + asw)
    return {
        "through": today.isoformat(),
        "towork_incl_againstworktime": towork_incl,
        "worked_incl_asworktime": worked_incl,
        "diff": worked_incl - towork_incl,
        "sumtoworkinclfree": tu.format_dec(towork_incl),
        "sumworkedinclvac": tu.format_dec(worked_incl),
        "sumdiff": tu.format_dec(worked_incl - towork_incl),
    }


def _day_rows(
    db: Session, user: User, sched: Schedules, start: int, end: int, days: list[dt.date], stats: dict
) -> list[dict]:
    """One entry per calendar day with its work periods and absences."""
    tz = tu.user_tz(user)
    asw_days, agw_days = stats["asworktime"]["days"], stats["againstworktime"]["days"]
    periods_by_day: dict[dt.date, list[dict]] = {d: [] for d in days}
    for work, breaks in finished_periods(db, user.member_id, start, end):
        day = tu.local_date(work.starttime, tz)
        break_time = sum(b.endtime - b.starttime for b in breaks if b.endtime is not None)
        worked = work.endtime - work.starttime - break_time
        periods_by_day.setdefault(day, []).append(
            {
                "id": work.id,
                "start": tu.local_dt(work.starttime, tz).strftime("%H:%M:%S"),
                "end": tu.local_dt(work.endtime, tz).strftime("%H:%M:%S"),
                "starttime": work.starttime,
                "endtime": work.endtime,
                "comment": work.comment,
                "project_id": work.project_id,
                "breaks": [
                    {
                        "id": b.id,
                        "start": tu.local_dt(b.starttime, tz).strftime("%H:%M"),
                        "end": tu.local_dt(b.endtime, tz).strftime("%H:%M") if b.endtime else "",
                    }
                    for b in breaks
                ],
                "break": tu.format_hms(break_time),
                "worked": tu.format_hms(worked),
                "workeddec": tu.format_dec(worked),
                "worked_seconds": worked,
            }
        )

    result = []
    for day in days:
        iso = day.isoformat()
        periods = periods_by_day.get(day, [])
        worked = sum(p["worked_seconds"] for p in periods)
        result.append(
            {
                "date": iso,
                "label": day.strftime("%d-%b"),
                "weekday": day.strftime("%a"),
                "isoweekday": day.isoweekday(),
                "workday": sched.is_workday(day),
                "target": round(sched.day_target(day)),
                "day": day.day,
                "periods": periods,
                "asworktime": asw_days.get(iso),
                "againstworktime": agw_days.get(iso),
                "worked": tu.format_hms(worked),
                "workeddec": tu.format_dec(worked),
            }
        )
    return result


def week_detail(db: Session, user: User, iso_year: int, week: int) -> dict:
    tz = tu.user_tz(user)
    sched = Schedules.load(db, user)
    stats = update_week_stats(db, user, iso_year, week, sched=sched)
    db.commit()
    start, end = tu.week_span(iso_year, week, tz)
    monday = tu.week_monday(iso_year, week)
    days = [monday + dt.timedelta(days=i) for i in range(7)]
    prev, nxt = tu.shift_week(iso_year, week, -1), tu.shift_week(iso_year, week, 1)
    rows = _day_rows(db, user, sched, start, end, days, stats)
    return {
        "year": iso_year,
        "week": week,
        "fromdate": days[0].isoformat(),
        "todate": days[-1].isoformat(),
        "prev": {"year": prev[0], "week": prev[1]},
        "next": {"year": nxt[0], "week": nxt[1]},
        "totals": {**_totals(stats), "to_date": _to_date(user, sched, rows)},
        "days": rows,
    }


def month_detail(db: Session, user: User, year: int, month: int) -> dict:
    tz = tu.user_tz(user)
    sched = Schedules.load(db, user)
    stats = update_month_stats(db, user, year, month, sched=sched)
    db.commit()
    start, end = tu.month_span(year, month, tz)
    last = tu.month_last_day(year, month)
    days = [dt.date(year, month, d) for d in range(1, last.day + 1)]
    prev, nxt = tu.shift_month(year, month, -1), tu.shift_month(year, month, 1)
    rows = _day_rows(db, user, sched, start, end, days, stats)
    return {
        "year": year,
        "month": month,
        "fromdate": days[0].isoformat(),
        "todate": days[-1].isoformat(),
        "prev": {"year": prev[0], "month": prev[1]},
        "next": {"year": nxt[0], "month": nxt[1]},
        "totals": {**_totals(stats), "to_date": _to_date(user, sched, rows)},
        "days": rows,
    }


def week_stats(db: Session, user: User, iso_year: int, week: int) -> dict:
    return {"year": iso_year, "week": week, **week_detail(db, user, iso_year, week)["totals"]}


def month_stats(db: Session, user: User, year: int, month: int) -> dict:
    return {"year": year, "month": month, **month_detail(db, user, year, month)["totals"]}
