# ONE TALIBON V1 — W02 Dashboard Hierarchy

ROLE: `KIRION FORGE: CODE WRITER`

Repository: `Kirch-Nairu/Talibon-Sales-Prototype`

Technical Authority: Kirch Ivan Balite

Forge authority: `Kirch-Nairu/KIRION-FORGE@44eb57e5b45b343be0033bf22a7a5e74d543c01a`

Correction integration branch: `KIRCH-TALIBON-V1-UIUX-CORRECTION`

Exact integration source used to create this writer branch: `903046298b211906c30b47b938fb063df0741e49`

Writer branch: `KIRCH-TALIBON-UIUX-W02-DASHBOARD-HIERARCHY`

The current writer-start SHA is the directly observed HEAD of this branch after this handoff commit. Verify it before mutation and stop if the branch moved unexpectedly.

## Main directive

Apply Operational Compression to Home/Dashboard. The first meaningful viewport must tell a municipal user what requires action now, what is coming next, what the current operating picture is, and what information is merely reference/history.

The governing hierarchy is:

`ACT NOW → NEXT / SOON → CURRENT OPERATING PICTURE → REFERENCE / HISTORY`

Do not solve this by merely shrinking everything. Recompose hierarchy, grouping, prominence, ordering, and progressive disclosure while preserving truthful data and role relevance.

## Accepted decisions

- Home is the highest-priority operational surface;
- immediate attention/overdue/changed/next work outranks broad reference information;
- useful data should be retained but lower-value material may move lower, compact, group, or progressively disclose;
- summaries must come from existing data or displayed records where practical;
- no fake charts, fake metrics, invented efficiency claims, decorative analytics, or fabricated municipal state;
- the existing role experience types remain authoritative for this wave;
- do not create bespoke HR/Legislative experiences here; W7 owns those corrections;
- do not create the persistent right utility rail here; W5 owns it.

## Owned files

Writer may edit only:

- `resources/js/pages/Dashboard.tsx`
- `resources/js/components/dashboard/**`
- tests directly required to validate Dashboard behavior

Existing municipal Dashboard data sources under `resources/js/data/municipal/` are readable dependencies but are **not owned for mutation** by W02 unless the Maintainer explicitly expands scope.

Do not edit `.forge/**` except this handoff remains read-only evidence.

## Explicitly forbidden ownership

Do not edit:

- `resources/js/components/shell/**`
- `resources/js/layouts/**`
- `resources/js/components/PageFrame.tsx`
- `resources/js/components/PageHeader.tsx`
- `resources/js/navigation/**`
- Planning pages/components
- Calendar pages/components
- Messages pages/components
- HRIS
- Legislative pages
- Admin pages
- Errors
- persistent utility rail implementation
- municipal fixture/data sources without Maintainer expansion
- unrelated backend/auth/session logic

If implementation requires crossing this boundary, STOP and return the collision to the Maintainer.

## Current source shape to correct

At the shared source authority, `Dashboard.tsx` renders a long sequential composition including:

- Dashboard header;
- attention summary;
- attention queue or system overview;
- operational metric groups;
- office or executive overview;
- MPDO updates;
- project portfolio;
- schedule;
- recent documents;
- recent correspondence;
- municipal updates;
- office activity;
- activity rail;
- quick actions.

The components are individually useful; the correction target is their cumulative hierarchy and task order.

## Required outcomes

1. Establish a visible, source-supported **ACT NOW** stratum using current attention/overdue/action data.
2. Establish **NEXT / SOON** for deadlines, schedule, and time-sensitive follow-up without duplicating the same information unnecessarily.
3. Place office/executive/system metrics and project state into a coherent **CURRENT OPERATING PICTURE**.
4. Demote recent documents, general updates, activity/history, and other reference material to **REFERENCE / HISTORY** when they are not immediately actionable.
5. Preserve persona relevance for Executive, Department Head, Employee, and System Administration using existing experience contracts.
6. Keep MPDO-specific planning updates coherent without allowing them to dominate unrelated personas.
7. Preserve truthful navigation/actions and remove no critical operational path merely for visual neatness.
8. Avoid giant whitespace, hero treatment, consumer-social layout, startup SaaS styling, or marketing copy.
9. Preserve light/dark parity and responsive integrity within Dashboard-owned components.
10. Keep the Dashboard compatible with the W01 shell changes without modifying W01-owned primitives.

## Commit-density directive

Commit aggressively at every real independently reviewable improvement.

Target approximately **35–60 meaningful atomic commits** if the actual work naturally supports that granularity. This is not a quota. A lower count is correct if fewer real atomic changes exist.

Every Forge-controlled commit must use:

`KIRCH-FORGE-CODE-WRITER-<REASON>`

Prefer W02 in the reason, for example:

- `KIRCH-FORGE-CODE-WRITER-W02-DEFINE-ACT-NOW-REGION`
- `KIRCH-FORGE-CODE-WRITER-W02-PRIORITIZE-OVERDUE-WORK`
- `KIRCH-FORGE-CODE-WRITER-W02-GROUP-NEXT-SOON-SCHEDULE`
- `KIRCH-FORGE-CODE-WRITER-W02-COMPOSE-OPERATING-PICTURE`
- `KIRCH-FORGE-CODE-WRITER-W02-DEMOTE-REFERENCE-HISTORY`
- `KIRCH-FORGE-CODE-WRITER-W02-TIGHTEN-DASHBOARD-RESPONSIVE-FLOW`

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
- focused Dashboard tests where available
- browser/runtime review for Executive, Department Head, Employee, and System Administration where available
- 1440x900 and 1280-class laptop first-viewport review
- 430-class / 390x844 mobile review where available
- light and dark checks
- keyboard/focus review for any Dashboard interaction introduced or changed
- exact candidate SHA GitHub Actions state if observable

A useful runtime review should answer whether a user can identify the main current action, overdue state, next deadline/event, and operating picture without scanning the full page.

Do not convert unavailable validation into PASS. Use `NOT RUN`, `NOT OBSERVED`, or `UNKNOWN`.

The repository-level Forge UIUX workflow is expected to run on this branch when pushed; its presence is not a green-CI claim.

## Stop conditions

STOP if:

- branch HEAD differs unexpectedly before work begins;
- source authority cannot be reconciled;
- W01 or another writer has modified a W02-owned Dashboard file;
- a required correction needs shell/layout/navigation ownership;
- a required correction needs HR/Legislative bespoke role policy or persistent utility architecture;
- backend/data contract changes become necessary;
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
