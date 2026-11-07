from sqlalchemy import select
from sqlalchemy.orm import Session

from ..errors import DomainError
from ..models import Project, User
from .serialize import project_dict

DEFAULT_COLOR = "#3a87ad"


def list_projects(db: Session, user: User) -> list[dict]:
    rows = db.scalars(select(Project).where(Project.member_id == user.member_id).order_by(Project.name))
    return [project_dict(p) for p in rows]


def create_project(db: Session, user: User, name: str, description: str | None, color: str | None) -> dict:
    name = name.strip()
    if not name:
        raise DomainError("Project name is required")
    project = Project(member_id=user.member_id, name=name, description=description, color=color or DEFAULT_COLOR)
    db.add(project)
    db.commit()
    return project_dict(project)
