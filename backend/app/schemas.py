import datetime as dt

from pydantic import BaseModel, Field

# Timestamps accept a unix timestamp, "HH:MM[:SS]" (today, user timezone) or an ISO
# datetime (naive values are interpreted in the user's timezone).
Timestamp = int | str


class LoginIn(BaseModel):
    username: str
    password: str


class TrustedLoginIn(BaseModel):
    username: str


class TokenOut(BaseModel):
    access_token: str
    token_type: str = "bearer"
    user: dict


class RegisterIn(BaseModel):
    username: str = Field(min_length=1, max_length=128)
    password: str = Field(min_length=4)
    workweek_hours: float = Field(gt=0, le=168, description="Hours to work per week")
    daysinworkweek: int = Field(5, ge=1, le=7)
    timezone: str | None = None
    firstname: str | None = None
    lastname: str | None = None


class TimerActionIn(BaseModel):
    timestamp: Timestamp | None = Field(None, description="Empty means now")
    comment: str | None = Field(None, description='"NOCHANGE" or null keeps the current comment')


class StartWorkIn(TimerActionIn):
    project_id: int | None = None


class ProjectAssignIn(BaseModel):
    project_id: int | None = None


class WorkIn(BaseModel):
    starttime: Timestamp
    endtime: Timestamp
    comment: str | None = None
    project_id: int | None = None


class PeriodPatch(BaseModel):
    starttime: Timestamp | None = None
    endtime: Timestamp | None = None
    comment: str | None = None


class BreakIn(BaseModel):
    starttime: Timestamp
    endtime: Timestamp
    comment: str | None = None


class AbsenceIn(BaseModel):
    start_date: dt.date
    end_date: dt.date | None = Field(None, description="Inclusive; defaults to start_date")
    type: int = 1
    time: int | str | None = Field(None, description='Seconds or "HH:MM[:SS]" per day; empty = standard day')
    comment: str | None = None


class AbsencePatch(BaseModel):
    time: int | str | None = None
    type: int | None = None
    comment: str | None = None


class ProjectIn(BaseModel):
    name: str = Field(min_length=1)
    description: str | None = None
    color: str | None = None


class Message(BaseModel):
    message: str
