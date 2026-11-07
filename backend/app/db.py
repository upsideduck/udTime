from collections.abc import Iterator

from sqlalchemy import create_engine
from sqlalchemy.orm import Session, sessionmaker

from .config import settings


def make_engine(url: str):
    connect_args = {}
    if url.startswith("mysql"):
        # TIMESTAMP columns (`modified`) are read and written as UTC naive datetimes.
        connect_args = {"init_command": "SET time_zone = '+00:00'", "charset": "utf8mb4"}
    return create_engine(url, pool_pre_ping=True, pool_recycle=3600, connect_args=connect_args)


engine = make_engine(settings.database_url)
SessionLocal = sessionmaker(engine, expire_on_commit=False)


def get_db() -> Iterator[Session]:
    with SessionLocal() as db:
        yield db
