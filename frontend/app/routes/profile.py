from typing import Annotated

from fastapi import APIRouter, File, Request, UploadFile
from fastapi.responses import Response

from ..api import BackendError
from ..web import Api, htmx_response, render, toast

router = APIRouter()


@router.get("/profile")
async def profile(request: Request, api: Api):
    return render(request, "profile.html", profile=await api.get("/auth/me"))


@router.get("/avatar")
async def avatar(api: Api):
    try:
        response = await api.get("/users/me/avatar", raw=True)
    except BackendError:
        return Response(status_code=404)
    return Response(response.content, media_type=response.headers.get("content-type", "image/jpeg"),
                    headers={"Cache-Control": "no-cache"})


@router.post("/profile/avatar")
async def upload_avatar(request: Request, api: Api, file: Annotated[UploadFile, File()]):
    data = await file.read()
    try:
        await api.put("/users/me/avatar", files={"file": (file.filename or "avatar", data, file.content_type)})
    except BackendError as exc:
        return htmx_response(toast(request, exc.detail, "danger"), reswap="none")
    return htmx_response(toast(request, "Avatar updated"), trigger={"avatarChanged": True}, reswap="none")


@router.post("/admin/recompute")
async def recompute(request: Request, api: Api):
    try:
        result = await api.post("/admin/recompute-stats")
        return htmx_response(toast(request, result["message"]), reswap="none")
    except BackendError as exc:
        return htmx_response(toast(request, exc.detail, "danger"), reswap="none")
