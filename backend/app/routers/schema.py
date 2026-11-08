from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, Request, status
from sqlalchemy.exc import SQLAlchemyError

from .. import migrations
from ..deps import DB, require_admin
from ..models import User

router = APIRouter(tags=["schema"])


@router.get("/schema")
def schema_status(db: DB, request: Request):
    """Public: database patches that must be applied before the app can be used."""
    conn = db.connection()
    pending = migrations.pending(conn)
    request.app.state.schema_pending = bool(pending)
    return {"up_to_date": not pending, "pending": migrations.describe(conn, pending)}


@router.post("/admin/schema/apply")
def apply_schema(db: DB, request: Request, _: Annotated[User, Depends(require_admin)]):
    """Apply all pending database patches. Admins only, with a password/SSO login."""
    try:
        applied = migrations.apply_pending(db.connection())
        db.commit()
    except SQLAlchemyError as exc:
        db.rollback()
        raise HTTPException(status.HTTP_500_INTERNAL_SERVER_ERROR,
                            f"The database update failed: {exc.__class__.__name__}: {exc.orig or exc}") from exc
    request.app.state.schema_pending = False
    return {"applied": applied}
