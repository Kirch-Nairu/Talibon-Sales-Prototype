# AGENTS.md — ONE TALIBON V1 FORGE GOVERNANCE

## Authority

Repository: `Kirch-Nairu/Talibon-Sales-Prototype`

Maintainer / technical authority: Kirch Ivan Balite.

Forge source: `Kirch-Nairu/KIRION-FORGE`

Pinned Forge authority for this program: `main@44eb57e5b45b343be0033bf22a7a5e74d543c01a`.

Accepted UI/UX correction baseline:

`KIRCH-TALIBON-V1-SHOWCASE-ACCESS@0913a37f96affd2c2a681697bdf6fdb6c381a99e`

Historical parent integration authority:

`KIRCH-TALIBON-SALES-V1@fdb7f5a272bad3c0f3efc352951a9746c544012a`

Active correction integration branch:

`KIRCH-TALIBON-V1-UIUX-CORRECTION`

The accepted baseline SHA is immutable evidence. Do not move or rewrite it as part of correction work.

## Forge role boundaries

- **Maintainer** owns authority transitions, decomposition, work coordination, acceptance routing, integration authorization, Nest state, and conflict resolution.
- **Reviewer** inspects and classifies evidence. Reviewer does not implement, integrate, promote, or accept unless separately re-authorized.
- **Acceptance** decides whether evidence satisfies a stated promotion contract. Acceptance does not silently broaden that contract.
- **Code Writer** implements only the bounded files and decisions in its handoff, on its own branch, from the exact starting SHA.
- **Integration Writer** integrates only explicitly accepted candidates into the authorized integration branch and does not redesign the product.

Writers do not self-promote. Review is not acceptance. Build evidence is not runtime evidence. Runtime evidence is not deployment evidence.

## Branch and commit policy

Writers branch only from the exact SHA named in their handoff. Do not modify `main`, `KIRCH-TALIBON-SALES-V1`, or the accepted Showcase baseline branch unless an explicit Maintainer transition authorizes it.

Do not force push. Do not deploy from a writer branch. Do not modify `Kirch-Nairu/Talibon-Intra-Office-Portal` from this program.

Forge-controlled commits use:

`KIRCH-FORGE-<ROLE>-<REASON>`

Examples:

- `KIRCH-FORGE-MAINTAINER-ESTABLISH-ONE-TALIBON-UIUX-NEST`
- `KIRCH-FORGE-CODE-WRITER-W01-SHELL-COMPACTION`
- `KIRCH-FORGE-INTEGRATION-W01-SHELL-CANDIDATE`

Every commit must still be a real, reviewable change. The naming convention does not justify empty or commit-count padding.

## Product direction

Build One Talibon as a dense internal municipal digital workspace. It should read like a system the Municipality of Talibon uses for day-to-day coordination, not a marketing site or startup dashboard.

Visible content should answer practical municipal questions:

- what is this record or work item;
- which office owns it;
- who is responsible;
- what is its status;
- when is it due or scheduled;
- what action is required;
- which document, plan, program, project, office or meeting it relates to.

Do not add AI assistants, AI branding, generated recommendation language, sales copy, fake efficiency metrics, fake charts, decorative glassmorphism, neon, giant hero space, startup landing layouts, random animation, or oversized whitespace.

Retain municipal navy, neutral surfaces, restrained shadows, dense but readable tables/lists, clear office context, consistent typography, restrained status treatment, and complete light/dark compatibility.

## Security presentation boundary

Do not advertise Audit & Security as an ordinary product surface. Do not add security posture navigation, MFA posture cards, authentication-event summaries, or security walkthrough blocks.

Do not weaken or remove authentication, authorization, sessions, CSRF, policies, audit logging, private-file controls, or other underlying security behavior.

## Backend and interaction boundary

Reuse working backend behavior. Do not replace real implementation with frontend mocks when a domain already exists.

Do not introduce microservices, a second authorization system, another workflow engine, realtime messaging infrastructure, video-conferencing infrastructure, another database architecture, or an external weather provider unless separately authorized.

Primary actions must work. If an action is not implemented, remove it or use an honest read-only action such as `View details`, `Open record`, `View agenda`, or `Open document`.

Do not display fake `Send`, `Approve`, `Submit`, `Save`, or `Join Meeting` controls.

## UI/UX correction doctrine

The correction program optimizes **Operational Compression**: reduce unnecessary scrolling, wasted whitespace, repeated information, action hunting, route bouncing, context loss, horizontal task travel, and equal visual weighting of unequal information while preserving readability and accessibility.

Accepted program decisions are recorded in `.forge/DECISIONS.md` and `.forge/SSOT_CURRENT.md`. Writers implement those decisions; they do not reopen them opportunistically.

Key boundaries include:

- shared density primitives before random page-local shrinking;
- compact shell/header/footer hierarchy;
- role-aware dashboard priority;
- context-preserving list/detail flows;
- adaptive Planning behavior on narrow screens;
- read-oriented Messages quick access only unless real collaboration backend is separately authorized;
- weather deferred from the first correction program;
- no visible ordinary Audit & Security entry point;
- accessibility is an acceptance gate, not optional polish.

## Validation discipline

Run only checks relevant to the changed surface, but report only what actually ran. Before candidate handoff, the writer must perform the handoff-specified type/build/test checks available in its environment.

Do not convert source inspection into a passing runtime claim. `NOT RUN`, `NOT OBSERVED`, and `UNKNOWN` are valid evidence states.

## Final boundary

Writers build their assigned candidate and stop. They do not integrate the correction branch, update `main`, deploy, or begin another writer's scope without a new Maintainer handoff.
