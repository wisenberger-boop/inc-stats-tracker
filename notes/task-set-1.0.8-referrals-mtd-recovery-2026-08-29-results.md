# Task Results — 1.0.8 Referrals MTD and Project-State Recovery

Date: 2026-08-29
Task: `notes/task-set-1.0.8-referrals-mtd-recovery-2026-08-29.md`
Outcome: DEPLOYED TO DEVELOPMENT AND PRODUCTION — `1.0.8` active; bounded smoke checks pass

## Implementation

- Reused `IST_Stats_Query::referral_leaderboard()` with the current-month date window and current
  BuddyBoss roster, retaining its top-ten default.
- Added an explicit empty-roster guard before the monthly referral query so an empty member set
  cannot fall back to all referral records.
- Passed the monthly rows only to the Referrals Given KPI card. The lower fiscal-year Top Referral
  Givers query/render path remains unchanged.
- Generalized the shared embedded KPI leaderboard to render either:
  - Closed Business amount plus submission count; or
  - count-only referral totals with singular/plural wording.
- Added a referral-specific accessible heading ID, visible heading, and empty state.
- Kept contextual escaping and semantic ordered-list markup. No JavaScript, dependency, capability,
  database, submission, or schema behavior changed.

## Release Metadata and Artifact

- Plugin header, `IST_VERSION`, changelog, WordPress readme stable tag, project README, and package
  metadata report `1.0.8`.
- No database schema changes.
- Artifact:
  `C:\Users\isen\OneDrive\Documents\Code Files\inc-stats-tracker\build\releases\inc-stats-tracker-1.0.8.zip`
- Size: 161,342 bytes.
- Entries: 65, all under `inc-stats-tracker/`.
- SHA-256:
  `7D23004DA77502371F187F07355B3F4F5C8E0B685B72CC22069B69ACD4B53E87`
- Two consecutive canonical builds produced the same checksum.

## Packaging Deviation and Fix

The first package attempt failed before creating a ZIP because `cmd.exe` misparsed non-ASCII box
drawing comments in the LF/UTF-8 batch wrapper. The wrapper also contained interactive `pause`
prompts that were unsuitable for deterministic release/deploy automation.

- Replaced decorative comments with ASCII-safe comments.
- Removed interactive pauses.
- Limited automatic replacement to the exact versioned ZIP path already constructed under
  `build/releases/` and added deletion exit checking.
- Reran two builds, artifact inspection, and the complete verifier successfully.

## Deterministic Verification

Commands and results:

- `powershell -NoProfile -File tools\verify-project.ps1`: PASS.
- PHP syntax: 56/56 plugin PHP files.
- Version consistency: `1.0.8` across five verifier-controlled sources.
- Fiscal-year regression: four date cases and three production-source contracts.
- Monthly leaderboard checks: 13 SQL assertions, 17 UI/source contracts, seven runtime-render contracts.
- Deployment script contracts: 43.
- `tools\package-plugin.bat`: PASS twice, reproducible SHA-256.
- `powershell -NoProfile -File tools\verify-project.ps1 -ZipPath build\releases\inc-stats-tracker-1.0.8.zip`: PASS; 65 entries.
- `git diff --check`: PASS except informational Windows line-ending warnings.
- Canonical stale-state scan: zero matches for the retired SSH/canonical/deployment blockers.

## UI/Accessibility Evidence

- Runtime-render contracts cover populated referral rows, singular/plural labels, absence of currency,
  the referral empty state, stable `aria-labelledby`, semantic ordered lists, and preservation of
  Closed Business amount-plus-count output.
- Ignored desktop fixture screenshot:
  `.local/ui-fixture/group-stats-kpi-1.0.8-desktop.png`.
- Desktop inspection passed for alignment, long-name wrapping, count hierarchy, and empty-state copy.
- The installed headless browser enforces a roughly 500px minimum layout viewport, so its nominal
  390px screenshot is not counted as valid mobile evidence. The existing `max-width: 480px`
  single-column rule and shared markup are source-verified; true mobile, 200% zoom, high contrast,
  assistive technology, Production-theme interaction, and subjective visual acceptance remain manual.

## Project-State Recovery

- Rewrote the canonical health, current-state, task-registry, continuation, and restart documents to
  reflect active `1.0.7` on both targets and the local `1.0.8` candidate.
- Removed contradictory claims that Production was both deployed and blocked and retired the false
  SSH authentication blocker.
- Preserved dated task/result packets as historical records rather than rewriting former-state facts.
- Recommended completion is 95% with medium confidence after Development visual acceptance and the
  approved Production deployment; authenticated Production visual review remains manual.

## Files Changed in This Task

- `plugin/inc-stats-tracker/frontend/class-ist-group-extension.php`
- `plugin/inc-stats-tracker/templates/frontend/tmpl-group-stats-reports.php`
- `plugin/inc-stats-tracker/templates/frontend/partials/tmpl-kpi-row.php`
- `plugin/inc-stats-tracker/inc-stats-tracker.php`
- `plugin/inc-stats-tracker/CHANGELOG.md`
- `plugin/inc-stats-tracker/readme.txt`
- `tests/monthly-submitter-leaderboard.php`
- `tools/package-plugin.bat`
- `README.md`
- `PROJECT_HEALTH.md`
- `notes/current-state.md`
- `notes/task-registry.md`
- `notes/continuation-brief.md`
- `notes/restart-prompt.md`
- this task/result packet
- ignored `.local/ui-fixture/group-stats-kpi.html` and generated QA screenshots

The cumulative working-tree diff also contains `.gitignore`, schema/hooks documentation,
`class-ist-deactivator.php`, the release checklist, and the `1.0.7` Closed Business query/CSS work.
Those files originate from separately reviewed prior task sets and are not reclassified as `1.0.8`
changes merely because the releases remain uncommitted together.

## Safety and Residual Risk

- The exact approved Development and Production plugin-only deployments occurred. No cache command,
  submission, edit, import, deletion, commit, push, tag, CSV action, or remote archive deletion occurred.
- The artifact is built from a dirty, uncommitted cumulative worktree that also contains the deployed
  `1.0.7` slice. Exact reproducibility mitigates local recovery risk but does not replace Git history.
- Seven tracked historical CSVs remain an owner decision and were not opened or changed.
- Production now runs the exact approved `1.0.8` artifact. Human authenticated visual review remains.

## Exact Development Preflight

- Result: PASS; no remote changes.
- Remote current plugin: `1.0.7`, active.
- Canonical target/root/WP-CLI/ZIP validation: PASS.
- Deployment ID: `20260829-075027`.
- Planned plugin-only rollback archive:
  `~/inc-stats-tracker-development-deploys/backups/inc-stats-tracker-predeploy-20260829-075027.tar.gz`.
- Development full-site backup reference: not configured.
- Development rollback owner: not configured.
- Exact approval-gated command:

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Development -Live -SkipBuild -ArtifactPath "C:\Users\isen\OneDrive\Documents\Code Files\inc-stats-tracker\build\releases\inc-stats-tracker-1.0.8.zip" -DeploymentId 20260829-075027
```

## Approved Development Deployment and Smoke Evidence

- The user explicitly approved the exact target, artifact, SHA-256, deployment ID, rollback path,
  and command in the current session, accepting the missing Development site-backup/owner metadata
  and uncommitted-artifact traceability risk.
- WordPress reported a successful plugin update; Development reports `1.0.8`, active.
- The live command exited `1` only after installation because its immediate public REST smoke received
  provider HTTP 403. Version/status checks had already passed, so rollback was not triggered.
- Approved plugin-only rollback archive: present and non-empty.
- Runtime Stats Query/referral method, BuddyBoss service/API, and summary REST route: ready.
- Stats Tracker matches in the last 500 Development debug-log lines: zero.
- Follow-up browser-like HTTP checks: REST index 200; protected summary 401 as expected; site root 403.
- WP-CLI printed deprecation notices from unrelated Development plugins during installation. They do
  not reference INC Stats Tracker; no unrelated plugin was inspected or changed.
- No raw member/stat rows were printed. No submission, edit, import, deletion, cache action, or
  Production command occurred.

## Approved Production Deployment and Smoke Evidence

- The owner visually accepted Development and then confirmed a current ManageWP full-site backup,
  retained rollback ownership, and explicitly approved the exact Production target, artifact,
  SHA-256, deployment ID, rollback path, and command in the current session.
- Production pre-deploy state: `1.0.7`, active; exact HTTPS `www` canonical URL and expected WordPress
  root passed read-only preflight.
- Deployment ID: `20260829-082440`.
- Plugin-only rollback archive:
  `~/inc-stats-tracker-production-deploys/backups/inc-stats-tracker-predeploy-20260829-082440.tar.gz`.
- WordPress reported a successful update; Production reports `1.0.8`, active, and the rollback archive
  is present and non-empty.
- The live command exited `1` only after installation because its deployment-specific REST request
  received provider HTTP 403. The installed version and active status had already passed, so rollback
  was not triggered.
- Corrected browser-like checks returned REST index 200 and protected summary 401 as expected.
- Stats Query, BuddyBoss, configured group, non-empty active roster, and summary REST route markers pass.
- The current-month referral leaderboard query completed without a database error and returned
  non-empty rows. Output was restricted to boolean markers; no names, IDs, amounts, or records printed.
- Stats Tracker matches in the last 500 Production debug-log lines: zero.
- WP-CLI printed deprecations/notices from unrelated plugins during installation. None referenced INC
  Stats Tracker; no unrelated plugin or data was changed.
- No submission, edit, import, deletion, purge, cache command, or unrelated-data query occurred.

## Reviewer History

- Initial reviewer-only verdict: PASS with no blocking issues.
- Justified recommendation fixed: reconciled the deterministic UI/source contract count from 15 to
  16 in the test output and all canonical evidence documents, then reran verification.
- Heading hierarchy recommendation deferred: each embedded leaderboard is a named subsection under
  the page `h2`; its `h3` and `aria-labelledby` are valid and consistent with the existing component.
  A broader heading-level change would be UI-polish scope and still needs theme/browser inspection.
- Git recommendation retained as P1: the cumulative `1.0.7`/`1.0.8` worktree needs a separately
  authorized handoff and deliberate commit-split decision; no commit/push/tag is authorized here.
- First rereview verdict: PASS with no blocking issues.
- Justified mobile recommendation fixed: stacked leaderboard totals now allow wrapping below 700px,
  with a deterministic CSS contract for long or translated count labels.
- Review transcript recommendation assessed: the runner persists the latest structured verdict under
  ignored `.local/agent-reviews/`; retaining every same-task revision would require a separately
  scoped workflow change and is deferred.
- Final rereview verdict: PASS with no blocking issues. Claude confirmed the referral query reuse,
  empty-roster guard, KPI-only wiring, FY/Closed Business preservation, escaping, responsive wrap,
  version alignment, test contracts, and safety boundaries.
- Post-deployment evidence rereview: PASS with no blocking issues.
- Final Production-evidence review initially returned FAIL because the original task's no-deployment
  boundary had not been formally amended even though exact later approvals were recorded in-session
  and in these results. This was a valid audit-trail finding, not a runtime or authorization failure.
- Added a dated Deployment Authorization Addendum to the task artifact recording the exact Development
  and Production gates, accepted risks, commands, and bounded QA authority; then resubmitted for review.
- Final Production-evidence rereview: PASS with no blocking issues. Claude accepted the addendum as an
  adequate documentary resolution and confirmed the implementation, version alignment, deterministic
  contract counts, exact deployment scope, and retained safety boundaries.
- Generic empty-message recommendation deferred: changing the partial/default after deployment would
  break source/artifact parity. Current Closed Business and Referral callers render the correct copy;
  require explicit per-metric messages in a future component-hardening release.
- Shared prior-year helper remains the existing deferred P2 task; it is unrelated to this release.
- Narrow Git handoff remains P1 and requires separate commit/push/tag authorization.

## Next Action

Perform authenticated Production Group Stats visual QA for the Referrals Given MTD card. Then decide
the separately authorized Git handoff and historical CSV retention workstreams.
