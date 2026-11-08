import pytest
from pydantic import ValidationError

from app.config import Settings
from app.services import timeutil as tu

from .conftest import ts

API = "/api/v1"


@pytest.mark.parametrize("secret", ["", "change-me", "short"])
def test_insecure_secrets_refused(secret):
    with pytest.raises(ValidationError):
        Settings(jwt_secret=secret, frontend_service_secret="x" * 32, _env_file=None)


def test_login_rate_limited_per_username(client, user):
    for _ in range(10):
        assert client.post(f"{API}/auth/login", json={"username": "johan", "password": "bad"}).status_code == 401
    res = client.post(f"{API}/auth/login", json={"username": "johan", "password": "secret"})
    assert res.status_code == 429 and "Retry-After" in res.headers


def test_login_rate_limited_per_ip(client, user):
    for i in range(30):
        client.post(f"{API}/auth/login", json={"username": f"nobody{i}", "password": "bad"})
    assert client.post(f"{API}/auth/login", json={"username": "johan", "password": "secret"}).status_code == 429


def test_client_ip_header_needs_service_secret(client, user):
    for i in range(30):
        client.post(f"{API}/auth/login", json={"username": f"x{i}", "password": "bad"}, headers={"X-Client-IP": "1.2.3.4"})
    # Without the service secret the header is ignored, so the requester's own IP got blocked
    from app.config import settings

    headers = {"X-Client-IP": "5.6.7.8", "X-Service-Secret": settings.frontend_service_secret}
    assert client.post(f"{API}/auth/login", json={"username": "johan", "password": "secret"}, headers=headers).status_code == 200


def test_input_bounds(client, user, auth):
    assert client.post(f"{API}/absences/asworktime", json={"start_date": "2024-01-01", "end_date": "2025-06-01"},
                       headers=auth).status_code == 400
    assert client.get(f"{API}/stats/week/1900/1", headers=auth).status_code == 400
    assert client.get(f"{API}/stats/month/2024/13", headers=auth).status_code == 400
    future = tu.now_ts() + 3600
    assert client.post(f"{API}/timer/start-work", json={"timestamp": future}, headers=auth).status_code == 400
    assert client.post(f"{API}/work", json={"starttime": ts(2024, 1, 1, 8), "endtime": ts(2024, 1, 20, 8)},
                       headers=auth).status_code == 400
    assert client.post(f"{API}/work", json={"starttime": 1, "endtime": 2}, headers=auth).status_code == 400
    assert client.post(f"{API}/projects", json={"name": "x", "color": "red;background:url(x)"}, headers=auth).status_code == 422


def test_options_and_headers(client):
    res = client.get(f"{API}/auth/options")
    assert res.json() == {"allow_registration": False}
    assert res.headers["X-Content-Type-Options"] == "nosniff"
    assert res.headers["X-Frame-Options"] == "DENY"


def test_limiter_memory_is_bounded(monkeypatch):
    from app import ratelimit

    limiter = ratelimit.FailureLimiter(max_failures=3, window_seconds=60)
    monkeypatch.setattr(ratelimit, "MAX_TRACKED_KEYS", 100)
    for i in range(1000):
        limiter.retry_after(f"user:probe{i}")  # checking must not create entries
        limiter.fail(f"user:flood{i}")
    assert len(limiter._failures) <= 100


def test_body_size_and_comment_limits(client, user, auth):
    assert client.post(f"{API}/projects", content=b"x" * (2 * 1024 * 1024), headers=auth).status_code == 413
    long_comment = {"timestamp": ts(2024, 1, 8, 8), "comment": "x" * 1001}
    assert client.post(f"{API}/timer/start-work", json=long_comment, headers=auth).status_code == 422


def test_service_secret_must_be_ascii():
    import pytest

    from app.config import Settings

    with pytest.raises(ValueError, match="ASCII"):
        Settings(jwt_secret="x" * 32, frontend_service_secret="abcdefghijklmnop€")
