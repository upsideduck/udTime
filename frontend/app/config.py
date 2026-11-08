from pydantic import model_validator
from pydantic_settings import BaseSettings, SettingsConfigDict

INSECURE_SECRETS = {"", "change-me", "change-me-too", "secret", "x"}
MIN_SECRET_LENGTH = 16


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    backend_url: str = "http://localhost:8000"
    session_secret: str = ""
    session_https_only: bool = False
    session_max_age: int = 12 * 3600
    frontend_service_secret: str = ""
    default_timezone: str = "Europe/Stockholm"

    # Single sign-on with OpenID Connect (e.g. Authentik). Enabled when issuer and client id are set.
    oidc_issuer: str = ""  # e.g. https://auth.example.com/application/o/udtime/
    oidc_client_id: str = ""
    oidc_client_secret: str = ""
    oidc_redirect_uri: str = ""  # default: <this site>/sso/callback
    oidc_scopes: str = "openid profile email"
    oidc_username_claim: str = "preferred_username"
    sso_label: str = "udauth"

    @property
    def oidc_enabled(self) -> bool:
        return bool(self.oidc_issuer and self.oidc_client_id)

    @model_validator(mode="after")
    def _require_real_secrets(self):
        for name in ("session_secret", "frontend_service_secret"):
            value = getattr(self, name)
            if value in INSECURE_SECRETS or len(value) < MIN_SECRET_LENGTH:
                raise ValueError(
                    f"{name.upper()} must be set to a random value of at least {MIN_SECRET_LENGTH} characters "
                    '(e.g. python3 -c "import secrets; print(secrets.token_urlsafe(48))")'
                )
        if not self.frontend_service_secret.isascii():
            # It is sent as an HTTP header, which only allows ASCII
            raise ValueError("FRONTEND_SERVICE_SECRET may only contain ASCII characters")
        return self


settings = Settings()
