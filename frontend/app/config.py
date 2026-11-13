from pydantic import model_validator
from pydantic_settings import BaseSettings, SettingsConfigDict

# Every setting is read from UDTIME2_<NAME>, e.g. UDTIME2_JWT_SECRET for jwt_secret
ENV_PREFIX = "UDTIME2_"

INSECURE_SECRETS = {"", "change-me", "change-me-too", "secret", "x"}
MIN_SECRET_LENGTH = 16


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_prefix=ENV_PREFIX, env_file=".env", extra="ignore")

    backend_url: str = "http://localhost:8000"
    session_secret: str = ""
    session_https_only: bool = False
    session_max_age: int = 12 * 3600
    frontend_service_secret: str = ""
    default_timezone: str = "Europe/Stockholm"

    # Single sign-on with OpenID Connect (e.g. Authentik). Needs issuer and client id when enabled.
    oidc_enabled: bool = False
    oidc_issuer: str = ""  # e.g. https://auth.example.com/application/o/udtime/
    oidc_client_id: str = ""
    oidc_client_secret: str = ""
    oidc_redirect_uri: str = ""  # default: <this site>/sso/callback
    oidc_scopes: str = "openid profile email"
    oidc_username_claim: str = "preferred_username"
    sso_label: str = "udauth"
    build_date: str = ""  # ISO 8601, set by the Docker build (BUILD_DATE build arg)

    @model_validator(mode="after")
    def _require_real_secrets(self):
        for name in ("session_secret", "frontend_service_secret"):
            value = getattr(self, name)
            if value in INSECURE_SECRETS or len(value) < MIN_SECRET_LENGTH:
                raise ValueError(
                    f"{ENV_PREFIX}{name.upper()} must be set to a random value of at least {MIN_SECRET_LENGTH} characters "
                    '(e.g. python3 -c "import secrets; print(secrets.token_urlsafe(48))")'
                )
        if self.oidc_enabled and not (self.oidc_issuer and self.oidc_client_id):
            raise ValueError(f"{ENV_PREFIX}OIDC_ENABLED=true needs {ENV_PREFIX}OIDC_ISSUER and {ENV_PREFIX}OIDC_CLIENT_ID")
        if not self.frontend_service_secret.isascii():
            # It is sent as an HTTP header, which only allows ASCII
            raise ValueError(f"{ENV_PREFIX}FRONTEND_SERVICE_SECRET may only contain ASCII characters")
        return self


settings = Settings()
