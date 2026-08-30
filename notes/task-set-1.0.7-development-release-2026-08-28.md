# Task Set — 1.0.7 Development Release Preparation

Date: 2026-08-28
Status: Approved by the user in the current session

## Objective

Resolve the Production canonical URL classification read-only, promote the locally implemented
monthly Closed Business leaderboard to release `1.0.7`, build and verify one immutable Development
artifact, and stop for approval of the exact Development deployment command.

## Approved Scope

1. Classify the Production URL mismatch as `www`, scheme-only, both, or other without printing it.
2. Move the implemented leaderboard from Planned into a dated `1.0.7` changelog entry and align all
   version/package metadata. No schema change is expected.
3. Make the dry-run/live handoff preserve an exact reviewed artifact and rollback archive location.
4. Build one plugin-only ZIP and record its version, SHA-256, Development remote version/status,
   rollback location, and exact live command.
5. Run deterministic verification, package/secret checks, and Claude reviewer-only review.
6. Stop for fresh approval of the exact Development artifact and command before any upload or mutation.
7. After a separately approved Development deployment, run only bounded WordPress/BuddyBoss smoke tests.
8. Consider Production only after Development acceptance, URL resolution, and confirmed backup/rollback ownership.

## Hard Stops

- No Development upload or mutation before exact artifact/command approval.
- No Production mutation under this task.
- No printing or committing SSH account, port, private configuration, keys, or the mismatched URL.
- No member, payment, registration, form-entry, or unrelated WordPress data access.
- No commit, push, historical CSV changes, or unrelated worktree cleanup.

## Verification

- `powershell -NoProfile -File tools\verify-project.ps1 -ZipPath <artifact>`.
- PowerShell parse/deployment contract checks and `git diff --check`.
- ZIP structure, reproducibility, version alignment, and secret scans.
- Development full dry-run with the exact artifact and deployment ID.
- Claude reviewer-only review of this task and its result artifact.
