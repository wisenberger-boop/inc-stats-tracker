# INC Stats Tracker — Plugin Release Checklist

> **Important:** Release ZIPs must only ever be built from `plugin/inc-stats-tracker/`.
> Never ZIP from the project root — it contains docs, project-management, and build artifacts
> that must not ship to production.

---

## 1. Pre-Build: Version and Changelog

- [ ] Update `IST_VERSION` in `plugin/inc-stats-tracker/inc-stats-tracker.php`
- [ ] Update `set VERSION=` in `tools/package-plugin.bat` to match exactly
- [ ] Update `CHANGELOG.md` — new version heading, date, and summary of changes
- [ ] Update `readme.txt` — `Stable tag:`, changelog entry, and upgrade notice if needed
- [ ] Confirm all three version strings match: plugin file, bat file, CHANGELOG heading

---

## 2. Pre-Build: Folder Hygiene

- [ ] No `.csv` files in `plugin/inc-stats-tracker/docs/source-assets/csv/`
  *(These are runtime import staging files — they must not ship)*
- [ ] No leftover debug output, `error_log()` calls, or `var_dump()` in any PHP files
- [ ] No `.DS_Store`, `Thumbs.db`, or other OS artifacts in the plugin folder
- [ ] `plugin/inc-stats-tracker/` contains only runtime/release files — no project-management,
  build artifacts, or canonical source docs from the project root

---

## 3. Build

- [ ] Run `tools\package-plugin.bat`
- [ ] Script exits with "Done" — no errors reported
- [ ] ZIP created at `build/releases/inc-stats-tracker-{VERSION}.zip`

---

## 4. ZIP Inspection

Open the ZIP and verify its contents before deploying anything:

- [ ] Top-level folder is exactly `inc-stats-tracker/` (no version suffix, no nesting)
- [ ] ZIP does **not** contain `.git/` or any `.gitkeep` files at unexpected locations
- [ ] ZIP does **not** contain `docs/project-management/`, `build/`, or `tools/` folders
- [ ] ZIP does **not** contain any `.csv` files anywhere
- [ ] ZIP does **not** contain `node_modules/`, `vendor/`, or other dev-only dependency folders
- [ ] Main plugin file `inc-stats-tracker/inc-stats-tracker.php` is present and has the correct version header

---

## 5. Production Backup

- [ ] Take a full site backup (files + database) before touching anything on production
- [ ] Note the current active plugin version on production before deploying

---

## 6. Deployment

- [ ] Upload ZIP via WordPress admin (Plugins > Add New > Upload) or SFTP
- [ ] If updating over an existing version: deactivate → update → reactivate, or use WP update flow
- [ ] Confirm activation succeeds with no PHP errors in the admin

---

## 7. Post-Deployment Verification

- [ ] Admin: plugin version shown matches the deployed version
- [ ] Admin > INC Stats > Dashboard: all three record counts show real numbers (not dashes or zeros)
- [ ] Admin > INC Stats > TYFCB: list loads, source column visible, delete action present
- [ ] Admin > INC Stats > Referrals: list loads, source column visible, delete action present
- [ ] Admin > INC Stats > Connects: list loads, source column visible, delete action present
- [ ] Admin > INC Stats > Reports: lifetime totals show real counts and amounts
- [ ] Frontend: My Stats page loads for a test member
- [ ] Frontend: Group Stats page loads

---

## 8. Historical Data Import (if applicable)

- [ ] Copy canonical CSVs from project root `docs/source-assets/csv/` into
  `plugin/inc-stats-tracker/docs/source-assets/csv/` on the server or locally before import
- [ ] Run Historical Data Import from Admin > INC Stats > Import / Export
- [ ] Confirm import row counts match expectations
- [ ] Remove all `.csv` files from `docs/source-assets/csv/` inside the plugin after import
- [ ] Verify Dashboard counts updated to reflect imported data
- [ ] If re-importing: use Purge Imported Records first, then re-run import

---

## 9. Sign-Off

- [ ] No PHP errors or warnings in site error log after deployment
- [ ] Release ZIP archived in `build/releases/` for reference
- [ ] CHANGELOG committed and pushed
