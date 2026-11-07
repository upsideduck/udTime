import datetime as dt

from app.models import AgainstWorkTime, AsWorkTime, Break, Work
from app.services import stats

from .conftest import make_user, ts

DAY = 8 * 3600


def add_work(db, user, start, end, breaks=()):
    work = Work(member_id=user.member_id, starttime=start, endtime=end, logged=0)
    db.add(work)
    db.flush()
    for b_start, b_end in breaks:
        db.add(Break(member_id=user.member_id, parent_id=work.id, starttime=b_start, endtime=b_end, logged=0))
    db.commit()
    return work


def test_worked_subtracts_breaks_and_ignores_open_periods(db, user):
    add_work(db, user, ts(2024, 1, 8, 8), ts(2024, 1, 8, 17), [(ts(2024, 1, 8, 12), ts(2024, 1, 8, 13))])
    db.add(Work(member_id=user.member_id, starttime=ts(2024, 1, 9, 8), endtime=None, logged=0))
    db.commit()
    worked, breaks = stats.worked_and_break(db, user.member_id, ts(2024, 1, 8), ts(2024, 1, 15) - 1)
    assert (worked, breaks) == (8 * 3600, 3600)


def test_period_counts_towards_day_it_started(db, user):
    add_work(db, user, ts(2024, 1, 7, 22), ts(2024, 1, 8, 2))  # Sunday night into Monday
    assert stats.update_week_stats(db, user, 2024, 1)["worked"] == 4 * 3600
    assert stats.update_week_stats(db, user, 2024, 2)["worked"] == 0


def test_week_towork_full_and_partial(db):
    # Stats start on Wednesday 2024-01-10: 7 - N(3) - 1 = 3 workdays that week
    user = make_user(db, statsstart=ts(2024, 1, 10, 9))
    assert stats.update_week_stats(db, user, 2024, 2)["towork"] == 3 * DAY
    assert stats.update_week_stats(db, user, 2024, 3)["towork"] == 5 * DAY
    assert stats.update_week_stats(db, user, 2024, 1)["towork"] == 0


def test_month_towork_counts_weekdays_from_stats_start(db):
    user = make_user(db, statsstart=ts(2024, 1, 15))
    # 15-31 January 2024: 13 weekdays
    assert stats.update_month_stats(db, user, 2024, 1)["towork"] == 13 * DAY
    # February 2024: 21 weekdays
    assert stats.update_month_stats(db, user, 2024, 2)["towork"] == 21 * DAY


def test_balance_formula(db):
    user = make_user(db, statsstart=ts(2024, 1, 1), offset=3600)
    add_work(db, user, ts(2024, 1, 1, 8), ts(2024, 1, 1, 18))  # 10h Monday
    db.add(AsWorkTime(member_id=user.member_id, date=dt.date(2024, 1, 2), time=DAY, type=1))
    db.add(AgainstWorkTime(member_id=user.member_id, date=dt.date(2024, 1, 3), time=DAY, type=1))
    db.commit()
    stats.update_week_stats(db, user, 2024, 1)
    stats.update_week_stats(db, user, 2024, 2)
    db.commit()

    rows = stats.balance_weeks(db, user, upto=(2024, 2))
    # week 1: 10h + 8h (vacation) - 40h + 8h (holiday) = -14h ; total = +1h - 14h
    assert rows[0]["diff"] == -14 * 3600
    assert rows[0]["total"] == -13 * 3600
    assert rows[0]["totaldifftime"] == "-13:00:00"
    # week 2: nothing worked
    assert rows[1]["diff"] == -40 * 3600
    assert rows[1]["total"] == -53 * 3600


def test_unknown_lookup_type_not_counted(db, user):
    db.add(AsWorkTime(member_id=user.member_id, date=dt.date(2024, 1, 2), time=DAY, type=99))
    db.commit()
    assert stats.update_week_stats(db, user, 2024, 1)["asworktime"]["sum"] == 0


def test_week_detail(db, user):
    add_work(db, user, ts(2024, 1, 2, 8), ts(2024, 1, 2, 16, 30), [(ts(2024, 1, 2, 12), ts(2024, 1, 2, 12, 30))])
    detail = stats.week_detail(db, user, 2024, 1)
    tuesday = detail["days"][1]
    assert tuesday["periods"][0]["worked"] == "08:00:00"
    assert tuesday["periods"][0]["workeddec"] == "8.00"
    assert detail["totals"]["sumtowork"] == "40.00"
    assert detail["prev"] == {"year": 2023, "week": 52}


def test_week_to_date(db, user, monkeypatch):
    # "Today" is Wednesday 2024-01-03: target Mon-Wed = 24h, worked Mon 9h + Tue 8h, holiday Wed 4h
    monkeypatch.setattr(stats.tu, "now_ts", lambda: ts(2024, 1, 3, 12))
    add_work(db, user, ts(2024, 1, 1, 8), ts(2024, 1, 1, 17))
    add_work(db, user, ts(2024, 1, 2, 8), ts(2024, 1, 2, 16))
    db.add(AgainstWorkTime(member_id=user.member_id, date=dt.date(2024, 1, 3), time=4 * 3600, type=1))
    db.commit()
    totals = stats.week_detail(db, user, 2024, 1)["totals"]
    assert totals["diff"] == 17 * 3600 - (40 - 4) * 3600
    to_date = totals["to_date"]
    assert to_date["through"] == "2024-01-03"
    assert to_date["towork_incl_againstworktime"] == 20 * 3600
    assert to_date["diff"] == -3 * 3600
    assert stats.week_detail(db, user, 2024, 2)["totals"]["to_date"] is None


def test_preview_recompute_writes_nothing(db, monkeypatch):
    from app.models import MonthStats, WeekStats
    from sqlalchemy import func, select

    user = make_user(db, statsstart=ts(2024, 1, 1))
    monkeypatch.setattr(stats.tu, "now_ts", lambda: ts(2024, 1, 20, 12))
    add_work(db, user, ts(2024, 1, 2, 8), ts(2024, 1, 2, 17))
    # A stale cached row for week 1 and no row for weeks 2-3
    db.add(WeekStats(member_id=user.member_id, year=2024, week=1, towork=40 * 3600, worked=0))
    db.commit()

    preview = stats.preview_recompute(db, user)
    weeks = preview["weeks"]
    assert weeks["checked"] == 3
    w1 = next(c for c in weeks["changes"] if c["number"] == 1)
    assert w1["status"] == "changed" and w1["fields"] == {"worked": {"old": 0, "new": 9 * 3600}}
    assert {c["status"] for c in weeks["changes"] if c["number"] != 1} == {"missing"}
    assert weeks["balance_after"] - weeks["balance_before"] == 9 * 3600 - 2 * 40 * 3600
    assert preview["months"]["changes"][0]["status"] == "missing"
    assert db.scalar(select(func.count()).select_from(WeekStats)) == 1
    assert db.scalar(select(func.count()).select_from(MonthStats)) == 0
    assert db.scalars(select(WeekStats)).one().worked == 0

    stats.recompute_all(db, user)
    assert stats.preview_recompute(db, user)["weeks"]["changes"] == []
