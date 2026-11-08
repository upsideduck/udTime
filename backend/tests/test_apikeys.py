import datetime as dt

from sqlalchemy import select

from app.models import ApiKey

from .conftest import make_user

API = "/api/v1"


def _create(client, auth, **body):
    return client.post(f"{API}/apikeys", json={"name": "phone", "scopes": ["timer:write"], **body}, headers=auth)


def test_create_list_and_use_key(client, auth):
    res = _create(client, auth)
    assert res.status_code == 201, res.text
    created = res.json()
    assert created["key"].startswith("udt_")
    assert created["scopes"] == ["timer:read", "timer:write"]

    listed = client.get(f"{API}/apikeys", headers=auth).json()
    assert [k["id"] for k in listed] == [created["id"]]
    assert "key" not in listed[0]

    key = {"X-API-Key": created["key"]}
    assert client.get(f"{API}/timer", headers=key).status_code == 200
    assert client.get(f"{API}/projects", headers=key).status_code == 403
    assert client.get(f"{API}/apikeys", headers=key).status_code == 403  # keys can't manage keys
    assert client.get(f"{API}/apikeys", headers=auth).json()[0]["last_used_at"]


def test_edit_deactivate_and_delete(client, auth):
    created = _create(client, auth).json()
    key = {"X-API-Key": created["key"]}

    res = client.patch(f"{API}/apikeys/{created['id']}", json={"name": "laptop", "scopes": ["projects:read"]},
                       headers=auth)
    assert res.status_code == 200 and res.json()["name"] == "laptop"
    assert client.get(f"{API}/projects", headers=key).status_code == 200
    assert client.get(f"{API}/timer", headers=key).status_code == 403

    assert client.patch(f"{API}/apikeys/{created['id']}", json={"is_active": False}, headers=auth).status_code == 200
    assert client.get(f"{API}/projects", headers=key).status_code == 401

    assert client.delete(f"{API}/apikeys/{created['id']}", headers=auth).status_code == 204
    assert client.get(f"{API}/apikeys", headers=auth).json() == []


def test_expiry(client, auth, db):
    tomorrow = (dt.date.today() + dt.timedelta(days=2)).isoformat()
    created = _create(client, auth, expires_on=tomorrow).json()
    assert created["expires_on"] == tomorrow and not created["expired"]
    assert _create(client, auth, expires_on="2001-01-01").status_code == 400

    res = client.patch(f"{API}/apikeys/{created['id']}", json={"expires_on": None}, headers=auth)
    assert res.json()["expires_on"] is None

    row = db.scalars(select(ApiKey)).first()
    row.expires_at = dt.datetime(2020, 1, 1)
    db.commit()
    assert client.get(f"{API}/timer", headers={"X-API-Key": created["key"]}).status_code == 401
    assert client.get(f"{API}/apikeys", headers=auth).json()[0]["expired"]


def test_validation_and_ownership(client, db, auth):
    assert _create(client, auth, scopes=["admin:write"]).status_code == 400
    assert _create(client, auth, scopes=[]).status_code == 422
    created = _create(client, auth).json()

    make_user(db, username="other", password="password1")
    token = client.post(f"{API}/auth/login", json={"username": "other", "password": "password1"}).json()
    other = {"Authorization": f"Bearer {token['access_token']}"}
    assert client.get(f"{API}/apikeys", headers=other).json() == []
    assert client.patch(f"{API}/apikeys/{created['id']}", json={"name": "x"}, headers=other).status_code == 404
    assert client.delete(f"{API}/apikeys/{created['id']}", headers=other).status_code == 404
