# Task Set — 1.0.9 Group Stats KPI Layout

Date: 2026-08-29
Status: Approved by the user in the current session

## Objective

Reduce the Group Stats KPI area from four cards to three, make the two leaderboard cards the first
desktop row, and combine Closed Business amount and submission-count context without losing any
fiscal-year, month-to-date, prior-year, or leaderboard information.

## Approved Scope

1. Render Referrals Given first and Closed Business second in the desktop two-column KPI grid.
2. Rename `Closed Business (Amount)` to `Closed Business`.
3. Add the existing fiscal-year and month-to-date Closed Business counts as secondary context within
   the corresponding amount rows; retain the prior-year amount row and MTD member leaderboard.
4. Remove the standalone `Closed Business (Count)` card.
5. Render Connects Logged by itself on the next row, spanning both desktop columns with a compact
   FY/MTD value arrangement.
6. Preserve DOM and mobile reading order as Referrals, Closed Business, Connects; keep narrow layouts
   single-column and allow long labels/count text to wrap.
7. Use the existing design system and shared KPI partial. Add no new framework or dependency.
8. Promote the focused UI change to `1.0.9`, align release metadata/changelog, extend deterministic
   tests, build one reproducible plugin-only ZIP, and run the reviewer-only fix/rereview loop.

## Acceptance Criteria

- Exactly three KPI cards render in the Group Stats KPI grid.
- Desktop order is Referrals Given, Closed Business, then full-row Connects Logged.
- Mobile order is Referrals Given, Closed Business, Connects Logged.
- Closed Business displays FY and MTD dollar amounts with their corresponding submission counts.
- Prior-year Closed Business amount and both MTD leaderboards remain present.
- Connects retains its FY and MTD counts and spans both columns only on non-mobile layouts.
- Existing output escaping, semantic ordered lists, accessible leaderboard naming, and empty states
  remain intact.
- No query, database, capability, submission, REST, import, or schema behavior changes.
- PHP lint, fiscal-year tests, leaderboard/render/layout contracts, deployment contracts, ZIP
  validation, and `git diff --check` pass.
- Claude reviewer-only returns PASS after any justified fixes.

## Safety Boundaries

- Local implementation, testing, documentation, and packaging only.
- No Development or Production upload, install, activation, update, cache action, data query, or
  other remote access/mutation under this approval.
- Any Development deployment requires a separately reviewed exact artifact, checksum, rollback path,
  deployment ID, command, and explicit current-session approval. Production remains a later gate.
- No commit, push, tag, history rewrite, historical CSV action, or unrelated working-tree cleanup.

## Verification

```powershell
powershell -NoProfile -File tools\verify-project.ps1
tools\package-plugin.bat
powershell -NoProfile -File tools\verify-project.ps1 `
  -ZipPath build\releases\inc-stats-tracker-1.0.9.zip
git diff --check
powershell -NoProfile -File scripts\agent-workflow\Invoke-ClaudeReview.ps1 `
  -TaskFile "notes/task-set-1.0.9-kpi-layout-2026-08-29.md" `
  -ResultsFile "notes/task-set-1.0.9-kpi-layout-2026-08-29-results.md"
```
