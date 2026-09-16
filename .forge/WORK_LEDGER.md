# ONE TALIBON V1 — Active Work Ledger

## Program authority

Baseline: `0913a37f96affd2c2a681697bdf6fdb6c381a99e`

Integration branch: `KIRCH-TALIBON-V1-UIUX-CORRECTION`

Technical authority: Kirch Ivan Balite

## Ledger rules

- Maintainer owns this ledger and wave state transitions.
- A row does not authorize work by itself.
- Each writer requires an exact handoff and starting SHA.
- Candidate SHA must be recorded before review.
- Review/Acceptance results must be recorded before integration.
- Writers must stop on authority drift or ownership collision.

## Current program

| Wave | Scope | State | Depends on | Candidate | Acceptance |
| --- | --- | --- | --- | --- | --- |
| G0 | Recon → Reviewer → correction-baseline Acceptance | CLOSED | — | `0913a37f...` | ACCEPT WITH RECORDED LIMITATION |
| N0 | Forge Nest materialization + pre-Nest decision closure | ACTIVE | G0 | Maintainer-owned | pending remote verification |
| W1 | Shell Compaction & Density Foundation | NOT ISSUED | N0 | — | — |
| W2 | Dashboard Hierarchy | NOT ISSUED | W1 | — | — |
| W3 | Context-Preserving Review Workflows | NOT ISSUED | W1 | — | — |
| W4 | Planning Responsive UX | NOT ISSUED | W1 | — | — |
| W5 | Calendar + Persistent Utility Rail | NOT ISSUED | W1 | — | — |
| W6 | Messaging Quick Access | NOT ISSUED | W5 | — | — |
| W7 | Role / HRIS / Admin / Error Completion | NOT ISSUED | W1 + W2 | — | — |
| W8 | Cross-Product Acceptance & Harness Expansion | NOT ISSUED | W2 + W3 + W4 + W5 + W6 + W7 | — | — |

## Collision policy

Before issuing parallel writers, the Maintainer must assign explicit ownership blocks and inspect overlapping shared files. Shared shell/navigation/design-token ownership must never be implicitly shared between active writers.

## Current blocking evidence

No blocker prevents Nest materialization.

Execution-layer evidence remains open and will be required by the relevant writer/acceptance waves:

- build/type checks;
- PHP tests when touched;
- browser/runtime;
- responsive task coverage;
- accessibility acceptance.
