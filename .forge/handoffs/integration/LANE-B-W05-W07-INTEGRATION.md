# KIRION FORGE — ONE TALIBON V1

## Lane B W05/W06/W07 — Bounded Integration Writer Handoff

### ROLE CALL

`KIRION FORGE: INTEGRATION WRITER`

Forge authority:

`Kirch-Nairu/KIRION-FORGE@44eb57e5b45b343be0033bf22a7a5e74d543c01a`

Repository:

`Kirch-Nairu/Talibon-Sales-Prototype`

Integration branch:

`KIRCH-TALIBON-V1-UIUX-CORRECTION`

The invoking Maintainer prompt MUST provide the exact current integration-branch SHA. Verify it immediately before any branch-affecting action.

## Accepted integration candidate

Branch:

`KIRCH-TALIBON-UIUX-SPRINT-LANE-B-W05-W06-W07`

Exact candidate SHA:

`af417bab384ad066814ba32145be83c00396ff69`

Exact sprint base:

`5727e5a258ecb358d6caa13b75127bec1c5c6d9d`

Pre-recovery SHA:

`270b1919109d33312e5552694b773c18d5108509`

Independent Review evidence:

`.forge/evidence/review/LANE-B-W05-W07-REVIEW-SUITABLE.md`

Acceptance evidence:

`.forge/evidence/acceptance/LANE-B-W05-W07-INTEGRATION-READINESS-ACCEPTED.md`

Acceptance result:

`ACCEPT FOR INTEGRATION WITH RECORDED LIMITATIONS`

## Mission

Mechanically integrate the exact accepted Lane B candidate into the exact current correction integration branch without redesign, source repair, history rewriting, scope expansion or evidence inflation.

This is not W08, release acceptance, UAT, deployment or production promotion.

## Required preflight

Before mutation, independently verify:

1. integration branch is exactly the SHA supplied by Maintainer;
2. Lane B remote branch is still exactly `af417bab384ad066814ba32145be83c00396ff69`;
3. exact candidate lineage remains 5 ahead / 0 behind sprint base `5727e5a258ecb358d6caa13b75127bec1c5c6d9d`;
4. exact-final-SHA Forge UIUX Validation run #103 / ID `35140216527` remains completed/success;
5. Review evidence gives `SUITABLE FOR ACCEPTANCE` for this exact candidate;
6. Acceptance evidence gives `ACCEPT FOR INTEGRATION WITH RECORDED LIMITATIONS` for this exact candidate;
7. current integration branch has no unexpected source overlap with the candidate's 10 owned files;
8. candidate diff remains exactly the accepted Lane B product surface and contains no governance/backend/auth/session expansion.

Stop and return `BLOCKED` on authority drift, unexpected overlap, candidate movement, failing exact-candidate CI, missing evidence or any need for source repair.

## PR transport

PR #5 may be used as the mechanical transport only if, at execution time:

- it is still open;
- base branch is `KIRCH-TALIBON-V1-UIUX-CORRECTION`;
- head branch is `KIRCH-TALIBON-UIUX-SPRINT-LANE-B-W05-W06-W07`;
- head SHA is exactly `af417bab384ad066814ba32145be83c00396ff69`;
- GitHub reports it clean/mergeable against the then-current integration branch.

The existing PR body contains a stale historical head SHA and is not authority. Do not use its prose as evidence.

If PR #5 cannot safely transport the exact accepted candidate, stop rather than silently substituting another candidate or repairing conflicts.

## Integration method

Preserve accepted candidate history.

Preferred method: ordinary merge commit of the exact Lane B branch / PR #5 into the correction integration branch.

Do not squash.
Do not rebase.
Do not cherry-pick individual Lane B commits unless a later Maintainer handoff explicitly authorizes that recovery method.
Do not force push.

Do not modify the accepted Lane B candidate branch.

## Post-integration verification

After the mechanical merge:

1. re-read remote `KIRCH-TALIBON-V1-UIUX-CORRECTION` and return its exact new HEAD SHA;
2. verify the integration commit contains the accepted Lane B history and no unrelated source changes;
3. verify the 10 Lane B files exist in the integrated state as expected;
4. observe the fresh `Forge UIUX Validation` run triggered by the integration-branch push;
5. require that exact integration-HEAD run to complete successfully for both frontend typecheck/build and Laravel feature tests before returning a successful Integration Writer result;
6. preserve all Acceptance limitations as still open: browser/runtime, responsive, theme, focus/body-scroll, rendered-ID uniqueness, accessibility, combined Lane A + Lane B, UAT, deployment and production runtime evidence remain unobserved unless directly established.

If integration-HEAD CI fails, return `INTEGRATED BUT VALIDATION FAILED` with the exact integration SHA and failure evidence. Do not repair under Integration Writer authority.

## Lane A / W08 boundary

Lane A is independently in repeat Review at a different exact candidate. Do not touch Lane A.

W08 remains blocked until Lane A also survives Review + Acceptance + integration and the combined W03–W07 state is explicitly advanced by Maintainer.

## Forbidden actions

Do not implement.
Do not repair.
Do not redesign.
Do not edit candidate source.
Do not edit Lane A.
Do not squash.
Do not rebase.
Do not force push.
Do not deploy.
Do not claim runtime/browser/accessibility evidence not observed.
Do not start or complete W08.

## Required Integration Writer Return

Return exactly these sections:

1. **Authority preflight** — exact starting integration SHA, exact candidate SHA, Review/Acceptance/CI verification.
2. **Integration method** — PR/merge mechanism actually used.
3. **Resulting integration SHA** — exact remote correction HEAD and merge-parent evidence.
4. **Integrated scope** — confirm the exact accepted Lane B surface and no unrelated source repair.
5. **Exact integration-HEAD validation** — workflow run number, ID, exact head SHA, final status/conclusion and job disposition.
6. **Recorded limitations** — preserve all unobserved evidence layers.
7. **W08 disposition** — still not authorized by this integration alone.
8. End with `INTEGRATION WRITER AUTHORITY RETURNED TO MAINTAINER.`

Then state:

`No implementation repair performed.`

`No deployment performed.`
