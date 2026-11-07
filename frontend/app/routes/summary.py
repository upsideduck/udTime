"""Summary pages: week/month totals, week/month views, calendars and CSV export."""

import asyncio
import datetime as dt
import math

from fastapi import APIRouter, Request
from fastapi.responses import Response

from ..web import Api, render, session_tz

router = APIRouter()

PAGE_SIZE = 10


def _paginate(rows: list, page: int) -> tuple[list, int, int]:
    rows = list(reversed(rows))  # newest first
    pages = max(1, math.ceil(len(rows) / PAGE_SIZE))
    page = min(max(page, 1), pages)
    return rows[(page - 1) * PAGE_SIZE : page * PAGE_SIZE], page, pages


@router.get("/summary/week-totals")
async def week_totals(request: Request, api: Api, page: int = 1):
    rows, page, pages = _paginate(await api.get("/stats/balance/weeks"), page)
    return render(request, "week_totals.html", rows=rows, page=page, pages=pages)


@router.get("/summary/month-totals")
async def month_totals(request: Request, api: Api, page: int = 1):
    rows, page, pages = _paginate(await api.get("/stats/balance/months"), page)
    return render(request, "month_totals.html", rows=rows, page=page, pages=pages)


def _today(request: Request) -> dt.date:
    return dt.datetime.now(session_tz(request)).date()


@router.get("/summary/week")
async def week_view(request: Request, api: Api, year: int | None = None, week: int | None = None,
                    date: dt.date | None = None):
    if year is None or week is None:
        year, week, _ = (date or _today(request)).isocalendar()
    detail = await api.get(f"/stats/week-detail/{year}/{week}")
    return render(request, "week_view.html", detail=detail, today=_today(request).isoformat())


@router.get("/summary/month")
async def month_view(request: Request, api: Api, year: int | None = None, month: int | None = None,
                     date: dt.date | None = None):
    if year is None or month is None:
        day = date or _today(request)
        year, month = day.year, day.month
    detail = await api.get(f"/stats/month-detail/{year}/{month}")
    return render(request, "month_view.html", detail=detail, today=_today(request).isoformat(),
                  month_name=dt.date(year, month, 1).strftime("%B"))


@router.get("/export/{year}/{month}.csv")
async def export_csv(year: int, month: int, api: Api):
    response = await api.get(f"/export/month/{year}/{month}.csv", raw=True)
    return Response(
        response.content,
        media_type="text/csv",
        headers={"Content-Disposition": response.headers.get("content-disposition", "attachment")},
    )


@router.get("/calendar/{view}")
async def calendar(request: Request, view: str, api: Api):
    if view not in ("week", "month"):
        view = "week"
    return render(request, "calendar.html", view=view)


def _work_title(seconds: int) -> str:
    return f"Work {seconds // 3600:02d}:{seconds % 3600 // 60:02d}"


@router.get("/calendar-data/events")
async def calendar_events(request: Request, api: Api, start: str, end: str):
    tz = session_tz(request)
    first = dt.datetime.fromisoformat(start).date()
    last = dt.datetime.fromisoformat(end).date() - dt.timedelta(days=1)
    params = {"from": first.isoformat(), "to": last.isoformat()}
    works, asw, agw, me = await asyncio.gather(
        api.get("/work", params=params),
        api.get("/absences/asworktime", params=params),
        api.get("/absences/againstworktime", params=params),
        api.get("/auth/me"),
    )

    def iso(ts: int) -> str:
        return dt.datetime.fromtimestamp(ts, tz).isoformat()

    events = []
    for w in works:
        events.append({
            "title": _work_title(w["worked"]),
            "start": iso(w["starttime"]),
            "end": iso(w["endtime"]),
            "color": "#4f46e5",
            "extendedProps": {"kind": "work", "itemId": w["id"], "comment": w["comment"] or ""},
        })
        for b in w["breaks"]:
            if b["endtime"]:
                events.append({
                    "title": "Break",
                    "start": iso(b["starttime"]),
                    "end": iso(b["endtime"]),
                    "color": "#f59e0b",
                    "classNames": ["break"],
                    "extendedProps": {"kind": "break", "itemId": b["id"]},
                })
    for kind, items, color in (("asworktime", asw, "#8b5cf6"), ("againstworktime", agw, "#10b981")):
        for item in items:
            events.append({
                "title": f"{item['typelabel'] or kind} {item['time_hms']}",
                "start": item["date"],
                "allDay": True,
                "color": color,
                "extendedProps": {"kind": kind, "itemId": item["id"]},
            })
    for title, ts in (("Registered", me["registerdate"]), ("Stats begin ->", me["statsstartdate"])):
        day = dt.datetime.fromtimestamp(ts, tz).date()
        if first <= day <= last:
            events.append({"title": title, "start": day.isoformat(), "allDay": True, "color": "#64748b"})
    return events


@router.get("/calendar-data/totals")
async def calendar_totals(request: Request, api: Api, view: str, date: dt.date):
    if view == "month":
        totals = await api.get(f"/stats/month/{date.year}/{date.month}")
        label = date.strftime("%B %Y")
    else:
        year, week, _ = date.isocalendar()
        totals = await api.get(f"/stats/week/{year}/{week}")
        label = f"week {week}, {year}"
    return render(request, "partials/calendar_totals.html", totals=totals, label=label,
                  period="month" if view == "month" else "week")
