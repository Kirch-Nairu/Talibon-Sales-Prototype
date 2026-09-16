# KIRION FORGE — ONE TALIBON V1

## W02 — DASHBOARD HIERARCHY

### BOUNDED REWORK CODE WRITER HANDOFF

ROLE CALL:

`KIRION FORGE: CODE WRITER`

## Forge authority

Repository: `Kirch-Nairu/KIRION-FORGE`

Authority: `main`

Pinned Forge SHA: `44eb57e5b45b343be0033bf22a7a5e74d543c01a`

Use the normal bounded Forge Code Writer bootstrap. This is writer rework authority only.

## Target repository

`Kirch-Nairu/Talibon-Sales-Prototype`

Technical Authority: Kirch Ivan Balite

Correction coordination branch: `KIRCH-TALIBON-V1-UIUX-CORRECTION`

The coordination branch is governance context only. Do not merge, rebase, or integrate it.

## Rework branch and exact starting authority

Work only on:

`KIRCH-TALIBON-UIUX-W02-DASHBOARD-HIERARCHY-REWORK`

Exact required starting SHA:

`cac9cef03354eb58d66a24809c9f702c0d78af51`

This rework branch was created directly from the reviewed W02 candidate. Verify the remote branch resolves exactly to the SHA above before changing anything.

Do not rewrite the original W02 candidate branch. Do not force push.

## Reviewer verdict being repaired

Reviewed candidate:

`KIRCH-TALIBON-UIUX-W02-DASHBOARD-HIERARCHY@cac9cef03354eb58d66a24809c9f702c0d78af51`

Verdict: **REWORK**

Blocking defect:

`dashboardAttentionWork()` merges generic non-completed `recentWork` into ACT NOW. The available `recentWork` source is a recent-work collection rather than an action-required collection and may include work merely created by the actor rather than work actually requiring that actor's immediate action.

Therefore an active `on_track` record can be presented under **Work requiring attention** without source evidence that it is overdue, due today, unassigned, or otherwise immediately actionable.

That violates the governing ACT NOW semantics.

## Mission

Correct ACT NOW semantics so every item presented there is supported by available source data as immediate-attention work.

Do not weaken labels to conceal the defect. Do not treat generic recency as action requirement.

Preserve the accepted hierarchy:

`ACT NOW → NEXT / SOON → CURRENT OPERATING PICTURE → REFERENCE / HISTORY`

## Owned mutation surface

Primary owned files for this rework:

- `resources/js/components/dashboard/dashboardSelectors.ts`
- `resources/js/pages/Dashboard.tsx` only if necessary to wire truthful selector semantics or labels
- directly related files under `resources/js/components/dashboard/**` only if strictly necessary for the fix

Do not mutate:

- `resources/js/data/municipal/**`;
- backend/query/controller code;
- shell/layout/navigation/shared page primitives;
- Planning/Calendar/Messages/HRIS/Legislative/Admin/Error surfaces;
- auth/session behavior.

If truthful ACT NOW semantics require backend changes or new data fields, STOP and return that requirement to Maintainer. Do not fabricate a frontend proxy for unavailable backend truth.

## Required semantic contract

ACT NOW may contain only work whose currently available data truthfully establishes an immediate-attention reason.

Safe examples include conditions that are directly provable from the existing model/source, such as:

- overdue work;
- work whose due date is today when that can be established from existing `dueAt` data;
- explicitly unassigned or attention-required work only where the existing data source actually exposes that fact;
- already accepted office/executive unresolved collections where the role contract intentionally treats unresolved scoped work as ACT NOW.

Generic `recentWork`, creation recency, update recency, mere non-completed status, or mere authorship must not by themselves qualify a record for ACT NOW.

Do not infer current-user assignment from `assignedEmployee` unless the candidate has reliable current-actor identity data available in its existing W02-owned inputs. If that relationship cannot be proven, do not claim it.

If an item is only upcoming or due soon, place/leave it in NEXT/SOON or current-state context as appropriate rather than duplicating it into ACT NOW.

The corrected selector should remain deterministic, deduplicated, and ordered coherently.

## Preserve accepted W02 behavior

Do not regress:

- explicit four-stage hierarchy/order;
- System Administration office follow-up treatment;
- MPDO-only planning context;
- executive completed-work demotion to history;
- first-view compaction;
- reduced duplicate unresolved displays;
- truthful existing routes;
- heading/region semantics;
- light/dark responsive classes already present.

## Validation

Required before return:

- inspect exact start-to-final diff for scope purity;
- `npm ci` or establish dependency state honestly;
- `npm run types:check`;
- `npm run build`;
- observe exact-final-SHA GitHub Actions conclusion if available;
- source-test the selector against at least these reasoning cases, whether through an existing test mechanism or explicit evidence in the return:
  - completed recent item → not ACT NOW;
  - on-track generic recent item with no proven attention reason → not ACT NOW;
  - overdue item → ACT NOW when in an eligible role scope;
  - due-today item → ACT NOW when provable from existing data;
  - future due-soon/on-track item → not ACT NOW solely because it is recent;
- if browser execution is available, inspect representative Employee, Department Head, Executive, and System Administration dashboards to ensure ACT NOW remains truthful and hierarchy is intact;
- if runtime/browser is unavailable, report NOT OBSERVED rather than inferring success.

## Commit policy

Use meaningful atomic commits only.

Commit identity:

`KIRCH-FORGE-CODE-WRITER-W02-<REASON>`

The prior Reviewer recorded a history-fragmentation concern. For this narrow rework, prefer the smallest coherent set of commits that expresses real reviewable changes. Do not split semantics, labels, and trivial formatting into separate commits merely for count.

## Stop conditions

STOP and return to Maintainer if:

- remote rework branch does not resolve exactly to the required starting SHA before first mutation;
- truthful ACT NOW classification requires a backend/query/schema change;
- the fix requires mutation outside W02 ownership;
- another branch must be merged/rebased;
- a destructive history operation would be required;
- preserving truthful semantics would require reopening an accepted product decision.

## Required writer return

Return:

1. starting SHA;
2. final SHA;
3. remote HEAD verification;
4. exact files changed;
5. concise semantic repair summary;
6. commit list/count;
7. explicit reasoning/evidence for ACT NOW inclusion/exclusion cases;
8. validation actually observed;
9. browser/persona/responsive/light-dark/accessibility evidence actually observed or explicit NOT OBSERVED;
10. exact-final-SHA CI state;
11. ownership/collision statement;
12. remaining risks/unknowns;
13. `WRITER AUTHORITY RETURNED TO MAINTAINER.`

Do not integrate, promote, merge, or deploy.
