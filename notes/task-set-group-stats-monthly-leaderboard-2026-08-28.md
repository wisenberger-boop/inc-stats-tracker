# Task Set — Group Stats Monthly Closed Business Leaderboard

Date: 2026-08-28
Status: Approved for autonomous execution

## Objective

Improve the Weekly Meetings Group INC Stats Reports KPI area by clarifying the Closed Business
Count fiscal-year period, matching the visual emphasis of Closed Business MTD and FY values, and
showing which active members submitted the most Closed Business amount during the current month.

## Approved Scope

1. Label Closed Business Count as the current fiscal year **YTD**.
2. Style the Closed Business Amount and Count MTD numbers with the same large, bold INC-blue
   treatment used for their FY values.
3. Add a current-month leaderboard directly below the MTD amount inside the Closed Business
   Amount KPI card.
4. Rank current BuddyBoss group members by the sum of Closed Business amount they submitted in
   the current month, using `submitted_by_user_id`, with submission count as supporting context.
5. Preserve the existing FY Closed Business Sources leaderboard, which intentionally ranks
   credited sources rather than submitters.
6. Provide populated and empty leaderboard states using semantic, responsive markup.
7. Document the feature under `[Planned — Next Release]` without changing version metadata or
   creating a release package.
8. Run deterministic QA and Claude reviewer-only review through PASS or a hard stop.

## Acceptance Criteria

- Closed Business Count displays a label such as `FY 2026–27 YTD`.
- Amount and Count MTD numbers use the same 18px, 700-weight, `#1e4e8c` treatment as FY values.
- The Amount card displays up to ten active-member rows ordered by monthly submitted amount,
  then submission count, with rank, name, formatted amount, and pluralized submission count.
- Imported/unresolved submitters and former members are excluded from this member leaderboard by
  the current BuddyBoss roster filter; aggregate KPI totals remain unchanged and include all rows.
- No-member/no-submission states render a concise message instead of an empty table/list.
- Existing FY source/referral/connect leaderboards and My Stats behavior remain unchanged.
- PHP/JavaScript syntax, query-contract test, source/UI assertions, and project verification pass.

## Allowed Autonomy

- Reversible edits to the Group Stats controller/template, shared KPI partial, stats query class,
  frontend CSS, tests, and planned-release documentation.
- Task-local fixes required by deterministic or Claude review findings.

## Hard Stops and Non-Goals

- No version bump, package rebuild, staging/production mutation, deployment, Git commit/push, CSV
  mutation, or history rewrite.
- No change to aggregate-reporting scope or the existing FY credited-source leaderboard meaning.
- No new frontend dependency, chart library, database table, migration, or write path.
- Human visual acceptance remains required because no live BuddyBoss browser target is configured.

## Verification and Review Gates

- `php -l` on every changed PHP file and full project verification.
- Standalone query-contract test with a fake `$wpdb` object.
- Static assertions for label wiring, optional leaderboard rendering, semantic markup, and scoped
  MTD emphasis.
- `node --check` on existing JavaScript assets.
- Responsive/CSS source review for narrow layouts, long names, empty state, and hierarchy.
- Tool-disabled Claude reviewer-only PASS/FAIL review and fix/rereview loop.
