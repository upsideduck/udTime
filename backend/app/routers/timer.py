from typing import Annotated

from fastapi import APIRouter, Depends

from ..deps import DB, require
from ..models import User
from ..schemas import ProjectAssignIn, StartWorkIn, TimerActionIn
from ..services import timer
from ..services.timeutil import parse_timestamp, user_tz

router = APIRouter(prefix="/timer", tags=["timer"])

Reader = Annotated[User, Depends(require("timer:read"))]
Writer = Annotated[User, Depends(require("timer:write"))]


def _result(db, user: User, message: str) -> dict:
    return {"message": message, "timer": timer.current_state(db, user)}


@router.get("")
def get_timer(db: DB, user: Reader):
    return timer.current_state(db, user)


@router.post("/start-work")
def start_work(body: StartWorkIn, db: DB, user: Writer):
    ts = parse_timestamp(body.timestamp, user_tz(user))
    return _result(db, user, timer.start_work(db, user, ts, body.comment, body.project_id))


@router.post("/start-break")
def start_break(body: TimerActionIn, db: DB, user: Writer):
    ts = parse_timestamp(body.timestamp, user_tz(user))
    return _result(db, user, timer.start_break(db, user, ts, body.comment))


@router.post("/end-break")
def end_break(body: TimerActionIn, db: DB, user: Writer):
    ts = parse_timestamp(body.timestamp, user_tz(user))
    return _result(db, user, timer.end_break(db, user, ts, body.comment))


@router.post("/end-work")
def end_work(body: TimerActionIn, db: DB, user: Writer):
    ts = parse_timestamp(body.timestamp, user_tz(user))
    return _result(db, user, timer.end_work(db, user, ts, body.comment))


@router.post("/switch-project")
def switch_project(body: ProjectAssignIn, db: DB, user: Writer):
    """End the active period now and start a new one with the given project."""
    return _result(db, user, timer.switch_project(db, user, body.project_id))
