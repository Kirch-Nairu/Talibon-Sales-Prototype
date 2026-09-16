# KIRION FORGE — ONE TALIBON V1

## Lane B W05–W07 Recovery Handoff

Role call: `KIRION FORGE: CODE WRITER`

Forge authority:

`Kirch-Nairu/KIRION-FORGE@44eb57e5b45b343be0033bf22a7a5e74d543c01a`

Repository:

`Kirch-Nairu/Talibon-Sales-Prototype`

Lane branch:

`KIRCH-TALIBON-UIUX-SPRINT-LANE-B-W05-W06-W07`

Exact recovery starting SHA:

`270b1919109d33312e5552694b773c18d5108509`

This recovery follows a ChatGPT message-delivery timeout. The remote candidate survived and has green exact-SHA CI, but Maintainer pre-review found two source-level defects that must be repaired before the normal independent Reviewer gate.

Read:

`.forge/evidence/maintainer/LANE-B-W05-W07-RECOVERY-INSPECTION.md`

## Scope

Primary mutable file:

- `resources/js/components/shell/MunicipalUtilities.tsx`

`resources/js/layouts/AppLayout.tsx` may be changed only if required to make breakpoint transition state correct.

Do not broaden into Calendar, Messages, Admin, HRIS, Employee Directory, Legislative, errors, Lane A, Dashboard, backend, auth, session, or data-source redesign unless a direct compile requirement caused by the bounded repair makes a tiny related adjustment unavoidable.

## Required repair 1 — responsive drawer state

Repair the source-confirmed breakpoint-transition defect:

- opening the utility drawer below 2xl must not leave an invisible modal/body-scroll lock after viewport growth into the 2xl persistent-rail range;
- focus must not remain trapped or associated with hidden drawer content;
- preserve the wide-desktop rail and compact below-2xl access pattern;
- preserve Escape/backdrop/explicit-close behavior and focus restoration;
- do not remove the modal semantics merely to avoid the defect.

A matchMedia/breakpoint-aware close path or an equivalent coherent solution is acceptable.

## Required repair 2 — unique utility labelling

`MunicipalUtilityContent` is rendered in both rail and drawer. Eliminate duplicate IDs for Calendar, Announcements and Messages section headings.

Requirements:

- every rendered `id` must remain unique;
- each section must retain an accessible name;
- do not weaken semantics to decorative-only text;
- use a surface-specific ID prefix/namespace or another simple deterministic solution.

## Preserve accepted Lane B work

Do not regress:

- W05 Calendar schedule clarity and utility rail;
- W06 read-only Messages preview boundary;
- W07 Admin/HRIS/Employee/Legislative/error improvements;
- light/dark behavior;
- existing backend/data behavior;
- no quick compose or fake thread route;
- no visible Audit & Security ordinary entrypoint.

## Validation

Before return:

- verify remote branch equals the exact recovery starting SHA before mutation;
- keep history linear; no force push or rebase;
- inspect start-to-final diff and confirm bounded ownership;
- run `npm ci --no-audit --no-fund` when available;
- run `npm run types:check`;
- run `npm run build`;
- obtain fresh Forge UIUX Validation against the exact final SHA, including Laravel feature tests;
- if browser runtime is available, specifically test drawer open → resize across 2xl → resize back, Escape, backdrop, close button, focus restoration, and unique IDs;
- if browser runtime is unavailable, label all such checks NOT OBSERVED.

## Commit guidance

Use one or a small number of meaningful commits:

`KIRCH-FORGE-CODE-WRITER-LANE-B-RECOVERY-<REASON>`

Do not pad commit count.

## Writer Return

Return:

1. starting SHA;
2. final SHA;
3. remote HEAD verification;
4. exact changed files;
5. drawer breakpoint repair summary;
6. accessibility ID repair summary;
7. commit list/count;
8. exact-final-SHA CI state;
9. browser/runtime evidence state;
10. ownership/collision result;
11. remaining risks/unknowns;
12. `WRITER AUTHORITY RETURNED TO MAINTAINER.`

No integration, independent Review, Acceptance, promotion, deployment, merge, rebase, or force push.
