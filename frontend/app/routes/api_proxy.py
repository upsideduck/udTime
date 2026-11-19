"""Public API: forwards /api/v1/* to the backend for requests that carry an API key.

Browser sessions, bearer tokens and the frontend's service secret are never passed on, so
this route only gives what the key's scopes allow. Endpoints that refuse API keys anyway
(sign-in, registration, key management, admin) are blocked here.
"""

import httpx
from fastapi import APIRouter, Request, Response
from fastapi.responses import JSONResponse

from ..api import API_PREFIX
from ..config import settings

router = APIRouter()

# Request headers passed to the backend; everything else (cookies, Authorization,
# X-Service-Secret, X-Client-IP, …) is dropped
FORWARD_REQUEST = ("x-api-key", "content-type", "accept")
# Response headers passed back to the client
FORWARD_RESPONSE = ("content-type", "content-disposition", "retry-after", "www-authenticate")

# Not usable with an API key: sign-in, registration, password change, key management, admin
BLOCKED_PATHS = {"auth/login", "auth/trusted", "users", "users/me/password"}
BLOCKED_PREFIXES = ("apikeys", "admin")


def _error(status: int, detail: str) -> JSONResponse:
    return JSONResponse({"detail": detail}, status_code=status, headers={"Cache-Control": "no-store"})


def _blocked(path: str) -> bool:
    return path in BLOCKED_PATHS or any(path == p or path.startswith(p + "/") for p in BLOCKED_PREFIXES)


@router.api_route(API_PREFIX + "/{path:path}", methods=["GET", "POST", "PUT", "PATCH", "DELETE"],
                  include_in_schema=False)
async def forward(path: str, request: Request):
    if not settings.public_api:
        return _error(404, "Not found")
    if any(part in ("", ".", "..") for part in path.split("/")) or _blocked(path):
        return _error(404, "Not found")
    if not request.headers.get("x-api-key"):
        return _error(401, "Send an API key in the X-API-Key header")

    headers = {name: request.headers[name] for name in FORWARD_REQUEST if name in request.headers}
    try:
        response = await request.app.state.http.request(
            request.method,
            f"{API_PREFIX}/{path}",
            params=request.query_params.multi_items(),
            content=await request.body(),
            headers=headers,
        )
    except httpx.TransportError:
        return _error(503, "The udTime server is not reachable right now. Try again shortly.")

    out = {name: response.headers[name] for name in FORWARD_RESPONSE if name in response.headers}
    out["Cache-Control"] = "no-store"
    return Response(response.content, status_code=response.status_code, headers=out)
