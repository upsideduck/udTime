"""SQLAlchemy models mapped 1:1 onto the existing udTime tables (see udtime.sql).

The schema is owned by the database; never run create_all() against production.
`modified` columns are MySQL TIMESTAMPs (ON UPDATE CURRENT_TIMESTAMP); they are also
set from Python so behaviour is identical on SQLite in tests.
"""

import datetime as dt

from sqlalchemy import (
    TIMESTAMP,
    BigInteger,
    Date,
    Enum,
    Integer,
    LargeBinary,
    SmallInteger,
    String,
    Text,
)
from sqlalchemy.orm import DeclarativeBase, Mapped, mapped_column

# BIGINT does not autoincrement on SQLite, so fall back to INTEGER there.
BigIntPK = BigInteger().with_variant(Integer, "sqlite")


def utcnow() -> dt.datetime:
    return dt.datetime.now(dt.timezone.utc).replace(tzinfo=None)


def to_epoch(value: dt.datetime | str | None) -> int:
    # Legacy zero dates ('0000-00-00 00:00:00') come back from PyMySQL as plain strings
    if not isinstance(value, dt.datetime):
        return 0
    return int(value.replace(tzinfo=dt.timezone.utc).timestamp())


def from_epoch(value: int) -> dt.datetime:
    return dt.datetime.fromtimestamp(value, dt.timezone.utc).replace(tzinfo=None)


class Base(DeclarativeBase):
    pass


def modified_column() -> Mapped[dt.datetime]:
    return mapped_column(TIMESTAMP, nullable=False, default=utcnow, onupdate=utcnow)


class User(Base):
    __tablename__ = "userdb"

    member_id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    username: Mapped[str] = mapped_column(Text, nullable=False)
    password: Mapped[str] = mapped_column(Text, nullable=False)
    firstname: Mapped[str | None] = mapped_column(Text)
    lastname: Mapped[str | None] = mapped_column(Text)
    registerdate: Mapped[int] = mapped_column(Integer, nullable=False)
    avatar: Mapped[bytes | None] = mapped_column(LargeBinary, deferred=True)
    dworkweek: Mapped[int] = mapped_column(Integer, nullable=False)
    daysinworkweek: Mapped[int] = mapped_column(Integer, nullable=False, default=5)
    offset: Mapped[int] = mapped_column(Integer, nullable=False, default=0)
    timezone: Mapped[str] = mapped_column(Text, nullable=False)
    activeperiod: Mapped[int | None] = mapped_column(Integer)
    activetype: Mapped[str | None] = mapped_column(Text)
    tempfiled: Mapped[int] = mapped_column(Integer, nullable=False, default=0)
    statsstartdate: Mapped[int] = mapped_column(Integer, nullable=False)
    admin: Mapped[int] = mapped_column(SmallInteger, nullable=False, default=0)

    @property
    def day_length(self) -> float:
        """Seconds of a standard working day."""
        return self.dworkweek / (self.daysinworkweek or 5)


class Work(Base):
    __tablename__ = "workdb"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    member_id: Mapped[int] = mapped_column(Integer, nullable=False)
    starttime: Mapped[int | None] = mapped_column(BigInteger)
    endtime: Mapped[int | None] = mapped_column(BigInteger)
    comment: Mapped[str | None] = mapped_column(Text)
    logged: Mapped[int] = mapped_column(SmallInteger, nullable=False, default=0)
    project_id: Mapped[int | None] = mapped_column(Integer)
    modified: Mapped[dt.datetime] = modified_column()


class Break(Base):
    __tablename__ = "breakdb"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    parent_id: Mapped[int] = mapped_column(Integer, nullable=False)
    member_id: Mapped[int] = mapped_column(Integer, nullable=False)
    starttime: Mapped[int | None] = mapped_column(Integer)
    endtime: Mapped[int | None] = mapped_column(Integer)
    comment: Mapped[str | None] = mapped_column(Text)
    logged: Mapped[int] = mapped_column(SmallInteger, nullable=False, default=0)
    modified: Mapped[dt.datetime] = modified_column()


class AsWorkTime(Base):
    """Paid absence that counts as worked time (vacation, sick leave, ...)."""

    __tablename__ = "asworktime"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    member_id: Mapped[int] = mapped_column(Integer, nullable=False)
    date: Mapped[dt.date] = mapped_column(Date, nullable=False)
    time: Mapped[int] = mapped_column(Integer, nullable=False)
    type: Mapped[int] = mapped_column(Integer, nullable=False, default=1)
    modified: Mapped[dt.datetime] = modified_column()


class AgainstWorkTime(Base):
    """Time that reduces the time to work (public holidays, reduced days, ...)."""

    __tablename__ = "againstworktime"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    member_id: Mapped[int] = mapped_column(Integer, nullable=False)
    date: Mapped[dt.date] = mapped_column(Date, nullable=False)
    time: Mapped[int] = mapped_column(Integer, nullable=False)
    comment: Mapped[str | None] = mapped_column(Text)
    type: Mapped[int] = mapped_column(Integer, nullable=False, default=1)
    modified: Mapped[dt.datetime] = modified_column()


class Lookup(Base):
    __tablename__ = "lookups"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    type: Mapped[str] = mapped_column(String(128), nullable=False)
    name: Mapped[str] = mapped_column(String(128), nullable=False)
    code: Mapped[int] = mapped_column(Integer, nullable=False)
    position: Mapped[int] = mapped_column(Integer, nullable=False, default=0)


class WeekStats(Base):
    __tablename__ = "weekstats"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    member_id: Mapped[int] = mapped_column(Integer, nullable=False)
    year: Mapped[int] = mapped_column(Integer, nullable=False)
    week: Mapped[int] = mapped_column(Integer, nullable=False)
    towork: Mapped[int] = mapped_column(Integer, nullable=False)
    againstworktime: Mapped[int] = mapped_column(Integer, nullable=False, default=0)
    worked: Mapped[int] = mapped_column(Integer, nullable=False)
    asworktime: Mapped[int] = mapped_column(Integer, nullable=False, default=0)
    modified: Mapped[dt.datetime] = modified_column()


class MonthStats(Base):
    __tablename__ = "monthstats"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    member_id: Mapped[int] = mapped_column(Integer, nullable=False)
    year: Mapped[int] = mapped_column(Integer, nullable=False)
    month: Mapped[int] = mapped_column(Integer, nullable=False)
    towork: Mapped[int] = mapped_column(Integer, nullable=False)
    againstworktime: Mapped[int] = mapped_column(Integer, nullable=False, default=0)
    worked: Mapped[int] = mapped_column(Integer, nullable=False, default=0)
    asworktime: Mapped[int] = mapped_column(Integer, nullable=False, default=0)
    modified: Mapped[dt.datetime] = modified_column()


class Project(Base):
    __tablename__ = "projectdb"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    member_id: Mapped[int] = mapped_column(Integer, nullable=False)
    name: Mapped[str] = mapped_column(Text, nullable=False)
    description: Mapped[str | None] = mapped_column(Text)
    color: Mapped[str] = mapped_column(Text, nullable=False)


class ApiKey(Base):
    __tablename__ = "apikeys"

    id: Mapped[int] = mapped_column(BigIntPK, primary_key=True, autoincrement=True)
    member_id: Mapped[int] = mapped_column(BigInteger, nullable=False)
    key_hash: Mapped[str] = mapped_column(String(64), nullable=False, unique=True)
    scopes: Mapped[str] = mapped_column(Text, nullable=False)
    is_active: Mapped[int] = mapped_column(SmallInteger, nullable=False, default=1)
    expires_at: Mapped[dt.datetime | None] = mapped_column(TIMESTAMP, nullable=True)
    last_used_at: Mapped[dt.datetime | None] = mapped_column(TIMESTAMP, nullable=True)
    name: Mapped[str | None] = mapped_column(String(255))
    created_at: Mapped[dt.datetime] = mapped_column(TIMESTAMP, nullable=False, default=utcnow)


class Deleted(Base):
    """Tombstones of hard-deleted rows, consumed by sync clients."""

    __tablename__ = "deleted"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    type: Mapped[str] = mapped_column(
        Enum("work", "break", "asworktime", "againstworktime", name="deleted_type"), nullable=False
    )
    original_id: Mapped[int] = mapped_column(Integer, nullable=False)
    member_id: Mapped[int] = mapped_column(Integer, nullable=False)
    modified: Mapped[dt.datetime] = modified_column()
