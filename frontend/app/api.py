"""Thin async client for the udTime backend API."""

from typing import Any

import httpx

from .config import settings

API_PREFIX = "/api/v1"


class NotLoggedIn(Exception):
    pass


class BackendError(Exception):
    def __init__(self, status_code: int, detail: str):
        super().__init__(detail)
        self.status_code = status_code
        self.detail = detail


def _detail(response: httpx.Response) -> str:
    try:
        detail = response.json().get("detail")
    except ValueError:
        return response.text or response.reason_phrase
    if isinstance(detail, list):  # FastAPI validation errors
        return "; ".join(f"{'.'.join(map(str, e.get('loc', [])[1:]))}: {e.get('msg')}" for e in detail)
    return str(detail)


class Backend:
    def __init__(self, client: httpx.AsyncClient, token: str | None = None):
        self.client = client
        self.token = token

    async def request(self, method: str, path: str, *, raw: bool = False, **kwargs) -> Any:
        headers = kwargs.pop("headers", {})
        if self.token:
            headers["Authorization"] = f"Bearer {self.token}"
        response = await self.client.request(method, API_PREFIX + path, headers=headers, **kwargs)
        if response.status_code == 401 and self.token:
            raise NotLoggedIn()
        if response.status_code >= 400:
            raise BackendError(response.status_code, _detail(response))
        if raw:
            return response
        if response.status_code == 204 or not response.content:
            return None
        return response.json()

    async def get(self, path: str, **kwargs) -> Any:
        return await self.request("GET", path, **kwargs)

    async def post(self, path: str, json: Any = None, **kwargs) -> Any:
        return await self.request("POST", path, json=json, **kwargs)

    async def patch(self, path: str, json: Any = None, **kwargs) -> Any:
        return await self.request("PATCH", path, json=json, **kwargs)

    async def put(self, path: str, json: Any = None, **kwargs) -> Any:
        return await self.request("PUT", path, json=json, **kwargs)

    async def delete(self, path: str, **kwargs) -> Any:
        return await self.request("DELETE", path, **kwargs)

    async def trusted_login(self, username: str) -> dict:
        return await self.post(
            "/auth/trusted",
            {"username": username},
            headers={"X-Service-Secret": settings.frontend_service_secret},
        )


def make_client() -> httpx.AsyncClient:
    return httpx.AsyncClient(base_url=settings.backend_url, timeout=30)
