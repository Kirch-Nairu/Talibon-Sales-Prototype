# AGENTS.md — TALIBON REPOSITORY OPERATING RULES

## Mandatory first read

Before doing any work in this repository, read:

1. `SSOT_CURRENT_INTRA_OFFICE_PORTAL_SCOPE.md`
2. `SSOT_BY_KIRCH.md`
3. `SSOT_COMMERCIAL_PHASE_AMENDMENT.md`
4. `docs/CODE_REVIEW_2026-08-22.md`
5. `docs/CURRENT_STATE.md`
6. relevant module documentation
7. `docs/ENGINEERING_LOG.md`

`SSOT_CURRENT_INTRA_OFFICE_PORTAL_SCOPE.md` is the controlling authority for active work under the present Core Intra-Office Portal procurement. Older SSOT, commercial-phase, quotation, architecture and phase documents remain preserved as historical/engineering authority where they do not conflict with the current procurement boundary.

Do not reinterpret older broader phase language as permission to expand active work beyond the current Core Intra-Office Portal TOR.

`docs/PHASE_1_PLAN.md` and older phase/release plans are preserved historical engineering references, not current execution authority.

If a request materially conflicts with the current procurement authority, stop and obtain explicit direction from Kirch Ivan A. Balite before expanding scope.

## Current branch / state

Primary active build branch:

`main`

Current formal project state:

`PRE-MOBILIZATION / WORKING PROTOTYPE PREPARATION`

Current coding objective:

**Make the existing backend engineering usable and presentable to Department Heads.**

This is a Portal/UI integration and prototype-readiness wave, not another backend architecture wave.

## Active scope freeze

Current active work maps only to the Core Intra-Office Portal TOR.

Preserve existing broader implementation but do not actively develop HRIS, payroll, DTR, attendance, leave, employee self-service, health-vault expansion, Property expansion, Legislative expansion, GAD, public portal, eBOSS, biometric integration, Project Monitoring expansion, Procurement/PR expansion, Budget-specific expansion, GIS, CBMS or unrelated integrations unless Kirch explicitly authorizes a scope change.

Parked code, routes, migrations, models, services, tests and documentation must not be deleted merely to simplify the prototype.

During the current Department Head prototype wave, do not start RELEASE, ARCHIVE, new correspondence terminal states, document versioning, retention/destruction, user-account administration, production deployment or backup/restore. Current-scope secure attachment/evidence storage is already authorized by the controlling SSOT and must reuse the existing shared document architecture.

## Existing foundations to reuse

Do not rebuild:

- authentication;
- active-account enforcement;
- MFA;
- roles/office authorization;
- generic workflow engine;
- correspondence classification authorization;
- audit history;
- notifications;
- scoped integration clients;
- idempotency;
- transactional outbox.

Correspondence backend already supports RECEIVE, REGISTER, CLASSIFY, ROUTE and ACT / IN_ACTION. The current product gap is authenticated human Portal access to those capabilities.

## Every commit must be documented

Every implementation commit MUST update `docs/ENGINEERING_LOG.md` in the same commit.

Each entry must include:

- timestamp/date;
- current TOR requirement / slice;
- commit message or intent;
- important files/modules changed;
- schema/migration impact;
- tests/verification actually run;
- known gaps/risks;
- next slice/action when applicable.

Do not claim green tests, builds, CI, device behavior, integrations, cloud benchmarks, restore drills, UAT, browser behavior or production readiness unless actually observed.

## Implementation discipline

- Inspect before replacing.
- Reuse shared services instead of duplicating logic per module.
- Keep PostgreSQL authoritative.
- Keep business transitions auditable.
- Use transactions/locking where invariants require them.
- Enforce permissions server-side.
- Treat office, assignment, workflow state, delegation and classification as authorization inputs where relevant.
- Never rely on React visibility as authorization.
- Keep presentation/test/production data boundaries explicit.
- Never commit secrets or database passwords.
- Never point destructive automated tests at presentation or production databases.
- Do not represent synthetic data as real LGU operational data.
- Do not give public/external integrations direct database access.
- Do not add business rules to React.
- Avoid controller bloat; move substantial query/read-model logic into focused query/application services.

## Architecture constraints

Maintain the modular monolith.

Do not introduce:

- microservices;
- another workflow engine;
- another Task engine;
- another notification architecture;
- Kafka/RabbitMQ;
- a giant universal records framework.

`TransactionWorkflowService` is already in the 301–400 LOC review band. Do not keep growing it casually. Reuse its existing behavior and place new read/query responsibilities elsewhere.

Keep React pages decomposed into sensible components and below the repository page cap.

## Source size and complexity standards

Source size is a design signal, not a formatting target.

General production-file thresholds:

- `<=300 LOC`: healthy target
- `301-400 LOC`: review required
- `401-500 LOC`: refactor-required territory
- `>500 LOC`: prohibited by default; requires explicit documented exception or immediate strangler decomposition

Role-specific hard caps:

- Controller: 300 LOC
- Middleware: 200 LOC
- Policy: 300 LOC
- Service: 400 LOC
- Engine: 450 LOC
- React page: 400 LOC

Method/function guidance:

- target `<=20 LOC`
- review when `>35 LOC`
- strong refactor signal when `>50 LOC`

Do not game these limits through arbitrary helper extraction, compressed formatting, multi-statement lines or misleading file boundaries.

## Prototype presentation rule

The Department Head prototype should present the current procurement rather than historical breadth.

Primary current-scope navigation may expose Dashboard, My Work, Correspondence, authorized Mayor/Executive work, Memoranda, Departments and authorized Audit & Security.

Parked modules should remain directly routable only where already implemented/authorized but should not be advertised in the current prototype navigation. Employees may remain reachable for assignment/routing dependencies without being presented as an HRIS feature.

## Department Head freeze rule

After the approved prototype slices are complete, stop feature development and run the integrated Department Head prototype verification gate.

The candidate must not be called production. The intended status after successful verification is:

`DEPARTMENT HEAD PROTOTYPE CANDIDATE`

After presentation, wait for consolidated client feedback and classify each request as DEFECT, IN-SCOPE CONFIGURATION, IN-SCOPE FUNCTIONAL REQUIREMENT or MATERIAL SCOPE EXPANSION before authorizing further implementation.
