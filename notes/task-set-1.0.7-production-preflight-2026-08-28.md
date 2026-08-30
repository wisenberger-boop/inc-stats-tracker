# Task Set — 1.0.7 Production Preflight

Date: 2026-08-28
Status: Approved by the user in the current session

## Objective

Prepare the reviewed `1.0.7` artifact for an exact Production deployment decision using the approved
HTTPS `www` canonical URL, completed ManageWP full-site backup, owner-operated rollback, and read-only
QA access to real Production stats/member data.

## Approved Scope

1. Change the Production canonical assertion to `https://www.inclusivenetworkingcoalition.org`.
2. Record a sanitized completed ManageWP backup reference and William Isenberger as rollback owner in
   ignored Production configuration.
3. Run deterministic local verification and a read-only Production dry run using the reviewed ZIP.
4. Record current remote plugin version/status, artifact SHA-256, deployment ID, plugin rollback path,
   backup/owner readiness, and exact Production command.
5. Obtain Claude reviewer-only PASS, then stop for final exact Production deployment approval.
6. If later deployed under separate exact approval, limit QA to read-only inspection; do not submit,
   edit, import, delete, purge, or otherwise mutate member/stat data.

## Hard Stops

- No Production backup creation, upload, installation, activation, update, cache action, database write,
  or other mutation during this preflight.
- No credentials, private configuration, key contents, or raw member/stat records in tracked evidence.
- No commit, push, historical CSV changes, or unrelated worktree cleanup.
- Prior Development approval does not authorize Production.
