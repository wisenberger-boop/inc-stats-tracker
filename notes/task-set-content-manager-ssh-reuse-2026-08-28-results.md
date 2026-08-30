# Task Results — Reuse Verified Content Manager SiteGround SSH Connection

Date: 2026-08-28
Task: `notes/task-set-content-manager-ssh-reuse-2026-08-28.md`
Outcome: PARTIAL — configuration and Development preflight complete; Production deployment blocked by canonical URL mismatch

## Work Completed

- Updated the shared-SSH helper to resolve the ignored deploy environment referenced by the verified
  Inclusive Networking Content Manager runner and reference that project's private key in place.
- Verified the source runner/config relationship and private-key fingerprint without printing credentials.
- Kept Development and Production configuration, known-host storage, remote temporary paths, and
  WordPress roots isolated. Existing backup reference and rollback owner fields survive refresh.
- Refreshed both ignored target configs. Each has all 11 required fields and remains ignored.
- Added `-ConnectionOnly` for a bounded remote preflight that performs no artifact build.
- Restricted displayed remote state to canonical-URL match plus Stats Tracker version/status, while
  suppressing unrelated WP-CLI PHP diagnostics.
- Required `-SkipBuild -ArtifactPath` in live mode so deployment cannot silently rebuild a different
  artifact after approval.

## Sanitized Remote Results

- Development: SSH authentication passed; expected WordPress root, `wp-config.php`, WordPress
  structure, WP-CLI, and canonical URL passed; Stats Tracker `1.0.3` is active.
- Production: SSH authentication passed; expected WordPress root, `wp-config.php`, WordPress
  structure, and WP-CLI passed; Stats Tracker `1.0.6` is active.
- Production canonical URL comparison failed. Its actual value was intentionally not printed.
  Production preflight exited nonzero and deployment remains blocked pending owner confirmation.
- No artifact was built by either connection-only preflight and no remote mutation occurred.

## Verification

- Deployment contract: PASS, 36 assertions.
- Project verifier: PASS — 56 PHP files, aligned `1.0.6` metadata, four timezone cases, seven
  leaderboard SQL assertions, eight UI/source contracts, and deployment contracts.
- `git diff --check`: PASS; informational Windows line-ending warnings only.

## Files Changed in This Task

- `scripts/configure-shared-inc-ssh.ps1`
- `scripts/deploy-plugin-ssh.ps1`
- `tests/deploy-script-contract.ps1`
- `docs/deployment.md`
- This task/result pair and current handoff/status notes
- Ignored `.local/deploy-dev.env`, `.local/deploy-production.env`, and target-specific known-host files

Unrelated pre-existing modified and untracked files were preserved.

## Future Approval-Gated Commands

Development artifact preflight:

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Development -SkipBuild -ArtifactPath "<exact-reviewed-zip>"
```

Development live command, only after fresh current-session approval naming target, artifact, checksum,
remote version, rollback path, and this exact command:

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Development -Live -SkipBuild -ArtifactPath "<exact-approved-zip>"
```

Production artifact preflight, only after resolving the canonical URL mismatch and configuring a
current backup reference and rollback owner:

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Production -SkipBuild -ArtifactPath "<exact-reviewed-zip>"
```

Production live command, only after separate fresh approval:

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Production -Live -ConfirmProduction -SkipBuild -ArtifactPath "<exact-reviewed-and-approved-zip>"
```

## Residual Risks and Next Action

- Do not weaken the canonical URL assertion. Confirm whether Production is intentionally configured
  with a different canonical URL, then update the approved expectation only through a separate decision.
- The locally implemented monthly leaderboard still needs a separate release/version decision and an
  immutable artifact; the existing canonical `1.0.6` ZIP predates that feature.
- No deployment is authorized by this task.

## Claude Review

- PASS on 2026-08-28 with no blocking issues.
- Claude confirmed the read-only boundary, exact-artifact and Production gates, host-key handling,
  shared-key-by-reference behavior, bounded output, and correct stop on the Production URL mismatch.
- Non-blocking recommendations: keep the future Git handoff narrowly scoped, obtain an owner decision
  on the Production canonical URL, and preserve literal deterministic QA output in future packets.
