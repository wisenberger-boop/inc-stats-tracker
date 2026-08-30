# Task Set — 1.0.9 Git Handoff

Date: 2026-08-30
Status: Approved by the owner in the current session

## Objective

Create an auditable Git recovery point for the cumulative reviewed `1.0.6`–`1.0.9` source and
supporting release evidence now running on Development and Production, push `main`, and rerun the
tool-disabled Claude review that failed solely for missing Git traceability.

## Approved Scope

1. Audit the dirty worktree and build an explicit manifest containing the reviewed plugin source,
   release metadata, tests, deployment/reviewer tooling, repository instructions, and task/handoff evidence.
2. Exclude `.local/`, release ZIPs, credentials, raw exports, historical CSV actions, and unrelated cleanup.
3. Run deterministic verification, ZIP inspection, secret-pattern checks, and staged-diff review.
4. Commit the intentional manifest to `main` and push `main` to `origin`.
5. Update the matching results with the commit identifier and rerun Claude reviewer-only until PASS
   or a genuine hard stop.

## Non-Goals and Hard Stops

- No tag under this approval.
- No Development or Production mutation.
- No historical CSV inspection, deletion, movement, sanitization, or history rewrite.
- No remote archive deletion or unrelated worktree cleanup.
- No force push, amend, rebase, or reset.

## Verification

- `powershell -NoProfile -File tools\verify-project.ps1 -ZipPath build\releases\inc-stats-tracker-1.0.9.zip`
- targeted secret-pattern scan excluding `.git`, `.local`, build output, and historical CSV content
- `git diff --cached --check`
- `git status --short` and staged manifest review
- normal `git push origin main`
- tool-disabled Claude review of this task and its results
