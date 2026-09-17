# KIRION FORGE — ONE TALIBON V1

## Lane A W03/W04 Reviewer Return

Reviewer authority: independent, non-mutating.

Candidate:

`KIRCH-TALIBON-UIUX-SPRINT-LANE-A-W03-W04@f61ea353fc26595e78aba99e6aa9b5ea0293181c`

Exact sprint base:

`5727e5a258ecb358d6caa13b75127bec1c5c6d9d`

## Verdict

`REWORK`

## Authority / lineage

PASS.

- remote candidate verified exact;
- 12 commits ahead / 0 behind exact sprint base;
- merge base equals exact sprint base;
- ownership/history passed;
- exact-final-SHA Forge UIUX Validation run #96, ID `35133625796`, succeeded for frontend install/typecheck/build and Laravel feature tests.

## W04 disposition

SOURCE-LEVEL PASS. No confirmed W04 defect was identified. Plans, PPAs and Project Monitoring have narrow-layout direct record actions with nearby selected detail while wide tables remain available at their governed breakpoints.

W04 is frozen during this rework unless a direct compile requirement makes a tiny related adjustment unavoidable.

## W03 confirmed defects

1. `resources/js/navigation/returnContext.ts` validates an internal list URL but preserves an already-present `return_to` query parameter. Re-entering detail from such a list URL can nest and accumulate stale return context.
2. Transactions workflow mutations redirect to a fresh `transactions.show` route without the captured `return_to`, so contextual Back falls back to canonical `/transactions` after an action.
3. Correspondence register/classify/act redirect to a fresh workspace route without the captured `return_to`; route action redirects directly to canonical `correspondence.index`, losing list context.
4. Approved Travel Order status updates redirect to a fresh detail route without the captured `return_to`, losing the original filtered/paged list context.

Maintainer independently source-confirmed these findings after Reviewer return:

- `TransactionController::transition()` redirects to `transactions.show` without preserved return context, or canonical transactions index when access changes;
- `CorrespondenceWorkspaceActionController` redirects register/classify/act to workspace show without return context and route directly to correspondence index;
- `TravelOrderController::updateStatus()` redirects to travel-order show without return context;
- current `returnContext.ts` does not remove an existing `return_to` before embedding the list URL.

## Required rework direction

Preserve the validated route-specific return context through the governed detail/workspace mutation cycle without changing workflow, authorization, business-state, or data semantics.

Client-supplied return targets must not become open redirects. Any server-side redirect that consumes return context must validate that the target is an internal path for the expected list route. Strip nested `return_to` from the canonical list context before carrying it forward.

If a mutation legitimately makes the detail inaccessible to the actor, returning to the validated list context is acceptable; do not force a now-forbidden detail route.

## Evidence limitations

Browser/runtime, responsive visual behavior, keyboard behavior, light/dark visual parity, zoom/reflow and broader accessibility remain NOT OBSERVED.

## Promotion disposition

WITHHELD. Candidate is not eligible for Acceptance until bounded W03 rework is returned, exact-final-SHA validation is green, and independent repeat Review clears the repaired candidate.

`REVIEWER AUTHORITY RETURNED TO MAINTAINER.`
