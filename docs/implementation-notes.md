# INC Stats Tracker — Implementation Notes

Internal project notes for decisions made during development that are not self-evident from
the code or changelog alone. These are not release notes and not intended for end-users.

---

## FY YTD Closed Business — Baseline Discrepancy and Decision (2026-04-01)

### What was observed

After deploying the plugin and importing historical data, the **Closed Business (Amount) FY YTD
total shown in Group Stats did not match** the total previously visible in the Google Looker Studio
embedded dashboard used by the prior workflow.

The Looker Studio total was higher. The plugin total was lower. The discrepancy appeared consistently
on both the dev environment and the production environment, which rules out an environment-specific
issue (mismatched data files, partial import, environment-specific cache).

### Investigation findings

The following contributing factors were identified. None could be fully ruled out, and the
investigation did not produce a single definitive root cause:

1. **Unresolved import rows (`submitted_by_user_id = 0`):** During historical data import, the
   importer maps CSV member names to WordPress user IDs using a normalized name-match lookup. Some
   historical CSV rows did not match any current WordPress user account — these rows were inserted
   with `submitted_by_user_id = 0` (unresolved). There was a known list of unresolved mapping cases
   that were not fully reconciled during the import phase. Prior to the scope change documented
   below, the group aggregate queries filtered by current BuddyBoss group member IDs, which would
   have silently excluded all `submitted_by_user_id = 0` rows from the reported total.

2. **Roster-filtered scope on group aggregates (prior implementation):** The original Group Stats
   implementation passed the current active member list to all group-level queries. Members who had
   left the group since the historical period would also have been excluded from group totals —
   including any business closed during their active membership that was recorded via the CSV import.

3. **Looker Studio configuration differences:** The prior Looker Studio dashboard was connected to
   Google Sheets data populated via Zapier. The exact filtering, date-scoping, and aggregation rules
   applied in that Looker configuration were not available for review. It is plausible that the
   Looker total reflected different date windows, duplicate rows, or different business-type filters
   that are not exactly replicated by the plugin.

4. **Import completeness:** It is possible that not all historical records from the legacy Google
   Sheets were present in the import source CSV, or that some rows were skipped during import due
   to validation failures or hash-collision deduplication.

The investigation confirmed that the plugin's internal data is **self-consistent** — the totals
are accurate representations of what is in the database. The discrepancy is between the plugin's
baseline and the prior system's baseline, not within the plugin itself.

### Scope change applied

The group aggregate scope was changed (see `CHANGELOG.md`, `[Unreleased — queued for 1.0.3]`,
"Group Stats: group-level aggregates now include all historical records within the reporting window").
This change ensures that group totals include all records in the date window regardless of current
membership — closing the membership-filter gap identified in item 2 above.

After this scope change the group totals remain lower than the prior Looker total. The remaining
gap is attributed to items 1, 3, and/or 4 above, which were not fully resolved.

### Decision

**The current plugin total is accepted as the new authoritative baseline going forward.**

This is a practical internal project decision. It is not a claim that the prior Looker Studio number
was wrong, nor is it a claim that the plugin's historical import is complete or perfectly reconciled.

Rationale:
- Perfectly reconciling the prior Looker number would require access to the original Looker
  configuration and a member-by-member reconciliation of the import mapping gaps — effort not
  proportionate to the benefit at this stage of the project.
- The plugin's FY YTD total is internally consistent and accurately reflects the records that are
  in the database.
- Going forward, new activity will be recorded exclusively through the plugin's native forms
  (`data_source = 'native'`), and future FY reporting will be based entirely on those native
  submissions. The historical import data is seed data and not the long-term source of truth.
- Accuracy of the ongoing totals can be validated as new submissions accumulate and can be verified
  against member-reported activity.

**Action:** No further reconciliation work is planned. The discrepancy is documented here for
historical context. If a future audit requires deeper reconciliation, the starting point would be
re-examining the unresolved `submitted_by_user_id = 0` import rows and the original import source
CSV files.

---

## Group Stats Reporting Scope — Design Decision (2026-04-01)

### Rule

Group Stats uses a two-tier scope:

| Query type | Scope | Rationale |
|---|---|---|
| Totals, trend charts, FY monthly charts, YTD comparison, attribution | All records in the date window (`$user_ids = []`) | Reflects true group historical performance regardless of roster changes or import mapping gaps |
| Leaderboards | Current BuddyBoss group roster only (`$roster_user_ids`) | Rankings represent active members; showing departed members in leaderboards is confusing and misleading |

### Implementation location

`IST_Group_Extension::display()` in `frontend/class-ist-group-extension.php`. The `$all_user_ids`
and `$roster_user_ids` variables are defined there and passed to `IST_Stats_Query` methods accordingly.

### Why this matters for future changes

If new query methods are added to Group Stats, apply the same rule: use `$all_user_ids` for any
aggregate or totals query, use `$roster_user_ids` only for member-ranked views (leaderboards, per-member tables).

Do not inadvertently revert this by copying a My Stats query (which correctly uses the individual
user's ID) into the Group Stats controller without removing the user filter.
