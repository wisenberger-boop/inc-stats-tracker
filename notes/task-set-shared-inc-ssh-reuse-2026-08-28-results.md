# Task Results — Reuse Authorized INC SiteGround SSH Identity

Date: 2026-08-28
Task: `notes/task-set-shared-inc-ssh-reuse-2026-08-28.md`
Outcome: PARTIAL — local configuration and QA complete; both remote preflights blocked at SSH authentication

## Work completed

- Resolved the supplied source paths to the sibling repository's actual `.local/` directory.
- Verified the existing private key and derived public key match the required fingerprint without
  printing private key contents, source values, host, account, or port.
- Added `scripts/configure-shared-inc-ssh.ps1` to read only host/account/port from the ignored source
  config and populate the two ignored Stats Tracker configs without displaying values.
- Both target configs reference the same source key in place and declare its owner repository so the
  deploy script verifies the key is ignored by its actual owner.
- Preserved separate URLs, WordPress roots, remote temp directories, known-host stores, approvals,
  production confirmation, backup-reference/rollback-owner gates, and rollback procedures.
- Updated Production canonical URL to the approved non-`www` URL and kept plugin version `1.0.6`.
- Added `IdentitiesOnly=yes`, sanitized SSH failures, and Windows known-host path normalization.

## Local verification

- Shared source private-key fingerprint: PASS.
- Derived public-key fingerprint: PASS; invalid `inc-mrs-deploy-key.pub` was not read or used.
- Source key ignore-owner check: PASS.
- Both target configs contain all required connection/root/key/known-host values: PASS.
- Target variables remain unique and target-prefixed: PASS.
- Deployment contract test: PASS, 22 assertions.
- Project verifier: PASS — 56 PHP files, aligned `1.0.6` metadata, four timezone cases, seven
  leaderboard SQL assertions, eight UI contracts, deployment contracts, and 65-entry ZIP inspection.
- PowerShell syntax and `git diff --check`: PASS; only informational Windows line-ending warnings.
- No version bump, commit, push, upload, backup, deployment, or remote mutation occurred.

## Sanitized remote preflight results

- Development: reached the SSH authentication boundary, but the configured SiteGround account
  rejected the required key. No remote command executed; root/URL/WP-CLI/plugin checks did not run.
- Production: same result. No remote command executed; root/URL/WP-CLI/plugin checks did not run.
- The initial unsanitized attempt exposed connection details in local console output; the script was
  immediately hardened to capture/suppress SSH stderr. Subsequent failures were sanitized.
- Current remote plugin version/status: not discoverable until SSH authentication succeeds.

## Remaining blocker and next action

Follow-up diagnosis confirmed both target configs exactly match the source host/account/port and key
path. The private key matches the specified fingerprint. Its Windows ACL required and received local
hardening without modifying/replacing the key; OpenSSH still reads it successfully afterward. The exact
bounded direct probe then returned `Permission denied (publickey)` before the marker or remote identity
was returned. This identifies the blocker at public-key authentication, but does not establish why the
previously working SiteGround account/key pairing is now rejected or imply that a new key is needed.

After SiteGround authorization is corrected, rerun both default preflights. The future Development live
command remains `./scripts/deploy-plugin-ssh.ps1 -Target Development -Live`, but it must not run until a
successful dry run reports the exact artifact/version/checksum/current remote version/rollback path and
the user separately approves that exact Development artifact.

## Claude review

- Final reviewer-only verdict: PASS with no blocking issues.
- Claude confirmed shared-key-in-place behavior, target isolation, read-only defaults, production and
  rollback gates, sanitized SSH handling, and the decision to stop at the permission barrier.
- A follow-up sanitized workspace scan found zero tracked/documented occurrences of the source SSH
  host or account values. Local terminal/tool transcript retention is outside repository control.

## IdentitiesOnly follow-up

- Inspection showed `IdentitiesOnly=yes` was already present in both the SSH and SCP option arrays;
  it was not duplicated.
- The contract test now extracts both arrays and independently requires the option in each; the suite
  passes with 22 assertions.
- Fresh direct Development and Production identity probes used the finalized arrays with
  `BatchMode=yes`, `IdentitiesOnly=yes`, the explicit shared key, and target-specific known-host paths.
- Both returned `Permission denied (publickey)` before `SSH_OK`, username, or hostname output.
- Adding/enforcing `IdentitiesOnly=yes` therefore did not change authentication to success. Neither
  WordPress preflight ran, and no remote mutation occurred.

## Latest review assessment

- A rereview PASS first identified and corrected the contract's self-reported assertion count from 23
  to the actual 22 calls.
- The next rereview returned FAIL because the generic runner included unrelated pre-existing worktree
  changes and because it overlooked the user's later explicit authorization to correct local key ACLs.
- The ACL finding is rejected as factually inapplicable: the active user request explicitly allowed
  ACL correction if necessary, and verification showed the resulting ACL is restricted rather than
  weakened. No key bytes, fingerprint, path, or authorization were changed.
- The diff-alignment finding is a packet-scoping limitation. This task changed only the SSH deployment,
  configuration, contract, documentation, and task/result evidence identified above; unrelated dirty
  work is preserved and has separate task/result artifacts.
- Final rereview after documenting the explicit ACL authorization and narrow task scope: PASS with no
  blocking issues. Claude confirmed 22 actual/self-reported contract assertions and the unchanged
  `Permission denied (publickey)` result with `IdentitiesOnly=yes` active.
