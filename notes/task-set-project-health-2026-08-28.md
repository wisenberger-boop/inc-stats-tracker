# Task Set — Project Health, Cleanup, and Recovery

Date: 2026-08-28
Status: Approved for autonomous execution

## Objective

Restore a coherent, testable, documented release baseline for INC Stats Tracker while
preserving working behavior, historical data, unrelated worktree changes, and all external
safety boundaries.

## Approved Scope

1. Create canonical health, current-state, risk, task, and result artifacts without creating
   competing documentation systems.
2. Reconcile release metadata to version `1.0.6`, including documentation for commit
   `86efee5`.
3. Correct lifecycle, schema, hooks, compatibility, and release-documentation drift.
4. Add lightweight deterministic local regression/release checks without introducing a
   dependency manager or large test framework.
5. Build and inspect the local `1.0.6` plugin ZIP from `plugin/inc-stats-tracker/` only.
6. Record the privacy/retention risk from tracked historical CSVs without deleting, moving,
   rewriting, or exposing their contents.
7. Run PHP lint, regression checks, release/package checks, targeted security checks, and
   independent Claude reviewer-only review. Fix justified findings and rereview until PASS
   or a hard stop.
8. Record an evidence-based completion estimate, health scores, risks, and next workstream.

## Acceptance Criteria

- Plugin header, `IST_VERSION`, changelog, `readme.txt`, README, and package metadata agree
  on `1.0.6`.
- Documentation matches current hooks, schema, lifecycle behavior, and known compatibility
  evidence.
- A repeatable local verification command checks PHP syntax, version consistency, the YTD
  timezone regression, release-folder hygiene, and package contents.
- The generated ZIP contains only the plugin directory, uses forward-slash entry names, and
  contains no CSV, project-management, tools, local review, or credential files.
- Project health/current state identifies current phase, completion, blockers, privacy risk,
  release readiness, and the next coherent workstream.
- Claude reviewer-only returns PASS, or a hard stop is documented.

## Allowed Autonomy

- Reversible edits to local source-adjacent documentation, verification tooling, release
  metadata, and local build artifacts.
- Task-local corrections required by deterministic or Claude review findings.

## Hard Stops and Non-Goals

- No staging or production mutation, deployment, or external messages.
- No Git push, history rewrite, force operation, branch deletion, or commit unless separately
  requested.
- No deletion, relocation, sanitization, or history purge of historical CSV data.
- No credential discovery beyond safe repository scans; never print secret values.
- No product redesign, new feature implementation, database destructive migration, or broad
  dependency/framework introduction.

## Verification and Review Gates

- PHP syntax lint across every plugin PHP file.
- Standalone fiscal-year timezone regression check under UTC.
- Version/documentation/release-source consistency checks.
- Local ZIP build and structural/content inspection.
- Targeted secret, PII-presence, WordPress write-surface, and Git-diff review.
- Results artifact followed by tool-disabled Claude PASS/FAIL review and rereview loop.
