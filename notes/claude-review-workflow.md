# Codex and Claude Workflow

## Roles

- The user and Codex collaborate on planning, scope, tradeoffs, acceptance criteria, and material decisions.
- The user explicitly approves the written task plan before execution begins.
- After approval, Codex executes the plan autonomously through implementation, deterministic QA, results documentation, Claude review, justified fixes, and rereview.
- Claude is reviewer only. Claude does not edit files, execute tests, access external systems, or grant approval.

## Autonomous Execution Contract

Within the approved plan, Codex should make reasonable task-local decisions, diagnose and correct incidental failures, run all applicable verification, and continue the fix-and-rereview loop until Claude returns PASS or a hard stop is reached. Codex should not return to the user for routine implementation choices already covered by the approved plan.

Claude exit `0` means PASS. Exit `2` means Codex must assess the findings, fix justified issues, document evidence for rejected findings, rerun affected verification, and resubmit. Other nonzero exits are review-mechanism failures, not review verdicts.

## Hard Stops

Stop and request user direction for material scope expansion, ambiguous business intent, unauthorized staging/production or other external writes, destructive non-disposable data actions, new secret/private-data authority, permission barriers, or acceptance of critical/high residual risk.

Project-specific safety rules always take precedence.
