# Task Set — Reuse Verified Content Manager SiteGround SSH Connection

Date: 2026-08-28
Status: Approved by the user in the current session

## Objective

Configure INC Stats Tracker to reuse the verified Inclusive Networking Content Manager SiteGround
connection by reference, retain isolated Development and Production configuration, and run bounded
read-only preflights without deploying or reading unrelated WordPress data.

## Approved Scope

1. Inspect existing deployment tooling and ignored configuration.
2. Update the configuration helper to resolve the ignored environment referenced by the verified
   Content Manager runner and reference its existing private key without copying or printing it.
3. Preserve separate target roots, temporary paths, known-host stores, approvals, and rollback data.
4. Repair deployment scripts/contracts for bounded preflight output, explicit target selection,
   exit-code handling, exact artifact validation, live approval gates, and rollback safeguards.
5. Run Development and Production read-only checks limited to authentication, expected WordPress
   structure, WP-CLI availability, canonical target comparison, and Stats Tracker version/status.
6. Run deterministic verification, document results, and obtain Claude reviewer-only PASS.

## Non-Goals and Hard Stops

- No upload, install, activation, update, deactivation, deletion, backup, rollback, or other remote mutation.
- No inspection of members, payments, registrations, forms, content, or unrelated WordPress data.
- No key copy, replacement, disclosure, or credential output.
- No version bump, commit, push, or unrelated worktree cleanup.
- Development requires fresh approval for an exact target/artifact/checksum/command.
- Production remains a separate gate requiring canonical-target resolution, reviewed artifact,
  backup/rollback plan, exact target confirmation, and fresh current-session approval.

## Verification

- PowerShell parsing and deployment contract tests.
- `powershell -NoProfile -File tools\verify-project.ps1`.
- `git diff --check`.
- Target-specific `-ConnectionOnly` preflights.
- Sanitized tracked-file scan for connection details and private-key material.
- Claude reviewer-only review using the task and result artifacts.
