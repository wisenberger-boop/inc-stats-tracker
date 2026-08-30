# AGENTS.md — INC Stats Tracker

Shared rules for Codex, Claude, and other AI coding agents working in this repository.

## Project Overview

WordPress plugin that tracks TYFCB (Thank You for Closed Business), referrals, connects, and
member activity for INC reporting. BuddyBoss Group membership defines the active member set;
`wp_users.ID` is the canonical identity used in all plugin tables — see `docs/project-overview.md`
for the full architecture summary.

Sibling repo in the same INC ecosystem: `inc-meeting-roles-scheduler`
(`C:\Users\isen\OneDrive\Documents\Code Files\inc-meeting-roles-scheduler`). Changes to
BuddyBoss group/member logic here may also matter there.

## Recommended Startup Reads

1. `README.md`
2. `docs/project-overview.md`
3. `docs/implementation-notes.md`
4. `plugin/inc-stats-tracker/CHANGELOG.md` — newest entry is the current version + `[Planned — Next Release]` items
5. `Version:` header in `plugin/inc-stats-tracker/inc-stats-tracker.php`

Reference on demand: `docs/database-schema.md`, `docs/hooks-reference.md`, `docs/naming-conventions.md`.

## Confirm Before Working

- The `Version:` header in `inc-stats-tracker.php` (and `IST_VERSION`) matches the newest
  `CHANGELOG.md` entry — flag a mismatch before making further changes.
- A release build packages `plugin/inc-stats-tracker/` only, via
  `tools/package-plugin.bat` / `.ps1`. `docs/`, `project-management/`, and `tools/` never ship.
- Walk `project-management/checklists/plugin-release-checklist.md` before cutting a release.

## Do Not

- Ship files outside `plugin/inc-stats-tracker/` in a release build.
- Bump the plugin version without a corresponding `CHANGELOG.md` entry.
- Mutate production WordPress without explicit user approval in the current session.

## Agent Compatibility

- `AGENTS.md` (this file) is the canonical shared instruction file for Codex, Claude, and other agents.
- Claude should also read `CLAUDE.md`.
- Claude project skills live in `.claude/skills/` (see `inc-project-init` for the startup-context skill).
- Codex global reusable skills live in `C:\Users\isen\.codex\skills`.

## Codex and Claude Review Workflow

- The user and Codex collaborate on scope, acceptance criteria, risks, and a written task plan.
- Implementation begins only after the user explicitly approves that plan. Once approved, Codex
  executes the agreed scope autonomously through implementation, deterministic QA, results
  documentation, Claude review, justified fixes, and rereview.
- Claude is reviewer only during this workflow. Claude must not edit files, execute tests, access
  external systems, perform deployment, or grant approval for actions requiring user authority.
- A Claude `FAIL` verdict requires Codex to assess every finding, fix justified issues, document
  evidence for rejected findings, rerun affected verification, and resubmit for review.
- Stop for material scope expansion, ambiguous business intent, unauthorized external writes,
  destructive non-disposable data actions, new secret or private-data authority, permission
  barriers, or acceptance of critical/high residual risk.
- See `notes/claude-review-workflow.md` and run reviews through
  `scripts/agent-workflow/Invoke-ClaudeReview.ps1`.
