from pydantic import model_validator
from pydantic_settings import BaseSettings, SettingsConfigDict

INSECURE_SECRETS = {"", "change-me", "change-me-too", "secret"}
MIN_SECRET_LENGTH = 16


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    database_url: str = "mysql+pymysql://udtime:udtime@localhost:3306/udTime"
    jwt_secret: str = ""
    jwt_expire_minutes: int = 12 * 60
    frontend_service_secret: str = ""
    allow_registration: bool = False
    db_has_delete_triggers: bool = False
    default_timezone: str = "Europe/Stockholm"
    # Interactive API docs at /docs; disable in production unless needed
    api_docs: bool = True

    @model_validator(mode="after")
    def _require_real_secrets(self):
        for name in ("jwt_secret", "frontend_service_secret"):
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
