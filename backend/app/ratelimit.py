"""In-memory limiter for failed login attempts (single process)."""

import threading
import time
from collections import defaultdict, deque

MAX_TRACKED_KEYS = 50_000


class FailureLimiter:
    def __init__(self, max_failures: int, window_seconds: int):
        self.max_failures = max_failures
        self.window = window_seconds
        self._failures: dict[str, deque[float]] = defaultdict(deque)
        self._lock = threading.Lock()

    def _prune(self, key: str, now: float) -> deque[float]:
        attempts = self._failures[key]
        while attempts and attempts[0] <= now - self.window:
            attempts.popleft()
        return attempts

    def retry_after(self, key: str) -> int:
        """Seconds until `key` may try again, 0 when not blocked."""
        now = time.monotonic()
        with self._lock:
            if key not in self._failures:  # don't create entries just by checking
                return 0
            attempts = self._prune(key, now)
            if len(attempts) < self.max_failures:
                return 0
            return int(attempts[0] + self.window - now) + 1

    def fail(self, key: str) -> None:
        now = time.monotonic()
        with self._lock:
            if len(self._failures) >= MAX_TRACKED_KEYS:
                self._sweep(now)
            self._prune(key, now).append(now)

    def _sweep(self, now: float) -> None:
        """Forget expired keys; if still too many (a flood of new usernames), drop the oldest half."""
        for key in [k for k, v in self._failures.items() if not v or v[-1] <= now - self.window]:
            del self._failures[key]
        if len(self._failures) >= MAX_TRACKED_KEYS:
            oldest = sorted(self._failures, key=lambda k: self._failures[k][-1])
            for key in oldest[: len(oldest) // 2]:
                del self._failures[key]

    def reset(self, key: str) -> None:
        with self._lock:
            self._failures.pop(key, None)


# Failed logins per username and per client IP, within 15 minutes
by_username = FailureLimiter(max_failures=10, window_seconds=15 * 60)
by_ip = FailureLimiter(max_failures=30, window_seconds=15 * 60)
