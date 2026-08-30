# Task Set — Dual-Environment SSH Deployment Setup

Date: 2026-08-28
Status: Approved by the user in the originating request

## Goal

Create safe, reusable, plugin-only SSH/WP-CLI deployment for INC Stats Tracker with fully separate
Development and Production targets. Perform local setup and read-only discovery only; stop before
the first remote mutation and request exact Development artifact approval.

## Scope and acceptance criteria

- Read project instructions, handoffs, release checklist, existing deployment material, and ignore rules.
- Reuse only non-secret patterns from related INC work; do not copy credentials, paths, or keys.
- Keep separate ignored target configs and distinct project-local SSH identities.
- Require explicit `Development` or `Production`; reject ambiguity and add an extra production guard.
- Default to read-only discovery/preflight and assert canonical URL, WordPress root, WP-CLI, versions,
  artifact contents/checksum, target plugin path, backup/rollback readiness, and secret exclusion.
- Live mode may update only `inc-stats-tracker` after exact target/artifact approval.
- Development and Production approvals remain separate, with development smoke evidence required first.
- Add sanitized documentation, deterministic tests, handoff evidence, and reviewer-only Claude review.

## Hard stops

- Missing or unverified SSH identity/root.
- Keys not authorized by the corresponding SiteGround account.
- Missing production full-site backup reference or rollback owner.
- Any content/user/member/payment/credential access or unrelated WordPress mutation.
- Any upload, deploy, commit, push, or remote mutation without separate explicit approval.
