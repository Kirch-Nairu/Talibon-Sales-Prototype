# ONE TALIBON V1 — Lane B Maintainer Recovery Inspection

Role: KIRION FORGE: MAINTAINER

This is not an independent Reviewer verdict.

## Context

The Lane B Code Writer session suffered a ChatGPT message-delivery timeout before a normal Writer Return was delivered. Maintainer therefore reconstructed the observable remote state from GitHub rather than assuming the work was lost or complete.

Lane:

`KIRCH-TALIBON-UIUX-SPRINT-LANE-B-W05-W06-W07`

Required sprint base:

`5727e5a258ecb358d6caa13b75127bec1c5c6d9d`

Observed remote head:

`270b1919109d33312e5552694b773c18d5108509`

Lineage:

- ahead 4;
- behind 0;
- merge base equals exact sprint base.

Observed commits:

1. `ea4dd4f6fa9a91259873247f35aeee7bd6046e93` — `KIRCH-FORGE-CODE-WRITER-W05-UTILITY-RAIL-CALENDAR`
2. `4461ffc0fe01de7f288ae308ce6382eac3eb5a5a` — `KIRCH-FORGE-CODE-WRITER-W06-READONLY-MESSAGES-UTILITY`
3. `6d9ac1f727b30ecb1c353df499929cb8d1a2ec85` — `KIRCH-FORGE-CODE-WRITER-W07-ROLE-PRESENTATION-COMPLETION`
4. `270b1919109d33312e5552694b773c18d5108509` — `KIRCH-FORGE-CODE-WRITER-W07-HRIS-DARK-PARITY`

Observed changed files are inside Lane B ownership:

- `resources/js/components/meetings/MunicipalCalendarAgenda.tsx`
- `resources/js/components/shell/MunicipalUtilities.tsx`
- `resources/js/layouts/AppLayout.tsx`
- `resources/js/pages/Admin/Index.tsx`
- `resources/js/pages/Calendar/Index.tsx`
- `resources/js/pages/Employees/Index.tsx`
- `resources/js/pages/Errors/403.tsx`
- `resources/js/pages/Errors/Status.tsx`
- `resources/js/pages/Hris/Dashboard.tsx`
- `resources/js/pages/Legislation/Index.tsx`

No Lane A ownership collision was observed.

## Validation

Draft PR #5 targets the correction branch from the Lane B branch.

Exact final SHA workflow run:

- Forge UIUX Validation run `#98`
- run ID `35134692709`
- exact head SHA `270b1919109d33312e5552694b773c18d5108509`
- conclusion `SUCCESS`

Observed jobs:

- frontend dependency install: PASS;
- TypeScript check: PASS;
- production build: PASS;
- Laravel container/environment setup: PASS;
- Composer install: PASS;
- Laravel feature tests: PASS.

Browser/runtime/responsive/light-dark/keyboard/zoom evidence remains NOT OBSERVED.

## Source-level outcome review

The remote work materially implements the intended W05–W07 direction:

- W05 adds a wide-desktop municipal utility rail, a compact dialog-based utility surface below the wide breakpoint, and clearer Calendar schedule semantics including end time, all-day and location display.
- W06 adds read-only recent coordination preview linked to the full Messages experience, without quick compose or fake thread behavior.
- W07 removes the visible Admin Audit & Security link, improves Employee Directory narrow-layout presentation, strengthens HRIS copy/dark parity, improves Legislative search/filter semantics, and normalizes 403/status recovery presentation.

## Confirmed recovery defects before formal Review

### 1. Utility drawer can become hidden while leaving the page scroll-locked across the 2xl breakpoint

`MunicipalUtilityDrawer` calls `showModal()` and sets `document.body.style.overflow = 'hidden'` until the drawer component unmounts.

The dialog itself is styled `2xl:hidden`, while the trigger is also `2xl:hidden` and the persistent rail becomes visible at `2xl`.

If a user opens the drawer below 2xl and then widens the viewport into the 2xl range, CSS can hide the still-mounted modal without changing `utilitiesOpen`. The cleanup does not run, so body overflow remains locked and focus may remain associated with hidden modal content.

This violates the W05 adaptive-utility requirement and must be repaired before independent Review.

### 2. Duplicate DOM IDs are created when the utility drawer is open

`MunicipalUtilityContent` hard-codes:

- `utility-calendar-title`
- `utility-announcements-title`
- `utility-messages-title`

The same component is rendered in the always-mounted desktop rail and again inside the drawer. When the drawer is open, duplicate IDs exist in the document even if the rail is CSS-hidden at that breakpoint. This makes the associated `aria-labelledby` relationships ambiguous and violates basic document/accessibility correctness.

The repair should provide unique IDs per surface or remove the shared hard-coded-ID collision while preserving semantic labelling.

## Process recovery note

PR #5 body still names `6d9ac1f727b30ecb1c353df499929cb8d1a2ec85` as its head, while the live PR head is now `270b1919109d33312e5552694b773c18d5108509`. This is stale descriptive evidence, not a code-lineage defect. The next Writer Return must report the live final SHA and remote verification explicitly.

## Disposition

Do not integrate and do not start independent Review yet.

Return the same Lane B Code Writer slot to bounded recovery on the same linear branch from exact current head `270b1919109d33312e5552694b773c18d5108509`.

After repair, require a normal Writer Return and fresh exact-final-SHA CI. Then issue independent Review.
