"""Maintenance commands.

    python -m app.cli create-apikey --user johan --scopes timer:write,sync:read --name phone
    python -m app.cli recompute-stats --user johan
    python -m app.cli scopes
"""

import argparse
import datetime as dt
import sys

from .db import SessionLocal
from .errors import DomainError
from .security import LEGACY_SCOPES, SCOPES, expand_scopes
from .services import apikeys, stats, timeutil, users


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="udtime")
    sub = parser.add_subparsers(dest="command", required=True)

    key = sub.add_parser("create-apikey", help="create an API key and print it once")
    key.add_argument("--user", required=True)
    key.add_argument("--scopes", required=True, help="comma separated, '*' for all")
    key.add_argument("--name")
    key.add_argument("--expires-days", type=int)

    rec = sub.add_parser("recompute-stats", help="recompute week/month stats since statsstartdate")
    rec.add_argument("--user", required=True)

    sub.add_parser("scopes", help="list scopes and legacy action names")

    args = parser.parse_args(argv)

    if args.command == "scopes":
        print("Scopes:", ", ".join(sorted(SCOPES)))
        for action, mapped in sorted(LEGACY_SCOPES.items()):
            print(f"  legacy {action:24} -> {', '.join(sorted(mapped))}")
        return 0

    with SessionLocal() as db:
        user = users.find_by_username(db, args.user)
        if user is None:
            print(f"No such user: {args.user}", file=sys.stderr)
            return 1

        if args.command == "recompute-stats":
            stats.recompute_all(db, user)
            print("Statistics recalculated")
            return 0

        scopes = expand_scopes(args.scopes)
        if not scopes:
            print("No valid scopes given", file=sys.stderr)
            return 1
        expires = None
        if args.expires_days:
            expires = dt.datetime.now(timeutil.user_tz(user)).date() + dt.timedelta(days=args.expires_days)
        try:
            created = apikeys.create_key(db, user, args.name or "cli", sorted(scopes), expires)
        except DomainError as exc:
            print(exc.message, file=sys.stderr)
            return 1
        print(created["key"])
        print(f"Scopes: {', '.join(sorted(scopes))}", file=sys.stderr)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
