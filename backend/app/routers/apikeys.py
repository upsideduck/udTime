from typing import Annotated

from fastapi import APIRouter, Depends, Response

from ..deps import DB, require_login
from ..models import User
from ..schemas import ApiKeyIn, ApiKeyPatch
from ..security import SCOPES
from ..services import apikeys

router = APIRouter(prefix="/apikeys", tags=["api keys"])

# Keys are managed with a password/SSO login only; an API key can't create or widen keys
Owner = Annotated[User, Depends(require_login)]


@router.get("")
def list_keys(db: DB, user: Owner):
    return apikeys.list_keys(db, user)


@router.get("/scopes")
def list_scopes(_: Owner):
    return sorted(SCOPES)


@router.post("", status_code=201, description="Creates a key. The plain `key` is only returned here, once.")
def create_key(body: ApiKeyIn, db: DB, user: Owner):
    return apikeys.create_key(db, user, body.name, body.scopes, body.expires_on)


@router.patch("/{key_id}")
def update_key(key_id: int, body: ApiKeyPatch, db: DB, user: Owner):
    return apikeys.update_key(db, user, key_id, body.model_dump(exclude_unset=True))


@router.delete("/{key_id}", status_code=204)
def delete_key(key_id: int, db: DB, user: Owner):
    apikeys.delete_key(db, user, key_id)
    return Response(status_code=204)
