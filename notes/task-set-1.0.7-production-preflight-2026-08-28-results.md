# Task Results — 1.0.7 Production Preflight

Date: 2026-08-28
Task: `notes/task-set-1.0.7-production-preflight-2026-08-28.md`
Outcome: DEPLOYED TO PRODUCTION — `1.0.7` active; bounded real-data QA passed without printing records

## Approved Target and Safeguards

- Canonical Production URL: approved HTTPS `www` target; remote comparison is exact.
- WordPress root: expected Production root containing `wp-config.php`, `wp-admin/`, and `wp-content/`.
- ManageWP full-site backup: user confirmed complete; sanitized reference is configured locally.
- Rollback owner: William Isenberger; configured locally.
- Both fields are stored only in ignored `.local/deploy-production.env` and were not printed.
- User authorized later read-only QA against real Production member/stat data, with no submission,
  edit, import, deletion, purge, or other data mutation.

## Reviewed Artifact

- Path: `C:\Users\isen\OneDrive\Documents\Code Files\inc-stats-tracker\build\releases\inc-stats-tracker-1.0.7.zip`
- Version: `1.0.7`
- SHA-256: `729042FD9A56C3D56DDA2EA4C397A1C99CFBB03E1FBFF2C1FE5065B361EA3213`
- Production deployment ID: `20260828-211605`
- Current Production plugin: `1.0.6`, active.
- Planned plugin-only rollback archive:
  `~/inc-stats-tracker-production-deploys/backups/inc-stats-tracker-predeploy-20260828-211605.tar.gz`

## Read-Only Preflight Evidence

- SSH authentication, expected root/structure, `wp-config.php`, WP-CLI, and exact canonical URL: PASS.
- Plugin version/status query only: `1.0.6`, active.
- Project verifier: PASS — 56 PHP files, aligned `1.0.7` metadata, four timezone cases, seven
  leaderboard SQL assertions, eight UI/source contracts, and 43 deployment contracts.
- ZIP verifier: PASS, 65 plugin-only entries.
- Backup reference and rollback owner readiness: configured.
- No Production upload, backup creation, plugin update, activation, cache action, database change,
  member/stat data read, commit, or push occurred.

## Approved Exact Production Command

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Production -Live -SkipBuild -ArtifactPath "C:\Users\isen\OneDrive\Documents\Code Files\inc-stats-tracker\build\releases\inc-stats-tracker-1.0.7.zip" -DeploymentId 20260828-211605 -ConfirmProduction
```

The user approved this Production target, artifact, SHA-256, rollback path, deployment ID, and exact
command in the current session. Earlier plan/Development approvals did not authorize it.

## Approved Production Deployment

- The user approved the exact Production target, artifact, SHA-256, deployment ID, rollback path, and
  command in the current session, accepting the uncommitted-artifact traceability risk.
- ManageWP full-site backup and William Isenberger rollback ownership were confirmed before execution.
- The approved plugin-only rollback archive was created before upload.
- WordPress reported a successful update; Production now runs active `1.0.7`.
- The live script exited nonzero after installation because its deployment-specific user agent received
  provider HTTP 403 at the public REST smoke. Browser-like HTTP checks afterward returned REST 200.
  The script now uses that verified browser-like user agent for future smoke checks.
- No rollback was triggered because installed version/status and all plugin-specific runtime checks pass.

## Read-Only Production QA

The user authorized real Production member/stat data for read-only QA. Output was restricted to
boolean/classification markers; no member names, IDs, amounts, record contents, or raw rows were printed.

- Approved plugin rollback archive: present and non-empty.
- Stats Tracker: `1.0.7`, active.
- Configured BuddyBoss group: present; active roster: non-empty.
- Current-month Closed Business submitter leaderboard: returned non-empty rows.
- Fiscal-year Closed Business, Referral, and Connect aggregate queries: ready with no database error.
- BuddyBoss integration: ready; summary REST route: registered.
- Stats Tracker matches in the last 500 `wp-content/debug.log` lines: zero.
- Browser-like HTTP checks: REST index 200; unauthenticated protected summary 401 as expected.
- Production site root: provider HTTP 403, so authenticated My Stats/Group Stats browser rendering was
  not tested. The real-data query/runtime path for the new leaderboard passed.
- No submission, edit, import, deletion, purge, cache command, database write, or unrelated data access
  occurred during QA.

## Claude Review

- PASS on 2026-08-28 with no blocking issues.
- Claude confirmed the read-only boundary, approved HTTPS `www` target, ignored backup/owner storage,
  version alignment, query/escaping safety, deterministic evidence, and absence of Production mutation.
- Non-blocking release risk: the artifact is reproducible and checksum-recorded but was built from
  uncommitted working-tree state. An intentional Git handoff remains recommended for traceability.
- First post-deployment rereview: PASS with no P0/P1 code, authorization, or QA defect.
- A follow-up rereview returned FAIL solely because the deployed artifact has no corresponding Git
  commit. The factual traceability risk is accepted; the proposed immediate broad commit/push is not:
  the user explicitly accepted this risk in the exact Production approval, this task explicitly forbids
  commit/push, and the dirty worktree contains unrelated work that must not be swept into a release commit.
- Fresh post-deployment reproducibility evidence: two independent plugin-only rebuilds from the current
  source both exactly match deployed SHA-256
  `729042FD9A56C3D56DDA2EA4C397A1C99CFBB03E1FBFF2C1FE5065B361EA3213` (65 entries). This proves the
  current plugin source has not drifted from the live artifact, while not pretending it replaces Git history.
- The intentional Git handoff remains a P1 next task requiring separate, narrowly scoped authorization.
- Final reviewer-only rereview after documenting the accepted-risk assessment: PASS with no blocking
  issues. Claude accepted the byte-for-byte reproducibility evidence and the separately scoped Git
  handoff as adequate mitigation for this task.
