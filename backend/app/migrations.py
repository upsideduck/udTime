"""Database patches added by udTime 2.0 on top of the original schema (udtime.sql).

Each migration detects itself by inspecting the database, so no version table is needed.
Pending migrations are reported at startup and by GET /api/v1/schema, and are applied by an
admin from the frontend's setup page (POST /api/v1/admin/schema/apply).
"""

import logging
from collections.abc import Callable
from dataclasses import dataclass

from sqlalchemy import inspect, insert, select, text, update
from sqlalchemy.engine import Connection
from sqlalchemy.schema import CreateTable

from .models import User, WorkSchedule
from .services.schedule import base_date, parse_workdays

log = logging.getLogger("udtime.migrations")


@dataclass(frozen=True)
class Migration:
    id: str
    title: str
    description: str
    is_applied: Callable[[Connection], bool]
    apply: Callable[[Connection], None]
    sql: Callable[[Connection], str]


def _create_workschedule(conn: Connection) -> None:
    WorkSchedule.__table__.create(conn)
    # One period per user, from the statistics start date, with today's userdb values
    users = conn.execute(select(User.__table__)).mappings().all()
    rows = [
        {"member_id": u.member_id, "valid_from": base_date(User(**{k: u[k] for k in ("statsstartdate", "timezone")})),
         "dworkweek": u.dworkweek, "daysinworkweek": u.daysinworkweek or 5,
         "workdays": "".join(map(str, parse_workdays(None, u.daysinworkweek)))}
        for u in users
    ]
    if rows:
        conn.execute(insert(WorkSchedule.__table__), rows)


def _workdays_applied(conn: Connection) -> bool:
    inspector = inspect(conn)
    if not inspector.has_table("workschedule"):
        return True  # 001 creates the table with the column
    return any(c["name"] == "workdays" for c in inspector.get_columns("workschedule"))


def _add_workdays(conn: Connection) -> None:
    conn.execute(text("ALTER TABLE workschedule ADD COLUMN workdays VARCHAR(7) NULL"))
    table = WorkSchedule.__table__
    for row in conn.execute(select(table.c.id, table.c.daysinworkweek)).all():
        digits = "".join(map(str, parse_workdays(None, row.daysinworkweek)))
        conn.execute(update(table).where(table.c.id == row.id).values(workdays=digits))


MIGRATIONS = [
    Migration(
        id="001_workschedule",
        title="Work schedule periods",
        description=(
            "Adds the table `workschedule`, so the work week, days per week and standard day can change over "
            "time. Every user gets one period starting at their statistics start date, with their current "
            "work week. Existing tables and data are not changed."
        ),
        is_applied=lambda conn: inspect(conn).has_table("workschedule"),
        apply=_create_workschedule,
        sql=lambda conn: str(CreateTable(WorkSchedule.__table__).compile(conn)).strip() + ";\n"
        "-- then one row per user: (member_id, date of statsstartdate, dworkweek, daysinworkweek, workdays)",
    ),
    Migration(
        id="002_workschedule_workdays",
        title="Workdays per schedule period",
        description=(
            "Adds the column `workdays` to `workschedule`, so each period can say which weekdays are worked "
            "(e.g. Mon, Tue, Thu, Fri). Existing periods get the first N weekdays from Monday, which is how they "
            "were counted until now, so no statistics change."
        ),
        is_applied=_workdays_applied,
        apply=_add_workdays,
        sql=lambda conn: "ALTER TABLE workschedule ADD COLUMN workdays VARCHAR(7) NULL;\n"
        "-- then each row: workdays = the first daysinworkweek weekday digits, e.g. '12345'",
    ),
]


def pending(conn: Connection) -> list[Migration]:
    return [m for m in MIGRATIONS if not m.is_applied(conn)]


def describe(conn: Connection, migrations: list[Migration]) -> list[dict]:
    return [{"id": m.id, "title": m.title, "description": m.description, "sql": m.sql(conn)} for m in migrations]


def apply_pending(conn: Connection) -> list[str]:
    applied = []
    for migration in pending(conn):
        log.warning("Applying database migration %s", migration.id)
        migration.apply(conn)
        applied.append(migration.id)
    return applied
