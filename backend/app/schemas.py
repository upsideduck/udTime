import datetime as dt

from typing import Annotated, Literal

from pydantic import BaseModel, Field, model_validator

MAX_COMMENT = 1000

# Timestamps accept a unix timestamp, "HH:MM[:SS]" (today, user timezone) or an ISO
# datetime (naive values are interpreted in the user's timezone).
Timestamp = int | str


class LoginIn(BaseModel):
    username: str = Field(max_length=128)
    password: str = Field(max_length=1024)


class TrustedLoginIn(BaseModel):
    username: str = Field(max_length=128)


class TokenOut(BaseModel):
    access_token: str
    token_type: str = "bearer"
    user: dict


class RegisterIn(BaseModel):
    username: str = Field(min_length=1, max_length=128)
    password: str = Field(min_length=8, max_length=1024)
    workweek_hours: float = Field(gt=0, le=168, description="Hours to work per week")
    daysinworkweek: int = Field(5, ge=1, le=7)
    timezone: str | None = None
    firstname: str | None = None
    lastname: str | None = None


class ProfilePatch(BaseModel):
    """The work week is changed through /schedules, as periods."""

    offset: int | None = Field(None, ge=-9999 * 3600, le=9999 * 3600, description="Starting balance in seconds")
    timezone: str | None = Field(None, max_length=64, description="IANA name, e.g. Europe/Stockholm")


Weekday = Annotated[int, Field(ge=1, le=7)]


class SchedulePatch(BaseModel):
    valid_from: dt.date | None = Field(None, description="First day the schedule applies (user's local date)")
    dworkweek: int | None = Field(None, gt=0, le=168 * 3600, description="Seconds to work per week")
    workdays: list[Weekday] | None = Field(
        None, min_length=1, max_length=7, description="ISO weekdays worked, 1 = Monday … 7 = Sunday"
    )
    daysinworkweek: int | None = Field(None, ge=1, le=7, description="Without workdays: the first N weekdays")

    @model_validator(mode="after")
    def _days_match(self):
        days = self.workdays
        if days is not None and self.daysinworkweek is not None and len(set(days)) != self.daysinworkweek:
            raise ValueError("daysinworkweek must equal the number of workdays")
        return self

    def resolved_workdays(self) -> tuple[int, ...] | None:
        if self.workdays is not None:
            return tuple(self.workdays)
        if self.daysinworkweek is not None:
            return tuple(range(1, self.daysinworkweek + 1))
        return None


class ScheduleIn(SchedulePatch):
    valid_from: dt.date = Field(description="First day the schedule applies (user's local date)")
    dworkweek: int = Field(gt=0, le=168 * 3600, description="Seconds to work per week")

    @model_validator(mode="after")
    def _days_given(self):
        if self.workdays is None and self.daysinworkweek is None:
            raise ValueError("Give workdays or daysinworkweek")
        return self


class SchedulePreviewIn(SchedulePatch):
    op: Literal["create", "update", "delete"]
    id: int | None = None


class PasswordChangeIn(BaseModel):
    current_password: str = Field(min_length=1, max_length=1024)
    new_password: str = Field(min_length=8, max_length=1024)


class TimerActionIn(BaseModel):
    timestamp: Timestamp | None = Field(None, description="Empty means now")
    comment: str | None = Field(None, max_length=MAX_COMMENT, description='"NOCHANGE" or null keeps the current comment')


class StartWorkIn(TimerActionIn):
    project_id: int | None = None


class ProjectAssignIn(BaseModel):
    project_id: int | None = None


class WorkIn(BaseModel):
    starttime: Timestamp
    endtime: Timestamp
    comment: str | None = Field(None, max_length=MAX_COMMENT)
    project_id: int | None = None


class PeriodPatch(BaseModel):
    starttime: Timestamp | None = None
    endtime: Timestamp | None = None
    comment: str | None = Field(None, max_length=MAX_COMMENT)


class BreakIn(BaseModel):
    starttime: Timestamp
    endtime: Timestamp
    comment: str | None = Field(None, max_length=MAX_COMMENT)


class AbsenceIn(BaseModel):
    start_date: dt.date
    end_date: dt.date | None = Field(None, description="Inclusive; defaults to start_date")
    type: int = 1
    time: int | str | None = Field(None, description='Seconds or "HH:MM[:SS]" per day; empty = standard day')
    comment: str | None = Field(None, max_length=MAX_COMMENT)


class AbsencePatch(BaseModel):
    time: int | str | None = None
    type: int | None = None
    comment: str | None = Field(None, max_length=MAX_COMMENT)


class ProjectIn(BaseModel):
    name: str = Field(min_length=1, max_length=100)
    description: str | None = Field(None, max_length=1000)
    color: str | None = Field(None, pattern=r"^#[0-9a-fA-F]{6}$", description="Hex colour, e.g. #4f46e5")


class ApiKeyIn(BaseModel):
    name: str = Field(min_length=1, max_length=100)
    scopes: list[str] = Field(min_length=1, max_length=20, description="e.g. timer:write, stats:read")
    expires_on: dt.date | None = Field(None, description="Last valid day, in the user's timezone; empty = never")


class ApiKeyPatch(BaseModel):
    name: str | None = Field(None, min_length=1, max_length=100)
    scopes: list[str] | None = Field(None, min_length=1, max_length=20)
    expires_on: dt.date | None = Field(None, description="Send null to remove the expiry")
    is_active: bool | None = None


class Message(BaseModel):
    message: str
