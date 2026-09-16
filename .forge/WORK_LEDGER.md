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
- Each writer/rework writer requires an exact handoff and starting SHA.
- Candidate SHA must be recorded before review.
- Review/Acceptance results must be recorded before integration.
- Writers must stop on authority drift or ownership collision.
- Parallel writers must own non-overlapping files unless an explicit Integration handoff resolves a planned shared-file touch.
- Commit density is encouraged only through real atomic changes. No empty, revert-for-count, whitespace-only, or artificial split commits.

## Writer plan

Eight writer waves are planned for the correction program.

Current execution state: **P1 RETURNED TO BOUNDED REWORK**.

W1 and W2 rework may run in parallel because their mutation surfaces remain isolated.

### Parallel Batch P1 — REVIEW REWORK REQUIRED

Original writer starts:

- W1 `KIRCH-TALIBON-UIUX-W01-SHELL-DENSITY@916fa6406abcfa4e4c00c603b5d6db43a5fed4f0`
- W2 `KIRCH-TALIBON-UIUX-W02-DASHBOARD-HIERARCHY@3940ee3f59746eea7863155c92ff6b623d3a4719`

Reviewed writer candidates:

- W1 `fa9fadf137c200081f2b96c2b87ca7dd4137aa2d`
- W2 `cac9cef03354eb58d66a24809c9f702c0d78af51`

Both candidates had exact-SHA Forge UIUX Validation **SUCCESS**, but both received Reviewer verdict **REWORK** for source-confirmed semantic/interaction defects.

### W1 rework authority

Branch:

`KIRCH-TALIBON-UIUX-W01-SHELL-DENSITY-REWORK`

Exact starting SHA:

`fa9fadf137c200081f2b96c2b87ca7dd4137aa2d`

Blocking Reviewer defect: Appearance disclosure can be clipped/unreachable inside the mobile drawer because the expanded/mobile footer invokes compact disclosure geometry that expands outside an overflow-clipping container.

Rework handoff:

`.forge/handoffs/rework/W01-SHELL-DENSITY-REWORK.md`

### W2 rework authority

Branch:

`KIRCH-TALIBON-UIUX-W02-DASHBOARD-HIERARCHY-REWORK`

Exact starting SHA:

`cac9cef03354eb58d66a24809c9f702c0d78af51`

Blocking Reviewer defect: ACT NOW treats generic non-completed `recentWork` as immediate-attention work even when the available source does not prove overdue, due-today, unassigned, or action-required semantics.

Rework handoff:

`.forge/handoffs/rework/W02-DASHBOARD-HIERARCHY-REWORK.md`

## Current program

| Wave | Scope | State | Depends on | Candidate / anchor | Review / Acceptance |
| --- | --- | --- | --- | --- | --- |
| G0 | Recon → Reviewer → correction-baseline Acceptance | CLOSED | — | `0913a37f...` | ACCEPT WITH RECORDED LIMITATION |
| N0 | Forge Nest materialization + pre-Nest decision closure | CLOSED | G0 | `9df14d2d...` | remote materialization verified |
| P0 | GitHub Actions + parallel-writer preparation | CLOSED | N0 | source `90304629...` | workflow installed and PHP runtime aligned to lock |
| W1 | Shell Compaction & Density Foundation | REWORK ISSUED | N0 + P0 | reviewed `fa9fadf...`; rework starts there | Reviewer: REWORK; Acceptance NOT STARTED |
| W2 | Dashboard Hierarchy | REWORK ISSUED | N0 + P0 | reviewed `cac9cef...`; rework starts there | Reviewer: REWORK; Acceptance NOT STARTED |
| W3 | Context-Preserving Review Workflows | NOT ISSUED | W1 | — | — |
| W4 | Planning Responsive UX | NOT ISSUED | W1 | — | — |
| W5 | Calendar + Persistent Utility Rail | NOT ISSUED | W1 | — | — |
| W6 | Messaging Quick Access | NOT ISSUED | W5 | — | — |
| W7 | Role / HRIS / Admin / Error Completion | NOT ISSUED | W1 + W2 | — | — |
| W8 | Cross-Product Acceptance & Harness Expansion | NOT ISSUED | W2 + W3 + W4 + W5 + W6 + W7 | — | — |

## Reviewer results

### W1

Verdict: **REWORK**.

Ownership: PASS.

Commit history: PASS.

Exact-SHA CI: PASS.

Blocking defect: mobile Appearance disclosure containment/reachability.

Runtime/browser/responsive/light-dark/keyboard evidence remains NOT OBSERVED.

Durable review evidence:

`.forge/evidence/review/W01-SHELL-DENSITY-REVIEW-REWORK.md`

### W2

Verdict: **REWORK**.

Ownership: PASS.

Commit history: CONCERN due substantial fragmentation, but not an independent blocker.

Exact-SHA CI: PASS.

Blocking defect: generic recent work can be mislabeled as ACT NOW immediate attention.

Runtime/browser/persona/responsive/light-dark/runtime accessibility evidence remains NOT OBSERVED.

Durable review evidence:

`.forge/evidence/review/W02-DASHBOARD-HIERARCHY-REVIEW-REWORK.md`

## Exact-candidate validation already observed

### W1 reviewed candidate

`fa9fadf137c200081f2b96c2b87ca7dd4137aa2d`

GitHub Actions run `#35`, run ID `35095466589`: **SUCCESS**.

### W2 reviewed candidate

`cac9cef03354eb58d66a24809c9f702c0d78af51`

GitHub Actions run `#60`, run ID `35096458349`: **SUCCESS**.

These successful runs do not validate future rework SHAs. Each rework candidate requires fresh exact-final-SHA validation.

## Rework collision policy

W1 rework is restricted primarily to:

- `resources/js/components/shell/SidebarAppearanceMenu.tsx`
- `resources/js/components/shell/SidebarFooter.tsx`

W2 rework is restricted primarily to:

- `resources/js/components/dashboard/dashboardSelectors.ts`
- `resources/js/pages/Dashboard.tsx` only if necessary;
- directly related Dashboard-owned components only if strictly necessary.

Neither rework may merge/rebase the other or the correction integration branch. Cross-owned requirements return to Maintainer.

## Evidence still open

The following remain open after Reviewer returns:

- browser/runtime behavior;
- responsive task coverage;
- visual light/dark parity;
- runtime keyboard/focus behavior;
- combined W1 + W2 behavior;
- broader accessibility acceptance.

W1 rework should collect mobile/constrained-height Appearance evidence when browser execution is available.

W2 rework must provide explicit evidence/reasoning that generic recent/on-track records are excluded from ACT NOW unless a real immediate-attention condition is provable.

## Next authorized Maintainer action

Run the two bounded Code Writer rework sessions from their exact rework branch authorities.

After each rework Writer Return:

1. re-read remote final SHA;
2. verify scope and lineage;
3. observe exact-final-SHA CI;
4. repeat bounded Reviewer review against the corrected candidate;
5. only a non-REWORK Reviewer result may proceed to the separate Acceptance gate.

Do not integrate either reviewed candidate. Do not start Acceptance while either current candidate remains under REWORK.
