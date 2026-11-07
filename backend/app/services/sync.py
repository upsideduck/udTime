"""Changes since a cursor for offline clients (port of api_serverupdates.php)."""

from sqlalchemy import select
from sqlalchemy.orm import Session

from ..models import AgainstWorkTime, AsWorkTime, Break, Deleted, User, Work, from_epoch, to_epoch
from . import timeutil as tu
from .serialize import absence_dict, break_dict, work_dict


def changes_since(db: Session, user: User, since: int) -> dict:
    cursor = tu.now_ts()
    after = from_epoch(since)

    def rows(model):
        return db.scalars(
            select(model).where(model.member_id == user.member_id, model.modified >= after).order_by(model.modified)
        )

    return {
        "cursor": cursor,
        "work": [work_dict(w) for w in rows(Work)],
        "break": [break_dict(b) for b in rows(Break)],
        "asworktime": [absence_dict("asworktime", r, None) for r in rows(AsWorkTime)],
        "againstworktime": [absence_dict("againstworktime", r, None) for r in rows(AgainstWorkTime)],
        "deleted": [
            {"original_id": d.original_id, "type": d.type, "modified": to_epoch(d.modified)} for d in rows(Deleted)
        ],
    }
