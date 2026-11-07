class DomainError(Exception):
    """A business rule violation, returned to the client as {"detail": message}."""

    status_code = 400

    def __init__(self, message: str, status_code: int | None = None):
        super().__init__(message)
        self.message = message
        if status_code is not None:
            self.status_code = status_code


class NotFound(DomainError):
    status_code = 404


class Conflict(DomainError):
    status_code = 409
