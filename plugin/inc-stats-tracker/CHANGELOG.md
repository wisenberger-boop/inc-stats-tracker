# Changelog — INC Stats Tracker

All notable changes to this plugin are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versions follow [Semantic Versioning](https://semver.org/).

---

## [Planned — Next Release]

The following items are identified for the next release cycle but are not yet implemented.

- **Edit record support** — inline or page-based editing for TYFCB, Referral, and Connect records to correct member mistakes without deleting and re-entering.
- **Bulk delete / batch utilities** — select and delete multiple records at once from the admin list pages, with appropriate capability and confirmation guards.
- **Admin notice for imported-row deletion** — a visible warning in the admin list when an admin attempts to delete a row tagged `data_source = 'import'`, clarifying that the row will not be re-imported unless the import hash is also cleared.

---

## [1.0.1-rc1] — 2026-03-30

### Fixed — Admin list pages blank due to stale ORDER BY column name

**Root cause:** All three model `all()` methods (`IST_Model_TYFCB`, `IST_Model_Referral`, `IST_Model_Connect`) passed `'recorded_at DESC'` as the ORDER BY clause to `IST_DB::get_rows()`. The column `recorded_at` has never existed in the schema — the real column is `created_at`. MySQL threw `Unknown column 'recorded_at' in 'order clause'`; `$wpdb->get_results()` returned `null`; the fallback `?: array()` produced an empty result set; all three admin list pages rendered as "No records found" regardless of actual data.

**Fix in `class-ist-model-tyfcb.php`, `class-ist-model-referral.php`, `class-ist-model-connect.php`:** Changed ORDER BY from `'recorded_at DESC'` to `'entry_date DESC, id DESC'` in the `all()` method of each model. Using `entry_date` (user-supplied activity date) matches the reporting layer's sort order and is more useful in the admin list context than the insert timestamp.

### Added — Source column and Delete action on admin record list pages

**TYFCB, Referrals, and Connects admin list pages now show:**

**Source column** — displays `import` (amber tag) or `native` (green tag) for each row, drawn from the `data_source` column added in 0.2.26. Lets admins immediately see whether a record arrived via historical CSV import or was submitted natively through the plugin.

**Delete row action** — a Delete link appears in the first column on hover (standard WordPress `.row-actions` pattern). Clicking requires a JS confirm dialog before proceeding. The delete request is:
- Handled by `admin_post_ist_delete_{tyfcb|referral|connect}`
- Capability-checked (`ist_manage_{tyfcb|referrals|connects}`)
- Nonce-verified (`ist_delete_{type}_{id}` — per-record nonce, cannot be replayed for a different row)
- Scoped to a single row by ID; no other rows are affected
- Affects both imported and native rows equally

After deletion, the page redirects back to the list with a dismissible success or error notice. On success the page reloads fresh — no stale data in the view.

**CSS additions in `ist-admin.css`:** `.ist-source-tag`, `.ist-source-tag--import` (amber), `.ist-source-tag--native` (green), `.ist-tag` base style moved from inline to stylesheet.

---

### Fixed — Admin Reports tab showing zeros (pure stub, never wired to database)

**Root cause:** `IST_Admin_Reports::page_reports()` contained only a `// TODO` comment and hardcoded all three totals to `0`. No database queries were ever executed. The template rendered those zeros correctly — the controller was the entire problem.

**Fix in `class-ist-admin-reports.php`:** Replaced hardcoded zeros with direct `COUNT(*)` and `SUM(amount)` queries against all three tables. Counts both imported historical records and native submissions — this is an admin view and should reflect the full database state.

**Fix in `tmpl-reports.php`:** Updated to use the new `tyfcb_count` / `tyfcb_amount` keys, added a Closed Business amount row, and applied `number_format()` / `ist_format_currency()` for consistent display.

---

### Fixed — Admin dashboard showing placeholder dashes instead of live record counts

**Root cause:** `IST_Admin::page_dashboard()` called `ist_get_template()` with no variables. The dashboard template had `<p class="ist-card__count">—</p>` hardcoded in all three stat cards — a placeholder that was never replaced with a live query.

**Fix in `class-ist-admin.php`:** Controller now fetches total row counts from all three tables via `$wpdb->get_var()` and passes a `$counts` array to the template.

**Fix in `tmpl-dashboard.php`:** Stat card counts now render `$counts['tyfcb']`, `$counts['referrals']`, `$counts['connects']` via `number_format()`.

---

## [1.0.0-rc.1] — 2026-03-29

Initial release candidate. Encompassed all development work from 0.2.19 through 0.2.28 — see those entries for the full development history. Post-RC admin hardening fixes are documented under 1.0.1-rc1 above.

---

## [0.2.28] — 2026-03-29

### Fixed — Custom capabilities not granted on activation

**Root cause:** `IST_Capabilities::add_caps()` was never called from `IST_Activator::activate()`, despite the docblock in `class-ist-capabilities.php` stating "Custom caps are granted/removed on activation/deactivation via IST_Activator." On a fresh WordPress installation, the administrator role would not have any `ist_*` capabilities, causing every capability-guarded admin page (dashboard, reports, TYFCB/referrals/connects management, import) to deny access immediately after activation.

The issue did not surface in dev because the developer's admin account had capabilities already stored in the role from prior development cycles.

**Fix in `class-ist-activator.php`:** Added `IST_Capabilities::add_caps()` call to `activate()`.

**Fix in `class-ist-deactivator.php`:** Added `IST_Capabilities::remove_caps()` call to `deactivate()` — caps are now cleaned up on plugin deactivation as originally intended.

---

## [0.2.27] — 2026-03-29

### Added — Pre-0.2.26 legacy row migration utility

**Problem:** When the `data_source` column was added in 0.2.26, `dbDelta` backfilled the column DEFAULT (`'native'`) on all existing rows. Dev/staging installs that imported historical data before 0.2.26 therefore had all rows tagged `'native'`, making the Purge Imported Records tool find nothing.

**Solution:** A one-time migration utility that re-tags `data_source='native'` rows as `'import'`, visible only when such rows exist, and intended only for environments where all existing rows are pre-0.2.26 historical imports (no genuine native member submissions yet).

#### Changes

**`class-ist-historical-importer.php`** — two new public methods:
- `get_legacy_native_count()`: `SELECT COUNT(*) WHERE data_source = 'native'` summed across all three tables. Used by the controller to conditionally show the migration section.
- `mark_legacy_as_imported()`: `UPDATE … SET data_source='import' WHERE data_source='native'` on all three tables. Returns per-table row counts.

**`class-ist-admin-import.php`** — `handle_mark_legacy()` POST handler. Calls `mark_legacy_as_imported()`, redirects with `legacy_marked={total}` query arg. Controller now passes `$legacy_native_count` to template.

**`ist-hooks.php`** — registered `admin_post_ist_mark_legacy_as_imported`.

**`templates/admin/tmpl-import.php`**:
- `legacy_marked` notice banner (shows re-tagged row count after redirect).
- **Mark Legacy Rows as Imported** section — conditional on `$legacy_native_count > 0`, so it disappears automatically once there are no more `'native'` rows. Includes inline warning notice, row count, explanation, JS confirm with explicit caveat about native submissions.
- Section uses a secondary (non-delete) button style to distinguish it visually from the destructive Purge action.

**Lifecycle on dev:** Mark Legacy → rows flip to `'import'` → section disappears → Purge Imported Records works correctly → re-import cleanly.

#### QA validated (dev environment — 2026-03-29)

End-to-end workflow confirmed on dev after 0.2.26 + 0.2.27 deploy:

- **Mark Legacy → Purge → Re-import cycle:** Pre-0.2.26 imported rows re-tagged via Mark Legacy utility; Purge Imported Records successfully deleted all imported rows; import re-ran cleanly with correct `data_source='import'` tagging on all newly inserted rows.
- **Native submission safety:** Member form submissions made after tagging were unaffected by Purge — `data_source='native'` rows are not touched by the purge query.
- **Re-import idempotency:** Re-running the import after purge produced the expected row counts with no duplicates (hash store is cleared as part of purge).

---

## [0.2.26] — 2026-03-29

### Added — data_source column and Purge Imported Records utility

**Purpose:** Safely distinguish imported historical records from native plugin submissions at the database level. Once the production database contains both imported seed data and live member submissions, there must be a way to reset or re-import without touching live records.

#### Schema changes (`class-ist-activator.php`)

Added `data_source VARCHAR(20) NOT NULL DEFAULT 'native'` to all three tables:
- `wp_ist_tyfcb`
- `wp_ist_referrals`
- `wp_ist_connects`

Indexed (`KEY data_source`) on each table for efficient `WHERE data_source = 'import'` deletes. Column added via additive `dbDelta` — safe on existing installs, picked up automatically via `IST_Activator::maybe_upgrade()` on admin load.

**Default value `'native'`:** Any row inserted without specifying `data_source` (i.e. all existing native form submissions) gets `'native'` automatically. No service layer changes required.

#### Importer changes (`class-ist-historical-importer.php`)

All three importers (`import_tyfcb`, `import_referrals`, `import_connects`) now include `'data_source' => 'import'` in every insert data array.

**New method `purge_imported()`:** Deletes all rows where `data_source = 'import'` from all three tables using `$wpdb->delete()`, then calls `reset_hashes()` to clear the hash store so the import can be run again cleanly. Returns `{ tyfcb, referrals, connects }` row counts. Native submissions (`data_source = 'native'`) are never touched.

#### Admin changes

**`class-ist-admin-import.php`:** New `handle_purge_imported()` POST handler. Calls `purge_imported()`, passes total row count through redirect query arg `purge_done`.

**`ist-hooks.php`:** Registered `admin_post_ist_purge_imported_records` action.

**`templates/admin/tmpl-import.php`:**
- Added `purge_done` notice banner (shows deleted row count after redirect).
- Added **Purge Imported Records** section below the existing Reset Import History section — separate `<form>` with JS confirm, nonce-protected, styled as a danger action.

---

## [0.2.25] — 2026-03-28

### Fixed — Fieldset legend visual positioning across all three forms

**Root cause:** HTML `<legend>` is always centered on the fieldset's top border by the browser — it cannot be repositioned with padding or margin alone. All prior attempts (padding-top on fieldset, margin-bottom on legend) moved the content but left the legend straddling the border, making the title appear embedded in the box edge rather than labeling it from above.

#### Changes in `assets/css/ist-frontend.css`

**`.ist-fieldset`**: added `position: relative` (positioning context for the absolute legend) and `margin-top: 46px` (creates space above the box for the legend to float into, plus breathing room between sections).

**`.ist-fieldset legend`**: changed to `position: absolute; top: 0; left: 0; transform: translateY(-100%)` — legend's bottom edge aligns with the fieldset's top border, placing the legend fully above the gray box. Added `padding-bottom: 6px` for a visual gap between the legend text and the box edge. The fieldset border now draws fully across the top (no browser notch). Applies consistently across Connects, Referrals, and Closed Business fieldsets.

**`.ist-fieldset > legend + *`**: added `margin-top: 12px !important` — adds internal spacing between the legend area and the first radio/input row inside the box.

---

## [0.2.24] — 2026-03-28

### Fixed — Form content containment across all three forms

#### Root causes

Three independent issues combined to make form content appear to extend past its background panel:

**1. Width mismatch between form-wrap and form**
`.ist-form-wrap { max-width: 560px }` contained `.ist-form { max-width: 520px }` — a 40px discrepancy. Elements outside `<form>` but inside the wrapper (notices: green/red banners) rendered at 560px, while all form fields rendered at 520px. Against any background that spanned the form-wrap width, the form fields appeared narrower than the container.

**2. No card treatment on form-wrap**
`.ist-form-wrap` had no `background`, `padding`, or `border-radius`. On standalone form pages (direct URL navigation, not modal), the form relied on BuddyBoss's page layout card for visual containment — but the BuddyBoss card's width/padding didn't align with the form's own element widths, making content appear to escape the intended box.

**3. Fieldset browser-default `min-width: min-content`**
HTML `<fieldset>` elements have a browser-default of `min-width: min-content` — they don't shrink below their content's intrinsic width. Without `min-width: 0`, any fieldset containing a long legend, radio label, or wide radio group could overflow its parent container on narrow screens or in the modal, visibly escaping the white panel.

#### Changes in `assets/css/ist-frontend.css`

**`.ist-form-wrap`**: now the definitive card container for standalone context. Added `background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px`. All form content (notices, submitter line, fieldsets, inputs, submit button) sits inside this card.

**`.ist-modal__body .ist-form-wrap`**: overrides card styling to `background: transparent; border: none; border-radius: 0; padding: 0` — the modal panel already provides the card treatment; form-wrap inside the modal is transparent. Removed the now-superseded duplicate rule that previously only set `max-width: 100%`.

**`.ist-form { max-width: 100% }`**: removed the 520px constraint. Form now fills its container (either the 24px-padded card or the modal body) at consistent width. Notices and form fields are now the same width.

**`.ist-fieldset`**: added `min-width: 0; width: 100%; box-sizing: border-box` — overrides the browser default `min-width: min-content` that caused fieldsets to overflow their container on narrow viewports or in the modal. `box-sizing: border-box` ensures fieldset padding is included in the declared width.

#### Why earlier modal passes didn't fully solve this

0.2.22 fixed the modal close button (scrolled away) and 0.2.22/0.2.19 improved layout/font. But neither pass addressed the `.ist-form { max-width: 520px }` vs `.ist-form-wrap { max-width: 560px }` mismatch, the absent card treatment on form-wrap for standalone pages, or the fieldset overflow risk. Those three were only visible once the modal structure was otherwise clean.

---

## [0.2.23] — 2026-03-28

### Fixed — Section spacing parity: My Stats now matches Group Stats

#### Root cause

The spacing improvements in 0.2.22 used `.ist-leaderboard h3` selectors, which only target the leaderboard partial used on **Group Stats**. My Stats uses `tmpl-recent-records.php`, whose stacked sections render under `.ist-recent-records h3` — a separate CSS block that was not updated. My Stats therefore kept the old pattern: `margin-top: 32px` on h3 with no `border-top` rule and `margin-bottom: 0` on tables.

#### Changes in `assets/css/ist-frontend.css`

**My Stats — `.ist-recent-records` block (parity fix):**
- `.ist-recent-records h3`: changed to `margin: 0; padding: 28px 0 8px; border-top: 1px solid #e2e8f0` — matches the leaderboard section-break pattern exactly
- `.ist-recent-records h3:first-child`: overrides to `padding-top: 0; border-top: none` — no rule above the first heading since the surrounding `margin-top: 32px` on the wrapper already provides separation
- `.ist-recent-records .ist-table`: `margin-bottom` changed from `0` → `24px`

**Group Stats — `.ist-leaderboard` block (spacing increase):**
- `.ist-leaderboard h3`: `padding-top` bumped from `22px` → `28px`
- `.ist-leaderboard .ist-table`: `margin-bottom` bumped from `20px` → `24px`

Both pages now use the same rhythm: 24px below each table → border-top rule → 28px above heading text.

---

## [0.2.22] — 2026-03-28

### Fixed — Section spacing, group stats label, modal close-button containment

#### 1. Leaderboard section spacing

**Root cause:** `.ist-leaderboard .ist-table` had `margin-bottom: 0`, so the only gap between a table's last row and the next section header was the h3's `margin-top: 36px`. No visual rule above the non-first h3s meant the space looked like loose whitespace rather than a deliberate section break.

**Fix in `assets/css/ist-frontend.css`:**
- `.ist-leaderboard h3` now uses `border-top: 1px solid #e2e8f0` and `padding-top: 22px` instead of `margin-top: 36px`. This gives each non-first section header a clear top rule with breathing room, matching the `.ist-section-divider` treatment used elsewhere.
- `.ist-leaderboard h3:first-child` resets `padding-top: 0; border-top: none` — the section divider above already provides separation.
- `.ist-leaderboard .ist-table` `margin-bottom` changed from `0` to `20px` — provides a gap between the table's last row and the section rule above the next header.

#### 2. "Referrals You Gave" label on Group Stats

**Root cause:** `tmpl-group-stats-reports.php` passed `'Referrals You Gave'` to the KPI row partial — a personal-voice label correct for My Stats but wrong in a group/global reporting context. Every other referral label on the Group Stats page already uses "Referrals Given" (leaderboard column, YTD comparison card, chart dataset label).

**Fix in `templates/frontend/tmpl-group-stats-reports.php`:** Changed the KPI row label from `'Referrals You Gave'` to `'Referrals Given'` (line 94). `tmpl-profile-my-stats.php` continues to use `'Referrals You Gave'` unchanged.

#### 3. Closed Business modal close-button containment

**Root cause:** `.ist-modal__panel` had `overflow-y: auto` and `.ist-modal__close` was `position: absolute; top: 12px; right: 14px`. Because `position: absolute` is positioned relative to the scrollable container's padding edge (not the visible viewport edge), the close button scrolled away as the TYFCB form content scrolled down. Additionally, `top: 12px` placed the button inside the 32px top padding zone — visually detached from the form content start line.

**Fix in `assets/css/ist-frontend.css`:**
- `.ist-modal__panel` converted to `display: flex; flex-direction: column; overflow: hidden`. Panel no longer scrolls.
- `.ist-modal__close` changed to `position: static; align-self: flex-end; margin: 10px 12px 0` — the button is now a natural flex item in the column header area, always visible regardless of scroll position.
- `.ist-modal__body` added: `flex: 1; min-height: 0; overflow-y: auto; padding: 12px 32px 32px` — only the form body scrolls independently.

---

## [0.2.21] — 2026-03-28

### Fixed — Referrals & Connects bar chart missing early FY months

#### Root cause

The `ref-con-comparison` bar chart appeared to have missing data for earlier fiscal year months (e.g., Jul–Nov 2025) because Chart.js renders 0-value bars with a height of exactly 0 — producing invisible bars. The x-axis labels for those months were present, but no bar was drawn.

By contrast, the `business-trend` line chart is not affected: a line always passes through y=0 for those months and remains visually present even when the value is 0.

The PHP data pipeline is correct:
- `fy_monthly_trend()` always emits one bucket per elapsed FY month, with `ref_count` and `con_count` as explicit integers (including 0) for every month
- The template builds both charts from the same `$fy_monthly_data` array with identical `array_column()` calls — both charts have the same number of labels and data points
- There is no key mismatch, sparse merge bug, or label/dataset length mismatch

#### Fix

Added `minBarLength: 2` to both datasets in the `ref-con-comparison` chart config in `buildChartConfig()`. This ensures every month renders at least a 2px stub bar even when the count is 0, making the full FY month sequence visually present. Tooltips continue to show the correct count (0 or actual).

Affects both My Stats and Group Stats pages, which both use the `ref-con-comparison` chart type.

The timezone fix in `fy_monthly_trend()` (`DateTime::modify('last day of this month')`) is unchanged.

---

## [0.2.20] — 2026-03-27

### Fixed — Help popup overflow, leaderboard spacing, encoding corruption, icon toggle

#### 1. Help popup overflow and close button layout

- `.ist-help-popup` gains `max-height: 320px; display: flex; flex-direction: column; overflow: hidden` — popup container no longer grows unbounded.
- `.ist-help-popup__body` gains `flex: 1; min-height: 0; overflow-y: auto` — body scrolls internally when content is long; close button stays fixed in the header.

#### 2. Help popup viewport clamping

`openHelp()` in `initFieldHelp()` now clamps the popup to the visible viewport: if placing the popup below the button would exceed `window.innerHeight - 8`, the popup flips above the button instead (`rect.top - maxPopupHeight - 8`, clamped to a minimum of 8px from the top edge).

#### 3. Help icon toggle behavior

`initFieldHelp()` now tracks the active button with a `$activeBtn` variable. Clicking the same icon that opened the popup closes it (toggle). Behavior matrix:
- First click on icon → open popup, set `$activeBtn`
- Second click on same icon → close popup, clear `$activeBtn`
- Click X / Escape / outside → close popup, clear `$activeBtn`
- Click different icon while open → close old, open new

#### 4. Encoding corruption fixed in group stats headings

Three headings in `tmpl-group-stats-reports.php` had a double-encoded em dash (`â` rendering as `â`) caused by a UTF-8 encoding error. Fixed by replacing the corrupted byte sequence (`\xc3\xa2\xc2\x80\xc2\x94`) with the correct UTF-8 em dash (`\xe2\x80\x94` → `—`) in all three instances:
- "Fiscal Year by Month — {FY}"
- "Group Closed Business by Month — {FY}"
- "Group Referrals & Connects by Month — {FY}"

#### 5. Leaderboard section divider

Added an `ist-section-divider` heading before the leaderboard charts in `tmpl-group-stats-reports.php`: **"Leaderboards — {FY label}"**. Provides clear visual separation from the attribution reporting section above and matching heading treatment to the "Fiscal Year by Month" divider.

---

## [0.2.19] — 2026-03-27

### Fixed — Help icon styling, form layout, Member/Other defaults, typography

#### 1. Help icon redesign — matches INC Meeting Roles Scheduler pattern

The help icon trigger (`.ist-help-icon`) has been redesigned to match the icon style used in the INC Meeting Roles Scheduler plugin (`.inc-mrs-role-instructions-btn`).

**Before:** 16×16px bordered circle button with `?` text, gray border and gray fill.
**After:** 18×18px icon-only button (2px padding) with an inline SVG info-circle (`ℹ`) — no border, no background, muted gray (`#9ca3af`) at rest, INC blue (`#1e4e8c`) on hover/focus.

SVG icon: `viewBox="0 0 16 16"` circle outline + dot + bar — the same SVG used in Meeting Roles. The button renders as a pure icon with `font-size: 0; line-height: 0` suppressing any text-rendering side effects.

BuddyBoss/theme override guard added: `#buddypress .ist-help-icon, .bp-nouveau .ist-help-icon, .buddypress .ist-help-icon` force-resets width/height/padding/border/box-shadow to prevent platform button resets from adding unwanted borders or sizing.

`:focus-visible` ring added for keyboard accessibility (2px `#1e4e8c` outline). Suppressed for mouse focus with `:focus:not(:focus-visible)`.

All three form templates (`tmpl-form-referral.php`, `tmpl-form-connect.php`, `tmpl-form-tyfcb.php`) updated: the `>?</button>` content replaced with the inline SVG on all help icon buttons.

#### 2. Form layout / legend and label alignment

**Legend fix:** `.ist-fieldset legend` now uses `display: flex; align-items: center; gap: 6px`. This aligns the legend text and icon as a proper flex row, eliminating the visual overlap and "bolted-on" appearance caused by inline flow. The `padding: 0 6px` notch behavior is preserved.

**Label fix:** Added `.ist-form p > label { display: flex; align-items: center; gap: 6px; }` — targets only standard field labels (direct children of `<p>` elements), not radio option labels. Aligns label text and help icon on the same baseline without affecting `.ist-radio-label` (which already uses `display: inline-flex`).

**Note:** The `gap` property on flex containers replaces the previous `margin-left: 5px` on the icon, giving consistent spacing whether the icon is the only element to the right of text or after an optional-label span.

#### 3. Member / Other default selection

**Referral form** (`tmpl-form-referral.php`): Group Member radio now has `checked` attribute (was Other). The Group Member panel now has `ist-visible` class (was hidden with `aria-hidden="true"`). The Other panel now starts hidden (`aria-hidden="true"`, no `ist-visible`). The member select no longer has `disabled` (JS re-enables it on DOMReady via `initRecipientToggle` trigger).

**Connect form** (`tmpl-form-connect.php`): Same changes — Group Member checked by default, member panel visible, other panel hidden.

The JS `initRecipientToggle` triggers `change` on the checked radio on DOMReady, so both HTML state and JS state are now consistent.

#### 4. Typography — sans-serif throughout

Added explicit `font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif` to:
- `.ist-form-wrap` — form container root, ensures cascade in all contexts including modal
- `.ist-fieldset legend` — `<legend>` elements do not reliably inherit font from BuddyBoss-controlled ancestors
- `.ist-form label` — prevents theme serif overrides on labels
- `.ist-radio-label` — radio/checkbox option labels
- `.ist-btn` — submit buttons

Added `font-family: inherit` to `.ist-form input[type="text"], input[type="number"], input[type="date"], select, textarea` — browser default behavior for form controls is to NOT inherit font-family; `inherit` picks up the sans-serif cascade from `.ist-form-wrap`.

**Files changed:** `inc-stats-tracker.php`, `assets/css/ist-frontend.css`, `templates/frontend/tmpl-form-referral.php`, `templates/frontend/tmpl-form-connect.php`, `templates/frontend/tmpl-form-tyfcb.php`.

---

## [0.2.18] — 2026-03-27

### Added — Phase 6 follow-up: Referral Origin table + terminology alignment

#### Referral Origin summary table (`tmpl-tyfcb-attribution.php`)

Added a compact summary table as the third enhanced-only attribution view, after the two existing horizontal bar charts. Sourced from `IST_Stats_Query::tyfcb_by_referrer_type()` — enhanced records only, referral-attributed only (`original_referrer_type != ''`). Shows three possible rows: Current Member / Former Member / Other. Amount and Records columns are right-aligned. Empty-state: section is hidden via `! empty( $attr_referrer )` guard — no output if no referral-attributed enhanced records exist. A `ist-chart-note` below the table clarifies the scope ("Covers referral-attributed enhanced records only").

Uses the existing `.ist-table` styles — no new CSS classes required.

#### Controller changes

`IST_Profile_Nav::content()` and `IST_Group_Extension::display()` each add `tyfcb_by_referrer_type()` to their attribution query set. Result passed as `$tyfcb_by_referrer` / `attr_referrer`.

#### Terminology alignment

- Partial docblock Tier 1 bucket list corrected: "Direct / Non-Referral / Unclassified" (was "Direct / Unclassified" — missed the full chip label).
- Partial docblock Tier 2 updated to document all three views (Attribution Source chart, Revenue Relationship Type chart, Referral Origin table).
- Enhanced intro paragraph updated: "The **breakdowns** below..." (was "The **charts** below..." — now accurate since a table is also present).
- `translators` comment on enhanced intro updated to match actual printf arguments.

**Files changed:** `inc-stats-tracker.php`, `frontend/class-ist-profile-nav.php`, `frontend/class-ist-group-extension.php`, `templates/frontend/tmpl-profile-my-stats.php`, `templates/frontend/tmpl-group-stats-reports.php`, `templates/frontend/partials/tmpl-tyfcb-attribution.php`.

---

## [0.2.17] — 2026-03-27

### Added — Phase 6: Closed Business attribution reporting views

Attribution reporting is now visible on both the My Stats profile tab and the Group Stats Reports group tab, scoped to the current fiscal year.

#### New partial template: `templates/frontend/partials/tmpl-tyfcb-attribution.php`

Shared partial rendered on both dashboard pages. Receives `$rollup_data`, `$coverage_data`, `$attr_source`, `$attr_rel_type`, and `$fy_label` from the parent template via `ist_get_template`. Returns early (no output) if no TYFCB records exist in the FY scope.

**Tier 1 — Cross-era rollup (all records):**
Three rollup chips showing Referral-Attributed / Direct Non-Referral / Unclassified amounts and record counts. Uses `tyfcb_attribution_rollup()` data. Safe to show regardless of data vintage; works on legacy-only groups.

**Attribution coverage note:**
Disclosure text above the chips adapts to data state: all-legacy, mixed, or all-enhanced, with exact record counts.

**Tier 2 — Enhanced-only attribution charts (gated on `enhanced.count > 0`):**
- Revenue Attribution Source horizontal bar chart — 5 attribution source buckets by amount.
- Revenue Relationship Type horizontal bar chart — 5 relationship type buckets by amount.
Both charts are labeled "Enhanced Only" and reference the fiscal year. Chart height scales with row count (`max(120, count * 44 + 48)px`).

#### New JS chart type: `attribution-horizontal`

Added to `buildChartConfig()` in `ist-frontend.js`. Horizontal bar chart with currency-formatted x-axis (same `$` / `k` shorthand as business-trend) and currency tooltip. Distinct from `leaderboard-horizontal` which uses integer counts.

#### New CSS classes

`.ist-rollup-chips` — flex row of chips, wraps on narrow viewports.
`.ist-rollup-chip` — base chip style (card with top border accent).
`.ist-rollup-chip--referral` — INC blue top border (`#1e4e8c`).
`.ist-rollup-chip--non-referral` — light blue top border (`#6b9fd4`).
`.ist-rollup-chip--unknown` — neutral grey top border (`#d1d5db`).
`.ist-rollup-chip__label`, `.ist-rollup-chip__amount`, `.ist-rollup-chip__count` — chip inner elements.
`.ist-coverage-note` — muted disclosure paragraph.
`.ist-enhanced-badge` — inline blue pill label for enhanced-only sections.
`.ist-enhanced-intro` — intro sentence preceding enhanced charts.

#### Controller changes

`IST_Profile_Nav::content()` and `IST_Group_Extension::display()` each call four new queries scoped to `$fy_start`/`$fy_end` and the relevant `$user_ids` scope (single user vs all group members):
- `IST_Stats_Query::tyfcb_attribution_rollup()`
- `IST_Stats_Query::tyfcb_model_coverage()`
- `IST_Stats_Query::tyfcb_by_attribution_source()`
- `IST_Stats_Query::tyfcb_by_relationship_type()`

Results passed to the respective dashboard templates as `$tyfcb_rollup`, `$tyfcb_coverage`, `$tyfcb_by_source`, `$tyfcb_by_rel`.

**Files changed:** `inc-stats-tracker.php`, `frontend/class-ist-profile-nav.php`, `frontend/class-ist-group-extension.php`, `templates/frontend/tmpl-profile-my-stats.php`, `templates/frontend/tmpl-group-stats-reports.php`, `templates/frontend/partials/tmpl-tyfcb-attribution.php` (new), `assets/js/ist-frontend.js`, `assets/css/ist-frontend.css`.

---

## [0.2.16] — 2026-03-27

### Added — Phase 5: reporting compatibility layer + name normalization

#### Name normalization (IST_Service_TYFCB)

Added `normalize_name()` private static helper. Applied at insert time to all free-text person name fields so GROUP-BY attribution queries are not fragmented by whitespace variants.

Rules:
- Trim leading/trailing whitespace.
- Normalize tabs, newlines, carriage returns, and vertical tabs to a single space.
- Collapse any run of Unicode whitespace (including `\u00A0`) to one ASCII space.
- Capitalization and punctuation preserved exactly as entered — no ucwords() or aggressive rewriting. Cultural and edge-case names (O'Brien, van der Berg, ALLCAPS initials) are left intact.

Applied to: `original_referrer_name` (enhanced `former_member` / `other` paths), `thank_you_to_name` (legacy `other` path). NOT applied to `client_payer_name` or `attribution_notes` (freeform, not used in GROUP-BY queries).

#### Phase 5 query methods (IST_Stats_Query)

All existing universal methods (`tyfcb_totals`, `tyfcb_leaderboard`, `three_month_trend`, `fy_monthly_trend`, `ytd_comparison`, `tyfcb_recent`) continue to work across legacy and enhanced records unchanged — they query `entry_date`, `amount`, and `thank_you_to_name`, which are populated for all records.

New methods added:

**`tyfcb_attribution_rollup( $date_start, $date_end, $user_ids )`**
Cross-era rollup over ALL records (legacy + enhanced). Returns three buckets: `referral_attributed`, `non_referral`, `unknown_legacy_unclassified`. All buckets are always returned (zero-filled if empty).

Mapping rules:
- Enhanced: `current_member_referral` / `former_member_referral` / `third_party_extended_referral` → `referral_attributed`; `direct_non_referral` → `non_referral`; `unknown_other` → `unknown_legacy_unclassified`
- Legacy: `inside` / `tier-3` → `referral_attributed`; `outside` → `unknown_legacy_unclassified` (**not** `non_referral` — legacy "outside" could represent a former member referral, indirect downstream revenue, or recurring business from an old referral; insufficient fidelity for a non-referral classification); `''` → `unknown_legacy_unclassified`
- Principle: `non_referral` is only assigned to enhanced records with explicit `direct_non_referral` source. Legacy records cannot claim non-referral status.

**`tyfcb_by_attribution_source( $date_start, $date_end, $user_ids )`**
Enhanced records only. Groups by `revenue_attribution_source`. Returns `{ source, amount, count }` rows ordered by amount DESC.

**`tyfcb_by_relationship_type( $date_start, $date_end, $user_ids )`**
Enhanced records only. Groups by `revenue_relationship_type`. Returns `{ relationship_type, amount, count }` rows ordered by amount DESC.

**`tyfcb_by_referrer_type( $date_start, $date_end, $user_ids )`**
Enhanced records only, referral-attributed records only (`original_referrer_type != ''`). Groups by `original_referrer_type` (`current_member`, `former_member`, `other`). Returns `{ referrer_type, amount, count }` rows ordered by amount DESC.

**`tyfcb_by_lineage_type( $date_start, $date_end, $user_ids )`**
Enhanced records only, restricted to referral-attributed attribution sources. Groups by `referral_lineage_type`. Records where the submitter left lineage blank (`''`) are included as a distinct "not specified" bucket — they represent valid referral-attributed business with no lineage characterisation. Returns `{ lineage_type, amount, count }` rows ordered by amount DESC.

**`tyfcb_model_coverage( $date_start, $date_end, $user_ids )`**
Returns `{ enhanced: { amount, count }, legacy: { amount, count } }`. Tells the dashboard how many FY records carry enhanced vs legacy attribution — used to show data-coverage context ("X of Y records include enhanced attribution data").

**Files changed:** `inc-stats-tracker.php`, `includes/services/class-ist-service-tyfcb.php`, `includes/class-ist-stats-query.php`.

---

## [0.2.15] — 2026-03-27

### Fixed — Former member attribution in Closed Business form

**Problem:** The Original Referrer section in the enhanced TYFCB form offered only "Group Member" (current active members dropdown) or "Other Person" (free text). Former members — people who were once in the group but are no longer active — had no distinct path and were incorrectly forced into the "Other Person" bucket, losing attribution context.

**Root cause:** `IST_Service_Members::get_group_members()` returns only current BuddyBoss group members. There is no former-member roster in the plugin. The original 2-option toggle did not account for the gap between "current member I can look up" and "genuinely outside the group."

**Fix — schema (additive):**
- Added `original_referrer_type VARCHAR(20) NOT NULL DEFAULT ''` to `wp_ist_tyfcb`.
- Stores the three-way classification at insert time: `current_member`, `former_member`, `other`.
- Legacy and non-referral records default to `''` (empty string).
- `dbDelta()` adds the column on next admin load after version bump.

**Fix — service:**
- `IST_Service_TYFCB` now accepts `original_referrer_type` values `current_member`, `former_member`, `other`.
- Added `VALID_REFERRER_TYPES` constant.
- Legacy import value `'member'` is normalised to `'current_member'` at service time — no import changes needed.
- `former_member` path: requires `original_referrer_name` (free text); `original_referrer_user_id` stays NULL; `thank_you_to_type = 'other'` for leaderboard compat.
- `other` path: same storage logic as `former_member`, distinct error message.
- `original_referrer_type` is now included in the INSERT data.

**Fix — form:**
- Original Referrer radio expanded from 2 to 3 options: Current Group Member / Former Group Member / Other Person / Non-Member.
- Three corresponding panels: member dropdown (current), free-text "Former Member Name", free-text "Their Name or Contact".
- Both former-member and other panels share `name="original_referrer_name"`; only the active panel's input is enabled at submit time.

**Fix — JS smart defaulting:**
- `initTyfcbAttributionToggle()` now auto-selects the natural referrer type when the attribution source changes:
  - `current_member_referral` → auto-selects "Current Group Member"
  - `former_member_referral` → auto-selects "Former Group Member"
  - `third_party_extended_referral` → auto-selects "Other Person / Non-Member"
- This prevents the user from having to make an obvious redundant choice after selecting the attribution source.

**Files changed:** `inc-stats-tracker.php`, `includes/class-ist-activator.php`, `includes/services/class-ist-service-tyfcb.php`, `templates/frontend/tmpl-form-tyfcb.php`, `assets/js/ist-frontend.js`.

---

### Added — Help/info icons on Referral and Connect forms

Extended the `initFieldHelp()` popup system (introduced in 0.2.14) to the Referral and Connect forms.

**Referral form** — help icons added to: Referred To, Referral Date, Handoff Method, Referral Type, Referral Details.

**Connect form** — help icons added to: Met With, Date of Connect, How Did You Meet?, Topic of Conversation.

**Files changed:** `templates/frontend/tmpl-form-referral.php`, `templates/frontend/tmpl-form-connect.php`.

---

## [0.2.14] — 2026-03-26

### Added — Enhanced Closed Business attribution model (Phases 2–4)

**Schema (Phase 2 — additive, backward-safe)**
- Added 8 new columns to `wp_ist_tyfcb`: `attribution_model` (DEFAULT `'legacy'`), `revenue_attribution_source`, `revenue_relationship_type`, `client_payer_name`, `original_referrer_name`, `original_referrer_user_id`, `referral_lineage_type`, `attribution_notes`.
- `dbDelta()` migration: existing records automatically receive `attribution_model = 'legacy'` via the column DEFAULT. No data is modified. Migration runs on first admin load after version bump.
- Added `KEY attribution_model` index for future reporting filters.

**Service layer (Phase 2)**
- `IST_Service_TYFCB::create_from_input()` now branches on `attribution_model` in POST input.
- **Enhanced path** (`attribution_model = 'enhanced'`): validates `revenue_attribution_source` (required), validates `original_referrer_*` fields (required when source is a referral type), validates `revenue_relationship_type` (required), accepts optional `referral_lineage_type`, `client_payer_name`, `attribution_notes`. Derives legacy `thank_you_to_*` from `original_referrer_*` for leaderboard / reporting compatibility. Auto-maps `referral_type` slug from attribution source via `ATTRIBUTION_SOURCE_TO_REFERRAL_TYPE` constant.
- **Legacy path** (`attribution_model = 'legacy'`): existing behavior preserved exactly — no changes to validation, field names, or insert logic.

**Closed Business form rewrite (Phase 3)**
- `templates/frontend/tmpl-form-tyfcb.php` fully rewritten for the enhanced attribution model. All new submissions carry `attribution_model = 'enhanced'`.
- New fields: Revenue Attribution Source (5-option radio, required), Original Referrer (member/other toggle, conditional on referral-type source), Referral Lineage Type (conditional, optional), Revenue Relationship Type (5-option radio, required), Client/Payer Name (optional text).
- Preserved fields: Business Type, Amount, Business Date, General Note.
- Removed legacy fields from the public form: Business Source member/other toggle, old Referral Type radio. These remain fully functional in the service layer for legacy/import paths.
- Referrer details section (`#ist-tyfcb-referrer-details`) is hidden by default and shown only when a referral-type attribution source is selected. Inputs in the hidden section are `disabled` to prevent ghost submission.

**Field help icon system (Phase 4)**
- New `.ist-help-icon` button pattern: small inline `?` button inside fieldset legends and labels, carrying `data-help-title` and `data-help-body` (paragraphs separated by `||`).
- `initFieldHelp()` in `assets/js/ist-frontend.js`: creates a single `#ist-field-help` popup appended to `document.body` on init (outside all modal stacks), event delegation on `document` for `.ist-help-icon` clicks, fixed positioning near the clicked button, ESC / outside-click / close-button dismissal.
- `initTyfcbAttributionToggle()`: handles show/hide of the referrer details section based on `data-shows-referrer` attribute on attribution source radios.
- `initTyfcbReferrerToggle()`: handles member/other panel toggle inside the referrer details section.
- CSS additions: `.ist-conditional-section` (hidden by default, shown via `.ist-visible`), `.ist-help-icon`, `.ist-help-popup` with header/title/close/body sub-elements, `.ist-label-optional` / `.ist-legend-optional`, `.ist-radio-group--stacked` alias.
- Help copy added to all fields in the new TYFCB form.

**Files changed:** `inc-stats-tracker.php`, `includes/class-ist-activator.php`, `includes/services/class-ist-service-tyfcb.php`, `templates/frontend/tmpl-form-tyfcb.php`, `assets/js/ist-frontend.js`, `assets/css/ist-frontend.css`.

---

## [0.2.13] — 2026-03-26

### Fixed — FY monthly chart: all prior-month buckets returning zero (timezone bug)

**Root cause:** `IST_Stats_Query::fy_monthly_trend()` computed each prior month's end date using `wp_date( 'Y-m-t', strtotime( $start ) )`, where `$start` is the first day of the month (e.g. `'2025-07-01'`). PHP's default timezone in WordPress is UTC, so `strtotime('2025-07-01')` returns midnight UTC July 1. `wp_date()` then converts that UTC timestamp to the site's configured timezone. On any UTC-minus site (all US timezones: Eastern, Central, Mountain, Pacific), midnight UTC July 1 is still June 30 in local time, so `Y-m-t` returned `2025-06-30` — the last day of **June**, not July. The resulting query `WHERE entry_date BETWEEN '2025-07-01' AND '2025-06-30'` has an inverted range and returns zero rows for every affected bucket.

**Why the current month was unaffected:** The current month always uses `$today` directly (passed from the controller as `wp_date('Y-m-d')`), bypassing the faulty calculation.

**Why FY KPI totals were unaffected:** `tyfcb_totals( $fy_start, $today )` is a single query spanning the full FY range; the bad end-date logic only applied to the per-month bucketing loop.

**Fix:** Replaced `wp_date() + strtotime()` with `DateTime::modify('last day of this month')` on a cloned cursor object. `DateTime::modify()` operates purely on the object's own internal state — no site-timezone conversion occurs, so the last day of the month is always computed correctly regardless of the WordPress site timezone setting.

**Files changed:** `includes/class-ist-stats-query.php` — `fy_monthly_trend()` only.

---

## [0.2.12] — 2026-03-26

### Added — FY monthly charts, YTD comparison cards, leaderboard spacing

**Fiscal Year monthly charts (both pages)**
- New "Fiscal Year by Month" chart section on both My Stats and INC Stats Reports. Shows Closed Business (line) and Referrals & Connects (grouped bar) broken out month-by-month from the FY start through the current month.
- Current month is MTD; all prior months use full-month totals. A "* Current month is month-to-date." footnote is shown beneath the chart section.
- Charts are visually separated from the existing 3-month trend section via a `.ist-section-divider` rule with an uppercase label heading.
- Data driven by new `IST_Stats_Query::fy_monthly_trend()` method; reuses existing Chart.js `business-trend` and `ref-con-comparison` config — no JS changes required.

**YTD same-point comparison cards (both pages)**
- New 3-card grid above the chart sections: Closed Business (amount), Referrals Given (count), Connects Logged (count).
- Each card shows the current FY YTD total alongside the equivalent elapsed-period total from the prior fiscal year (today minus one year as the comparison anchor).
- Delta line shows absolute change and percentage change (percentage suppressed when prior period has no data).
- Delta color-coded: green for improvement, red for decline, grey for no change.
- Data driven by new `IST_Stats_Query::ytd_comparison()` method. Prior-period anchor uses `strtotime('-1 year')` for simplicity and leap-year safety.
- Rendered by new partial `templates/frontend/partials/tmpl-ytd-comparison.php`.

**Leaderboard spacing and padding**
- `.ist-leaderboard` top margin increased from `32px` to `36px`; added `padding-top: 4px` for cleaner visual separation from chart sections above.
- Leaderboard `h3` inter-subsection margin increased from `28px` to `36px`; bottom padding from `8px` to `10px` for better label-to-table spacing.
- `.ist-leaderboard-note` margin adjusted to `8px 0 12px` (was `4px 0 8px`).
- `.ist-rank` column width set to an explicit `36px` (was unsized) for consistent alignment across all leaderboard tables.

**Responsive**
- `.ist-ytd-grid` collapses from 3-column to 1-column at ≤480px. Card value/period pairs stack vertically in single-column mode.

---

## [0.2.11] — 2026-03-26

### Changed — Typography, spacing, and label polish pass

**Font consistency**
- Added `font-family: sans-serif stack` to `.ist-my-stats` and `.ist-group-stats-reports` wrappers. All dashboard/report UI now inherits a consistent sans-serif baseline regardless of the active BuddyBoss theme, eliminating any serif bleed-through.
- Explicit `font-family` added to `.ist-fy-progress__range`, `.ist-fy-progress__days`, `.ist-table`, `.ist-empty`, `.ist-leaderboard-note`, and `.ist-chart-note` for belt-and-suspenders coverage.

**Chart section headings**
- `.ist-chart-title` completely reworked: was 11px uppercase muted editorial label (`#9ca3af`), now a real section heading: 16px, 700 weight, `#374151`, no uppercase, slight negative tracking. Applies to "Closed Business — 3-Month Trend", "Referrals & Connects — 3-Month Comparison", and all leaderboard chart sections.

**KPI card readability**
- `.ist-kpi-card__period` (period labels under values): `font-size: 10px` → `11px`, `color: #9ca3af` → `#6b7280`. Period labels are now clearly readable as secondary text, not barely-visible metadata.

**Helper / meta text contrast**
- `.ist-chart-note` (MTD footnote): `11px #b0bac5` → `12px #6b7280`. Also fixed fragile negative margin (`-16px`) to `margin: 8px 0 24px` — now uses normal flow spacing.
- `.ist-leaderboard-note`: `11px #b0bac5` → `12px #6b7280`.
- `.ist-fy-progress__range` and `__days`: `#9ca3af` → `#6b7280`.

**Section spacing and headings**
- `.ist-recent-records` top margin: `4px` → `32px`. Prevents the "Recent Closed Business" heading from riding up on the chart above it.
- `.ist-leaderboard` top margin: `4px` → `32px`. Same treatment for the leaderboard section.
- `.ist-recent-records h3` and `.ist-leaderboard h3`: `11px #9ca3af` → `12px #4b5563`. Visibly readable section labels that still feel secondary to the chart titles.
- Inter-subsection margin for h3: `28px` → `32px` for more breathing room between each record group.
- `h3:first-child` margin reset to `0` — outer section `margin-top` handles the spacing cleanly.

**Label change — "INC Stats Reports"**
- Group tab nav label: "Group Stats Reports" → "INC Stats Reports" (`class-ist-group-extension.php`).
- Group stats page heading: "Group Stats Reports" → "INC Stats Reports" (`tmpl-group-stats-reports.php`).

---

## [0.2.10] — 2026-03-26

### Changed — Dashboard QA and polish pass

**Data presentation**
- Recent Records tables now display dates in human-readable "Mar 15, 2026" format instead of raw `Y-m-d` database strings. All three record types (Closed Business, Referrals, Connects) updated.

**Chart readability**
- Added "* Current month is month-to-date." footnote below the Referrals & Connects 3-month comparison chart on both My Stats and Group Stats Reports. Prevents confusion when the current month's bar is shorter than prior full months.
- Group Stats leaderboard chart titles now include the fiscal year label ("Top Referral Givers — FY 2025–26") to match the corresponding detail table headers below them, eliminating the confusing near-duplication where charts had short titles and tables had longer ones.

**Spacing and layout**
- `.ist-chart-section` now has `margin: 28px 0` (added bottom margin) instead of `margin: 28px 0 0`. This prevents charts from abutting the next section (Recent Records / Leaderboard) with only a 4px gap.
- Added `min-height: 180px` to `.ist-chart-wrap` and `min-height: 120px` to `.ist-chart-wrap--hbar` as fallback guards if Chart.js encounters a sizing issue.

**Responsive behavior**
- KPI grid switches to a single column below 480px viewport width. Previously stayed 2-column at all sizes, making cards cramped on narrow phones.
- KPI card values stack vertically (FY on top, month below with a top rule) rather than side-by-side when in single-column mode.
- Page header (title + action links) stacks vertically on narrow screens.
- Chart wrap min-height relaxed to 140px on small screens.

---

## [0.2.9] — 2026-03-26

### Added — 3-month trend charts and leaderboard visuals

**Data layer**
- `IST_Stats_Query::three_month_trend( $today, $user_ids )`: returns 3 calendar-month buckets (2 months ago, last month, current month MTD) with `label`, `tyfcb_amount`, `ref_count`, `con_count` per bucket. Delegates to existing totals methods.

**My Stats charts**
- "Closed Business — 3-Month Trend" line chart: filled area, INC blue, 0.35 tension, currency tooltip and y-axis labels.
- "Referrals & Connects — 3-Month Comparison" grouped bar chart: Referrals in INC blue, Connects in light blue, legend at top.
- Both charts appear between FY Progress and Recent Records.

**Group Stats Reports charts**
- Same two 3-month trend charts as My Stats (group-scoped data).
- "Top Referral Givers" horizontal bar chart: top 8 members by FY referral count.
- "Top Connect Loggers" horizontal bar chart: top 8 members by FY connect count.
- Leaderboard charts appear before the existing detail tables; tables remain as exact-value fallback.

**Chart.js integration**
- Chart.js 4.4.0 registered from jsDelivr CDN via `wp_register_script`; added as dependency of `ist-frontend.js`.
- `buildChartConfig( type, raw )` in `ist-frontend.js`: visual config (colors, axes, tooltips, border-radius) separated from server-rendered data. Handles `business-trend`, `ref-con-comparison`, `leaderboard-horizontal` types.
- `initCharts()` scans `.ist-chart[data-chart-type]` canvas elements, parses `data-chart` JSON attribute, instantiates `new Chart()`. Called in `$(document).ready()`.

**CSS**
- Added `.ist-chart-section`, `.ist-chart-title` (editorial label style matching section headers), `.ist-chart-wrap`, `.ist-chart-wrap--hbar` (explicit-height variant for horizontal bar charts).

---

## [0.2.8] — 2026-03-26

### Changed — Stitch-inspired dashboard/report visual polish

**KPI presentation**
- Replaced the plain summary table (`ist-kpi-section` / `ist-kpi-table`) with a 2-column metric card grid (`ist-kpi-grid` / `ist-kpi-card`) on both My Stats and Group Stats Reports.
- Each card shows: uppercase muted label, large FY value (INC blue, 22px), smaller month value (dark gray, 15px) separated by a thin rule. A 3px INC blue top-border accent identifies each card as a metric tile.
- `tmpl-kpi-row.php` now outputs a `<div class="ist-kpi-card">` instead of a `<tr>`. Both parent templates pass `month_label` to the partial.

**Fiscal Year Progress**
- Slimmed progress bar from 8px to 6px. Bar track changed from `#e2e8f0` to `#e8f0fa` (accent tint) for tonal cohesion.
- Reduced padding (16px → 14px top/bottom), label font-size (15px → 13px), meta text (14px/13px → 12px). Net result: lighter, less dominant module.
- Bottom margin increased to 28px (from 20px) to give the FY module more breathing room below the KPI cards.

**Tables — shared base**
- Cell padding increased to `10px 14px` (was `9px 12px`) for better row breathing.
- Row separator color lightened to `#f0f4f9` (was `#e2e8f0`). Last row no longer has a bottom separator.
- Table header: font-size reduced to 11px, color lightened to `#9ca3af`, border weight reduced to 1px solid.
- `margin-bottom` standardised to 20px.

**Recent Records section headers**
- `h3` labels redesigned as editorial section dividers: 11px uppercase, `#9ca3af`, 1px bottom border. Replaced the 15px bold heading with a soft data-label treatment.

**Leaderboard section headers and ranks**
- Same editorial h3 treatment as Recent Records.
- Leaderboard note text: 11px italic, color lightened to `#b0bac5`.
- Rank column narrowed (36px → 32px), 12px (was 13px).
- Top-3 rank colors refined: gold `#d97706` / silver `#9ca3af` / bronze `#b45309`.

---

## [0.2.7] — 2026-03-26

### Changed
- **Group Stats Reports header refactored** — replaced the standalone dark-blue button row (`tmpl-submit-actions.php`) with a compact inline header cluster. The page title and three action links now sit in the same flex row: title left, actions right. This removes the visual heaviness of the separate filled-button bar.
- **New action link style** (`.ist-header-action`) — outline treatment (INC blue border, transparent fill) with a hover fill. Reads as secondary controls in a reporting context rather than primary CTAs. Font, size, and border-radius match the rest of the UI.
- **New page header layout** (`.ist-page-header` / `.ist-header-actions`) — reusable CSS block added to `ist-frontend.css`. Handles baseline alignment and wrapping on narrow viewports.
- **Modal behavior unchanged** — action links still link to real form URLs. The `initModals()` href interceptor in JS catches clicks when `#ist-modal` is present (which it is on the Group Stats page) and opens the form in a modal instead of navigating. Fallback direct navigation still works when JS is off.
- **Direct form pages unchanged** — the fallback form pages under the member profile subnav remain clean: identity line, form, back link on success. No extra action clutter added.

---

## [0.2.6] — 2026-03-26

### Changed
- **Group Stats Reports now supports modal form opening** — the same modal workflow used on My Stats (hidden form containers + `#ist-modal` overlay) is now rendered on the Group Stats Reports group tab. Clicking Log Closed Business / Log a Referral / Log a Connect in the action row opens the corresponding form in a modal instead of navigating away.
- **Styling consistency** — both screens now share the same `.ist-submit-actions` / `.ist-submit-btn` / modal CSS with no additional rules needed.
- **Fallback direct URLs preserved** — action row links remain `<a>` tags pointing to real form page URLs. When JS is disabled the links navigate normally. Modal behavior is purely progressive enhancement.
- **Group extension controller updated** (`class-ist-group-extension.php`) — passes `group_members`, `current_user`, `my_stats_url`, and `atts` to the Group Stats template so the embedded form partials render correctly inside the modal containers.

---

## [0.2.5] — 2026-03-25

### Changed
- **Modal triggers moved to BuddyBoss My Stats subnav** — the "Log Closed Business", "Log a Referral", and "Log a Connect" items in the BuddyBoss profile subnav are now the sole modal triggers on the My Stats summary page. Clicking them intercepts the default navigation and opens the corresponding form in a modal overlay instead.
- **Duplicate in-content action row removed** — the `<div class="ist-submit-actions">` block (three `<button>` elements) that was added in 0.2.4 has been removed from `tmpl-profile-my-stats.php`. The BuddyBoss subnav is now the single source of form-entry actions.
- **JS subnav interceptor** (`initModals()`) — added a delegated click handler on `document` that matches anchor hrefs ending in `/log-tyfcb/`, `/log-referral/`, or `/log-connect/` and opens the corresponding modal. Because the handler is only bound when `#ist-modal` exists (i.e. the My Stats summary page), direct navigation to sub-nav form URLs continues to work normally as a fallback.

---

## [0.2.4] — 2026-03-25

### Added
- **Modal-based form workflow on My Stats** — "Log Closed Business / Log a Referral / Log a Connect" buttons on the My Stats summary page now open the respective form in a lightweight modal overlay. No framework dependencies; uses a jQuery + `fetch()` approach.
  - Forms are embedded in hidden containers on the page and moved (not cloned) into the modal panel on demand, preserving nonce values and JS event listeners.
  - Submit is intercepted by `fetch()` with `credentials: 'same-origin'`; the redirect URL is checked for `?ist_saved=1` or `?ist_error=…`.
  - On success: "Record saved successfully." notice shown in modal → modal closes after 1.4 s → page reloads so KPI totals reflect the new record.
  - On error: error message shown inline inside the modal; submit button re-enabled.
  - Escape key, backdrop click, and ✕ button all close the modal.
  - Existing BuddyBoss sub-nav routes (`/log-tyfcb/`, `/log-referral/`, `/log-connect/`) continue to work as direct-access fallback.

### Changed
- **My Stats button row** — replaced the `tmpl-submit-actions.php` partial (anchor tags) with native `<button>` elements carrying `data-ist-modal` attributes. Eliminates theme link-style bleed (dark blue + serif/red text) since `<button>` elements do not inherit `a {}` rules.
- **`.ist-submit-btn` CSS** — added `font-family`, `color: #ffffff !important`, `text-decoration: none !important`, and `border: none` to ensure button appearance is enforced regardless of theme stylesheet specificity.
- **Connect form** (`tmpl-form-connect.php`) — "Met With" field is now a two-panel toggle matching the Referral form:
  - **Group Member** panel: dropdown of configured BuddyBoss group members. Server resolves `connected_with_name` from the selected user's WP account.
  - **Other** panel: free-text name input (default active).
- **`IST_Service_Connects::create_from_input()`** — handles `connected_with_type` ('member' | 'other'). When type is 'member', `connected_with_name` is resolved from the selected user's `display_name`.
- **`IST_Profile_Nav::content()`** — now computes and passes `$group_members`, `$current_user`, and `$my_stats_url` to the My Stats template so embedded form containers render correctly.
- **CSS** — added `.ist-recipient-panel` / `.ist-recipient-panel.ist-visible` rules (parallel to `.ist-source-panel`) used by both Referral and Connect recipient toggles. Added full modal overlay styles.
- **JS** — refactored recipient toggle into a shared `initRecipientToggle(formSelector)` helper called by `initReferralRecipientToggle()` and new `initConnectRecipientToggle()`. Added `initModals()`.

---

## [0.2.3] — 2026-03-25

### Added
- **Referral email notification** — when a member logs a referral with handoff method "Emailed introduction", the system automatically sends a professionally formatted HTML email to the referral recipient.
  - Recipient resolved from the selected group member's WP account (member panel) or from the manually entered email address (other panel).
  - Email includes: INC blue header bar, intro paragraph, structured details block (date, referred by, referral type, details), direct contact link for the referring member, footer disclaimer.
  - Subject and intro paragraph are both filterable (`ist_referral_notification_subject`, `ist_referral_notification_intro`).
  - Reply-To header set to the referring member's name and email.
  - Notification failure is silent and does not affect the form redirect.
- **`includes/ist-notifications.php`** — new functions file containing `ist_send_referral_notification( array $args ): bool`.
- **`templates/email/tmpl-referral-notification.php`** — new HTML email template rendered via `ob_start()` and `ist_get_template()`.

### Changed
- **Referral form** (`tmpl-form-referral.php`) — "Referred To" field is now a two-panel toggle:
  - **Group Member** panel: dropdown of configured BuddyBoss group members. Server resolves `referred_to_name` and email from the selected user's WP account.
  - **Other** panel: free-text name field + optional email address field. Email used for notification when handoff method is "Emailed introduction".
- **`IST_Service_Referrals::create_from_input()`** — handles `referred_to_type` ('member' | 'other'). When type is 'member', `referred_to_name` is resolved from the selected user's `display_name`.
- **`IST_Forms::handle_referral()`** — calls `ist_send_referral_notification()` after a successful save when status is 'emailed'. Recipient email resolved from member user record or entered email field.
- **`inc-stats-tracker.php`** — loads `ist-notifications.php` alongside `ist-functions.php`.

---

## [0.2.2] — 2026-03-25

### Fixed
- **BuddyBoss My Stats 404 — route registration** (`IST_Profile_Nav::register()`) — nav and sub-nav items are now registered using `bp_displayed_user_domain()` instead of `bp_loggedin_user_domain()`, matching the URL BuddyBoss resolves from the request. The `bp_is_my_profile()` guard was also moved from a hard registration exit to a `$can_view` access parameter so BuddyBoss always has a route handler for `/ist-my-stats/`, regardless of timing differences in how the displayed-user context is set during `bp_setup_nav`.
- **My Stats / Summary 404 — split access model** — the summary sub-nav was previously gated behind `ist_submit_records`, which was only granted to administrators. The registration logic now uses two separate gates: `$can_view` (own profile, any logged-in user) registers the parent nav and summary sub-nav; form sub-nav routes register for all `$can_view` users. Access enforcement moves to `content_form()`.
- **Form button 404s — capability vs. group membership** — `ist_submit_records` was never assigned to non-admin WordPress users, causing all group members to see "You do not have permission to submit records" when clicking any form button. Removed the custom-capability gate from the member submission path entirely. Access model is now: **logged in + own profile + configured BuddyBoss group member = can submit**. This matches the real-world intent of the plugin. `ist_submit_records` remains defined for administrator-level use but is no longer required for member form submission. The `IST_Frontend::access_guard()` shortcode path already used this model; the BuddyBoss profile nav path is now consistent with it.
- **KPI label consistency** — "Referrals Given" → "Referrals You Gave" in Group Stats Reports KPI table, matching the label already used on My Stats.
- **Admin diagnostic notice** — submit action buttons partial (`tmpl-submit-actions.php`) now shows an admin-only error notice with a link to INC Stats → Settings when all form URLs are empty and the BP fallback also fails, instead of silently rendering nothing.
- **CTA URL fallback guard** (`ist_get_form_urls()`) — BP fallback URLs are now skipped when `get_base_url()` returns a relative-only path (no `http` prefix), preventing broken relative URLs from being passed to the submit buttons.

---

## [0.2.1] — 2026-03-24

### Added
- **Submit action CTAs — both screens** — "Log Closed Business / Log a Referral / Log a Connect" button row now appears near the top of both My Stats and Group Stats Reports, immediately after the page heading. Previously there were no visible navigation buttons to the forms.
- **BuddyBoss profile sub-nav form pages** — `IST_Profile_Nav` now registers `log-tyfcb`, `log-referral`, and `log-connect` as sub-nav items under the "My Stats" tab. Each page renders the appropriate inline form. The `summary` page becomes the explicit default landing.
- **Auto form URL fallback** — `ist_get_form_urls()` now falls back to the BP sub-nav URLs when the admin settings `form_url_tyfcb/referral/connect` are not configured. Submit action CTAs appear on both pages without any manual URL setup required.
- **"← Back to My Stats" link** — all three form success notices now include a link back to the My Stats summary page when `$my_stats_url` is available (both the BP sub-nav path and the shortcode path). Users are no longer left without a clear next step after saving a record.
- **`IST_Profile_Nav::get_base_url()`** — new static helper that returns the base URL for the My Stats sub-nav (`/members/{user}/ist-my-stats/`). Used by both `ist_get_form_urls()` and `IST_Frontend::get_my_stats_url()`.

### Changed
- **`IST_Profile_Nav`** — `default_subnav_slug` changed from the parent slug to `SLUG_SUMMARY = 'summary'` so the landing page is an explicit named route.
- **`IST_Frontend` render methods** — all three shortcode render methods now compute `$my_stats_url` via the new `get_my_stats_url()` private method and pass it to the form template. Empty string on non-BP installs; templates skip the back link when empty.

### New CSS classes
- `.ist-notice__back` — "← Back to My Stats" inline link styled inside success notice banners (inherits success green; bold weight; underline).

---

## [0.2.0] — 2026-03-24

### Added
- **Fiscal Year Progress module** — new `IST_Fiscal_Year::get_progress()` static method returns `fy_start`, `fy_end`, `fy_label`, `total_days`, `elapsed_days`, `remaining_days`, and `percent_elapsed` for the current fiscal year. Does not alter stat-query behavior; totals remain driven by `entry_date`.
- **FY Progress partial** (`templates/frontend/partials/tmpl-fy-progress.php`) — displays FY label, date range, a compact progress bar, percent complete, and days elapsed/remaining. Shown on both My Stats and Group Stats Reports below the KPI table.
- **Front-end form access guard** — all three form shortcodes (`[ist_tyfcb_form]`, `[ist_referral_form]`, `[ist_connect_form]`) now check login status and group membership before rendering. Non-members and logged-out visitors see an error message; they never reach the form.
- **`admin_post_nopriv` handlers** — non-logged-in POST attempts to any form action are redirected to the WordPress login page (referer preserved as return URL).
- **Server-side future-date guard** — `IST_Forms::check_entry_date()` rejects any `entry_date` that is in the future before the service is called. Complements the `max="today"` HTML attribute added to all three form templates. Admin backfill is unaffected (guard only runs through the front-end handler).
- **`IST_Activator::maybe_upgrade()`** — static method that runs `dbDelta` when `ist_db_version` does not match `IST_VERSION`. Hooked to `admin_init` so schema updates apply automatically after a plugin update without requiring deactivate/reactivate.
- **`updated_at` column** — added to `wp_ist_tyfcb`, `wp_ist_referrals`, and `wp_ist_connects`. Set to `NULL` on insert; auto-populated by MySQL `ON UPDATE CURRENT_TIMESTAMP` when any row is modified. Used for audit purposes only; does not affect reporting.

### Changed
- **TYFCB form** (`tmpl-form-tyfcb.php`) — "Reporting Member" dropdown removed. Ownership is now system-assigned (current logged-in user) and displayed as a read-only "Submitting as: [name]" line. `submitted_by_user_id` sent as a hidden field for transparency; server always overwrites it from `get_current_user_id()`.
- **Referral form** (`tmpl-form-referral.php`) — "Referring Member" dropdown removed. Same system-assigned ownership pattern as TYFCB. "How did you hand it off?" replaces "Referral Status" legend for clarity. Submit button relabeled "Log a Referral".
- **Connect form** (`tmpl-form-connect.php`) — "Member" dropdown removed. Same system-assigned ownership pattern. Legend changed to "How did you meet?". Submit button relabeled "Log a Connect".
- **`IST_Forms` handlers** — all three handlers now force the ownership field from `get_current_user_id()` (overwriting any POST value), call `require_login()` as a belt-and-suspenders check, and run the future-date guard before delegating to the service.
- **Submit action button labels** (`tmpl-submit-actions.php`) — "Submit TYFCB / Submit Referral / Submit Connect" → "Log Closed Business / Log a Referral / Log a Connect".
- **All three form templates** — `max="today"` added to date inputs to prevent future-date selection in the browser. Success/error notices now rendered from `?ist_saved=1` / `?ist_error=…` query args at the top of each form.
- **Frontend render methods** (`IST_Frontend`) — now pass `$current_user` (WP_User) to all three form templates. Access guard applied before any template data is loaded.
- **`IST_Profile_Nav` and `IST_Group_Extension`** — both now call `IST_Fiscal_Year::get_progress()` and pass `$fy_progress` to their respective templates.
- **Frontend CSS** (`assets/css/ist-frontend.css`) — full visual polish pass:
  - Accent color updated to INC blue (`#1e4e8c`).
  - KPI section rendered as a soft card (`background: #f7f9fc`, `border-radius: 8px`).
  - KPI table column headers use 12px uppercase labels; value cells use accent blue bold type.
  - Table borders lightened to `#e2e8f0`; header rows use subtle uppercase label treatment instead of grey fill.
  - Leaderboard top-3 rank numbers colored (gold/silver/bronze).
  - Section `h3` headings have a `#e8f0fa` bottom border for tonal separation.
  - Form inputs styled with focus rings (`box-shadow: 0 0 0 3px rgba(30,78,140,0.12)`).
  - Fieldsets use `#fafbfc` background and softer borders.
  - Radio inputs use `accent-color: #1e4e8c`.
  - `.ist-btn--primary` modifier added; `.ist-btn` without modifier continues to work as alias.

### Fixed
- **Leaderboard variable bug** — `tmpl-group-stats-reports.php` was passing `$fy_label` via `compact()` but `tmpl-leaderboard.php` expects `$period_label`. Fixed by using an explicit array with the correct key. This caused blank heading text in all three leaderboard sections.

### New CSS classes
- `.ist-notice` / `.ist-notice--success` / `.ist-notice--error` — tonal alert banners for form feedback.
- `.ist-submitter-info` — light blue identity pill shown above form fields.
- `.ist-form-wrap` / `.ist-form-wrap--{type}` — outer wrapper constraining form width.
- `.ist-fy-progress` / `.ist-fy-progress__bar-wrap` / `.ist-fy-progress__bar` / `.ist-fy-progress__meta` — fiscal year progress module.

### Schema changes
- `updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP` added to `wp_ist_tyfcb`, `wp_ist_referrals`, `wp_ist_connects`.
- Column is added automatically on the next admin page load via `IST_Activator::maybe_upgrade()` → `dbDelta()`. No manual SQL needed.
- **MySQL 5.7 note:** MySQL 5.7 only permits one `ON UPDATE CURRENT_TIMESTAMP` column per table. If hosted on MySQL 5.7, verify that `updated_at` was added without error. If not, the `ON UPDATE` clause must be removed from that column definition and the value set explicitly in PHP on any future update path. MySQL 8.0+ handles this correctly without changes.

### Upgrade steps
1. Deploy plugin files.
2. Visit any WordPress admin page — `IST_Activator::maybe_upgrade()` runs automatically and adds the `updated_at` column to all three tables.
3. No data migration required; existing records will have `updated_at = NULL` until a row is next modified.
4. No settings changes needed.

---

## [0.1.0] — 2026-03-22

### Added
- Initial plugin scaffold: custom tables (`wp_ist_tyfcb`, `wp_ist_referrals`, `wp_ist_connects`), BuddyBoss profile tab ("My Stats"), BuddyBoss group extension ("Group Stats Reports"), KPI reporting, leaderboards, recent records, admin record list pages, settings page, historical CSV import.
- All three stat types fully queryable by `entry_date` for monthly and fiscal year reporting.
- Fiscal year start month configurable per group (default July).
- Successful one-time historical CSV import completed; imported data visible in reports and leaderboards.
