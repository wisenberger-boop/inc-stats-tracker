# SSH Deployment

INC Stats Tracker deploys as a plugin-only ZIP over SSH/WP-CLI. Development and production have
separate ignored configuration, WordPress roots, temporary directories, host-key stores, and
approvals. They reuse the verified INC SiteGround SSH identity from the Inclusive Networking Content
Manager project by reference. A development approval never authorizes production.

## Private target configuration

The ignored files are:

- `.local/deploy-dev.env`
- `.local/deploy-production.env`
- `.local/known-hosts-development`
- `.local/known-hosts-production`

Never copy these files into documentation, Git, release ZIPs, logs, or another INC project. Each
environment file has target-prefixed values for its canonical URL, SSH host/account/port,
WordPress root, temporary directory, shared-key reference, key-owner repository, and target-specific
host-key store. The WordPress root must be the absolute directory containing `wp-config.php`,
`wp-admin/`, and `wp-content/`.

The first authorized connection writes the target host key into that environment's ignored
`known_hosts` file with `accept-new`. Every later connection requires the pinned key and fails if
it changes. Investigate a changed host key with the hosting provider; never delete the pin merely
to make a warning disappear.

Production live mode also requires a sanitized SiteGround backup reference and named rollback
owner in the ignored production configuration. The reference must identify a recent full-site
files-and-database restore point; its value is checked locally but never printed.

Run `scripts/configure-shared-inc-ssh.ps1` to resolve the ignored deploy environment referenced by
the verified Content Manager runner, copy only its SiteGround host, account, and port into the two
ignored target configs, and reference the Content Manager private key in place. The helper verifies
the expected host, private-key fingerprint, runner/config/key ignore rules, and preserves any existing
target-specific backup reference and rollback owner. Never copy or commit the key.

Configured roots and canonical URLs:

- Development: `https://dev.inclusivenetworkingcoalition.org` at
  `/home/customer/www/dev.inclusivenetworkingcoalition.org/public_html`
- Production: `https://www.inclusivenetworkingcoalition.org` at
  `/home/customer/www/inclusivenetworkingcoalition.org/public_html`

## Read-only discovery

After configuring an SSH host, account, port, and key, locate candidate roots without mutation:

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Development -Discover
.\scripts\deploy-plugin-ssh.ps1 -Target Production -Discover
```

Verify the candidate belonging to the expected canonical URL, then save that root only in the
matching ignored environment file.

## Dry-run release preflight

For a bounded connection and remote-state check that does not build an artifact:

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Development -ConnectionOnly
.\scripts\deploy-plugin-ssh.ps1 -Target Production -ConnectionOnly
```

This checks authentication, the configured WordPress root and canonical URL, `wp` availability,
and only the Stats Tracker plugin version/status.

For full local artifact planning, dry run is the default and must precede every deployment:

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Development
.\scripts\deploy-plugin-ssh.ps1 -Target Production
```

Preflight validates the explicit target, ignored private paths, SSH identity, exact WordPress
root and canonical URL, WP-CLI, the Stats Tracker plugin version and activation state, local lint and
regression checks, two-build reproducibility, ZIP structure, release version consistency, SHA-256,
and a target-specific plugin-backup path. It reads no users, content, registrations, payments,
member records, credentials, cron, caches, mail configuration, or database rows other than the
public `siteurl` option and version/status metadata.

## Approval and deployment

Before `-Live`, record the target URL, artifact path/version/checksum, WordPress/plugin paths,
current remote plugin version/status, rollback archive path, and exact command. Obtain explicit
approval for that exact target and artifact in the active session.

Development command shape:

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Development -Live -SkipBuild `
  -ArtifactPath "<exact-approved-zip>" -DeploymentId <approved-YYYYMMDD-HHMMSS>
```

Production requires a new approval after development smoke testing and an extra guard:

```powershell
.\scripts\deploy-plugin-ssh.ps1 -Target Production -Live -ConfirmProduction -SkipBuild `
  -ArtifactPath "<exact-reviewed-and-approved-zip>" -DeploymentId <approved-YYYYMMDD-HHMMSS>
```

Live mode refuses to rebuild implicitly: the exact verified ZIP and deployment ID used in the approved
dry run must be supplied with `-SkipBuild -ArtifactPath -DeploymentId`. This keeps the planned rollback
archive path identical between dry run and live execution. The approval must name that artifact,
checksum, target, rollback path, and command in the current session. Production additionally requires
configured backup/rollback metadata.

Live mode creates a timestamped, plugin-only remote `tar.gz` rollback archive, uploads the verified
ZIP to that target's temporary directory, and runs `wp plugin install ... --force --activate` for
`inc-stats-tracker` only. It never pushes a site, database, uploads directory, theme, settings,
users, content, cron, caches, roles, capabilities, or mail configuration.

## Rollback and smoke testing

Rollback trigger: activation failure, version mismatch, fatal/PHP error, broken My Stats or Group
Stats rendering, REST failure, or a material BuddyBoss integration regression. Stop testing and
request separate rollback approval. Rollback removes only `wp-content/plugins/inc-stats-tracker`,
extracts the recorded pre-deploy archive into `wp-content/plugins`, and verifies/reactivates the
restored version with WP-CLI. Never run an unrecorded or cross-environment backup.

After an approved deployment, bounded checks are limited to plugin version/status, public REST
index health, authenticated My Stats and Group Stats rendering with an authorized test account,
the protected summary endpoint's expected authentication behavior, relevant PHP error logs, and
presence of the rollback archive. Do not inspect unrelated/private records or submit test data
without separate authorization.
