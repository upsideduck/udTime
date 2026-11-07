import datetime as dt
from typing import Annotated, Literal

from fastapi import APIRouter, Depends, Query, Response

from ..deps import DB, require
from ..models import User
from ..schemas import AbsenceIn, AbsencePatch
from ..services import absences
from ..services.timeutil import parse_duration

router = APIRouter(tags=["absences"])

Kind = Literal["asworktime", "againstworktime"]
Reader = Annotated[User, Depends(require("absences:read"))]
Writer = Annotated[User, Depends(require("absences:write"))]


@router.get("/lookups/{kind}")
def lookups(kind: Kind, db: DB, user: Reader):
    return absences.lookups(db, kind)


@router.get("/absences/{kind}")
def list_absences(
    kind: Kind,
    db: DB,
    user: Reader,
    start: Annotated[dt.date, Query(alias="from")],
    end: Annotated[dt.date, Query(alias="to")],
):
    return absences.list_absences(db, user, kind, start, end)


@router.post("/absences/{kind}", status_code=201)
def add_absences(kind: Kind, body: AbsenceIn, db: DB, user: Writer):
    """Registers one row per weekday in the (inclusive) range that has none yet."""
    return absences.add_range(
        db, user, kind, body.start_date, body.end_date or body.start_date, body.type,
        parse_duration(body.time), body.comment,
    )


@router.get("/absences/{kind}/{item_id}")
def get_absence(kind: Kind, item_id: int, db: DB, user: Reader):
    return absences.get_absence(db, user, kind, item_id)


@router.patch("/absences/{kind}/{item_id}")
def update_absence(kind: Kind, item_id: int, body: AbsencePatch, db: DB, user: Writer):
    time = None if body.time in (None, "") else parse_duration(body.time)
    return absences.update_absence(db, user, kind, item_id, time, body.type, body.comment)


@router.delete("/absences/{kind}/{item_id}", status_code=204)
def delete_absence(kind: Kind, item_id: int, db: DB, user: Writer):
    absences.delete_absence(db, user, kind, item_id)
    return Response(status_code=204)
