# Task Results — 1.0.7 Development Release Preparation

Date: 2026-08-28
Task: `notes/task-set-1.0.7-development-release-2026-08-28.md`
Outcome: DEPLOYED TO DEVELOPMENT — `1.0.7` active; bounded runtime checks pass with disclosed HTTP/UI limitations

## Decisions and Work Completed

- Production's canonical URL mismatch was classified remotely as `www_alias`. The full actual URL
  was not printed or stored. Production remains blocked pending an owner decision on the approved URL.
- Promoted the already-implemented and reviewed monthly Closed Business submitter leaderboard to
  release `1.0.7`; retained the other planned features for a future release.
- Aligned plugin header, `IST_VERSION`, changelog, stable tag, plugin readme changelog/upgrade notice,
  README, and package metadata at `1.0.7`. No database schema change was introduced.
- Added an explicit deployment ID to keep the planned rollback archive identical between dry run and
  live execution. Live mode now requires the exact artifact and deployment ID from the approved dry run.
- Corrected artifact-builder output handling so verification/package diagnostics cannot pollute the
  single artifact path returned to the deployment script.

## Immutable Development Artifact

- Path: `C:\Users\isen\OneDrive\Documents\Code Files\inc-stats-tracker\build\releases\inc-stats-tracker-1.0.7.zip`
- Version: `1.0.7`
- SHA-256: `729042FD9A56C3D56DDA2EA4C397A1C99CFBB03E1FBFF2C1FE5065B361EA3213`
- Deployment ID: `20260828-202039`
- Entry count: 65
- Top-level root: `inc-stats-tracker/` only
- Reproducibility: both independent build hashes exactly match the release artifact hash
- Expected runtime staging placeholder: `docs/source-assets/csv/.gitkeep`; no CSV files ship
- Unexpected release/dev entries: zero

## Development Dry-Run Evidence

- SSH authentication: PASS.
- Expected WordPress root, `wp-config.php`, WordPress structure, WP-CLI, and canonical URL: PASS.
- Current remote Stats Tracker: `1.0.3`, active.
- Planned plugin-only rollback archive:
  `~/inc-stats-tracker-development-deploys/backups/inc-stats-tracker-predeploy-20260828-202039.tar.gz`
- Site backup reference and named rollback owner are not configured for Development. The script still
  creates the plugin-only rollback archive before upload; this absence is disclosed for approval.
- No remote changes were made.

## Approved Exact Development Command

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Development -Live -SkipBuild -ArtifactPath "C:\Users\isen\OneDrive\Documents\Code Files\inc-stats-tracker\build\releases\inc-stats-tracker-1.0.7.zip" -DeploymentId 20260828-202039
```

The user approved this Development target, artifact, SHA-256, rollback path, deployment ID, and exact
command in the current session. This approval did not authorize Production.

## Approved Development Deployment

- The user approved the exact artifact, checksum, rollback path, deployment ID, and command in the
  current session, explicitly accepting the absence of a Development full-site backup reference and
  named rollback owner.
- The first live attempt completed the plugin-only backup step but stopped before upload because SCP
  could not expand the configured `~/` temporary path. It may have created a plugin-only archive under
  a literal tilde directory; no plugin update occurred in that attempt.
- The deployment script was corrected to resolve the remote home internally while continuing to show
  the approved `~/` path. The same artifact/checksum/deployment ID then uploaded and installed.
- WordPress reported a successful plugin update; Development now runs active `1.0.7`.
- The live script initially exited nonzero after installation because a generic PowerShell user agent
  received provider HTTP 403 at the public REST smoke. The script now reports only bounded status codes
  and uses an explicit deployment user agent for future checks.

## Post-Deploy Smoke Evidence

- Correct approved rollback archive: present and non-empty.
- Stats Tracker version/status: `1.0.7`, active.
- `IST_Stats_Query::tyfcb_submitter_leaderboard()`: available at runtime.
- BuddyBoss integration class and BuddyBoss API presence: ready.
- `/inc-stats-tracker/v1/my-summary` REST route: registered internally.
- Stats Tracker matches in the last 500 `wp-content/debug.log` lines: zero.
- Public REST index with browser-like user agent: HTTP 200.
- Protected summary endpoint without authentication: HTTP 401, expected.
- Development site root: provider-level HTTP 403. Authenticated My Stats and Group Stats browser
  rendering were not tested because no authorized test session was supplied.
- A second plugin-only archive from the failed pre-upload attempt exists under a literal tilde
  directory. It was not deleted or moved because cleanup was not separately authorized.
- No Production mutation, member-data access, database write, cache action, commit, or push occurred.

## Verification

- Folder hygiene: zero CSVs, OS artifacts, dev dependency directories, or PHP debug calls.
- Project verifier before deployment: PASS — 56 PHP files, aligned `1.0.7` metadata, four timezone
  cases, seven monthly leaderboard SQL assertions, eight UI/source contracts, and 39 deployment
  contract assertions. Post-incident hardening raises deployment contracts to 43 derived assertions.
- ZIP verifier: PASS, 65 entries.
- Reproducibility: PASS; first, second, and release ZIP SHA-256 values match.
- Scoped private-key-material scan: zero detections.
- `git diff --check`: PASS; informational Windows line-ending warnings only.

## Deviations and Safety

- The non-interactive PowerShell package path was used twice through the deployment preflight instead
  of the interactive batch wrapper. It invokes the same package implementation and adds reproducibility.
- The first build attempt stopped locally before producing an artifact because command diagnostics
  polluted a function return value. The defect was fixed and the complete preflight reran successfully.
- No upload, backup creation, install, activation, update, database change, cache action, member-data
  access, Production mutation, commit, or push occurred.
- Unrelated pre-existing worktree changes were preserved.

## Claude Review

- Pre-deployment PASS on 2026-08-28 with no blocking issues.
- Claude confirmed `1.0.7` version alignment, leaderboard query/roster guards, KPI/template claims,
  deterministic test coverage, bounded Production URL classification, exact artifact/rollback-path
  handling, and adherence to the no-deploy/no-commit hard stops.
- Non-blocking recommendations remain: schedule the intentional Git handoff, address the separately
  tracked empty-roster guards, derive the contract assertion count, and consider configuring a
  Development site-backup reference and rollback owner.
- Post-deployment rereview: PASS on 2026-08-28 with no blocking issues.
- Claude confirmed version/changelog alignment, leaderboard safety, deterministic coverage, deployment
  path/user-agent/output fixes, Development/Production separation, and accurate disclosure of the
  site-root 403, backup limitation, failed-attempt archive, and uncommitted deployed state.
