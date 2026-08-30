# INC Stats Tracker — Project Health

## Current Review — 2026-08-29

### Executive Status

INC Stats Tracker is a coherent WordPress/BuddyBoss plugin in late stabilization. Development runs
active `1.0.9` and Production runs active `1.0.8`; the Production HTTPS `www` target, ManageWP full-site backup,
named rollback owner, and plugin-only rollback archive were verified during the approved deployment.
Bounded Production runtime QA passed without exposing raw records, and the owner's authenticated
screenshot confirms the Group Stats page renders.

The local `1.0.9` candidate consolidates the Group Stats KPI layout into three cards: Referrals and
Closed Business share the first row, Closed Business contains its FY/MTD amount and count context, and
Connects spans a compact second row. Deterministic tests, responsive fixture inspection, and reproducible
packaging and reviewer-only audit pass. The exact approved Development deployment and bounded smoke
checks also pass, followed by a post-deployment reviewer PASS; human real-theme acceptance remains
before any Production consideration.

### Current Scope

- Native Closed Business, Referral, and Connect entry with BuddyBoss membership enforcement.
- Member My Stats and BuddyBoss Group Stats reporting.
- Fiscal-year totals, trends, comparisons, attribution, recent records, and roster leaderboards.
- Embedded current-month Closed Business and Referrals Given member leaderboards.
- Historical CSV import/purge separated from native submissions by `data_source`.
- Authenticated read-only current-user REST summary.
- Plugin-only SSH/WP-CLI release path with separate Development and Production gates.

Referral/Connect editing, bulk utilities, and imported-row deletion warnings remain future work.

### Completion

Recommended completion: **95% for the current tracked scope (medium confidence)**.

| Category | Score | Evidence |
|---|---:|---|
| Scope and planning | 10/10 | Product, 1.0.9 layout criteria, non-goals, safety boundaries, and gates are explicit. |
| Implementation | 35/35 | Core plugin, both MTD leaderboards, and the approved three-card KPI layout are implemented. |
| Data/content/config | 13/15 | Both targets and rollback metadata are configured; historical-data retention is unresolved. |
| Verification | 19/20 | Lint, date/query/render/deploy contracts, reproducible ZIP, Development acceptance, Production real-data markers, and reviewer PASS exist; no full integration/browser suite. |
| Deploy/readiness | 9/10 | Development 1.0.9 is active with rollback/smoke evidence; Production remains separately gated on 1.0.8. |
| Documentation/handoff | 9/10 | Canonical state distinguishes deployed 1.0.8 from local 1.0.9; release files remain uncommitted/unpushed. |

### Baseline Verified This Review

- Development: active `1.0.9` with approved rollback and bounded layout/bootstrap/REST/log evidence.
- Production: active `1.0.8` with its prior approved rollback/runtime evidence.
- PHP syntax: 56/56 plugin PHP files passed.
- Release metadata: five checked sources report `1.0.9`.
- Fiscal-year regression: four boundary cases and three production-source contracts passed.
- Monthly leaderboards/layout: 13 SQL, 25 source/UI, and nine runtime-render contracts passed.
- Deployment tooling: 43 contracts passed.
- `1.0.9` ZIP: 65 plugin-only entries, 162,195 bytes; two builds produced SHA-256
  `29AE3AF2CD30453640270EB39AF9E8A001E6E48F42379A605368CE250B3E634E`.
- Chromium fixture checks passed at 1280×900 and mobile-emulated 390×844 with no overflow or console issues.
- Targeted secret scan: no private-key/token/password material; every `.local` file is ignored.
- `git diff --check`: passed apart from informational Windows line-ending warnings.

### What's Healthy

- `wp_users.ID` remains canonical; BuddyBoss calls stay isolated to the member service.
- Historical aggregates and current-roster leaderboard scope are explicit and preserved.
- Referral MTD reuses the established prepared referral query instead of duplicating SQL.
- Empty-roster guards prevent fallback to unfiltered member rankings.
- The shared KPI component uses semantic ordered lists, accessible headings, escaped output,
  metric-specific count/currency formatting, and explicit empty states.
- Primary writes remain protected by login/ownership or capabilities plus nonces.
- Packaging is plugin-only and now runs non-interactively with checked exits.
- Development and Production configuration, approvals, and rollback data remain isolated.

### Critical Findings and Risks

No P0 security or data-integrity finding was identified. This was a targeted review, not a penetration test.

| Priority | Finding | Required action |
|---|---|---|
| P1 | Cumulative `1.0.6`–`1.0.9` source is uncommitted and untagged. | Prepare a narrow reviewed Git handoff; commit/push only with separate authorization. |
| P1 | Seven tracked historical CSVs (about 12.1 MB) contain operational/member data. | Owner must choose retention/history policy; do not inspect, delete, or rewrite casually. |
| P1 | `1.0.9` needs Development real-theme acceptance. | Review the deployed three-card layout at desktop/mobile/zoom before Production consideration. |
| P2 | Automated browser, migration, permission, and full integration coverage is limited. | Add targeted environment-backed tests as the plugin evolves. |
| P2 | `IST_DB::get_rows()` accepts dynamic identifiers/order strings. | Keep callers internal and whitelist before user-controlled reuse. |
| P2 | An extra Development archive from a failed `1.0.7` upload remains. | Delete only under separate exact-target authorization. |

### Drift and Cleanup Findings

- Corrected documents that simultaneously described Production as deployed and blocked.
- Replaced the obsolete SSH-failure restart prompt with the verified `1.0.8` workstream.
- Closed stale deployment tasks while retaining dated result packets as history.
- Confirmed Referrals MTD was absent from `1.0.7` source rather than lost in deployment.
- Added the feature without changing the fiscal-year Top Referral Givers leaderboard.
- Repaired the batch packager's non-ASCII parsing and interactive-pause failure.

### Release Readiness

Development `1.0.9` and Production `1.0.8` are active with rollback and bounded runtime evidence.
Development `1.0.9` is **ready for human visual acceptance**.

### Agent Workflow Health

Score: **3/5**. Planning approval, autonomous execution, deterministic QA, reviewer-only Claude, and
deployment hard stops are explicit. Canonical state is coherent, but many control files and release
slices remain outside Git, so future-session portability still depends on this worktree.

### Health Scores

| Area | Score | Rationale |
|---|---:|---|
| Product alignment | 5/5 | Requested three-card hierarchy and preserved reporting behavior are explicit. |
| Architecture | 4/5 | Cohesive layers and query reuse; small duplicated controller patterns remain. |
| Code quality | 4/5 | Scoped, escaped, prepared, and convention-aligned implementation. |
| Test confidence | 4/5 | Strong deterministic contracts, live 1.0.8 markers, and 1.0.9 desktop/mobile fixture evidence; limited full integration coverage. |
| Security/safety | 4/5 | Boundaries are sound; historical-data retention remains unresolved. |
| Documentation | 4/5 | Canonical documents are reconciled; Git portability remains incomplete. |
| Operational readiness | 4/5 | Target isolation, backup, rollback, preflight, and exact-artifact gates exist. |
| Release readiness | 4/5 | Reproducible 1.0.9 is packaged and locally verified but remains undeployed/reviewer-pending. |
| Agent workflow | 3/5 | Strong controls, but uncommitted control artifacts remain a traceability risk. |
| Overall project health | 4/5 | Healthy late-stabilization project with bounded release/governance work remaining. |

### Next Workstream

Perform human Development Group Stats visual QA for `1.0.9`. Keep Production, Git handoff, and CSV
actions behind their separate approvals.

## Health Check History

| Date | Phase | Completion | Overall | P0/P1 summary | Next priority |
|---|---|---:|---:|---|---|
| 2026-08-28 | Stabilization | 80% | 4/5 | Runtime/deploy evidence, historical PII, and Git handoff remained P1. | Real-page acceptance and staged release. |
| 2026-08-29 | Late stabilization | 93% | 4/5 | No P0; uncommitted releases, CSV retention, and human 1.0.8 UI acceptance remain P1. | Development visual acceptance. |
| 2026-08-29 | Production QA | 95% | 4/5 | No P0; Production visual review, Git handoff, and CSV retention remain. | Authenticated Production visual acceptance. |
| 2026-08-29 | Local 1.0.9 candidate | 95% | 4/5 | No P0; Development acceptance, Git handoff, and CSV retention remain. | Authorized Development preflight. |
