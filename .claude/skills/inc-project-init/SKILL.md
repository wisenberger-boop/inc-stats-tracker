---
name: inc-project-init
description: Initialize or refresh Claude project context for INC Stats Tracker. Use at the start of a new Claude session, after editor restart, or when resuming plugin work.
---

# INC Stats Tracker — Startup Context

Read in order:

1. `AGENTS.md` — canonical shared instructions (also used by Codex and other agents); covers
   the startup reading list, the version/changelog consistency check, packaging rules, and the
   release checklist pointer
2. `CLAUDE.md`
3. Newest entry in `plugin/inc-stats-tracker/CHANGELOG.md` (confirm it matches the `Version:`
   header in `inc-stats-tracker.php` — flag a mismatch before making further changes)

Reference docs (consult when touching the relevant area): `docs/database-schema.md`,
`docs/hooks-reference.md`, `docs/naming-conventions.md`.

Do not run releases, packaging, or version bumps during init — see `AGENTS.md` for the rules
that apply once work starts.
