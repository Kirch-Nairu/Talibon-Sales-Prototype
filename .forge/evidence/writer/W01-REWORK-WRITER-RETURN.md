# ONE TALIBON V1 — W01 Rework Writer Return

## Authority

Rework branch:

`KIRCH-TALIBON-UIUX-W01-SHELL-DENSITY-REWORK`

Required starting SHA:

`fa9fadf137c200081f2b96c2b87ca7dd4137aa2d`

Returned candidate SHA:

`8bcdb18441e3cdc071a96921ac29616d9391052c`

Maintainer re-read the remote branch and confirmed that it resolves exactly to the returned candidate SHA.

Start-to-final comparison is 2 commits ahead / 0 behind with merge base equal to the required starting SHA.

## Bounded repair

Reviewer-confirmed defect addressed: mobile/expanded Appearance disclosure containment.

Writer changed only:

- `resources/js/components/shell/SidebarAppearanceMenu.tsx`
- `resources/js/components/shell/SidebarFooter.tsx`

Source changes include explicit start/end disclosure alignment, end-alignment for expanded/mobile footer use, bounded `40dvh` disclosure height with internal scrolling/overscroll containment, and Escape close with trigger-focus restoration. Compact desktop retains start alignment.

No shell redesign, portal overlay, navigation change, Dashboard change, backend/auth/domain mutation, merge, rebase, integration, promotion, deployment, or force push was performed.

## Commit evidence

Two commits:

1. `7d72bd41ef7cf8e0422b65ebbfda057dff4a3cc1` — `KIRCH-FORGE-CODE-WRITER-W01-BOUND-APPEARANCE-DISCLOSURE`
2. `8bcdb18441e3cdc071a96921ac29616d9391052c` — `KIRCH-FORGE-CODE-WRITER-W01-ALIGN-MOBILE-APPEARANCE`

## Validation evidence

Writer-local npm execution: NOT RUN because the execution sandbox could not resolve GitHub.

Exact-final-SHA GitHub Actions run `#77`, run ID `35116074412`, head `8bcdb18441e3cdc071a96921ac29616d9391052c`.

Maintainer re-observation at rework-return processing time:

- frontend dependency install: PASS;
- TypeScript check: PASS;
- production build: PASS;
- Laravel container/setup/Composer/environment preparation: PASS;
- Laravel feature tests: IN PROGRESS;
- overall workflow: IN PROGRESS.

No green overall-CI claim is made until the final backend conclusion is observed.

## Open evidence

Browser/runtime: NOT OBSERVED.

The requested 430-class, 390×844, 360-class, constrained-height, light/dark, keyboard-only, zoom/reflow, and runtime focus checks remain unobserved.

## Authority return

WRITER AUTHORITY RETURNED TO MAINTAINER.

No integration, promotion, deployment, merge, rebase, or force push performed.
