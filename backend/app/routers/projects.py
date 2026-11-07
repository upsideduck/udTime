from typing import Annotated

from fastapi import APIRouter, Depends

from ..deps import DB, require
from ..models import User
from ..schemas import ProjectIn
from ..services import projects

router = APIRouter(prefix="/projects", tags=["projects"])


@router.get("")
def list_projects(db: DB, user: Annotated[User, Depends(require("projects:read"))]):
    return projects.list_projects(db, user)


@router.post("", status_code=201)
def create_project(body: ProjectIn, db: DB, user: Annotated[User, Depends(require("projects:write"))]):
    return projects.create_project(db, user, body.name, body.description, body.color)
