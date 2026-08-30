# Current State — INC Stats Tracker

Last updated: 2026-08-29

## Stage

Development and Production run active `1.0.9`. The deployed release consolidates the
Group Stats KPI area into Referrals Given, Closed Business, and Connects Logged cards. Deterministic,
responsive browser, reproducible packaging, and reviewer-only checks pass.
The exact approved Development and Production updates and bounded smoke checks pass with rollback evidence.

## What Works

- Core member submissions, admin operations, reporting, import/purge, and read-only REST summary.
- Group totals include historical records while leaderboards rank the current BuddyBoss roster.
- Production `1.0.9` is active after the exact approved plugin-only deployment; its rollback archive and bounded smoke markers pass.
- `1.0.8` adds a guarded top-ten Referrals Given MTD leaderboard without changing the FY leaderboard.
- Shared KPI output supports amount-plus-count and count-only leaderboards with explicit empty states.
- Separate ignored SSH configuration and rollback paths exist for Development and Production.
- Production real-data QA confirmed a non-empty Referrals Given MTD query without printing records.
- Local `1.0.9` places Referrals and Closed Business together, combines Closed Business FY/MTD amounts
  and submission counts, and moves Connects to a compact full-width row without changing data queries.
- Development `1.0.9` reports the deployed layout contract, plugin bootstrap, BuddyBoss, and REST route ready.

## Current Evidence

- Both targets: exact canonical match and active `1.0.9`, with approved rollback archives present.
- Local verifier: 56 PHP files, four date cases/three source contracts, 13 leaderboard SQL assertions,
  25 UI/source contracts, nine render contracts, and 43 deployment contracts passed.
- Reproducible 65-entry `1.0.9` ZIP SHA-256:
  `29AE3AF2CD30453640270EB39AF9E8A001E6E48F42379A605368CE250B3E634E`.
- Desktop 1280×900 and mobile-emulated 390×844 populated/empty/long-name fixture inspection passed
  with three-card order, equal first-row heights, compact Connects, and no overflow/console issues.
  True mobile device, 200% zoom,
  assistive technology, and Production-theme interaction remain manual QA.

## Top Risks

1. Cumulative `1.0.6`–`1.0.9` source/control artifacts are uncommitted and untagged.
2. Production `1.0.9` still needs owner visual acceptance in the real theme; Development visual acceptance passed.
3. Seven tracked historical CSVs need an owner-approved privacy/retention decision.
4. An extra Development plugin backup from the first failed `1.0.7` upload awaits deletion approval.
5. Full browser/integration coverage and the repository-listed later compatibility target remain unverified.

## Next

Perform human Production Group Stats visual QA for the three-card layout.
