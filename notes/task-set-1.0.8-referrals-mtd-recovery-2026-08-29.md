# Task Set — 1.0.8 Referrals MTD and Project-State Recovery

Date: 2026-08-29
Status: Approved by the user in the current session

## Objective

Reconcile the canonical project-state documents after the successful `1.0.7` Development and
Production deployments, then release a focused `1.0.8` candidate that adds a current-month
Referrals Given member leaderboard to the Group Stats KPI card using the same interaction and
visual pattern as the accepted Closed Business MTD leaderboard.

## Approved Scope

1. Update `PROJECT_HEALTH.md`, `notes/current-state.md`, `notes/task-registry.md`,
   `notes/continuation-brief.md`, and `notes/restart-prompt.md` so the canonical state reflects:
   - active `1.0.7` on Development and Production;
   - resolved Production canonical URL and configured backup/rollback ownership;
   - bounded runtime QA and the remaining browser/Git/privacy risks;
   - the accepted `1.0.8` Referrals Given MTD workstream.
2. Preserve dated task/result packets as historical evidence; do not rewrite their former-state facts.
3. Reuse `IST_Stats_Query::referral_leaderboard()` with the current-month window, current BuddyBoss
   roster, top-ten limit, and explicit empty-roster guard.
4. Pass that result to the Referrals Given KPI without changing the existing fiscal-year Top Referral
   Givers leaderboard.
5. Generalize the embedded KPI leaderboard renderer so Closed Business keeps currency plus submission
   counts while Referrals shows referral counts and referral-specific empty copy.
6. Preserve the existing visual system, semantic ordered-list structure, stable accessible heading
   association, responsive behavior, and contextual output escaping.
7. Promote the focused change to `1.0.8` with aligned plugin header, constant, changelog, WordPress
   readme, project README, and package metadata. No database schema change is allowed.
8. Extend deterministic query/UI contracts, run the complete verifier, build and inspect one
   reproducible plugin-only ZIP, and record its SHA-256.
9. Write a results artifact and run the mandatory Claude reviewer-only PASS/FAIL loop.

## Acceptance Criteria

- The Referrals Given card displays a current-month top-ten leaderboard of active BuddyBoss group
  members ranked by referrals given.
- No current roster IDs produces an empty leaderboard without falling back to all referral records.
- Referral rows display count-only totals with correct singular/plural wording.
- The referral empty state says that no referrals were given this month.
- Closed Business MTD and the lower fiscal-year leaderboards retain their existing behavior.
- Version sources all report `1.0.8`; no schema migration is introduced.
- PHP lint, fiscal-year regressions, leaderboard contracts, deployment contracts, ZIP validation,
  and `git diff --check` pass.
- Claude returns reviewer-only PASS, or every finding is resolved/documented and rereviewed.

## Non-Goals and Safety Boundaries

- No Development or Production upload, install, activation, update, cache action, database write,
  submission, edit, import, deletion, or other remote mutation.
- No member/stat record inspection and no unrelated WordPress data access.
- No commit, push, tag, Git-history rewrite, historical CSV move/delete, or remote rollback-archive
  cleanup without separate approval.
- Do not modify unrelated working-tree changes or introduce a new frontend framework/dependency.
- A future deployment requires a newly reviewed exact artifact and current-session target approval;
  Production remains a separate backup/rollback/target gate.

## Verification and Review

Run:

```powershell
powershell -NoProfile -File tools\verify-project.ps1
tools\package-plugin.bat
powershell -NoProfile -File tools\verify-project.ps1 -ZipPath build\releases\inc-stats-tracker-1.0.8.zip
git diff --check
powershell -NoProfile -File scripts\agent-workflow\Invoke-ClaudeReview.ps1 `
  -TaskFile "notes/task-set-1.0.8-referrals-mtd-recovery-2026-08-29.md" `
  -ResultsFile "notes/task-set-1.0.8-referrals-mtd-recovery-2026-08-29-results.md"
```

## Deployment Authorization Addendum — 2026-08-29

The original implementation plan above intentionally prohibited remote mutation. After the reviewed,
immutable `1.0.8` artifact was built, the user separately expanded the authorized scope through two
exact current-session deployment gates. These later approvals supersede the original no-deployment
boundary only for the commands and bounded follow-up QA recorded below; all other safety boundaries
remain in force.

### Development Gate

- Target: Development (`https://dev.inclusivenetworkingcoalition.org`).
- Artifact:
  `C:\Users\isen\OneDrive\Documents\Code Files\inc-stats-tracker\build\releases\inc-stats-tracker-1.0.8.zip`.
- SHA-256: `7D23004DA77502371F187F07355B3F4F5C8E0B685B72CC22069B69ACD4B53E87`.
- Deployment ID: `20260829-075027`.
- Plugin rollback:
  `~/inc-stats-tracker-development-deploys/backups/inc-stats-tracker-predeploy-20260829-075027.tar.gz`.
- The user explicitly approved the exact target, artifact, checksum, deployment ID, rollback path, and
  command, accepting the disclosed absence of a Development full-site backup reference/rollback owner
  and the uncommitted-artifact traceability risk.
- Approved command:

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Development -Live -SkipBuild -ArtifactPath "C:\Users\isen\OneDrive\Documents\Code Files\inc-stats-tracker\build\releases\inc-stats-tracker-1.0.8.zip" -DeploymentId 20260829-075027
```

- Authorized follow-up: bounded read-only WordPress/BuddyBoss smoke testing with no raw member/stat
  output and no submission, edit, import, deletion, cache command, or unrelated-data access.

### Production Gate

- Development visual acceptance was confirmed before Production consideration.
- Target: Production (`https://www.inclusivenetworkingcoalition.org`).
- Artifact and SHA-256: the exact Development-reviewed `1.0.8` ZIP and checksum above.
- Pre-deploy plugin state: `1.0.7`, active.
- Deployment ID: `20260829-082440`.
- Plugin rollback:
  `~/inc-stats-tracker-production-deploys/backups/inc-stats-tracker-predeploy-20260829-082440.tar.gz`.
- The user confirmed a current ManageWP full-site backup, retained rollback ownership, and explicitly
  approved the exact Production target, artifact, checksum, deployment ID, rollback path, and command.
- Approved command:

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Production -Live -SkipBuild -ArtifactPath "C:\Users\isen\OneDrive\Documents\Code Files\inc-stats-tracker\build\releases\inc-stats-tracker-1.0.8.zip" -DeploymentId 20260829-082440 -ConfirmProduction
```

- Authorized follow-up: bounded read-only QA against real Production member/stat data. Output must be
  boolean/status-only, with no names, IDs, amounts, raw rows, submissions, edits, imports, deletions,
  cache commands, or unrelated WordPress-data access.
