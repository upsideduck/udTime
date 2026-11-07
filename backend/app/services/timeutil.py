"""Time helpers. All periods are unix timestamps; calendar logic runs in the user's timezone.

Ports fetchStartEndTime, timestampToTime, timestampToDecTime and timeToTimestamp from
archive/php/app/func/func_misc.php, using ISO years for ISO weeks and real local midnights.
"""

import datetime as dt
import re
import time
from zoneinfo import ZoneInfo, ZoneInfoNotFoundError

from ..config import settings
from ..errors import DomainError
from ..models import User

Span = tuple[int, int]  # (start, end) inclusive, in unix seconds

_HHMM = re.compile(r"^(\d{1,2}):(\d{2})(?::(\d{2}))?$")


def now_ts() -> int:
    return int(time.time())


def user_tz(user: User) -> ZoneInfo:
    try:
        return ZoneInfo(user.timezone or settings.default_timezone)
    except (ZoneInfoNotFoundError, ValueError):
        return ZoneInfo(settings.default_timezone)


def local_dt(ts: int, tz: ZoneInfo) -> dt.datetime:
    return dt.datetime.fromtimestamp(ts, tz)


def local_date(ts: int, tz: ZoneInfo) -> dt.date:
    return local_dt(ts, tz).date()


def midnight(day: dt.date, tz: ZoneInfo) -> int:
    return int(dt.datetime.combine(day, dt.time(), tz).timestamp())


def date_span(first: dt.date, last: dt.date, tz: ZoneInfo) -> Span:
    """From local midnight of `first` to the last second of `last`."""
    return midnight(first, tz), midnight(last + dt.timedelta(days=1), tz) - 1


def week_monday(iso_year: int, week: int) -> dt.date:
    try:
        return dt.date.fromisocalendar(iso_year, week, 1)
    except ValueError as exc:
        raise DomainError(f"Invalid week {iso_year}-W{week}") from exc


def week_span(iso_year: int, week: int, tz: ZoneInfo) -> Span:
    monday = week_monday(iso_year, week)
    return date_span(monday, monday + dt.timedelta(days=6), tz)


def month_last_day(year: int, month: int) -> dt.date:
    if not 1 <= month <= 12:
        raise DomainError(f"Invalid month {month}")
    nxt = dt.date(year + 1, 1, 1) if month == 12 else dt.date(year, month + 1, 1)
    return nxt - dt.timedelta(days=1)


def month_span(year: int, month: int, tz: ZoneInfo) -> Span:
    return date_span(dt.date(year, month, 1), month_last_day(year, month), tz)


def shift_week(iso_year: int, week: int, delta: int) -> tuple[int, int]:
    y, w, _ = (week_monday(iso_year, week) + dt.timedelta(weeks=delta)).isocalendar()
    return y, w


def shift_month(year: int, month: int, delta: int) -> tuple[int, int]:
    index = year * 12 + (month - 1) + delta
    return index // 12, index % 12 + 1


NAMED_SPANS = ("today", "yesterday", "thisweek", "lastweek", "thismonth", "lastmonth")


def named_span(name: str, tz: ZoneInfo, now: int | None = None) -> Span:
    today = local_date(now if now is not None else now_ts(), tz)
    if name == "today":
        return date_span(today, today, tz)
    if name == "yesterday":
        day = today - dt.timedelta(days=1)
        return date_span(day, day, tz)
    if name in ("thisweek", "lastweek"):
        y, w, _ = today.isocalendar()
        if name == "lastweek":
            y, w = shift_week(y, w, -1)
        return week_span(y, w, tz)
    if name in ("thismonth", "lastmonth"):
        y, m = today.year, today.month
        if name == "lastmonth":
            y, m = shift_month(y, m, -1)
        return month_span(y, m, tz)
    raise DomainError(f"Unknown span '{name}'")


def weeks_in_span(start: int, end: int, tz: ZoneInfo) -> list[tuple[int, int]]:
    """Every ISO (year, week) touched by the span."""
    first, last = local_date(start, tz), local_date(max(start, end), tz)
    monday = first - dt.timedelta(days=first.weekday())
    weeks = []
    while monday <= last:
        y, w, _ = monday.isocalendar()
        weeks.append((y, w))
        monday += dt.timedelta(weeks=1)
    return weeks


def months_in_span(start: int, end: int, tz: ZoneInfo) -> list[tuple[int, int]]:
    """Every (year, month) touched by the span."""
    first, last = local_date(start, tz), local_date(max(start, end), tz)
    y, m = first.year, first.month
    months = []
    while (y, m) <= (last.year, last.month):
        months.append((y, m))
        y, m = shift_month(y, m, 1)
    return months


def parse_timestamp(value: str | int | None, tz: ZoneInfo) -> int:
    """Empty -> now, "HH:MM[:SS]" -> today at that local time, digits -> unix timestamp,
    otherwise an ISO date/datetime (naive values are local)."""
    if value is None or (isinstance(value, str) and not value.strip()):
        return now_ts()
    if isinstance(value, int):
        return value
    value = value.strip()
    if value.lstrip("-").isdigit():
        return int(value)
    if match := _HHMM.match(value):
        h, m, s = (int(g or 0) for g in match.groups())
        if h > 23 or m > 59 or s > 59:
            raise DomainError(f"Invalid time '{value}'")
        today = local_date(now_ts(), tz)
        return int(dt.datetime.combine(today, dt.time(h, m, s), tz).timestamp())
    try:
        parsed = dt.datetime.fromisoformat(value)
    except ValueError as exc:
        raise DomainError(f"Invalid timestamp '{value}'") from exc
    if parsed.tzinfo is None:
        parsed = parsed.replace(tzinfo=tz)
    return int(parsed.timestamp())


def parse_duration(value: str | int | None) -> int:
    """Seconds from an int or "HH:MM[:SS]"."""
    if value is None or value == "":
        return 0
    if isinstance(value, int):
        return value
    value = value.strip()
    if value.isdigit():
        return int(value)
    if match := _HHMM.match(value):
        h, m, s = (int(g or 0) for g in match.groups())
        return h * 3600 + m * 60 + s
    raise DomainError(f"Invalid duration '{value}'")


def format_hms(seconds: int | float) -> str:
    """±HH:MM:SS with unbounded hours (timestampToTime)."""
    seconds = int(seconds)
    sign = "-" if seconds < 0 else ""
    seconds = abs(seconds)
    return f"{sign}{seconds // 3600:02d}:{seconds % 3600 // 60:02d}:{seconds % 60:02d}"


def format_signed_hms(seconds: int | float) -> str:
    """Like format_hms but with an explicit "+" for positive values (balance diffs)."""
    return ("+" if seconds > 0 else "") + format_hms(seconds)


def format_dec(seconds: int | float) -> str:
    """Decimal hours "H.mm", minutes expressed as hundredths (timestampToDecTime)."""
    seconds = int(seconds)
    sign = "-" if seconds < 0 else ""
    seconds = abs(seconds)
    hours, minutes = seconds // 3600, seconds % 3600 // 60
    hundredths = round(minutes / 60 * 100)
    if hundredths == 100:
        hours, hundredths = hours + 1, 0
    return f"{sign}{hours}.{hundredths:02d}"
