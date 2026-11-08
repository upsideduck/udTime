import datetime as dt
import hashlib
import os

os.environ.setdefault("UDTIME2_JWT_SECRET", "test-jwt-secret-0123456789abcdef")
os.environ.setdefault("UDTIME2_FRONTEND_SERVICE_SECRET", "test-service-secret-0123456789")
from zoneinfo import ZoneInfo

import pytest
from fastapi.testclient import TestClient
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker
from sqlalchemy.pool import StaticPool

from app.db import get_db
from app.main import create_app
from app.models import Base, Lookup, User

TZ = ZoneInfo("Europe/Stockholm")


def ts(year, month, day, hour=0, minute=0) -> int:
    return int(dt.datetime(year, month, day, hour, minute, tzinfo=TZ).timestamp())


@pytest.fixture
def db():
    engine = create_engine("sqlite://", connect_args={"check_same_thread": False}, poolclass=StaticPool)
    Base.metadata.create_all(engine)
    Session = sessionmaker(engine, expire_on_commit=False)
    with Session() as session:
        session.add_all(
            [
                Lookup(type="againstworktime", name="Holiday", code=1, position=1),
                Lookup(type="asworktime", name="Vacation", code=1, position=1),
                Lookup(type="asworktime", name="Sick leave", code=2, position=2),
            ]
        )
        session.commit()
        yield session


def make_user(db, username="johan", password="secret", statsstart=None, offset=0, admin=0) -> User:
    user = User(
        username=username,
        password=hashlib.md5(password.encode()).hexdigest(),
        registerdate=ts(2024, 1, 1),
        dworkweek=40 * 3600,
        daysinworkweek=5,
        offset=offset,
        timezone="Europe/Stockholm",
        tempfiled=0,
        statsstartdate=statsstart if statsstart is not None else ts(2024, 1, 1),
        admin=admin,
    )
    db.add(user)
    db.commit()
    return user


@pytest.fixture
def user(db):
    return make_user(db)


@pytest.fixture(autouse=True)
def _reset_rate_limits():
    from app import ratelimit

    for limiter in (ratelimit.by_username, ratelimit.by_ip):
        limiter._failures.clear()


@pytest.fixture
def client(db):
    app = create_app()
    app.dependency_overrides[get_db] = lambda: db
    return TestClient(app)


@pytest.fixture
def auth(client, user):
    token = client.post("/api/v1/auth/login", json={"username": "johan", "password": "secret"}).json()["access_token"]
    return {"Authorization": f"Bearer {token}"}
