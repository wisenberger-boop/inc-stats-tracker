=== INC Stats Tracker ===
Contributors: William Isenberger
Tags: stats, reporting, buddyboss, community, membership
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.1-rc1
License: Proprietary
License URI: https://axisweb.com

Native stats entry and reporting for a BuddyBoss-powered business-networking community. Replaces a legacy external workflow with a self-contained WordPress system.

== Description ==

INC Stats Tracker is a custom WordPress plugin built for a BuddyBoss-powered membership and community environment. It is designed for private/custom deployment within a specific business-networking community and is not intended for general public distribution.

The plugin replaces a legacy workflow that relied on external Google Forms, Google Sheets, Zapier automation, and Looker Studio embedded dashboards with a fully self-contained system running inside WordPress.

**Member-Facing Features**

* Native entry forms for three activity types:
  * **Connects** — one-to-one meetings with other members or outside contacts
  * **Referrals** — referrals given to other members or received from outside the group
  * **Closed Business** — business closed with attribution to the group member or source who contributed to it
* My Stats dashboard: a member-specific reporting view showing personal activity, fiscal-year trends, and recent records
* Inline help/info guidance within each form to assist members with accurate data entry
* Direct-access fallback forms for members accessing forms outside of the standard modal workflow

**Group and Admin Reporting**

* Group Stats / INC Stats Reports: group-wide and fiscal-year reporting views with leaderboards, KPI summaries, and monthly trend charts
* Enhanced attribution support for Closed Business records, including lineage tracking and revenue relationship classification

**Historical Data Migration**

* CSV import tools for migrating legacy data from the prior external workflow into the WordPress database
* Source-aware record handling: imported historical records are tagged separately from native plugin submissions, enabling safe re-import and reset workflows without touching live member data
* Admin maintenance utilities for running, resetting, and purging imported data independently of native submissions

**BuddyBoss Integration**

* Designed for use in a BuddyBoss Platform Pro / BuddyBoss Theme environment
* Member lookup and group-context resolution via BuddyBoss APIs
* Reporting views are accessible within the BuddyBoss member profile and group tab structure

This plugin is a purpose-built replacement system for a specific organization's stats workflow. It is not a general-purpose stats or reporting tool and is maintained as a private plugin.

== Installation ==

This plugin is intended for custom/private deployment. It is not distributed through the WordPress Plugin Directory.

1. Upload the `inc-stats-tracker` folder to the `/wp-content/plugins/` directory, or install the plugin zip via **Plugins > Add New > Upload Plugin** in the WordPress admin.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **INC Stats** in the WordPress admin menu to access settings, import tools, and admin reporting.
4. Configure the plugin under **INC Stats > Settings**: set the BuddyBoss group ID, fiscal year start month, and any form URL overrides.
5. If migrating from the legacy workflow, use **INC Stats > Import Historical Data** to import legacy CSV exports before members begin submitting new records natively.

**Requirements**

* WordPress 6.0 or later
* PHP 8.0 or later
* BuddyBoss Platform Pro (for member lookup and group-context features)
* BuddyBoss Theme (recommended; plugin includes modal and layout compatibility rules for BuddyBoss Theme)

== Frequently Asked Questions ==

= Is this plugin available on the WordPress Plugin Directory? =

No. This is a custom plugin built for a specific private deployment. It is not listed on WordPress.org and is not intended for general distribution.

= What replaces the old Google Forms / Zapier / Looker Studio workflow? =

INC Stats Tracker provides native WordPress entry forms for Connects, Referrals, and Closed Business activity. Reporting views (My Stats and Group Stats) replace the Looker Studio dashboards. Historical data from the legacy workflow can be imported via the included CSV import tools.

= Can historical data be safely imported without affecting new records? =

Yes. The plugin uses source-aware record tagging. Records imported from legacy CSV files are stored with a distinct marker (`data_source = 'import'`), separate from records entered natively through the plugin forms (`data_source = 'native'`). The import admin page includes a **Purge Imported Records** tool that removes only imported rows, leaving all native submissions intact. This allows safe re-import testing and clean migration workflows.

= What happens if the plugin is reactivated on a site where the admin already has capabilities? =

Capability grants are idempotent — calling `add_caps()` on an account that already has the capabilities is a no-op. Reactivation is safe.

= Does this plugin work without BuddyBoss? =

Partially. The core form submission and database logic does not depend on BuddyBoss. However, member lookup, group-context resolution, and the BuddyBoss profile tab integration all require BuddyBoss Platform. The plugin is not tested or supported in non-BuddyBoss environments.

= Where are database tables stored? =

The plugin creates three custom tables in the WordPress database:
`{prefix}ist_tyfcb`, `{prefix}ist_referrals`, `{prefix}ist_connects`.
Tables are created on activation and updated safely on plugin upgrade via `dbDelta`.

== Screenshots ==

1. My Stats — member-facing dashboard with KPI summary, fiscal-year trend charts, and recent records.
2. Group Stats / INC Stats Reports — group-wide leaderboards, KPI totals, and monthly activity charts.
3. Closed Business entry form — with enhanced attribution fields and inline help guidance.
4. Historical Data Import admin page — source file status, run import, purge and reset utilities.

== Changelog ==

= 1.0.1-rc1 =
Post-launch admin hardening release.

* Fixed admin list pages for TYFCB, Referrals, and Connects — records now display correctly (blank pages were caused by a stale ORDER BY column reference in the model layer).
* Fixed admin Dashboard — stat cards now show live record counts instead of placeholder dashes.
* Fixed admin Reports — page now displays real lifetime totals (record counts and closed business amount) for the full system rather than hardcoded zeros. Reports represents all-time totals, not fiscal-year filtered data.
* Added Source column to TYFCB, Referrals, and Connects admin list pages — each row is tagged "import" (amber) or "native" (green) so administrators can immediately distinguish historical imported records from live member submissions.
* Added single-record Delete action to TYFCB, Referrals, and Connects admin list pages — nonce-protected, capability-protected, requires browser confirmation before proceeding, and returns a clear success or error notice after deletion.

= 1.0.0-rc.1 =
Initial production release candidate. See full development history in CHANGELOG.md.

* First production-ready release, replacing the legacy external stats workflow.
* Native entry forms for Connects, Referrals, and Closed Business activity.
* My Stats member dashboard and Group Stats reporting views with fiscal-year trend charts and leaderboards.
* Enhanced Closed Business attribution model with revenue relationship and lineage tracking.
* Historical CSV import with source-aware record tagging: imported records are distinguished from native submissions at the database level.
* Admin maintenance tools: run/reset/purge import workflows independently of live member data.
* Legacy row migration utility for pre-source-tagged dev/staging environments.
* BuddyBoss member profile tab and group-context integration.
* Direct-access fallback forms for non-modal entry paths.
* Inline help/info guidance on all three entry forms.
* Full fiscal-year reporting including monthly trend charts; zero-value months rendered as visible stubs.
* UI polish: section spacing parity between My Stats and Group Stats; fieldset legend positioning; form containment and modal close-button fixes.
* Custom capability registration on activation; cleanup on deactivation.

== Upgrade Notice ==

= 1.0.1-rc1 =
Admin hardening release. No database schema changes. Safe to deploy over any 1.0.0-rc.1 installation without deactivation.

= 1.0.0-rc.1 =
Initial production release. If upgrading from a development build, deactivate and reactivate the plugin once to ensure custom capabilities are correctly granted to the administrator role.
