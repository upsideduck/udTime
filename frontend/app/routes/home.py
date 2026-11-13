"""Home page: timer, user/statistics panel and projects."""

import asyncio
import datetime as dt
from typing import Annotated

from fastapi import APIRouter, Form, Request

from ..api import BackendError
from ..config import settings
from ..web import Api, fragment, htmx_response, render, session_tz, toast

router = APIRouter()

TIMER_ACTIONS = ("start-work", "start-break", "end-break", "end-work")


async def _stats(api: Api) -> dict:
    summary, weeks = await asyncio.gather(
        api.get("/stats/summary", params={"types": "today,thisweek,thismonth"}),
        api.get("/stats/balance/weeks"),
    )
    finished = [w for w in weeks if not w["ongoing"]]
    summary["balance"] = finished[-1] if finished else None
    return summary


def _greeting(request: Request) -> str:
    hour = dt.datetime.now(session_tz(request)).hour
    return "Good morning" if 5 <= hour < 12 else "Good afternoon" if hour < 18 else "Good evening"


def _build_date(request: Request) -> str:
    """Build time from the Docker image, in the user's time zone; empty outside an image build."""
    try:
        built = dt.datetime.fromisoformat(settings.build_date)
    except ValueError:
        return ""
    if built.tzinfo is None:
        built = built.replace(tzinfo=dt.timezone.utc)
    return built.astimezone(session_tz(request)).strftime("%Y-%m-%d %H:%M")


@router.get("/")
async def home(request: Request, api: Api):
    timer, stats, projects, me = await asyncio.gather(
        api.get("/timer"), _stats(api), api.get("/projects"), api.get("/auth/me")
    )
    return render(request, "index.html", timer=timer, stats=stats, projects=projects, profile=me,
                  greeting=_greeting(request), today=dt.datetime.now(session_tz(request)),
                  build_date=_build_date(request))


@router.get("/partials/timer")
async def timer_partial(request: Request, api: Api):
    return htmx_response(fragment(request, "partials/timer.html", timer=await api.get("/timer")))


async def _timer_refresh(request: Request, api: Api, message: str, level: str = "success"):
    timer, stats = await asyncio.gather(api.get("/timer"), _stats(api))
    return htmx_response(
        fragment(request, "partials/timer.html", timer=timer, stats=stats),
        fragment(request, "partials/stats.html", stats=stats, oob=True),
        toast(request, message, level),
        trigger={"dataChanged": True},
    )


@router.post("/timer")
async def timer_action(
    request: Request,
    api: Api,
    action: Annotated[str, Form()],
    comment: Annotated[str, Form()] = "",
    time: Annotated[str, Form()] = "",
):
    if action not in TIMER_ACTIONS:
        return await _timer_refresh(request, api, "Unknown action", "danger")
    body = {"timestamp": time or None, "comment": comment}
    try:
        result = await api.post(f"/timer/{action}", body)
    except BackendError as exc:
        return await _timer_refresh(request, api, exc.detail, "danger")
    return await _timer_refresh(request, api, result["message"])


@router.post("/projects")
async def add_project(request: Request, api: Api, name: Annotated[str, Form()], color: Annotated[str, Form()] = ""):
    try:
        await api.post("/projects", {"name": name, "color": color or None})
        message, level = f"Project '{name}' added", "success"
    except BackendError as exc:
        message, level = exc.detail, "danger"
    projects = await api.get("/projects")
    return htmx_response(fragment(request, "partials/projects.html", projects=projects), toast(request, message, level))


def _project_id(value: str) -> int | None:
    return None if value == "none" else int(value)


@router.post("/projects/{project}/select")
async def select_project(request: Request, api: Api, project: str):
    """Attach the project to the running period, or ask whether to change it or start a new period."""
    timer = await api.get("/timer")
    if timer["state"] == "free":
        return htmx_response(toast(request, "No active period", "warning"), reswap="none")
    project_id = _project_id(project)
    current = timer["project"]["id"] if timer["project"] else None
    if current is None or current == project_id:
        return await assign_project(request, api, project, "change")
    projects = {str(p["id"]): p for p in await api.get("/projects")}
    return htmx_response(
        fragment(request, "forms/project_choice.html", project=project, target=projects.get(project), timer=timer),
        trigger_after_swap={"openModal": True},
    )


@router.post("/projects/{project}/assign")
async def assign_project(request: Request, api: Api, project: str, mode: Annotated[str, Form()] = "change"):
    project_id = _project_id(project)
    try:
        if mode == "newperiod":
            result = await api.post("/timer/switch-project", {"project_id": project_id})
            message = result["message"]
        else:
            timer = await api.get("/timer")
            if timer["state"] == "free":
                return htmx_response(toast(request, "No active period", "warning"), reswap="none")
            await api.put(f"/work/{timer['work_id']}/project", {"project_id": project_id})
            message = "Project updated"
    except BackendError as exc:
        return htmx_response(toast(request, exc.detail, "danger"), reswap="none")
    timer = await api.get("/timer")
    return htmx_response(
        fragment(request, "partials/timer.html", timer=timer, oob=True),
        toast(request, message),
        trigger={"closeModal": True, "dataChanged": True},
        reswap="none",
    )
