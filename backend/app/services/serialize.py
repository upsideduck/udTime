from ..models import AgainstWorkTime, AsWorkTime, Break, Project, User, Work, to_epoch
from .timeutil import format_hms


def break_dict(b: Break) -> dict:
    return {
        "id": b.id,
        "parent_id": b.parent_id,
        "starttime": b.starttime,
        "endtime": b.endtime,
        "comment": b.comment,
        "logged": bool(b.logged),
        "modified": to_epoch(b.modified),
    }


def work_dict(w: Work, breaks: list[Break] | None = None, project: Project | None = None) -> dict:
    data = {
        "id": w.id,
        "starttime": w.starttime,
        "endtime": w.endtime,
        "comment": w.comment,
        "logged": bool(w.logged),
        "project_id": w.project_id,
        "modified": to_epoch(w.modified),
    }
    if breaks is not None:
        data["breaks"] = [break_dict(b) for b in breaks]
        data["break_time"] = sum(b.endtime - b.starttime for b in breaks if b.endtime is not None)
        if w.endtime is not None:
            data["worked"] = w.endtime - w.starttime - data["break_time"]
    if project is not None:
        data["project"] = project_dict(project)
    return data


def absence_dict(kind: str, row: AsWorkTime | AgainstWorkTime, typelabel: str | None) -> dict:
    data = {
        "id": row.id,
        "kind": kind,
        "date": row.date.isoformat(),
        "time": row.time,
        "time_hms": format_hms(row.time),
        "type": row.type,
        "typelabel": typelabel,
        "modified": to_epoch(row.modified),
    }
    if isinstance(row, AgainstWorkTime):
        data["comment"] = row.comment
    return data


def project_dict(p: Project) -> dict:
    return {"id": p.id, "name": p.name, "description": p.description, "color": p.color}


def user_dict(u: User) -> dict:
    return {
        "member_id": u.member_id,
        "username": u.username,
        "firstname": u.firstname,
        "lastname": u.lastname,
        "registerdate": u.registerdate,
        "statsstartdate": u.statsstartdate,
        "dworkweek": u.dworkweek,
        "daysinworkweek": u.daysinworkweek,
        "offset": u.offset,
        "timezone": u.timezone,
        "admin": bool(u.admin),
        "activeperiod": u.activeperiod,
        "activetype": u.activetype,
    }
