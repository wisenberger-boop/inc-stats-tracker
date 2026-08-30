# Task Registry — INC Stats Tracker

Last updated: 2026-08-29

| Priority | Status | Workstream | Evidence / next action |
|---|---|---|---|
| P1 | Production deployed / awaiting visual acceptance | `1.0.9` three-card KPI layout | Both targets active 1.0.9. Development visual acceptance passed. Production rollback, plugin/layout/bootstrap/BuddyBoss/REST/log markers pass; obtain Production real-theme acceptance. |
| P1 | Superseded by 1.0.9 | `1.0.8` Referrals Given MTD | Preserved in 1.0.9; both targets have advanced to 1.0.9. |
| P1 | Superseded | Production `1.0.7` | Replaced by the exact approved `1.0.8` deployment; prior Closed Business MTD slice remains preserved. |
| P1 | Superseded | Development `1.0.7` | Replaced by the exact approved `1.0.8` deployment. Extra old failed-attempt archive still awaits cleanup approval. |
| P1 | Fix/rereview in progress | Intentional `1.0.9` Git handoff | Commits `1ebd785`, `080b8ea`, and `f01b0d4` are on `origin/main`. First rereview exposed a packet-builder HEAD omission; fix and contract test are pending verification/push/rereview. |
| P1 | Blocked | Historical CSV privacy/retention | Owner decision required before moving, deleting, sanitizing, or rewriting history. |
| P2 | Ready | WordPress/BuddyBoss integration checks | Add activation, permission, schema, REST, and member workflow coverage. |
| P2 | Ready | Repository-listed later compatibility target | Test the target stack before changing `Tested up to: 6.8`. |
| P2 | Ready | Empty-roster guards for existing FY leaderboards | Prevent accidental empty-scope fallback in three pre-existing FY methods. |
| P2 | Deferred | Shared prior-YTD date helper | Consider extracting duplicated controller calculation into `IST_Fiscal_Year`. |
| P3 | Backlog | Referral and Connect editing | Future release feature. |
| P3 | Backlog | Bulk record utilities | Require capability and confirmation safeguards. |
| P3 | Backlog | Imported-row delete warning | Planned admin UX improvement. |

Superseded/closed:

- Fiscal-year YTD timezone correction: implemented in `86efee5` and released in `1.0.6`.
- Release metadata drift: reconciled during the 2026-08-28 health cleanup.
- Dual-environment SSH reuse and Production canonical URL resolution: complete.
- Development and Production `1.0.7` deployment gates: complete under dated result records.
- Closed Business MTD leaderboard: released in `1.0.7` and visually accepted by the owner.
- Referrals Given MTD: released as `1.0.8`; Development visually accepted and Production deployed.
