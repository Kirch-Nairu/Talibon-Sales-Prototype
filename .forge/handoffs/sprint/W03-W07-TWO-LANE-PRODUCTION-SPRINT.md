# KIRION FORGE — ONE TALIBON V1

## W03–W07 Two-Lane Production Sprint

Role boundary: `KIRION FORGE: CODE WRITER`

Forge authority: `Kirch-Nairu/KIRION-FORGE@44eb57e5b45b343be0033bf22a7a5e74d543c01a`

Sprint source baseline: `KIRCH-TALIBON-V1-UIUX-CORRECTION@5757114a02fc5d407e0f8cf4b7b2026c6e824e5f`

This baseline contains accepted W01 shell-density and W02 dashboard-hierarchy work. The P1 integration validation state is tracked separately and must not be overstated by either writer.

# Execution shape

Exactly two implementation lanes exist for W03–W07. Do not create writer-per-wave branches.

Both writers start from the exact sprint source baseline above. Do not merge or rebase the other lane. Do not touch the correction branch directly. Do not force push.

## Lane A — Context & Planning

Branch: `KIRCH-TALIBON-UIUX-SPRINT-LANE-A-W03-W04`

Carries:
- W03 Context-Preserving Review Workflows
- W04 Planning Responsive UX

Primary ownership:
- `resources/js/pages/Transactions/**`
- `resources/js/pages/Correspondence/**`
- `resources/js/pages/Memoranda/**`
- `resources/js/pages/TravelOrders/**`
- `resources/js/pages/Plans/**`
- `resources/js/pages/PPAs/**`
- `resources/js/pages/ProjectMonitoring/**`
- `resources/js/components/work-queue/**`
- `resources/js/components/correspondence/**`
- `resources/js/components/plans/**`
- `resources/js/components/ppas/**`
- `resources/js/components/project-monitoring/**`
- a new narrowly scoped internal return-context helper under `resources/js/navigation/**` if required

Required outcomes:
1. Preserve useful list/filter/page context across list → detail → return for governed review workflows where current source loses it. Use only validated internal return targets; never trust arbitrary external URLs.
2. Keep existing truthful routes/backend behavior. Do not fabricate related records or new workflow semantics.
3. Planning pages must not require long horizontal action hunting on 430-class, 390×844, or 360-class layouts. Desktop tables may remain, but narrow layouts require direct card/adaptive actions.
4. Plan/PPA/project details must remain near the selected record on non-wide screens rather than after a long register.
5. Preserve light/dark treatment, keyboard names/focus, and existing data contracts.

Hard exclusions:
- `resources/js/layouts/AppLayout.tsx`
- `resources/js/components/shell/**`
- `resources/js/pages/Calendar/**`
- `resources/js/pages/Messages/**`
- `resources/js/pages/Announcements/**`
- `resources/js/pages/Hris/**`
- `resources/js/pages/Admin/**`
- `resources/js/pages/Employees/**`
- `resources/js/pages/Legislation/**`
- `resources/js/pages/Errors/**`
- Dashboard implementation
- backend/auth/session/data-source redesign

## Lane B — Utilities & Role Completion

Branch: `KIRCH-TALIBON-UIUX-SPRINT-LANE-B-W05-W06-W07`

Carries, in dependency order:
- W05 Calendar + Persistent Utility Rail
- W06 Messaging Quick Access after the W05 rail exists
- W07 Role / HRIS / Admin / Error Completion

Primary ownership:
- `resources/js/layouts/AppLayout.tsx`
- new utility-rail components under `resources/js/components/shell/**`
- `resources/js/pages/Calendar/**`
- relevant `resources/js/components/meetings/**`
- `resources/js/pages/Messages/**`
- `resources/js/components/messages/**`
- `resources/js/pages/Announcements/**`
- `resources/js/components/announcements/**`
- `resources/js/pages/Hris/**`
- `resources/js/pages/Admin/**`
- `resources/js/pages/Employees/**`
- `resources/js/pages/Legislation/**`
- relevant `resources/js/components/legislative/**`
- `resources/js/pages/Errors/**`

Required outcomes:
1. Add a restrained persistent wide-desktop utility surface for real Calendar / Announcements / recent Messages context without crushing the primary canvas.
2. On laptop/tablet/mobile, utilities must collapse to a compact accessible control/drawer or equivalent. Preserve keyboard reachability and focus recovery.
3. W06 quick Messages is READ ONLY. It may show recent real/source-backed coordination summaries and open the full Messages experience. No quick compose, fake thread route, or hidden history search.
4. Calendar must expose existing end-time, all-day, and location data when present and reduce dual-schedule ambiguity.
5. Remove ordinary visible Audit & Security entrypoints while preserving underlying authorized security behavior under Administration.
6. Normalize inherited W07 debt: HRIS operational copy/dark parity, HR/Legislative discoverability/accessibility, Employee Directory narrow-screen usability, and 403/status recovery/presentation.
7. Do not create fake Users/Departments implementations or change backend authorization.

Hard exclusions:
- Lane A pages/components
- Dashboard implementation
- backend/auth/session/data-source redesign

# Shared writer validation

Before return:
- verify branch still descends from the exact sprint baseline;
- inspect exact diff for ownership collision;
- `npm ci` or exact-SHA CI dependency install;
- `npm run types:check`;
- `npm run build`;
- Laravel feature tests through Forge UIUX Validation;
- focused source checks for the lane outcomes;
- browser/runtime evidence only if actually observed.

Do not claim responsive, browser, accessibility, UAT, deployment, or production evidence from CI alone.

# Writer return

Return:
- starting SHA;
- final SHA;
- remote HEAD verification;
- exact changed files;
- outcome summary mapped to carried W-waves;
- meaningful commit list/count;
- exact-SHA CI state;
- browser/runtime evidence state;
- ownership/collision result;
- remaining risks/unknowns;
- `WRITER AUTHORITY RETURNED TO MAINTAINER.`

No integration, promotion, deployment, force push, or self-acceptance.

# W08 boundary

W08 is not carried by either writer lane as an implementation-complete claim. After both reviewed lanes are integrated into the correction branch, Maintainer must create the integrated W08 evidence state and exercise the strongest available combined CI/browser/responsive/accessibility validation. Missing runtime evidence stays missing; it is not inferred.