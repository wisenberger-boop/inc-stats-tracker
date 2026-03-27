# Changelog — INC Stats Tracker

All notable changes to this plugin are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versions follow [Semantic Versioning](https://semver.org/).

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
