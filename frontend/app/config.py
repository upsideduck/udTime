from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    backend_url: str = "http://localhost:8000"
    session_secret: str = "change-me"
    session_https_only: bool = False
    frontend_service_secret: str = "change-me-too"
    trust_authentik_header: bool = False
    authentik_header: str = "X-Authentik-Username"
    authentik_logout_url: str = "/outpost.goauthentik.io/sign_out"
    sso_label: str = "udauth"
    default_timezone: str = "Europe/Stockholm"


settings = Settings()
