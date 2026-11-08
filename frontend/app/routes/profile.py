import datetime as dt
import re
from functools import cache
from typing import Annotated
from zoneinfo import available_timezones

from fastapi import APIRouter, File, Form, Request, UploadFile
from fastapi.responses import Response

from ..api import BackendError
from ..web import Api, failed, fragment, htmx_response, render, session_tz, toast

router = APIRouter()

# Recomputing every week/month since statsstartdate can take a while on a large history
RECOMPUTE_TIMEOUT = 300
MAX_AVATAR_BYTES = 20000

# API key scope areas: (area, label, has a write scope)
SCOPE_AREAS = [
    ("timer", "Timer", True),
    ("periods", "Work & breaks", True),
    ("absences", "Absences", True),
    ("projects", "Projects", True),
    ("profile", "Profile", True),
    ("stats", "Statistics", False),
    ("sync", "Sync", False),
    ("export", "CSV export", False),
]


@router.get("/profile")
async def profile(request: Request, api: Api):
    return render(request, "profile.html", profile=await api.get("/auth/me"), schedules=await api.get("/schedules"),
                  keys=await api.get("/apikeys"), areas=SCOPE_AREAS)


@router.get("/profile/card")
async def profile_card(request: Request, api: Api):
    return render(request, "partials/profile_card.html", profile=await api.get("/auth/me"),
                  schedules=await api.get("/schedules"))


@cache
def _timezones() -> list[str]:
    return sorted(tz for tz in available_timezones() if "/" in tz and not tz.startswith(("Etc/", "SystemV/")))


def _parse_hm(text: str, label: str) -> int:
    """'40', '7:30' or '-1:15' as seconds."""
    match = re.fullmatch(r"\s*([+-]?)(\d{1,4})(?::([0-5]\d))?\s*", text or "")
    if not match:
        raise BackendError(400, f"{label}: use hours or HH:MM")
    seconds = int(match[2]) * 3600 + int(match[3] or 0) * 60
    return -seconds if match[1] == "-" else seconds


@router.get("/forms/profile")
async def profile_form(request: Request, api: Api):
    profile = await api.get("/auth/me")
    zones = _timezones()
    if profile["timezone"] not in zones:
        zones = [profile["timezone"], *zones]
    return render(request, "forms/profile.html", profile=profile, timezones=zones,
                  headers={"HX-Trigger-After-Swap": '{"openModal": true}'})


@router.post("/forms/profile")
async def profile_save(
    request: Request,
    api: Api,
    offset: Annotated[str, Form()],
    timezone: Annotated[str, Form()],
):
    try:
        body = {"offset": _parse_hm(offset, "Starting offset"), "timezone": timezone}
        profile = await api.patch("/users/me", body)
    except BackendError as exc:
        return failed(request, exc)
    # Pages render times in the session's timezone
    request.session["user"] = {**request.session.get("user", {}), "timezone": profile["timezone"]}
    return htmx_response(toast(request, "Profile saved"),
                         trigger={"profileChanged": True, "dataChanged": True, "closeModal": True}, reswap="none")


# --- work schedule periods ------------------------------------------------------------

WEEKDAYS = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"]
SCHEDULE_MESSAGES = {"create": "Schedule change added", "update": "Schedule period saved",
                     "delete": "Schedule period removed"}


def _schedule_form(request: Request, values: dict, status_code: int = 200):
    return render(request, "forms/schedule.html", status_code=status_code, values=values, weekdays=WEEKDAYS,
                  headers={"HX-Trigger-After-Swap": '{"openModal": true}'})


@router.get("/forms/schedule")
async def schedule_form(request: Request, api: Api, id: int | None = None):
    periods = await api.get("/schedules")
    current = next((p for p in periods if p["current"]), periods[-1])
    if id is not None:
        period = next((p for p in periods if p["id"] == id), None)
        if period is None:
            return failed(request, BackendError(404, "Schedule period not found"))
        values = {**period, "op": "update"}
    else:
        today = dt.datetime.now(session_tz(request)).date().isoformat()
        values = {**current, "op": "create", "id": "", "valid_from": today}
    return _schedule_form(request, values)


def _workdays(values: list[str]) -> list[int]:
    return sorted({int(v) for v in values if v.isdigit() and 1 <= int(v) <= 7})


def _schedule_body(op: str, valid_from: str, dworkweek: str, workdays: list[str]) -> dict:
    if op == "delete":
        return {}
    days = _workdays(workdays)
    if not days:
        raise BackendError(400, "Choose at least one workday")
    return {"valid_from": valid_from, "dworkweek": _parse_hm(dworkweek, "Work week"), "workdays": days}


ScheduleField = Annotated[str, Form()]
WorkdaysField = Annotated[list[str], Form()]


@router.post("/forms/schedule/preview")
async def schedule_preview(request: Request, api: Api, op: ScheduleField, id: ScheduleField = "",
                           valid_from: ScheduleField = "", dworkweek: ScheduleField = "",
                           workdays: WorkdaysField = []):
    """Dry run of a schedule change, shown in the modal with Apply / Back."""
    values = {"op": op, "id": id, "valid_from": valid_from, "dworkweek": dworkweek, "workdays": _workdays(workdays)}
    try:
        body = _schedule_body(op, valid_from, dworkweek, workdays)
        preview = await api.post("/schedules/preview", {"op": op, "id": int(id) if id else None, **body})
        periods = await api.get("/schedules")
    except BackendError as exc:
        return failed(request, exc)
    period = next((p for p in periods if str(p["id"]) == id), None)
    return render(request, "forms/schedule_preview.html", preview=preview, values=values, body=body, period=period,
                  weekdays=WEEKDAYS, headers={"HX-Trigger-After-Swap": '{"openModal": true}'})


@router.post("/forms/schedule/back")
async def schedule_back(request: Request, op: ScheduleField, id: ScheduleField = "", valid_from: ScheduleField = "",
                        dworkweek: ScheduleField = "", workdays: WorkdaysField = []):
    try:
        seconds = _parse_hm(dworkweek, "Work week")
    except BackendError:
        seconds = 0
    days = _workdays(workdays) or [1, 2, 3, 4, 5]
    return _schedule_form(request, {"op": op, "id": id, "valid_from": valid_from, "dworkweek": seconds,
                                    "workdays": days, "day_length": seconds // len(days)})


@router.post("/forms/schedule/apply")
async def schedule_apply(request: Request, api: Api, op: ScheduleField, id: ScheduleField = "",
                         valid_from: ScheduleField = "", dworkweek: ScheduleField = "",
                         workdays: WorkdaysField = []):
    try:
        body = _schedule_body(op, valid_from, dworkweek, workdays)
        if op == "create":
            await api.post("/schedules", body)
        elif op == "update":
            await api.patch(f"/schedules/{int(id)}", body)
        else:
            await api.delete(f"/schedules/{int(id)}")
    except (BackendError, ValueError) as exc:
        return failed(request, exc if isinstance(exc, BackendError) else BackendError(400, "Invalid schedule period"))
    return htmx_response(toast(request, SCHEDULE_MESSAGES.get(op, "Saved")),
                         trigger={"profileChanged": True, "dataChanged": True, "closeModal": True}, reswap="none")


@router.get("/avatar")
async def avatar(api: Api):
    try:
        response = await api.get("/users/me/avatar", raw=True)
    except BackendError:
        return Response(status_code=404)
    return Response(response.content, media_type=response.headers.get("content-type", "image/jpeg"),
                    headers={"Cache-Control": "no-cache"})


@router.get("/forms/avatar")
async def avatar_form(request: Request):
    return render(request, "forms/avatar.html", headers={"HX-Trigger-After-Swap": '{"openModal": true}'})


@router.post("/profile/avatar")
async def upload_avatar(request: Request, api: Api, file: Annotated[UploadFile, File()]):
    data = await file.read(MAX_AVATAR_BYTES + 1)
    if len(data) > MAX_AVATAR_BYTES:
        return htmx_response(toast(request, "Avatar must be smaller than 20 kB", "danger"), reswap="none")
    try:
        await api.put("/users/me/avatar", files={"file": (file.filename or "avatar", data, file.content_type)})
    except BackendError as exc:
        return htmx_response(toast(request, exc.detail, "danger"), reswap="none")
    return htmx_response(toast(request, "Avatar updated"), trigger={"avatarChanged": True}, reswap="none")


@router.post("/profile/password")
async def change_password(
    request: Request,
    api: Api,
    current_password: Annotated[str, Form()],
    new_password: Annotated[str, Form()],
    confirm_password: Annotated[str, Form()],
):
    if new_password != confirm_password:
        return failed(request, BackendError(400, "The new passwords do not match"))
    try:
        await api.put("/users/me/password", {"current_password": current_password, "new_password": new_password})
    except BackendError as exc:
        return failed(request, exc)
    return htmx_response(toast(request, "Password changed"), trigger={"passwordChanged": True}, reswap="none")


@router.get("/admin/recompute/preview")
async def recompute_preview(request: Request, api: Api):
    """Dry run: show what recalculating the statistics would change."""
    try:
        preview = await api.get("/admin/recompute-stats/preview", timeout=RECOMPUTE_TIMEOUT)
    except BackendError as exc:
        return htmx_response(toast(request, exc.detail, "danger"), reswap="none")
    return htmx_response(fragment(request, "partials/recompute_preview.html", preview=preview))


@router.post("/admin/recompute")
async def recompute(request: Request, api: Api):
    try:
        result = await api.post("/admin/recompute-stats", timeout=RECOMPUTE_TIMEOUT)
    except BackendError as exc:
        return htmx_response(toast(request, exc.detail, "danger"), reswap="none")
    return htmx_response(
        fragment(request, "partials/recompute_preview.html", applied=True),
        toast(request, result["message"]),
        trigger={"dataChanged": True},
    )


# --- API keys -------------------------------------------------------------------------


def _key_levels(key: dict | None) -> dict[str, str]:
    """Access level per area ("", "read" or "write") for the form."""
    scopes = set(key["scopes"]) if key else set()
    return {area: "write" if f"{area}:write" in scopes else "read" if f"{area}:read" in scopes else ""
            for area, _, _ in SCOPE_AREAS}


@router.get("/profile/apikeys")
async def apikey_list(request: Request, api: Api):
    return render(request, "partials/apikeys.html", keys=await api.get("/apikeys"), areas=SCOPE_AREAS)


@router.get("/forms/apikey")
async def apikey_form(request: Request, api: Api, id: int | None = None):
    key = None
    if id is not None:
        key = next((k for k in await api.get("/apikeys") if k["id"] == id), None)
        if key is None:
            return failed(request, BackendError(404, "API key not found"))
    return render(request, "forms/apikey.html", key=key, levels=_key_levels(key), areas=SCOPE_AREAS,
                  headers={"HX-Trigger-After-Swap": '{"openModal": true}'})


@router.post("/forms/apikey")
async def apikey_save(
    request: Request,
    api: Api,
    name: Annotated[str, Form()],
    expires_on: Annotated[str, Form()] = "",
    is_active: Annotated[str, Form()] = "",
    id: Annotated[str, Form()] = "",
):
    form = await request.form()
    scopes = []
    for area, _, writable in SCOPE_AREAS:
        level = form.get(f"scope_{area}", "")
        if level == "write" and writable:
            scopes.append(f"{area}:write")
        elif level in ("read", "write"):
            scopes.append(f"{area}:read")
    if not scopes:
        return failed(request, BackendError(400, "Give the key access to at least one area"))
    body = {"name": name, "scopes": scopes, "expires_on": expires_on or None}
    try:
        if id:
            await api.patch(f"/apikeys/{int(id)}", {**body, "is_active": bool(is_active)})
        else:
            created = await api.post("/apikeys", body)
    except BackendError as exc:
        return failed(request, exc)
    if id:
        return htmx_response(toast(request, "API key saved"), trigger={"apikeysChanged": True, "closeModal": True},
                             reswap="none")
    # Show the new key once, in the modal
    return htmx_response(fragment(request, "forms/apikey_created.html", key=created),
                         trigger={"apikeysChanged": True})


@router.delete("/apikeys/{key_id}")
async def apikey_delete(request: Request, api: Api, key_id: int):
    try:
        await api.delete(f"/apikeys/{key_id}")
    except BackendError as exc:
        return failed(request, exc)
    return htmx_response(toast(request, "API key removed"), trigger={"apikeysChanged": True, "closeModal": True},
                         reswap="none")
