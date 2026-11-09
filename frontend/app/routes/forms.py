"""Modal forms (work, break, absences, choice) and deletes. Forms are loaded into the
shared #modal-content; on success the modal closes and views refresh via `dataChanged`."""

import datetime as dt
from typing import Annotated, Literal

from fastapi import APIRouter, Form, Request

from ..api import BackendError
from ..web import Api, changed, failed, input_value, render, session_tz

router = APIRouter()

AbsenceKind = Literal["asworktime", "againstworktime"]
KIND_TITLES = {"asworktime": "As work time", "againstworktime": "Reduced work time"}


def _modal(request: Request, name: str, **ctx):
    return render(request, name, headers={"HX-Trigger-After-Swap": '{"openModal": true}'}, **ctx)


def _prefill_times(request: Request, date: str | None, start: str | None, end: str | None) -> tuple[str, str]:
    tz = session_tz(request)
    if start and end:  # from a calendar selection, e.g. 2026-11-09T08:00:00
        return start[:19].replace("T", " "), end[:19].replace("T", " ")
    day = dt.date.fromisoformat(date) if date else dt.datetime.now(tz).date()
    return f"{day.isoformat()} 08:00:00", f"{day.isoformat()} 17:00:00"


# --- work -----------------------------------------------------------------------------


@router.get("/forms/work")
async def work_form(request: Request, api: Api, id: int | None = None, date: str | None = None,
                    start: str | None = None, end: str | None = None):
    projects = await api.get("/projects")
    if id is not None:
        work = await api.get(f"/work/{id}")
        tz = session_tz(request)
        return _modal(request, "forms/work.html", work=work, projects=projects,
                      start=input_value(work["starttime"], tz), end=input_value(work["endtime"], tz))
    start, end = _prefill_times(request, date, start, end)
    return _modal(request, "forms/work.html", work=None, projects=projects, start=start, end=end)


@router.post("/forms/work")
async def work_save(
    request: Request,
    api: Api,
    starttime: Annotated[str, Form()],
    endtime: Annotated[str, Form()] = "",
    comment: Annotated[str, Form()] = "",
    project_id: Annotated[str, Form()] = "",
    id: Annotated[str, Form()] = "",
):
    try:
        if id:
            body = {"starttime": starttime, "comment": comment}
            if endtime:
                body["endtime"] = endtime
            work = await api.patch(f"/work/{id}", body)
            current = work.get("project_id")
            wanted = int(project_id) if project_id else None
            if current != wanted:
                await api.put(f"/work/{id}/project", {"project_id": wanted})
            return changed(request, "Work period updated")
        await api.post("/work", {"starttime": starttime, "endtime": endtime, "comment": comment or None,
                                 "project_id": int(project_id) if project_id else None})
        return changed(request, "Work period added")
    except BackendError as exc:
        return failed(request, exc)


# --- break ----------------------------------------------------------------------------


@router.get("/forms/break")
async def break_form(request: Request, api: Api, id: int | None = None, parent: int | None = None,
                     date: str | None = None, start: str | None = None, end: str | None = None):
    tz = session_tz(request)
    if id is not None:
        brk = await api.get(f"/breaks/{id}")
        parent_work = await api.get(f"/work/{brk['parent_id']}")
        return _modal(request, "forms/break.html", brk=brk, parent=parent_work,
                      start=input_value(brk["starttime"], tz), end=input_value(brk["endtime"], tz))
    parent_work = None
    if parent is not None:
        parent_work = await api.get(f"/work/{parent}")
        middle = (parent_work["starttime"] + (parent_work["endtime"] or parent_work["starttime"])) // 2
        start, end = input_value(middle, tz), input_value(middle + 1800, tz)
    elif start and end:
        start, end = start[:19], end[:19]
    else:
        day = date or dt.datetime.now(tz).date().isoformat()
        start, end = f"{day}T12:00:00", f"{day}T12:30:00"
    return _modal(request, "forms/break.html", brk=None, parent=parent_work, start=start, end=end)


@router.post("/forms/break")
async def break_save(
    request: Request,
    api: Api,
    starttime: Annotated[str, Form()],
    endtime: Annotated[str, Form()],
    comment: Annotated[str, Form()] = "",
    id: Annotated[str, Form()] = "",
):
    try:
        if id:
            await api.patch(f"/breaks/{id}", {"starttime": starttime, "endtime": endtime, "comment": comment})
            return changed(request, "Break updated")
        await api.post("/breaks", {"starttime": starttime, "endtime": endtime, "comment": comment or None})
        return changed(request, "Break added")
    except BackendError as exc:
        return failed(request, exc)


# --- absences -------------------------------------------------------------------------


@router.get("/forms/absence/{kind}")
async def absence_form(request: Request, api: Api, kind: AbsenceKind, id: int | None = None,
                       start: str | None = None, end: str | None = None):
    lookups = await api.get(f"/lookups/{kind}")
    item = await api.get(f"/absences/{kind}/{id}") if id is not None else None
    today = dt.datetime.now(session_tz(request)).date().isoformat()
    return _modal(request, "forms/absence.html", kind=kind, title=KIND_TITLES[kind], lookups=lookups, item=item,
                  start=(start or today)[:10], end=(end or start or today)[:10])


@router.post("/forms/absence/{kind}")
async def absence_save(
    request: Request,
    api: Api,
    kind: AbsenceKind,
    type: Annotated[int, Form()],
    time: Annotated[str, Form()] = "",
    start_date: Annotated[str, Form()] = "",
    end_date: Annotated[str, Form()] = "",
    comment: Annotated[str, Form()] = "",
    id: Annotated[str, Form()] = "",
):
    try:
        if id:
            body = {"type": type, "time": time or None}
            if kind == "againstworktime":
                body["comment"] = comment
            await api.patch(f"/absences/{kind}/{id}", body)
            return changed(request, f"{KIND_TITLES[kind]} updated")
        result = await api.post(f"/absences/{kind}", {
            "start_date": start_date, "end_date": end_date or start_date, "type": type,
            "time": time or None, "comment": comment or None,
        })
        message = f"{KIND_TITLES[kind]}: {len(result['created'])} day(s) added"
        if result["skipped"]:
            message += f", {len(result['skipped'])} skipped (weekend or already registered)"
        return changed(request, message)
    except BackendError as exc:
        return failed(request, exc)


# --- choice (calendar selection / "+" in views) ---------------------------------------


@router.get("/forms/choice")
async def choice_form(request: Request, start: str, end: str | None = None):
    return _modal(request, "forms/choice.html", start=start[:10], end=(end or start)[:10])


# --- deletes --------------------------------------------------------------------------


@router.delete("/items/work/{item_id}")
async def delete_work(request: Request, api: Api, item_id: int):
    try:
        await api.delete(f"/work/{item_id}")
        return changed(request, "Work period removed")
    except BackendError as exc:
        return failed(request, exc)


@router.delete("/items/break/{item_id}")
async def delete_break(request: Request, api: Api, item_id: int):
    try:
        await api.delete(f"/breaks/{item_id}")
        return changed(request, "Break removed")
    except BackendError as exc:
        return failed(request, exc)


@router.delete("/items/{kind}/{item_id}")
async def delete_absence(request: Request, api: Api, kind: AbsenceKind, item_id: int):
    try:
        await api.delete(f"/absences/{kind}/{item_id}")
        return changed(request, f"{KIND_TITLES[kind]} removed")
    except BackendError as exc:
        return failed(request, exc)
