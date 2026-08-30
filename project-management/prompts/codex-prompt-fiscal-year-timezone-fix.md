# Codex Prompt — Fix Fiscal-Year Timezone Bug In YTD Comparison

Date: 2026-08-06

Paste this file as the opening prompt for Codex. Read this entire file and the files listed
under "Read First" before writing any code. A user reported that fiscal-year dates shown to
members look wrong; investigation (by Claude, this session) traced it to a specific timezone
bug in two files, confirmed by direct code reading, not assumed.

---

## Workspace

`C:\Users\isen\OneDrive\Documents\Code Files\inc-stats-tracker`

## Read First, In Order

1. `AGENTS.md`, `CLAUDE.md`
2. `plugin/inc-stats-tracker/includes/class-ist-fiscal-year.php` — the correct reference
   implementation. Its date math is already validated as correct; do not change its logic,
   only mirror its `DateTime`-based approach in the fix below.
3. `plugin/inc-stats-tracker/includes/class-ist-stats-query.php`, lines ~140–260 — three
   existing comments in this file already document, in detail, why
   `strtotime()`/`wp_date()` round-trips are unsafe for date-range math under WordPress
   (PHP's default timezone is UTC; `wp_date()` converts back to site-local time, landing on
   the previous calendar day on any US timezone). The fix below must match the pattern this
   file already uses everywhere else.
4. `plugin/inc-stats-tracker/frontend/class-ist-group-extension.php` around line 118.
5. `plugin/inc-stats-tracker/frontend/class-ist-profile-nav.php` around line 216.
6. `plugin/inc-stats-tracker/templates/frontend/partials/tmpl-ytd-comparison.php` — confirms
   `$prior_fy_label` is rendered directly to members (line 85), on both the My Stats and
   Group Stats pages. This is almost certainly what the reporting member saw.

## Current Baseline

- Plugin version `1.0.6`. **Do not bump the version or touch `CHANGELOG.md` /
  `readme.txt`** — there's a pre-existing, separately-tracked version/changelog sync gap in
  this repo; versioning for this fix is the user's decision and is out of scope here.
- No PHPUnit harness or test suite exists in this repo. Verification is `php -l` plus a
  standalone PHP reasoning/script check (see below) — do not invent a test framework.
- No WordPress/WP-CLI/DB connection should be assumed available for this repo in your
  session unless you already have one explicitly configured. State plainly if you could not
  do a live check.

## Confirmed Bug

`frontend/class-ist-group-extension.php:118` and `frontend/class-ist-profile-nav.php:216`
both contain this identical line:

```php
$prior_equiv_end = wp_date( 'Y-m-d', strtotime( '-1 year', strtotime( $today ) ) );
```

This is the exact anti-pattern `class-ist-stats-query.php` documents as broken elsewhere in
the same file: `strtotime( $today )` parses the `Y-m-d` string as UTC midnight (WordPress
sets PHP's default timezone to UTC); `wp_date()` then converts that UTC timestamp back to
the site's local timezone, landing on the **previous calendar day** for any timezone west of
UTC (all US timezones). So `$prior_equiv_end` silently resolves one day earlier than
intended.

Most days this only shifts the YTD comparison query window by a day (quietly wrong totals).
But on the fiscal year's first day (July 1 by default — see
`IST_Fiscal_Year::DEFAULT_START_MONTH`), the one-day shift pushes the reference date across
the fiscal-year boundary, so `IST_Fiscal_Year::get_fy_start()` / `get_label()` return an
**entirely wrong prior fiscal year** — e.g. labeling the comparison period "FY 2023–24"
instead of "FY 2024–25". That label renders directly to members via `$prior_fy_label` in
`tmpl-ytd-comparison.php:85`.

Root cause of the duplication: `includes/class-ist-stats-query.php` around line 286 has a
docblock "Typical usage" example that itself shows this exact broken line, so both call
sites copied it verbatim from the class's own documentation.

## The Fix

1. In `frontend/class-ist-group-extension.php:118` and
   `frontend/class-ist-profile-nav.php:216` (identical line in both files), replace:

   ```php
   $prior_equiv_end = wp_date( 'Y-m-d', strtotime( '-1 year', strtotime( $today ) ) );
   ```

   with `DateTime`-based math that avoids the UTC round-trip entirely, e.g.:

   ```php
   $prior_equiv_end = ( new DateTime( $today ) )->modify( '-1 year' )->format( 'Y-m-d' );
   ```

   This mirrors how `IST_Fiscal_Year` and the rest of `class-ist-stats-query.php` already do
   date math — no `strtotime()`/`wp_date()` round trip anywhere in the calculation.

2. Update the docblock "Typical usage" example in
   `includes/class-ist-stats-query.php` (around line 286) to show the corrected
   `DateTime`-based line instead of the broken one, so it stops teaching the bug forward to
   any future caller.

3. Run `grep -rn "strtotime" plugin/inc-stats-tracker/` and check every hit: does it do
   date-*range* math (computing a boundary used in a query or FY calculation), or does it
   just format an already-known, already-correct date for display? Only the former is this
   bug. If you find another occurrence of the range-math pattern beyond the two identified
   above, apply the same fix. If you find none, say so explicitly in the handoff — don't
   silently skip this step.

## Verification

1. `php -l` on every changed file.
2. There's no PHPUnit harness here, so verify the corrected date math directly: write a
   small standalone PHP script (no WordPress bootstrap required — just `DateTime`) that
   reproduces the fixed line for these cases and prints the result:
   - `$today` one day after the fiscal-year start-month boundary (e.g. `2026-07-02` with
     start month 7)
   - `$today` exactly on the fiscal-year start-month's 1st (e.g. `2026-07-01`) — this is the
     case that previously broke
   - a leap-year Feb 29 boundary (e.g. `$today = 2028-02-29`, confirm the prior-year result
     lands on `2027-02-28`, not `2027-03-01` or similar)

   Confirm `$prior_equiv_end` is exactly `$today` minus one calendar year in every case,
   with no off-by-one — and that this holds regardless of PHP's default timezone (run the
   script with `date_default_timezone_set('UTC')` set explicitly, matching the WordPress
   runtime environment, to prove the old bug is actually gone, not just untested).
3. Do not connect to or mutate any live/staging WordPress site as part of this verification
   unless you already have an explicitly approved, pre-configured connection for this repo.
   If you can't do a live check, say so plainly — static/script verification is sufficient
   for this fix.

## Hard Safety Boundaries — Non-Negotiable

1. Do not bump `IST_VERSION`, and do not touch `CHANGELOG.md` or `readme.txt` — out of
   scope, tracked separately.
2. Do not modify any file outside the ones identified in "Read First" / "The Fix" without
   explaining why in the handoff first.
3. Do not attempt a release build or run `tools/package-plugin.bat` / `.ps1`.
4. No production or staging WordPress mutation of any kind.
5. Do not change `IST_Fiscal_Year`'s own logic — it's already validated correct; this fix is
   scoped to the two call sites and the misleading docblock only.

## Handoff (required, last step)

Report clearly:

- Every file changed, with a one-line summary of the diff per file.
- Confirmation of the `grep -rn "strtotime"` sweep result: any additional occurrences found
  and fixed, or explicit confirmation none were found.
- The verification method actually used (script output for all three test cases) — paste
  the actual output, not a description of what it should show.
- Explicit confirmation `IST_VERSION`, `CHANGELOG.md`, and `readme.txt` were left untouched.

Commit as its own commit. Do not push.
