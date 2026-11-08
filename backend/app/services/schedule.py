"""Work schedules that change over time (table `workschedule`).

A period applies from its `valid_from` date until the next period starts; days before the
first period use the first one. A day's target is the period's standard day
(dworkweek / daysinworkweek) when its weekday is one of the period's `workdays`, else 0.
userdb.dworkweek/daysinworkweek are kept equal to the period valid today.
"""

import bisect
import datetime as dt
from dataclasses import dataclass, replace

from sqlalchemy import select
from sqlalchemy.orm import Session

from ..errors import DomainError, NotFound
from ..models import User, WorkSchedule
from . import timeutil as tu

MAX_WORKWEEK = 168 * 3600
WEEKDAY_NAMES = ("Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun")


def parse_workdays(raw: str | None, daysinworkweek: int) -> tuple[int, ...]:
    """workschedule.workdays as ISO weekdays; empty means the first `daysinworkweek` days."""
    days = tuple(sorted({int(c) for c in raw or "" if c in "1234567"}))
    return days or tuple(range(1, min(max(daysinworkweek or 5, 1), 7) + 1))


def workdays_label(days: tuple[int, ...]) -> str:
    """(1, 2, 3, 4) -> "Mon–Thu", (1, 2, 4, 5) -> "Mon, Tue, Thu, Fri"."""
    if len(days) >= 3 and days == tuple(range(days[0], days[-1] + 1)):
        return f"{WEEKDAY_NAMES[days[0] - 1]}–{WEEKDAY_NAMES[days[-1] - 1]}"
    return ", ".join(WEEKDAY_NAMES[d - 1] for d in days)


@dataclass(frozen=True)
class Period:
    id: int | None
    valid_from: dt.date
    dworkweek: int
    workdays: tuple[int, ...]

    @property
    def daysinworkweek(self) -> int:
        return len(self.workdays)

    @property
    def day_length(self) -> float:
        return self.dworkweek / (self.daysinworkweek or 5)


@dataclass(frozen=True)
class Change:
    """A proposed change: op is "create", "update" or "delete"."""

    op: str
    id: int | None = None
    valid_from: dt.date | None = None
    dworkweek: int | None = None
    workdays: tuple[int, ...] | None = None


class Schedules:
    def __init__(self, periods: list[Period]):
        self.periods = sorted(periods, key=lambda p: p.valid_from)
        self._starts = [p.valid_from for p in self.periods]

    @classmethod
    def load(cls, db: Session, user: User) -> "Schedules":
        rows = db.scalars(select(WorkSchedule).where(WorkSchedule.member_id == user.member_id))
        periods = [Period(r.id, r.valid_from, r.dworkweek, parse_workdays(r.workdays, r.daysinworkweek))
                   for r in rows]
        if not periods:  # not migrated yet for this user: the userdb values apply to every day
            periods = [Period(None, base_date(user), user.dworkweek, parse_workdays(None, user.daysinworkweek))]
        return cls(periods)

    def for_day(self, day: dt.date) -> Period:
        return self.periods[max(bisect.bisect_right(self._starts, day) - 1, 0)]

    def is_workday(self, day: dt.date) -> bool:
        return day.isoweekday() in self.for_day(day).workdays

    def day_target(self, day: dt.date) -> float:
        period = self.for_day(day)
        return period.day_length if day.isoweekday() in period.workdays else 0.0

    def target(self, first: dt.date, last: dt.date) -> float:
        total, day = 0.0, first
        while day <= last:
            total += self.day_target(day)
            day += dt.timedelta(days=1)
        return total

    def get(self, period_id: int) -> Period:
        for period in self.periods:
            if period.id == period_id:
                return period
        raise NotFound("Schedule period not found")

    def with_change(self, change: Change) -> tuple["Schedules", dt.date]:
        """The schedules after `change`, and the first day whose target may differ."""
        periods = list(self.periods)
        if change.op == "create":
            new = _checked(Period(None, change.valid_from, change.dworkweek, change.workdays))
            periods.append(new)
            affected = new.valid_from
        elif change.op == "update":
            old = self.get(change.id)
            new = _checked(replace(old, **{
                k: v for k, v in (("valid_from", change.valid_from), ("dworkweek", change.dworkweek),
                                  ("workdays", change.workdays)) if v is not None
            }))
            periods[periods.index(old)] = new
            affected = min(old.valid_from, new.valid_from)
        elif change.op == "delete":
            old = self.get(change.id)
            if old is self.periods[0]:
                raise DomainError("The first schedule period can't be removed; edit it instead")
            periods.remove(old)
            affected = old.valid_from
        else:
            raise DomainError(f"Unknown operation {change.op}")
        starts = [p.valid_from for p in periods]
        if len(set(starts)) != len(starts):
            raise DomainError("Another schedule period already starts on that date")
        result = Schedules(periods)
        if affected <= result._starts[0] or affected <= self._starts[0]:
            affected = dt.date.min  # the first period also covers every earlier day
        return result, affected


def _checked(period: Period) -> Period:
    if period.valid_from is None or period.dworkweek is None or not period.workdays:
        raise DomainError("Start date, work week and workdays are required")
    tu.check_year(period.valid_from.year)
    if not 0 < period.dworkweek <= MAX_WORKWEEK:
        raise DomainError("The work week must be between 0 and 168 hours")
    if len(set(period.workdays)) != len(period.workdays) or not set(period.workdays) <= set(range(1, 8)):
        raise DomainError("Workdays must be distinct weekdays 1 (Mon) to 7 (Sun)")
    return replace(period, workdays=tuple(sorted(period.workdays)))


def _digits(workdays: tuple[int, ...]) -> str:
    return "".join(map(str, workdays))


def base_date(user: User) -> dt.date:
    """Start of the first period: the user's statistics start date."""
    if user.statsstartdate and user.statsstartdate >= tu.MIN_TIMESTAMP:
        return tu.local_date(user.statsstartdate, tu.user_tz(user))
    return dt.date(tu.MIN_YEAR, 1, 1)


def period_dict(period: Period, following: Period | None, today: dt.date) -> dict:
    valid_to = following.valid_from - dt.timedelta(days=1) if following else None
    return {
        "id": period.id,
        "valid_from": period.valid_from.isoformat(),
        "valid_to": valid_to.isoformat() if valid_to else None,
        "dworkweek": period.dworkweek,
        "daysinworkweek": period.daysinworkweek,
        "workdays": list(period.workdays),
        "workdays_label": workdays_label(period.workdays),
        "day_length": round(period.day_length),
        "current": period.valid_from <= today and (valid_to is None or today <= valid_to),
    }


def list_periods(db: Session, user: User) -> list[dict]:
    periods = Schedules.load(db, user).periods
    today = tu.local_date(tu.now_ts(), tu.user_tz(user))
    following = periods[1:] + [None]
    return [period_dict(p, f, today) for p, f in zip(periods, following)]


def create_base_period(db: Session, user: User) -> None:
    workdays = parse_workdays(None, user.daysinworkweek)
    db.add(WorkSchedule(member_id=user.member_id, valid_from=base_date(user), dworkweek=user.dworkweek,
                        daysinworkweek=len(workdays), workdays=_digits(workdays)))


def apply_change(db: Session, user: User, change: Change) -> tuple[Schedules, dt.date]:
    """Validate and store `change`; returns the new schedules and the first affected day.
    The caller recalculates the statistics."""
    current = Schedules.load(db, user)
    if current.periods[0].id is None:  # materialise the implicit period first
        create_base_period(db, user)
        db.flush()
        current = Schedules.load(db, user)
    new, affected = current.with_change(change)
    if change.op == "create":
        created = next(p for p in new.periods if p.id is None)
        db.add(WorkSchedule(member_id=user.member_id, valid_from=created.valid_from, dworkweek=created.dworkweek,
                            daysinworkweek=created.daysinworkweek, workdays=_digits(created.workdays)))
    else:
        row = db.get(WorkSchedule, change.id)
        if change.op == "delete":
            db.delete(row)
        else:
            updated = next(p for p in new.periods if p.id == change.id)
            row.valid_from, row.dworkweek, row.daysinworkweek, row.workdays = (
                updated.valid_from, updated.dworkweek, updated.daysinworkweek, _digits(updated.workdays))
    today = new.for_day(tu.local_date(tu.now_ts(), tu.user_tz(user)))
    user.dworkweek, user.daysinworkweek = today.dworkweek, today.daysinworkweek
    db.flush()
    return new, affected
