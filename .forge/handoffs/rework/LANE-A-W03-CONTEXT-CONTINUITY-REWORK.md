# KIRION FORGE — ONE TALIBON V1

## Lane A W03 Context Continuity — Bounded Rework Handoff

Role call: `KIRION FORGE: CODE WRITER`

Forge authority:

`Kirch-Nairu/KIRION-FORGE@44eb57e5b45b343be0033bf22a7a5e74d543c01a`

Repository:

`Kirch-Nairu/Talibon-Sales-Prototype`

Writer slot:

Lane A only.

Work branch:

`KIRCH-TALIBON-UIUX-SPRINT-LANE-A-W03-W04`

Exact rework starting SHA:

`f61ea353fc26595e78aba99e6aa9b5ea0293181c`

Reviewer evidence:

`.forge/evidence/review/LANE-A-W03-W04-REVIEW-REWORK.md`

This is bounded rework after independent Review. W04 received a source-level PASS and is frozen.

## Mission

Repair W03 so validated list/filter/page context survives not only initial list → detail → Back navigation, but also the existing mutation cycle inside Transactions, Correspondence and Approved Travel Orders.

Do not redesign these workflows. Preserve all business-state, authorization, evidence, routing and domain semantics.

## Required defect repairs

### 1. Prevent nested `return_to`

The canonical list return target must never embed another `return_to` value.

Before a list URL becomes the carried return context:

- require an internal path;
- require the exact expected list pathname;
- preserve legitimate list query/filter/page parameters;
- remove `return_to` itself;
- reject protocol-relative, external-origin, wrong-path, backslash-containing and malformed values;
- fall back to the canonical expected list route when invalid.

### 2. Transactions mutation continuity

Current detail actions POST to the existing transaction transition endpoint. The current controller redirects to a fresh detail URL and drops the list context.

Carry the validated Transactions return context through the POST and subsequent redirect.

If the actor can still view the updated transaction, redirect to the transaction detail while preserving the validated return context in the detail URL.

If the action legitimately removes detail visibility from the actor, redirect to the validated Transactions list context rather than forcing a forbidden detail route or discarding filters.

Do not change workflow action semantics or policy checks.

### 3. Correspondence mutation continuity

Carry the validated Correspondence return context through register, classify, route and act actions.

For actions that remain on the workspace, the resulting workspace URL must preserve the validated return context.

For the existing route action that returns to the Correspondence list, return to the validated list context instead of unconditionally dropping to canonical `/correspondence`.

Do not change correspondence lifecycle semantics, evidence handling, correlation IDs or authorization.

### 4. Approved Travel Order mutation continuity

Carry the validated Travel Orders return context through status update.

After status mutation, redirect to the same Travel Order detail with the validated list context preserved.

Do not change travel-order status semantics, service behavior, authorization or evidence handling.

## Server trust boundary

Do not trust a raw client `return_to` merely because frontend code created it.

The server side must validate any return target used for redirects against the expected internal list route. A small shared server-side helper/value utility is acceptable if it reduces duplication and remains narrowly scoped.

No open redirect may be introduced.

## Expected mutable scope

Frontend, only where needed:

- `resources/js/navigation/returnContext.ts`
- `resources/js/pages/Transactions/Show.tsx`
- `resources/js/pages/Correspondence/Show.tsx`
- `resources/js/pages/TravelOrders/Show.tsx`

Backend, only where required for redirect continuity:

- `app/Http/Controllers/TransactionController.php`
- `app/Http/Controllers/CorrespondenceWorkspaceActionController.php`
- `app/Http/Controllers/TravelOrderController.php`
- a narrowly-scoped return-context helper/request utility if justified
- directly relevant feature tests

If the exact current action request classes must admit/validate `return_to`, they may be changed narrowly. Do not broaden their domain rules.

Do not modify W04 Planning files. Do not touch Lane B, Dashboard, shell/AppLayout, authentication/session, unrelated controllers or data sources.

## Tests required

Add or extend automated tests for at least:

- nested `return_to` stripping;
- invalid/external/wrong-route return targets falling back safely;
- Transactions action retaining filtered/paged return context;
- Transactions access-loss path returning to validated list context;
- Correspondence register/classify/act retaining detail return context;
- Correspondence route returning to validated list context;
- Travel Order status update retaining detail return context.

Do not weaken existing tests.

## Validation

Before return:

- verify remote branch is exactly the rework starting SHA before mutation;
- preserve linear history; no rebase or force push;
- inspect starting-SHA → final-SHA diff for bounded ownership;
- run frontend install/typecheck/build;
- run Laravel feature tests / obtain Forge UIUX Validation against exact final SHA;
- if browser runtime is available, test filtered list → detail → mutation → contextual Back for each governed workflow;
- otherwise label browser/runtime evidence NOT OBSERVED;
- push final candidate and re-read remote HEAD.

## Writer Return

Return exactly:

1. starting SHA;
2. final SHA;
3. remote HEAD verification;
4. exact changed files;
5. nested-return repair;
6. Transactions continuity result;
7. Correspondence continuity result;
8. Travel Orders continuity result;
9. added/changed tests;
10. commit list/count;
11. exact-final-SHA CI state;
12. browser/runtime evidence state;
13. ownership/collision result;
14. remaining risks/unknowns;
15. `WRITER AUTHORITY RETURNED TO MAINTAINER.`

No self-review, Acceptance, merge, integration, promotion, deployment, rebase or force push.
