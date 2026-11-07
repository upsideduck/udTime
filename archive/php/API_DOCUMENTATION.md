# udTime API Guide

## Access Pattern

- **Endpoint:** `app/api/call_api.php`.
- **Authentication:** Every request must include `username` and `password`. `api_login.php` handles authentication, starts the PHP session, and stores the active user in `$_SESSION['SESS_MEMBER_ID']`. No other action runs if the login fails (`app/api/call_api.php:21-26`, `app/api/api_login.php:21-34`).
- **Actions:** Pass a comma-separated list in the `action` parameter. The dispatcher executes them in order, requiring files such as `api_newperiod.php` or `api_fetchperiods.php`.
- **Output:** Choose `output=json` (recommended) or omit it for XML. The response object always contains `results` (per-action success flag and messages) and may include `arrays`, `periods`, `stats`, `againstworktime`, and `asworktimes` depending on the scripts that ran (`app/classes/output.class.php:1-90`).

Example request:

```bash
curl -X POST 'https://<host>/app/api/call_api.php' \
  -d 'username=alice&password=secret&action=currentperiod,serverupdates&output=json&modafter=1683686400'
```

## Live Tracking Actions

| Action | Purpose | Required Params | Notes |
| --- | --- | --- | --- |
| `newperiod` / `startwork` | Start a work period | `type` (`work`), optional `comment`, optional `timestamp` (UNIX epoch or `HH:MM`) | On success, `currentperiod2` automatically refreshes to expose latest state (`app/api/api_newperiod.php`). |
| `startbreak` | Begin a break | `type=break`, optional `comment`, optional `timestamp` | Validates that there is an active work period and inserts a `breakdb` row (`app/api/api_ongoingperiod.php`). |
| `endbreak` | End a break or end work (if `type=work`) | `type` (`break` or `work`), optional `comment`, optional `timestamp` | Closing work during a break automatically ends the break first (`app/api/api_endbreak.php`). |
| `ongoingperiod` | Toggle current period state | `type` (`work` to end work, `break` to start one), optional `comment`, optional `timestamp` | Internally calls `endWork` or `goOnBreak`. |
| `currentperiod` | Basic snapshot of the active period | *(none)* | Returns the active work/break plus total break time to date (`app/api/api_currentperiod.php`). |
| `currentperiod2` | Detailed view of the active period | *(none)* | Adds `arrays.info` (member, active type/ID, last modification) and `arrays.current` (latest work/break entries) for polling clients (`app/api/api_currentperiod2.php`). |

## Manual Period Editing

| Action | Purpose | Required Params | Notes |
| --- | --- | --- | --- |
| `setwork` | Create a historical work period | `start_time`, `end_time`, optional `comment` | Accepts UNIX timestamps or parseable date strings; response includes `arrays.work` with the inserted row (`app/api/api_setwork.php`). |
| `setbreak` | Create a break | `start_time`, `end_time`, optional `comment`, optional `pid` (parent work ID) | Must fall within an existing work period; response includes `arrays.break` (`app/api/api_setbreak.php`). |
| `updatework` / `updatebreak` | Modify existing periods | `id`, `start_time`, `end_time` | Validates boundaries using `fetchWorkPeriod`/`fetchBreakPeriod` before updating (`app/api/api_updatework.php`, `app/api/api_updatebreak.php`). |
| `removework` / `removebreak` | Delete a period | `id` | Removing work also deletes child breaks and recalculates stats (`app/api/api_removework.php`, `app/func/func_remove.php`). |

## Day Adjustments

| Action | Purpose | Required Params | Notes |
| --- | --- | --- | --- |
| `setasworktime` | Mark days as paid time (vacation, sick days, etc.) | `starttime`, `endtime`, `type`, optional `time` (`HH:MM:SS` or seconds) | Default time equals the user’s standard workday; inserted rows are echoed under `arrays.asworktime` (`app/api/api_setasworktime.php`, `app/classes/timespan.class.php:90-175`). |
| `setagainstworktime` | Mark days as days off | Same as above | Days already marked or falling on weekends are skipped with explanatory messages. |
| `updateasworktime` / `updateagainstworktime` | Change existing adjustments | `id`, `time`, optional `type` | Direct wrappers around edit helpers (`app/api/api_updateasworktime.php`, `app/api/api_updateagainstworktime.php`). |
| `removeasworktime` / `removeagainstworktime` | Delete adjustments | `itemid` | Stats are recalculated after deletion (`app/api/api_removeasworktime.php`, `app/func/func_remove.php`). |

## Projects and Sync

| Action | Purpose | Required Params | Notes |
| --- | --- | --- | --- |
| `newproject` | Create a project | `pname` | Returns the new project ID and name (`app/api/api_newproject.php`). |
| `attachproject` | Assign a project to a period | `project_id`, optional `period_id`, `action` (`add`, `update`, `newperiod`) | `newperiod` closes the current work session, starts a new period, then attaches the project (`app/api/api_attachproject.php`). |
| `serverupdates` | Pull changes since a timestamp | `modafter` (UNIX timestamp) | Streams modified work, breaks, asworktime, againstworktime, and deleted IDs for offline sync (`app/api/api_serverupdates.php`). |

## Reporting

| Action | Purpose | Required Params | Notes |
| --- | --- | --- | --- |
| `fetchperiods` | Retrieve work/break history | `y` plus either `w` (ISO week) or `m` (month); alternatively `wid`, `bid`, `fid`, or `vid` for specific records | Response includes `periods`, `againstworktime`, `asworktimes`, and `arrays.userinfo` (`app/api/api_fetchperiods.php`). |
| `weekdetail` | Weekly breakdown view | Same as `fetchperiods` | Converts the first `periods` entry into `arrays.weekview` and `arrays.weekinfo` (`app/api/api_week_day.php`). |
| `statistics` | Aggregate stats for time spans | `stattype` (comma-separated list such as `today`, `thisweek`, `month`, `weekbalance`), plus `statyear`, `statmonth`, `statweek`, `statyearforweekofyear` when needed | Results stored in `arrays.stats` (`app/api/api_statisitcs.php`). |
| `weektotals` / `monthtotals` | Rolling balances | *(none)* | Returns arrays from `balanceUptoWeek`/`balanceUptoMonth` for the current week/month (`app/api/api_week_totals.php`, `app/api/api_month_totals.php`). |
| `fullstatsupdate` | Force recalculation of all stats | *(none)* | Invokes `timespan::updateStats` over the entire membership lifespan (`app/api/api_full_stats_update.php`). Note: not currently exposed via `call_api.php`. |

## Tips

1. Chain actions when you want dependent updates, e.g., `action=newperiod,currentperiod2`.
2. Use the `lastmodified` field returned by `currentperiod2` as the next `serverupdates` `modafter` cursor.
3. Prefer JSON responses for easier parsing, but the XML format mirrors the same structures if needed.
