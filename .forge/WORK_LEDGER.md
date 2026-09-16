# ONE TALIBON V1 — Active Work Ledger

## Program authority

Baseline: `0913a37f96affd2c2a681697bdf6fdb6c381a99e`

Integration branch: `KIRCH-TALIBON-V1-UIUX-CORRECTION`

Technical authority: Kirch Ivan Balite

## Main directive

Correct One Talibon V1 into a dense, coherent, municipal operations workspace through **Operational Compression**: reduce unnecessary scrolling, wasted whitespace, action hunting, route bouncing, context loss, horizontal task travel, and equal visual weighting of unequal information while preserving accessibility, municipal professionalism, real backend behavior, light/dark parity, and role relevance.

This program is not a visual rewrite, marketing redesign, backend replacement, or feature-fabrication wave.

## Ledger rules

- Maintainer owns this ledger and wave state transitions.
- A row does not authorize work by itself.
- Each writer requires an exact handoff and starting SHA.
- Candidate SHA must be recorded before review.
- Review/Acceptance results must be recorded before integration.
- Writers must stop on authority drift or ownership collision.
- Parallel writers must own non-overlapping files unless an explicit Integration handoff resolves a planned shared-file touch.
- Commit density is encouraged only through real atomic changes. No empty, revert-for-count, whitespace-only, or artificial split commits.

## Writer plan

Eight writer waves are planned for the correction program.

Current concurrency: **2 writers in parallel**.

### Parallel Batch P1 — ISSUED

- W1 — Shell Compaction & Density Foundation
- W2 — Dashboard Hierarchy

Both writer branches were created from exact prepared integration source:

`903046298b211906c30b47b938fb063df0741e49`

They are deliberately file-isolated. W2 does not edit shared shell/page primitives; W1 does not edit dashboard implementation.

Writer-start authorities after Maintainer handoff + CI alignment:

- W1 `KIRCH-TALIBON-UIUX-W01-SHELL-DENSITY@916fa6406abcfa4e4c00c603b5d6db43a5fed4f0`
- W2 `KIRCH-TALIBON-UIUX-W02-DASHBOARD-HIERARCHY@3940ee3f59746eea7863155c92ff6b623d3a4719`

### Later batches

- P2: W3 Context-Preserving Review Workflows + W4 Planning Responsive UX, after W1 integration.
- P3: W5 Calendar + Persistent Utility Rail + W7 Role / HRIS / Admin / Error Completion, after required W1/W2 integration.
- W6 Messaging Quick Access follows W5 because it depends on the persistent utility architecture.
- W8 Cross-Product Acceptance & Harness Expansion follows all product correction waves.

Concurrency may be reduced whenever ownership or dependency evidence makes parallel execution unsafe.

## Current program

| Wave | Scope | State | Depends on | Candidate / anchor | Acceptance |
| --- | --- | --- | --- | --- | --- |
| G0 | Recon → Reviewer → correction-baseline Acceptance | CLOSED | — | `0913a37f...` | ACCEPT WITH RECORDED LIMITATION |
| N0 | Forge Nest materialization + pre-Nest decision closure | CLOSED | G0 | `9df14d2dc09022e7f9f569163db402fd622ea8b4` | remote materialization verified |
| P0 | GitHub Actions + parallel-writer preparation | CLOSED | N0 | source `903046298b211906c30b47b938fb063df0741e49` | workflow installed and PHP runtime aligned to lock |
| W1 | Shell Compaction & Density Foundation | ISSUED / ACTIVE | N0 + P0 | `916fa6406abcfa4e4c00c603b5d6db43a5fed4f0` | writer pending |
| W2 | Dashboard Hierarchy | ISSUED / ACTIVE | N0 + P0 | `3940ee3f59746eea7863155c92ff6b623d3a4719` | writer pending |
| W3 | Context-Preserving Review Workflows | NOT ISSUED | W1 | — | — |
| W4 | Planning Responsive UX | NOT ISSUED | W1 | — | — |
| W5 | Calendar + Persistent Utility Rail | NOT ISSUED | W1 | — | — |
| W6 | Messaging Quick Access | NOT ISSUED | W5 | — | — |
| W7 | Role / HRIS / Admin / Error Completion | NOT ISSUED | W1 + W2 | — | — |
| W8 | Cross-Product Acceptance & Harness Expansion | NOT ISSUED | W2 + W3 + W4 + W5 + W6 + W7 | — | — |

## Commit-density mode

Writers should commit every independently reviewable improvement rather than batching unrelated work.

Recommended aggressive targets, not quotas:

- W1: approximately 40–70 meaningful atomic commits if the implementation naturally supports that granularity.
- W2: approximately 35–60 meaningful atomic commits if the implementation naturally supports that granularity.

A smaller count is correct when fewer real atomic changes exist. Commit count never outranks correctness, coherence, tests, or reviewability.

Every Forge-controlled commit must use:

`KIRCH-FORGE-<ROLE>-<REASON>`

Writer examples:

- `KIRCH-FORGE-CODE-WRITER-W01-COMPACT-SIDEBAR-IDENTITY`
- `KIRCH-FORGE-CODE-WRITER-W01-REDUCE-HEADER-VERTICAL-COST`
- `KIRCH-FORGE-CODE-WRITER-W02-PRIORITIZE-ACT-NOW`
- `KIRCH-FORGE-CODE-WRITER-W02-COMPRESS-REFERENCE-REGIONS`

## GitHub Actions preparation

`.github/workflows/forge-uiux-validation.yml` is installed on the correction integration branch and writer branches.

Defined CI jobs:

- frontend `npm ci`;
- `npm run types:check`;
- `npm run build`;
- PHP 8.4 dependency install;
- PostgreSQL 16 test service;
- Laravel feature tests via `composer test`.

The first W1 handoff run exposed a CI-environment mismatch: frontend typecheck/build passed, while Composer install failed because the lock resolved Symfony 8.1 packages requiring PHP >=8.4.1 and the workflow had been configured for PHP 8.3. The Maintainer aligned the workflow to PHP 8.4 on integration and both writer branches. Corrected exact-SHA runs were subsequently triggered; their final conclusions must be observed rather than assumed.

Workflow presence is not a passing CI claim. Results must be observed per candidate SHA.

## Collision policy

W1 owns shared shell/layout/density primitives named in its handoff. W2 owns Dashboard implementation/components named in its handoff. Neither may cross into the other's block.

Before later parallel writers are issued, the Maintainer must inspect overlapping shared files again.

## Current blocking evidence

No source blocker prevents W1 and W2 execution.

Execution-layer evidence remains open and must be collected from writer environments and/or GitHub Actions:

- completed exact-candidate CI conclusions;
- browser/runtime;
- responsive task coverage;
- accessibility acceptance.

## Next authorized Maintainer action

Wait for W1 and W2 writer returns. Review each independently before any integration. Do not integrate either candidate merely because it exists or because commit count is high.
