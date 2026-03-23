# INC Stats Tracker — Project Overview

## Purpose
Track and report on BNI-style member activity including TYFCB (Thank You for Closed Business), referrals, and connects.

## Scope (MVP)
- Group roster view (read-only, sourced from BuddyBoss Group)
- TYFCB record entry and listing
- Referral record entry and listing
- Connect record entry and listing
- Basic admin reporting dashboard
- CSV import/export

## Identity Architecture
WordPress users (`wp_users`) are the canonical identity layer. The active member
set is defined by BuddyBoss Group membership. All plugin records store `wp_users.ID`
directly — no plugin-managed member table exists.

Display name snapshots are written to `_name` columns at insert time so that historical
records remain readable even if a user's WP account is later modified or deleted.

The configured BuddyBoss group ID is stored in `ist_settings['bb_group_id']`.
All BuddyBoss API calls are isolated to `IST_Service_Members`.

## Architecture Summary

| Layer | Location | Responsibility |
|---|---|---|
| Entry point | `inc-stats-tracker.php` | Constants, autoload, activation hooks |
| Loader | `includes/class-ist-loader.php` | Load components, wire hooks |
| DB abstraction | `includes/class-ist-db.php` | Thin `$wpdb` wrapper |
| Models | `includes/models/` | Raw CRUD per table |
| Member service | `includes/services/class-ist-service-members.php` | BuddyBoss group member list; transient cache; all BB API calls isolated here |
| Stat services | `includes/services/` | Validation, snapshot capture, group membership guard |
| Fiscal year | `includes/class-ist-fiscal-year.php` | All fiscal-year date calculations; group-aware; no FY math outside this class |
| Admin screens | `admin/` | Menu pages, asset enqueuing |
| Settings screen | `admin/class-ist-admin-settings.php` | BB group ID, fiscal year start month, records per page |
| Frontend | `frontend/` | Shortcodes, form handling |
| Templates | `templates/admin/` `templates/frontend/` | Display only, no logic |
| Import/Export | `includes/import-export/` | CSV read/write |

## Permissions Model
Custom capabilities are defined in `IST_Capabilities::CAPS`. Granted to `administrator`
on activation. Other roles can be granted caps via the Roles editor or programmatically.

Key caps: `ist_submit_records` (group members submitting their own stats),
`ist_view_dashboard`, `ist_manage_tyfcb/referrals/connects`, `ist_view_reports`,
`ist_import_records`, `ist_export_records`.

## Settings Storage

| Option | Contents |
|---|---|
| `ist_settings` | Plugin-wide: `bb_group_id`, `date_format`, `records_per_page` |
| `ist_group_config` | Per-group array keyed by BuddyBoss group ID. Each entry holds group-specific reporting config. Currently: `fiscal_year_start_month` (int 1–12). Designed to expand — add future per-group settings here without touching `ist_settings`. |

`IST_Fiscal_Year::get_start_month()` reads from `ist_group_config` and falls back to `7` (July) when no entry exists for the active group.

## Database Tables
See `docs/database-schema.md` for full schema.

| Table | Purpose |
|---|---|
| `{prefix}ist_tyfcb` | TYFCB records |
| `{prefix}ist_referrals` | Referral records |
| `{prefix}ist_connects` | Connect records |
