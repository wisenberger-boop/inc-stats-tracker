# Task Results — Dual-Environment SSH Deployment Setup

Date: 2026-08-28
Task: `notes/task-set-ssh-deployment-setup-2026-08-28.md`
Outcome: PARTIAL — local setup and deterministic QA complete; remote discovery blocked on SiteGround key authorization/account details

## Work completed

- Added explicit-target, dry-run-first SSH/WP-CLI tooling for Development and Production.
- Added separate ignored configs with unique target-prefixed variables and distinct project-local
  ED25519 keypairs. Private material remains under `.local/` and is Git-ignored.
- Added bounded read-only discovery for `wp-config.php`, canonical `siteurl`, WP-CLI, WordPress/PHP
  versions, and the installed plugin version/status.
- Added reproducible two-build packaging, deterministic project verification, ZIP inspection,
  private-key artifact scanning, SHA-256 output, plugin-only remote backup, and production backup/owner gates.
- Added sanitized deployment and rollback documentation and README routing.
- Added a 12-assertion deployment-script contract test and integrated it with project verification.
- Added separate ignored `known_hosts` pin stores: first authorized discovery uses `accept-new`,
  while every later connection requires the recorded target host key.

## Verification actually run

- PowerShell parser: PASS for `scripts/deploy-plugin-ssh.ps1`.
- Ambiguous batch invocation: PASS, refused with exit code 2.
- Missing Development SSH host: PASS, failed closed before any SSH connection.
- Git ignore checks: PASS for both configs, both private keys, and both public keys.
- Distinct key hashes/fingerprints: PASS; no private key content printed.
- `tests/deploy-script-contract.ps1`: PASS, 12 assertions.
- `tools/verify-project.ps1`: PASS — 56 PHP files, five aligned `1.0.6` sources, four timezone
  cases, seven leaderboard SQL assertions, eight UI contracts, and deployment contracts.
- Two isolated package builds: PASS, identical SHA-256
  `CB623305E3B204A6D7F26B911867D7B141F7ACD668A4F72C85D467B3DE3587F0`, 65 files.
- ZIP verification: PASS, 65 entries with no forbidden or private-key material.
- `git diff --check`: PASS apart from informational Windows line-ending warnings.
- Targeted tracked secret-pattern scan excluding ignored/local/raw CSV data: PASS.

## Remote discovery state

- Public DNS confirms both requested domains currently resolve to the same public server IP; this
  was not treated as evidence of a shared SSH identity, WordPress root, or deploy authorization.
- No project SSH config or pre-existing project deployment credentials existed.
- Two new project-specific public keys must be authorized in their corresponding SiteGround accounts.
- Each ignored config still needs its exact SSH host, SSH account, port, and discovered WordPress root.
- Therefore no SSH connection, remote WP-CLI command, upload, backup, deploy, cache action, database
  action, or other remote mutation occurred.

## Files added or updated for this task

- `scripts/deploy-plugin-ssh.ps1`
- `scripts/deploy-plugin.bat`
- `docs/deployment.md`
- `tests/deploy-script-contract.ps1`
- `tools/verify-project.ps1`
- `README.md`
- ignored `.local/deploy-dev.env`, `.local/deploy-production.env`, and two target-specific keypairs
- task/result and handoff/status notes

## Residual blockers and next action

1. Authorize the Development public key and provide the Development SSH account/host/port.
2. Run `-Target Development -Discover`, verify canonical URL/root, then run full dry-run preflight.
3. Resolve the release decision: current source still reports `1.0.6` but includes the planned monthly
   leaderboard not present in the prior `1.0.6` artifact.
4. Only then report the immutable Development artifact and exact command for approval.

No commit, push, deployment, or remote mutation was performed.

## Claude review history

- Initial invocation returned PASS but reviewed only `HEAD`; it was rejected as deployment evidence.
- Corrected packet explicitly included all deployment files. Claude returned PASS with no blocking issues.
- Its target-specific host-key pinning recommendation was accepted and implemented; affected
  deterministic checks pass and the deployment contract count increased from 10 to 12.
- Final rereview: PASS with no blocking issues. Claude confirmed explicit mutation gates,
  plugin-only scope, config quoting/validation, host-key pinning, and honest PARTIAL status.
