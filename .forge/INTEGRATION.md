# ONE TALIBON V1 — Integration Policy

## Active integration authority

`KIRCH-TALIBON-V1-UIUX-CORRECTION`

## Writer boundary

Code Writers do not move the integration branch. They return an exact candidate SHA with validation evidence and limitations.

## Integration prerequisites

Before integrating a candidate:

1. verify the integration branch still resolves to the expected authority SHA;
2. verify the candidate exact SHA and parent/source authority;
3. inspect the candidate diff and ownership boundary;
4. obtain required Reviewer/Acceptance outcome for the stated promotion;
5. ensure unresolved findings/limitations are recorded;
6. stop on unexpected branch movement or overlap.

## Integration method

Prefer non-force integration preserving candidate history. Do not rewrite accepted evidence branches merely to simplify history.

An Integration Writer may perform bounded mechanical integration when explicitly handed off. Product redesign remains Maintainer authority.

## Post-integration verification

After integration:

- verify remote branch HEAD;
- record resulting SHA in the work ledger;
- rerun or preserve the validation required by the integration handoff;
- update evidence/engineering log where the promotion contract requires it.

## Main and deployment

The UI/UX correction integration branch is not `main` and does not authorize deployment. Promotion beyond it requires a separate Maintainer/Acceptance contract.
