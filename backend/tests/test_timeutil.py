import pytest

from app.errors import DomainError
from app.services import timeutil as tu

from .conftest import TZ, ts


def test_format_hms():
    assert tu.format_hms(0) == "00:00:00"
    assert tu.format_hms(3661) == "01:01:01"
    assert tu.format_hms(-5400) == "-01:30:00"
    assert tu.format_hms(200 * 3600) == "200:00:00"
    assert tu.format_signed_hms(60) == "+00:01:00"
    assert tu.format_signed_hms(0) == "00:00:00"


def test_format_dec():
    assert tu.format_dec(8 * 3600 + 30 * 60) == "8.50"
    assert tu.format_dec(3 * 60) == "0.05"
    assert tu.format_dec(-45 * 60) == "-0.75"


def test_week_span_uses_local_midnights():
    start, end = tu.week_span(2024, 1, TZ)
    assert start == ts(2024, 1, 1)
    assert end == ts(2024, 1, 8) - 1


def test_week_span_across_dst():
    start, end = tu.week_span(2024, 13, TZ)  # DST starts Sunday 31 March
    assert end - start + 1 == 7 * 86400 - 3600


def test_weeks_in_span_uses_iso_year():
    weeks = tu.weeks_in_span(ts(2024, 12, 30), ts(2025, 1, 6, 12), TZ)
    assert weeks == [(2025, 1), (2025, 2)]


def test_months_in_span_never_skips():
    months = tu.months_in_span(ts(2024, 1, 31), ts(2024, 4, 1), TZ)
    assert months == [(2024, 1), (2024, 2), (2024, 3), (2024, 4)]


def test_named_span_lastmonth():
    start, end = tu.named_span("lastmonth", TZ, now=ts(2024, 3, 15))
    assert (start, end) == (ts(2024, 2, 1), ts(2024, 3, 1) - 1)


def test_parse_timestamp():
    assert tu.parse_timestamp("1700000000", TZ) == 1700000000
    assert tu.parse_timestamp("2024-05-02T08:00", TZ) == ts(2024, 5, 2, 8)
    assert tu.local_dt(tu.parse_timestamp("07:45", TZ), TZ).strftime("%H:%M") == "07:45"
    with pytest.raises(DomainError):
        tu.parse_timestamp("25:00", TZ)


def test_parse_duration():
    assert tu.parse_duration("08:00:00") == 28800
    assert tu.parse_duration("7:30") == 27000
    assert tu.parse_duration(3600) == 3600
