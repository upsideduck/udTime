from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    database_url: str = "mysql+pymysql://udtime:udtime@localhost:3306/udTime"
    jwt_secret: str = "change-me"
    jwt_expire_minutes: int = 60 * 24 * 7
    frontend_service_secret: str = "change-me-too"
    allow_registration: bool = False
    db_has_delete_triggers: bool = False
    default_timezone: str = "Europe/Stockholm"


settings = Settings()
