import hashlib

from sqlalchemy import select

from app.models import ApiKey, Deleted, User, Work

from .conftest import make_user, ts

API = "/api/v1"


def test_login_upgrades_md5_hash(client, db, user):
    assert client.post(f"{API}/auth/login", json={"username": "johan", "password": "wrong"}).status_code == 401
    res = client.post(f"{API}/auth/login", json={"username": "johan", "password": "secret"})
    assert res.status_code == 200
    db.refresh(user)
    assert user.password.startswith("$argon2")
    assert client.post(f"{API}/auth/login", json={"username": "johan", "password": "secret"}).status_code == 200


def test_timer_flow(client, db, user, auth):
    t0 = ts(2024, 1, 8, 8)
    res = client.post(f"{API}/timer/start-work", json={"timestamp": t0, "comment": "hello"}, headers=auth)
    assert res.status_code == 200, res.text
    assert res.json()["timer"]["state"] == "work"

    # Idempotent resend
    assert client.post(f"{API}/timer/start-work", json={"timestamp": t0}, headers=auth).status_code == 200
    assert client.post(f"{API}/timer/start-break", json={"timestamp": t0 - 60}, headers=auth).status_code == 400

    client.post(f"{API}/timer/start-break", json={"timestamp": t0 + 4 * 3600}, headers=auth)
    state = client.get(f"{API}/timer", headers=auth).json()
    assert state["state"] == "break"
    assert state["clock_start"] == t0 + 4 * 3600

    client.post(f"{API}/timer/end-break", json={"timestamp": t0 + 4 * 3600 + 1800}, headers=auth)
    # End work while working
    res = client.post(f"{API}/timer/end-work", json={"timestamp": t0 + 9 * 3600, "comment": "NOCHANGE"}, headers=auth)
    assert res.status_code == 200, res.text
    assert res.json()["timer"]["state"] == "free"

    work = db.scalars(select(Work)).one()
    assert work.comment == "hello"
    week = client.get(f"{API}/stats/week/2024/2", headers=auth).json()
    assert week["worked"] == 9 * 3600 - 1800


def test_end_work_ends_active_break(client, db, user, auth):
    t0 = ts(2024, 1, 8, 8)
    client.post(f"{API}/timer/start-work", json={"timestamp": t0}, headers=auth)
    client.post(f"{API}/timer/start-break", json={"timestamp": t0 + 3600}, headers=auth)
    res = client.post(f"{API}/timer/end-work", json={"timestamp": t0 + 7200}, headers=auth)
    assert res.status_code == 200, res.text
    assert res.json()["timer"]["state"] == "free"


def test_other_users_rows_are_not_accessible(client, db, user, auth):
    other = make_user(db, username="other")
    work = Work(member_id=other.member_id, starttime=ts(2024, 1, 8, 8), endtime=ts(2024, 1, 8, 9), logged=0)
    db.add(work)
    db.commit()
    assert client.get(f"{API}/work/{work.id}", headers=auth).status_code == 404
    assert client.delete(f"{API}/work/{work.id}", headers=auth).status_code == 404
    assert db.get(Work, work.id) is not None


def test_work_crud_and_tombstones(client, db, user, auth):
    res = client.post(
        f"{API}/work",
        json={"starttime": "2024-01-08T08:00", "endtime": "2024-01-08T17:00", "comment": "manual"},
        headers=auth,
    )
    assert res.status_code == 201, res.text
    work_id = res.json()["id"]
    res = client.post(f"{API}/breaks", json={"starttime": "2024-01-08T12:00", "endtime": "2024-01-08T12:45"}, headers=auth)
    assert res.status_code == 201, res.text
    # Work cannot shrink past its break
    res = client.patch(f"{API}/work/{work_id}", json={"endtime": "2024-01-08T12:30"}, headers=auth)
    assert res.status_code == 400

    listed = client.get(f"{API}/work", params={"year": 2024, "week": 2}, headers=auth).json()
    assert listed[0]["worked"] == 9 * 3600 - 45 * 60

    assert client.delete(f"{API}/work/{work_id}", headers=auth).status_code == 204
    kinds = sorted(d.type for d in db.scalars(select(Deleted)))
    assert kinds == ["break", "work"]

    changes = client.get(f"{API}/sync/changes", params={"since": 0}, headers=auth).json()
    assert len(changes["deleted"]) == 2


def test_absence_range_skips_weekends_and_duplicates(client, user, auth):
    body = {"start_date": "2024-01-05", "end_date": "2024-01-09", "type": 1}
    res = client.post(f"{API}/absences/asworktime", json=body, headers=auth)
    assert res.status_code == 201, res.text
    created = res.json()["created"]
    assert [c["date"] for c in created] == ["2024-01-05", "2024-01-08", "2024-01-09"]
    assert created[0]["time"] == 8 * 3600
    again = client.post(f"{API}/absences/asworktime", json=body, headers=auth).json()
    assert again["created"] == []

    res = client.patch(f"{API}/absences/asworktime/{created[0]['id']}", json={"time": "04:00"}, headers=auth)
    assert res.json()["time"] == 4 * 3600
    assert client.post(f"{API}/absences/asworktime", json={**body, "type": 42}, headers=auth).status_code == 400


def test_api_key_with_legacy_scopes(client, db, user):
    db.add(ApiKey(member_id=user.member_id, key_hash=hashlib.md5(b"legacykey").hexdigest(),
                  scopes="currentperiod2;startwork", is_active=1))
    db.commit()
    headers = {"X-API-Key": "legacykey"}
    assert client.get(f"{API}/timer", headers=headers).status_code == 200
    assert client.get(f"{API}/stats/balance/weeks", headers=headers).status_code == 403
    db.scalars(select(ApiKey)).one().is_active = 0
    db.commit()
    assert client.get(f"{API}/timer", headers=headers).status_code == 401


def test_projects_and_switch(client, db, user, auth):
    project = client.post(f"{API}/projects", json={"name": "Alpha"}, headers=auth).json()
    assert project["color"]
    client.post(f"{API}/timer/start-work", json={"timestamp": ts(2024, 1, 8, 8)}, headers=auth)
    work_id = client.get(f"{API}/timer", headers=auth).json()["work_id"]
    res = client.put(f"{API}/work/{work_id}/project", json={"project_id": project["id"]}, headers=auth)
    assert res.json()["project_id"] == project["id"]
    res = client.post(f"{API}/timer/switch-project", json={"project_id": None}, headers=auth)
    assert res.status_code == 200, res.text
    timer = res.json()["timer"]
    assert timer["state"] == "work" and timer["work_id"] != work_id


def test_month_csv(client, user, auth):
    client.post(f"{API}/work", json={"starttime": "2024-01-08T08:00", "endtime": "2024-01-08T17:00"}, headers=auth)
    client.post(f"{API}/breaks", json={"starttime": "2024-01-08T12:00", "endtime": "2024-01-08T12:30"}, headers=auth)
    res = client.get(f"{API}/export/month/2024/1.csv", headers=auth)
    lines = res.text.splitlines()
    assert len(lines) == 31
    assert lines[7] == "8,Mon,08:00,12:00,12:30,17:00,,"


def test_registration_disabled_by_default(client):
    body = {"username": "new", "password": "secret-pass1", "workweek_hours": 40}
    assert client.post(f"{API}/users", json=body).status_code == 403


def test_registration(client, db, monkeypatch):
    monkeypatch.setattr("app.routers.auth.settings.allow_registration", True)
    body = {"username": "new", "password": "secret-pass1", "workweek_hours": 40, "timezone": "Europe/Stockholm"}
    res = client.post(f"{API}/users", json=body)
    assert res.status_code == 201, res.text
    user = db.scalars(select(User).where(User.username == "new")).one()
    assert user.dworkweek == 144000 and user.statsstartdate > 0
    assert client.post(f"{API}/users", json=body).status_code == 409


def test_switch_and_end_within_same_second(client, db, user, auth):
    t0 = ts(2024, 1, 8, 8)
    client.post(f"{API}/timer/start-work", json={"timestamp": t0}, headers=auth)
    # switch-project uses "now"; emulate a same-second end by ending at the new period's start
    res = client.post(f"{API}/timer/switch-project", json={"project_id": None}, headers=auth).json()
    new_start = res["timer"]["work"]["starttime"]
    res = client.post(f"{API}/timer/end-work", json={"timestamp": new_start}, headers=auth)
    assert res.json()["timer"]["state"] == "free"
    assert db.scalars(select(Work).where(Work.endtime.is_(None))).first() is None


def test_change_password(client, auth):
    url = f"{API}/users/me/password"
    assert client.put(url, json={"current_password": "wrong", "new_password": "newpassword"},
                      headers=auth).status_code == 400
    assert client.put(url, json={"current_password": "secret", "new_password": "short"}, headers=auth).status_code == 422
    res = client.put(url, json={"current_password": "secret", "new_password": "newpassword"}, headers=auth)
    assert res.status_code == 200, res.text
    assert client.post(f"{API}/auth/login", json={"username": "johan", "password": "secret"}).status_code == 401
    assert client.post(f"{API}/auth/login", json={"username": "johan", "password": "newpassword"}).status_code == 200

    key = client.post(f"{API}/apikeys", json={"name": "k", "scopes": ["profile:write"]}, headers=auth).json()["key"]
    assert client.put(url, json={"current_password": "newpassword", "new_password": "another1"},
                      headers={"X-API-Key": key}).status_code == 403


def test_change_password_rate_limited(client, auth):
    url = f"{API}/users/me/password"
    for _ in range(10):
        client.put(url, json={"current_password": "wrong", "new_password": "newpassword"}, headers=auth)
    res = client.put(url, json={"current_password": "secret", "new_password": "newpassword"}, headers=auth)
    assert res.status_code == 429


def test_update_profile(client, auth):
    res = client.patch(f"{API}/users/me", json={"offset": -3600, "timezone": "America/New_York"}, headers=auth)
    assert res.status_code == 200, res.text
    me = client.get(f"{API}/auth/me", headers=auth).json()
    assert (me["offset"], me["timezone"]) == (-3600, "America/New_York")
    assert client.patch(f"{API}/users/me", json={"timezone": "Mars/Base"}, headers=auth).status_code == 400
