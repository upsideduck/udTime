"""OpenID Connect sign-in (Authorization Code flow with PKCE), e.g. against Authentik.

The ID token is verified here (signature, issuer, audience, expiry, nonce); the frontend
then signs the user in through the backend's service-secret protected trusted login.
"""

import base64
import hashlib
import secrets
import time
from urllib.parse import urlencode

import httpx
import jwt

from .config import settings

ALLOWED_ALGORITHMS = ["RS256", "RS384", "RS512", "ES256", "ES384", "ES512", "PS256", "HS256"]
CACHE_SECONDS = 3600


class OIDCError(Exception):
    pass


class OIDCClient:
    def __init__(self, http: httpx.AsyncClient):
        self.http = http
        self._config: dict | None = None
        self._jwks: jwt.PyJWKSet | None = None
        self._fetched = 0.0

    async def _get_json(self, url: str) -> dict:
        try:
            response = await self.http.get(url, timeout=10)
            response.raise_for_status()
            return response.json()
        except (httpx.HTTPError, ValueError) as exc:
            raise OIDCError(f"Could not reach the identity provider ({exc.__class__.__name__})") from exc

    async def config(self) -> dict:
        if self._config is None or time.monotonic() - self._fetched > CACHE_SECONDS:
            url = settings.oidc_issuer.rstrip("/") + "/.well-known/openid-configuration"
            self._config = await self._get_json(url)
            self._jwks = None
            self._fetched = time.monotonic()
        return self._config

    async def _signing_key(self, kid: str | None):
        for refresh in (False, True):
            if self._jwks is None or refresh:
                self._jwks = jwt.PyJWKSet.from_dict(await self._get_json((await self.config())["jwks_uri"]))
            for key in self._jwks.keys:
                if kid is None or key.key_id == kid:
                    return key.key
        raise OIDCError("Unknown signing key")

    @staticmethod
    def new_login() -> dict:
        """State, nonce and PKCE verifier to keep in the session during sign-in."""
        return {
            "state": secrets.token_urlsafe(32),
            "nonce": secrets.token_urlsafe(32),
            "verifier": secrets.token_urlsafe(64),
            "started": int(time.time()),
        }

    async def authorization_url(self, login: dict, redirect_uri: str) -> str:
        challenge = base64.urlsafe_b64encode(hashlib.sha256(login["verifier"].encode()).digest()).rstrip(b"=")
        params = {
            "response_type": "code",
            "client_id": settings.oidc_client_id,
            "redirect_uri": redirect_uri,
            "scope": settings.oidc_scopes,
            "state": login["state"],
            "nonce": login["nonce"],
            "code_challenge": challenge.decode(),
            "code_challenge_method": "S256",
        }
        return (await self.config())["authorization_endpoint"] + "?" + urlencode(params)

    async def exchange_code(self, code: str, redirect_uri: str, verifier: str) -> dict:
        data = {
            "grant_type": "authorization_code",
            "code": code,
            "redirect_uri": redirect_uri,
            "code_verifier": verifier,
            "client_id": settings.oidc_client_id,
        }
        auth = (settings.oidc_client_id, settings.oidc_client_secret) if settings.oidc_client_secret else None
        try:
            response = await self.http.post((await self.config())["token_endpoint"], data=data, auth=auth, timeout=10)
        except httpx.HTTPError as exc:
            raise OIDCError("Could not reach the identity provider") from exc
        if response.status_code != 200:
            raise OIDCError(f"Sign-in was rejected by the identity provider ({response.status_code})")
        return response.json()

    async def verify_id_token(self, id_token: str, nonce: str) -> dict:
        try:
            header = jwt.get_unverified_header(id_token)
            algorithm = header.get("alg")
            if algorithm not in ALLOWED_ALGORITHMS:
                raise OIDCError(f"Unsupported token algorithm {algorithm}")
            if algorithm == "HS256":
                if not settings.oidc_client_secret:
                    raise OIDCError("HS256 tokens need UDTIME2_OIDC_CLIENT_SECRET")
                key = settings.oidc_client_secret
            else:
                key = await self._signing_key(header.get("kid"))
            claims = jwt.decode(
                id_token,
                key,
                algorithms=[algorithm],
                audience=settings.oidc_client_id,
                issuer=(await self.config())["issuer"],
                options={"require": ["exp", "iat", "iss", "aud", "sub"]},
                leeway=60,
            )
        except jwt.PyJWTError as exc:
            raise OIDCError(f"Invalid sign-in token ({exc})") from exc
        if claims.get("nonce") != nonce:
            raise OIDCError("Invalid sign-in token (nonce mismatch)")
        return claims

    async def logout_url(self, id_token: str | None, post_logout_redirect_uri: str) -> str | None:
        try:
            endpoint = (await self.config()).get("end_session_endpoint")
        except OIDCError:
            return None
        if not endpoint:
            return None
        params = {"post_logout_redirect_uri": post_logout_redirect_uri, "client_id": settings.oidc_client_id}
        if id_token:
            params["id_token_hint"] = id_token
        return endpoint + "?" + urlencode(params)
