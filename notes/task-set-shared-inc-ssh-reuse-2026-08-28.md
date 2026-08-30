# Task Set — Reuse Authorized INC SiteGround SSH Identity

Date: 2026-08-28
Status: Approved by the user in the originating request

## Goal

Update INC Stats Tracker deployment setup to reference the existing INC Meeting Roles Scheduler
SiteGround private key and source SSH host/account/port while preserving independent target state,
then run local QA and bounded read-only preflights only.

## Acceptance criteria

- Verify the required private-key fingerprint without displaying private content or config values.
- Reference the source key in place; do not generate, copy, replace, delete, or reauthorize keys.
- Populate separate ignored Development/Production configs from the source env.
- Preserve distinct canonical URLs, roots, temp directories, known-host stores, approvals, production
  confirmation, backup/rollback gates, and rollback procedures.
- Use the explicit Development and Production URLs/roots supplied by the user.
- Update docs/contracts and keep plugin release version unchanged.
- Run local contracts/project verification and read-only SSH/WP-CLI preflights for both targets.
- Do not upload, install, activate, back up, deploy, or mutate either remote environment.

## Authorized diagnostic follow-up

The user subsequently and explicitly authorized checking whether Windows OpenSSH accepts the existing
private key and correcting its local ACLs if necessary, without modifying or replacing the key. The
same follow-up required `IdentitiesOnly=yes` in both SSH and SCP arrays and bounded identity probes.

Review scope is limited to the SSH deployment/configuration helper, deploy script, deployment contract,
deployment documentation, and matching task/results notes. Other dirty-worktree changes belong to prior
separately documented and reviewed workstreams and are not changes made by this follow-up.
