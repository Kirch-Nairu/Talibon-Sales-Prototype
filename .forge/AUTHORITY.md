# ONE TALIBON V1 — Authority

## Human technical authority

Kirch Ivan Balite

## Repository

`Kirch-Nairu/Talibon-Sales-Prototype`

## Forge authority

`Kirch-Nairu/KIRION-FORGE`

Pinned authority for this program:

`main@44eb57e5b45b343be0033bf22a7a5e74d543c01a`

## Accepted correction baseline

`KIRCH-TALIBON-V1-SHOWCASE-ACCESS@0913a37f96affd2c2a681697bdf6fdb6c381a99e`

This exact SHA is immutable baseline evidence. The branch name does not redefine the accepted baseline if it later moves.

## Active correction integration branch

`KIRCH-TALIBON-V1-UIUX-CORRECTION`

This branch is the coordination/integration authority for the UI/UX correction program after Nest materialization. Writers branch from exact SHAs supplied by the Maintainer; branch names alone are insufficient authority.

## Historical parent integration

`KIRCH-TALIBON-SALES-V1@fdb7f5a272bad3c0f3efc352951a9746c544012a`

It is lineage context, not the active integration target for new correction waves unless explicitly re-authorized.

## Current authority rule

Directly observed Git/GitHub state outranks remembered state and stale documentation.

Within the correction program, use this order when evidence conflicts:

1. directly observed Git/GitHub state and runtime evidence;
2. explicit human/Maintainer authority transition;
3. `.forge/AUTHORITY.md`, `.forge/SSOT_CURRENT.md`, `.forge/DECISIONS.md`, and root `AGENTS.md` as current repository governance;
4. accepted Reviewer/Acceptance evidence;
5. historical repository documents as provenance/cold memory.

Do not silently reconcile a material conflict. Escalate it to the Maintainer.

## Mutation policy

- accepted baseline SHA remains untouched;
- writers mutate only their assigned candidate branch/files;
- writers never self-integrate or self-promote;
- integration requires accepted evidence plus exact authority precheck;
- non-force transitions only by default;
- deployment is outside writer authority;
- `main` is not a correction work branch;
- `Kirch-Nairu/Talibon-Intra-Office-Portal` is outside this program's mutation authority.

## Commit identity convention

Forge-controlled project commits use:

`KIRCH-FORGE-<ROLE>-<REASON>`

The prefix communicates operating role; it does not replace atomic commit quality or evidence requirements.

## Promotion model

```text
ACCEPTED CORRECTION AUTHORITY
→ BOUNDED WRITER HANDOFF
→ WRITER CANDIDATE
→ REVIEW
→ ACCEPTANCE
→ INTEGRATION AUTHORIZATION
→ AUTHORITY PRECHECK
→ NON-FORCE INTEGRATION
→ REMOTE VERIFICATION
→ WORK LEDGER / EVIDENCE UPDATE
```

## Release boundary

Source-baseline acceptance is not release acceptance.

Any later release/UAT/deployment/production claim requires its own explicit evidence and promotion contract.
