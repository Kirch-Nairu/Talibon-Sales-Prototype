# KIRION FORGE — ONE TALIBON V1

## W01 — SHELL COMPACTION & DENSITY FOUNDATION

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

`KIRCH-TALIBON-UIUX-W01-SHELL-DENSITY-REWORK`

Exact required starting SHA:

`fa9fadf137c200081f2b96c2b87ca7dd4137aa2d`

This rework branch was created directly from the reviewed W01 candidate. Verify the remote branch resolves exactly to the SHA above before changing anything.

Do not rewrite the original W01 candidate branch. Do not force push.

## Reviewer verdict being repaired

Reviewed candidate:

`KIRCH-TALIBON-UIUX-W01-SHELL-DENSITY@fa9fadf137c200081f2b96c2b87ca7dd4137aa2d`

Verdict: **REWORK**

Blocking defect:

The W01 Appearance disclosure is source-confirmed to clip horizontally inside the mobile navigation drawer. The expanded/mobile footer invokes `SidebarAppearanceMenu` in compact mode; compact mode anchors a `w-56` panel using `left-0` from a narrow trigger while the mobile sidebar container clips overflow. This can hide most Appearance controls at mobile widths.

Likely related risk: the absolute `bottom-full` placement can also clip vertically in constrained-height navigation drawers.

## Mission

Repair only the Appearance disclosure/mobile containment defect and directly related geometry/interaction behavior.

The result must preserve W01's accepted shell-density outcomes while making Appearance controls reachable in expanded desktop, compact desktop, tablet/mobile navigation, and constrained-height contexts.

Do not redesign the shell.

## Owned mutation surface

Primary owned files for this rework:

- `resources/js/components/shell/SidebarAppearanceMenu.tsx`
- `resources/js/components/shell/SidebarFooter.tsx`

You may read the surrounding mobile/sidebar implementation for containment context.

If repair genuinely requires mutation outside those two files, STOP and return the cross-owned requirement to Maintainer rather than broadening scope silently.

## Required behavior

The repaired disclosure must:

- remain a secondary control rather than regaining dominant footer weight;
- keep Appearance keyboard-reachable;
- remain programmatically named;
- render the complete Appearance controls within the usable navigation viewport on mobile widths;
- avoid horizontal clipping at 430-class, 390×844, and 360-class widths;
- avoid obvious constrained-height clipping or inaccessible placement;
- preserve Switch Workspace and Sign out priority/reachability;
- preserve light/dark behavior;
- preserve existing shell semantics and mobile dialog behavior;
- avoid portaling or global overlay architecture unless absolutely necessary for this bounded fix;
- introduce no unrelated density, navigation, backend, auth, Dashboard, or domain changes.

Use geometry appropriate to the actual container. Do not merely hide overflow symptoms or shrink controls below usable size.

## Validation

Required before return:

- inspect the exact start-to-final diff for scope purity;
- `npm ci` or establish dependency state honestly;
- `npm run types:check`;
- `npm run build`;
- observe exact-final-SHA GitHub Actions conclusion if available;
- if browser execution is available, exercise Appearance open/close and control reachability at representative desktop plus 430-class, 390×844, and 360-class mobile widths;
- if browser execution is available, include at least one constrained-height check;
- if browser execution is available, inspect light and dark and keyboard-only opening/closing;
- if runtime/browser is unavailable, report it as NOT OBSERVED. Do not fabricate evidence.

The rework does not need unrelated feature tests unless changed behavior requires them; repository-wide CI still provides backend regression evidence.

## Commit policy

Use meaningful atomic commits only.

Commit identity:

`KIRCH-FORGE-CODE-WRITER-W01-<REASON>`

Do not create artificial commits to increase count. This is a narrow rework wave and a small number of commits is expected.

## Stop conditions

STOP and return to Maintainer if:

- remote rework branch does not resolve exactly to the required starting SHA before your first mutation;
- the fix requires mutation outside the bounded files above;
- a merge/rebase from another writer or integration branch appears necessary;
- a destructive history operation would be required;
- the Appearance control cannot be made reachable without reopening a previously accepted shell decision.

## Required writer return

Return:

1. starting SHA;
2. final SHA;
3. remote HEAD verification;
4. exact files changed;
5. concise repair summary;
6. commit list/count;
7. validation actually observed;
8. browser/mobile/light-dark/keyboard evidence actually observed or explicit NOT OBSERVED;
9. exact-final-SHA CI state;
10. ownership/collision statement;
11. remaining risks/unknowns;
12. `WRITER AUTHORITY RETURNED TO MAINTAINER.`

Do not integrate, promote, merge, or deploy.
