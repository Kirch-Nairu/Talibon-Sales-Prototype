# ONE TALIBON V1 — W01 Shell Compaction & Density Foundation

ROLE: `KIRION FORGE: CODE WRITER`

Repository: `Kirch-Nairu/Talibon-Sales-Prototype`

Technical Authority: Kirch Ivan Balite

Forge authority: `Kirch-Nairu/KIRION-FORGE@44eb57e5b45b343be0033bf22a7a5e74d543c01a`

Correction integration branch: `KIRCH-TALIBON-V1-UIUX-CORRECTION`

Exact integration source used to create this writer branch: `903046298b211906c30b47b938fb063df0741e49`

Writer branch: `KIRCH-TALIBON-UIUX-W01-SHELL-DENSITY`

The current writer-start SHA is the directly observed HEAD of this branch after this handoff commit. Verify it before mutation and stop if the branch moved unexpectedly.

## Main directive

Apply Operational Compression to the shared shell and page-density foundation: reduce wasted vertical space, action hunting, footer competition, and shell overhead while preserving municipal professionalism, accessibility, real backend behavior, light/dark parity, and existing navigation semantics.

This is not a redesign. Preserve the current municipal information architecture and strong mobile-dialog behavior.

## Accepted decisions

- preserve independent scrollable navigation and fixed footer architecture;
- compress sidebar identity;
- demote Appearance relative to operational actions;
- keep Switch Workspace and Sign out directly reachable;
- reduce header vertical cost without destroying municipal identity;
- Records search remains Records search; compact/expand presentation is allowed but universal-search semantics are not;
- shared density should be improved through coherent primitives, not random page-local font shrinking;
- no persistent utility rail in this wave;
- no dashboard implementation work in this wave.

## Owned files

Writer may edit only:

- `resources/js/components/shell/**`
- `resources/js/layouts/AppLayout.tsx`
- `resources/js/components/PageFrame.tsx`
- `resources/js/components/PageHeader.tsx`
- `resources/js/navigation/**` only when strictly necessary for shell metadata or hierarchy presentation; do not change approved top-level IA
- tests directly required to validate the owned shell behavior

Do not edit `.forge/**` except this handoff remains read-only evidence.

## Explicitly forbidden ownership

Do not edit:

- `resources/js/pages/Dashboard.tsx`
- `resources/js/components/dashboard/**`
- Planning pages/components
- Calendar pages/components
- Messages pages/components
- HRIS
- Legislative
- Admin domain pages
- Errors
- persistent utility rail implementation
- unrelated backend/auth/session logic

If implementation requires crossing this boundary, STOP and return the collision to the Maintainer.

## Required outcomes

1. Sidebar/footer remains structurally stable at desktop and constrained laptop heights.
2. Navigation keeps primary visual priority.
3. Identity uses materially less vertical space while retaining useful office/person context.
4. Appearance is available but no longer dominates the footer.
5. Switch Workspace and Sign out are easy to reach without awkward footer competition.
6. Header consumes less vertical space where safely possible.
7. Records search stays semantically truthful and does not become fake universal search.
8. `PageFrame` / `PageHeader` density improves coherently without indiscriminate typography reduction.
9. Workspace selector restores opener focus where the owned shell implementation permits the fix.
10. Existing mobile dialog focus management, body-lock behavior, keyboard behavior, and dark mode must not regress.

## Commit-density directive

Commit aggressively at every real independently reviewable improvement.

Target approximately **40–70 meaningful atomic commits** if the actual work naturally supports that granularity. This is not a quota. A lower count is correct if fewer real atomic changes exist.

Every Forge-controlled commit must use:

`KIRCH-FORGE-CODE-WRITER-<REASON>`

Prefer W01 in the reason, for example:

- `KIRCH-FORGE-CODE-WRITER-W01-COMPACT-SIDEBAR-IDENTITY`
- `KIRCH-FORGE-CODE-WRITER-W01-DEMOTE-APPEARANCE-CONTROL`
- `KIRCH-FORGE-CODE-WRITER-W01-PRESERVE-WORKSPACE-ACTIONS`
- `KIRCH-FORGE-CODE-WRITER-W01-REDUCE-HEADER-VERTICAL-COST`
- `KIRCH-FORGE-CODE-WRITER-W01-TIGHTEN-PAGE-FRAME-SPACING`
- `KIRCH-FORGE-CODE-WRITER-W01-RESTORE-WORKSPACE-FOCUS`

Prohibited commit-maxing behavior:

- empty commits;
- whitespace-only commits;
- change/revert pairs for count;
- knowingly broken intermediate commits solely to split history;
- meaningless rename churn;
- splitting one inseparable change merely to inflate count.

## Validation

Before handoff, run what the environment actually supports and report exact evidence:

- `npm ci`
- `npm run types:check`
- `npm run build`
- focused tests for owned behavior where available
- browser/runtime checks at 1440x900, 1280-class laptop, 430-class mobile, 390x844, and constrained laptop height where available
- keyboard/focus checks for modified dialogs/controls
- light and dark checks
- exact candidate SHA GitHub Actions state if observable

Do not convert unavailable validation into PASS. Use `NOT RUN`, `NOT OBSERVED`, or `UNKNOWN`.

The repository-level Forge UIUX workflow is expected to run on this branch when pushed; its presence is not a green-CI claim.

## Stop conditions

STOP if:

- branch HEAD differs unexpectedly before work begins;
- source authority cannot be reconciled;
- W02 or another writer has modified an owned W01 file;
- a necessary fix requires dashboard/domain/backend ownership outside this handoff;
- destructive Git action or force push would be required;
- a product decision must be invented rather than derived from accepted decisions.

## Writer return

Push the writer branch, verify its remote HEAD, and report:

- exact starting SHA;
- exact final SHA;
- commit count and commit list/grouping;
- files changed;
- validation commands and results;
- GitHub Actions state if observed;
- screenshots/runtime evidence if actually captured;
- unresolved risks/unknowns;
- explicit confirmation that no integration/promotion/deployment occurred.

End with:

`WRITER AUTHORITY RETURNED TO MAINTAINER. No integration performed. No promotion performed. No deployment performed.`
