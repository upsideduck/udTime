import datetime as dt

from sqlalchemy import func, select, text

from app.models import AsWorkTime, MonthStats, WeekStats, Work, WorkSchedule
from app.services import schedule as sch
from app.services import stats

from .conftest import ts

API = "/api/v1"
H = 3600


def _week(db, user, year, week):
    return stats.update_week_stats(db, user, year, week, persist=False)["towork"]


def _month(db, user, year, month):
    return stats.update_month_stats(db, user, year, month, persist=False)["towork"]


def test_resolver_rules():
    periods = sch.Schedules([
        sch.Period(1, dt.date(2024, 1, 1), 40 * H, (1, 2, 3, 4, 5)),
        sch.Period(2, dt.date(2024, 3, 6), 32 * H, (1, 2, 3, 4)),  # from a Wednesday
    ])
    assert periods.for_day(dt.date(2023, 6, 1)).id == 1  # before the first period
    assert periods.for_day(dt.date(2024, 3, 5)).id == 1
    assert periods.for_day(dt.date(2024, 3, 6)).id == 2
    # Week of 2024-03-04: Mon, Tue at 8 h; Wed, Thu at 8 h (4-day week); Fri not a workday
    assert periods.target(dt.date(2024, 3, 4), dt.date(2024, 3, 10)) == 32 * H
    assert periods.day_target(dt.date(2024, 3, 8)) == 0


def test_without_rows_uses_userdb(db, user):
    assert _week(db, user, 2024, 10) == 40 * H
    assert _month(db, user, 2024, 2) == 21 * 8 * H  # 21 weekdays in Feb 2024
    assert _week(db, user, 2024, 1) == 40 * H


def test_change_applies_from_its_date(client, db, user, auth):
    assert client.get(f"{API}/schedules", headers=auth).json()[0]["id"] is None  # implicit, not stored yet
    res = client.post(f"{API}/schedules", json={"valid_from": "2024-03-04", "dworkweek": 30 * H, "daysinworkweek": 4},
                      headers=auth)
    assert res.status_code == 201, res.text
    periods = res.json()
    assert [(p["valid_from"], p["valid_to"]) for p in periods] == [("2024-01-01", "2024-03-03"), ("2024-03-04", None)]
    assert periods[1]["current"] and periods[1]["day_length"] == 7.5 * H
    db.refresh(user)
    assert (user.dworkweek, user.daysinworkweek) == (30 * H, 4)  # userdb mirrors today's period

    assert _week(db, user, 2024, 9) == 40 * H  # Feb 26 - Mar 3
    assert _week(db, user, 2024, 10) == 30 * H
    # March 2024: Fri 1 at 8 h, then Mon-Thu at 7.5 h: 4 weeks of 4 days (4-28 March)
    assert _month(db, user, 2024, 3) == 8 * H + 16 * 7.5 * H
    # The change recalculated the stored stats
    row = db.scalars(select(WeekStats).where(WeekStats.year == 2024, WeekStats.week == 10)).one()
    assert row.towork == 30 * H


def test_weeks_and_months_agree(db, user):
    sch.apply_change(db, user, sch.Change("create", None, dt.date(2024, 2, 14), 24 * H, (1, 3, 5)))
    sched = sch.Schedules.load(db, user)
    months = sum(stats._target(user, sched, dt.date(2024, m, 1), dt.date(2024, m + 1, 1) - dt.timedelta(days=1))
                 for m in (1, 2, 3))
    weeks = stats._target(user, sched, dt.date(2024, 1, 1), dt.date(2024, 3, 31))
    assert months == weeks


def test_update_delete_and_rules(client, auth):
    first = client.post(f"{API}/schedules", json={"valid_from": "2024-06-01", "dworkweek": 20 * H,
                                                  "daysinworkweek": 5}, headers=auth).json()
    base_id, second_id = first[0]["id"], first[1]["id"]
    assert client.delete(f"{API}/schedules/{base_id}", headers=auth).status_code == 400
    assert client.post(f"{API}/schedules", json={"valid_from": "2024-06-01", "dworkweek": 10 * H,
                                                 "daysinworkweek": 5}, headers=auth).status_code == 400
    assert client.post(f"{API}/schedules", json={"valid_from": "2024-07-01", "dworkweek": 10 * H,
                                                 "daysinworkweek": 9}, headers=auth).status_code == 422
    res = client.patch(f"{API}/schedules/{second_id}", json={"dworkweek": 25 * H}, headers=auth)
    assert res.json()[1]["dworkweek"] == 25 * H
    assert len(client.delete(f"{API}/schedules/{second_id}", headers=auth).json()) == 1


def test_preview_saves_nothing(client, db, user, auth):
    stats.recompute_all(db, user)
    before = (db.scalar(select(func.count()).select_from(WorkSchedule)),
              db.scalar(select(func.sum(WeekStats.towork))), db.scalar(select(func.sum(MonthStats.towork))))
    res = client.post(f"{API}/schedules/preview", json={"op": "create", "valid_from": "2024-03-04",
                                                         "dworkweek": 30 * H, "daysinworkweek": 4}, headers=auth)
    assert res.status_code == 200, res.text
    preview = res.json()
    assert preview["weeks"]["changes"] and preview["weeks"]["balance_after"] > preview["weeks"]["balance_before"]
    assert preview["weeks"]["changes"][0]["period"] == "2024-W10"
    after = (db.scalar(select(func.count()).select_from(WorkSchedule)),
             db.scalar(select(func.sum(WeekStats.towork))), db.scalar(select(func.sum(MonthStats.towork))))
    assert before == after

    # Applying gives exactly the previewed balance. The preview stops at yesterday; the stored
    # week total also includes what today and the rest of this week add.
    client.post(f"{API}/schedules", json={"valid_from": "2024-03-04", "dworkweek": 30 * H, "daysinworkweek": 4},
                headers=auth)
    weeks = client.get(f"{API}/stats/balance/weeks", headers=auth).json()
    assert weeks[-1]["total"] - preview["weeks"]["pending"] == preview["weeks"]["balance_after"]


def test_preview_differences_run_through_yesterday(client, db, user, auth, monkeypatch):
    # 8 h days, changed to 7 h days (35 h week) from Monday 2024-03-04. "Today" is Wednesday
    # 2024-03-13: 3 h worked so far today and a vacation day booked for Friday must not count.
    monkeypatch.setattr(stats.tu, "now_ts", lambda: ts(2024, 3, 13, 12))
    for day in (4, 5, 6, 7, 8, 11, 12):
        db.add(Work(member_id=user.member_id, starttime=ts(2024, 3, day, 8), endtime=ts(2024, 3, day, 15), logged=0))
    db.add(Work(member_id=user.member_id, starttime=ts(2024, 3, 13, 8), endtime=ts(2024, 3, 13, 11), logged=0))
    db.add(AsWorkTime(member_id=user.member_id, date=dt.date(2024, 3, 15), time=8 * H, type=1))
    db.commit()
    stats.recompute_all(db, user)

    preview = client.post(f"{API}/schedules/preview", json={"op": "create", "valid_from": "2024-03-04",
                                                             "dworkweek": 35 * H, "daysinworkweek": 5},
                          headers=auth).json()
    assert preview["through"] == "2024-03-12"
    weeks = {c["period"]: c for c in preview["weeks"]["changes"]}
    # Past week: 35 h worked against 40 h, then 35 h
    assert (weeks["2024-W10"]["old_diff"], weeks["2024-W10"]["new_diff"]) == (-5 * H, 0)
    assert not weeks["2024-W10"]["to_date"]
    # Current week, Mon-Tue only: 14 h worked against 16 h, then 14 h
    assert (weeks["2024-W11"]["old_diff"], weeks["2024-W11"]["new_diff"]) == (-2 * H, 0)
    assert weeks["2024-W11"]["to_date"]
    # March through the 12th: Fri 1st (8 h, before the change) + 7 days of 7 h worked
    month = preview["months"]["changes"][0]
    assert (month["old_diff"], month["new_diff"]) == (49 * H - 64 * H, 49 * H - 57 * H)
    assert preview["weeks"]["balance_after"] - preview["weeks"]["balance_before"] == 7 * H


def test_absence_standard_day_follows_schedule(client, db, user, auth):
    client.post(f"{API}/schedules", json={"valid_from": "2024-03-04", "dworkweek": 30 * H, "daysinworkweek": 4},
                headers=auth)
    res = client.post(f"{API}/absences/asworktime", json={"start_date": "2024-03-01", "end_date": "2024-03-08",
                                                           "type": 1}, headers=auth)
    assert res.status_code == 201, res.text
    rows = {r.date: r.time for r in db.scalars(select(AsWorkTime))}
    assert rows[dt.date(2024, 3, 1)] == 8 * H and rows[dt.date(2024, 3, 4)] == 7.5 * H
    assert dt.date(2024, 3, 8) not in rows  # Friday is not a workday any more
    assert {"date": "2024-03-08", "reason": "not a workday"} in res.json()["skipped"]


def test_other_users_periods_are_hidden(client, db, auth):
    from .conftest import make_user

    periods = client.post(f"{API}/schedules", json={"valid_from": "2024-06-01", "dworkweek": 20 * H,
                                                    "daysinworkweek": 5}, headers=auth).json()
    make_user(db, username="other", password="password1")
    token = client.post(f"{API}/auth/login", json={"username": "other", "password": "password1"}).json()
    other = {"Authorization": f"Bearer {token['access_token']}"}
    assert client.patch(f"{API}/schedules/{periods[1]['id']}", json={"dworkweek": H}, headers=other).status_code == 404


def test_pending_migration_blocks_api(client, db, user, auth):
    db.execute(text("DROP TABLE workschedule"))
    db.commit()
    client.app.state.schema_pending = None
    status = client.get(f"{API}/schema").json()
    assert not status["up_to_date"] and status["pending"][0]["id"] == "001_workschedule"
    assert "CREATE TABLE workschedule" in status["pending"][0]["sql"]
    res = client.get(f"{API}/timer", headers=auth)
    assert res.status_code == 503 and res.json()["code"] == "schema_outdated"
    assert client.get(f"{API}/auth/me", headers=auth).status_code == 200  # sign-in still works

    assert client.post(f"{API}/admin/schema/apply", headers=auth).status_code == 403  # not an admin
    user.admin = 1
    db.commit()
    res = client.post(f"{API}/admin/schema/apply", headers=auth)
    assert res.status_code == 200 and res.json()["applied"] == ["001_workschedule"]
    assert client.get(f"{API}/timer", headers=auth).status_code == 200
    seeded = db.scalars(select(WorkSchedule)).all()
    assert [(r.member_id, r.valid_from, r.dworkweek) for r in seeded] == [(user.member_id, dt.date(2024, 1, 1), 40 * H)]
    assert client.get(f"{API}/schema").json() == {"up_to_date": True, "pending": []}


def test_chosen_workdays(client, db, user, auth):
    res = client.post(f"{API}/schedules", json={"valid_from": "2024-03-04", "dworkweek": 30 * H,
                                                 "workdays": [5, 1, 2, 4]}, headers=auth)
    assert res.status_code == 201, res.text
    period = res.json()[1]
    assert (period["workdays"], period["daysinworkweek"], period["workdays_label"]) == ([1, 2, 4, 5], 4,
                                                                                       "Mon, Tue, Thu, Fri")
    sched = sch.Schedules.load(db, user)
    assert sched.day_target(dt.date(2024, 3, 6)) == 0  # Wednesday off
    assert sched.day_target(dt.date(2024, 3, 8)) == 7.5 * H  # Friday
    assert _week(db, user, 2024, 10) == 30 * H
    # March 2024: Fri 1 at 8 h; from the 4th Mon, Tue, Thu, Fri: 4 weeks of 4 days
    assert _month(db, user, 2024, 3) == 8 * H + 16 * 7.5 * H

    res = client.post(f"{API}/absences/asworktime", json={"start_date": "2024-03-06", "end_date": "2024-03-08",
                                                           "type": 1}, headers=auth)
    assert [r["date"] for r in res.json()["created"]] == ["2024-03-07", "2024-03-08"]
    assert res.json()["skipped"] == [{"date": "2024-03-06", "reason": "not a workday"}]

    detail = client.get(f"{API}/stats/week-detail/2024/10", headers=auth).json()
    assert [d["workday"] for d in detail["days"]] == [True, True, False, True, True, False, False]
    assert detail["days"][0]["target"] == 7.5 * H


def test_weekend_workday(client, db, auth):
    client.post(f"{API}/schedules", json={"valid_from": "2024-03-04", "dworkweek": 24 * H, "workdays": [5, 6, 7]},
                headers=auth)
    res = client.post(f"{API}/absences/asworktime", json={"start_date": "2024-03-09", "end_date": "2024-03-09",
                                                           "type": 1}, headers=auth)
    assert [r["date"] for r in res.json()["created"]] == ["2024-03-09"]


def test_workdays_validation(client, auth):
    def create(**body):
        return client.post(f"{API}/schedules", json={"valid_from": "2024-03-04", "dworkweek": 30 * H, **body},
                           headers=auth)

    assert create(workdays=[]).status_code == 422
    assert create(workdays=[8]).status_code == 422
    assert create(workdays=[1, 1, 2]).status_code == 400
    assert create(workdays=[1, 2], daysinworkweek=3).status_code == 422
    assert create().status_code == 422
    res = create(daysinworkweek=3)  # without workdays: Mon-Wed
    assert res.status_code == 201 and res.json()[1]["workdays"] == [1, 2, 3]


def test_migration_002_adds_workdays(client, db, user, auth):
    from app import migrations

    db.execute(text("DROP TABLE workschedule"))
    db.execute(text("CREATE TABLE workschedule (id INTEGER PRIMARY KEY, member_id INTEGER NOT NULL, "
                    "valid_from DATE NOT NULL, dworkweek INTEGER NOT NULL, daysinworkweek INTEGER NOT NULL, "
                    "modified TIMESTAMP NOT NULL)"))
    db.execute(text("INSERT INTO workschedule VALUES (1, :m, '2024-01-01', 144000, 4, '2024-01-01 00:00:00')"),
               {"m": user.member_id})
    db.commit()
    assert [m.id for m in migrations.pending(db.connection())] == ["002_workschedule_workdays"]
    migrations.apply_pending(db.connection())
    db.commit()
    assert db.execute(text("SELECT workdays FROM workschedule")).scalar() == "1234"
    assert migrations.pending(db.connection()) == []

    db.execute(text("DROP TABLE workschedule"))
    assert [m.id for m in migrations.pending(db.connection())] == ["001_workschedule"]
