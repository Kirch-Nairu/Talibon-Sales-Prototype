# SSOT COMMERCIAL PHASE AMENDMENT — 2026-08-22

Owner / approving authority: Kirch Ivan Balite  
Organization: Kirjane Labs  
Client: Local Government Unit of Talibon, Bohol  
Applies to branch: `KIRCH-PHASE1-INTERNAL-OPS-HRIS`

## 1. Authority

This document is an explicit amendment to `SSOT_BY_KIRCH.md`, authorized by Kirch Ivan Balite on 2026-08-22 after reviewing the client-facing **Municipal Digital Operations Platform — Engineering & Project Architecture Plan with Detailed Quotation**.

This amendment does **not** delete or roll back implementation that already exists. It resolves a nomenclature and commercial-scope conflict that emerged because the internal `P1-M0` through `P1-M12` sprint plan accelerated work that the client-facing quotation places in later commercial phases.

Where phase naming or phase-completion claims conflict, this amendment controls. `SSOT_BY_KIRCH.md` continues to control all other product, engineering, security, and implementation rules.

## 2. Two different meanings of "Phase 1"

From this point forward, agents must distinguish:

### A. Contract / quotation phases

These are the client-facing commercial phases defined in the Engineering & Project Architecture Plan with Detailed Quotation.

#### Contract Phase 1 — Inter-Municipality Engine Core — ₱400,000

Required scope:

- Identity & Access Foundation, including privileged-account MFA target and secure session/permission baseline;
- API Gateway / Integration Layer with scoped integration credentials, rate limiting, request validation, and audit logging;
- Correspondence & Communication lifecycle: RECEIVE -> REGISTER -> CLASSIFY -> ROUTE -> ACT -> RELEASE -> ARCHIVE, including return/escalation paths, reference numbering, document metadata/attachments, and live status;
- Legislative / Ordinance lifecycle: draft -> committee -> session -> voting -> certification -> attestation -> mayoral approval -> effectivity / archival, including author/sponsor/co-sponsor and voting/certification structure;
- Core shared database entities used by later modules;
- cloud staging and benchmark testing, including concurrency/load behavior and backup/restore timing.

Quoted duration: 2–3 months.

#### Contract Phase 2 — Comprehensive HRIS Engine — ₱250,000

Required scope:

- 201-file/personnel records;
- appointment and movement workflow;
- leave policy and balance engine;
- DTR/attendance data model;
- performance record structure;
- HR administration dashboard.

The quotation explicitly excludes the employee-facing UI, full statutory payroll/disbursement engine, and physical biometric integration from this commercial phase.

#### Contract Phase 3 — Employee Self-Service Portal — ₱100,000

Required scope:

- My Profile;
- My Attendance/DTR and correction-request submission;
- My Leave and status/balance view;
- My Notices;
- My Requests;
- mobile-responsive employee-facing UI pass.

#### Development Mobilization Fee — ₱50,000

Separate one-time fee due before Contract Phase 1 begins, allocated to development tools/subscriptions, staging/testing infrastructure, QA/security/integration resources, connectivity/build/backup/UAT preparation.

Quoted grand total: **₱800,000** across Contract Phases 1–3 plus the mobilization fee, excluding production hosting/hardware and post-pilot support/maintenance.

## 3. Internal engineering sprint nomenclature

The repository branch name and historical milestones `P1-M0` through `P1-M12` are now interpreted as:

**Internal Build Wave A / accelerated internal-platform implementation**, not as a one-to-one representation of Contract Phase 1.

The current branch contains work from multiple quoted phases because client priorities caused the team to accelerate internal operations, HRIS, onboarding/offboarding, DTR/payroll context, health-vault, calendar/notifications, executive reporting, legislative workspace foundations, and property/asset management in one engineering wave.

Do not rename or destroy the historical branch/milestone structure merely to match the quotation. Instead, use the commercial mapping in this amendment for all client-facing completion, quotation, billing, and phase-status statements.

## 4. Current cross-phase implementation reality

The current branch materially includes:

- substantial Contract Phase 1 foundations: identity, office model, routing, memoranda, records metadata, audit, executive oversight, legislative sessions/agenda foundation;
- substantial Contract Phase 2 work: employee master/201 metadata, onboarding, movement, leave, DTR, performance/development, offboarding, HR administration;
- partial Contract Phase 3 work: employee profile, self-facing HR views, leave/DTR/payroll-context surfaces, notices/notifications, responsive UI;
- client-requested property/asset lifecycle work that is **not explicitly priced as a line item in the original ₱800,000 quotation**;
- additional internal calendar, notification, health-vault, operations/reporting, and accountability work.

This accelerated breadth is useful engineering progress, but later-phase implementation does **not** compensate for missing Contract Phase 1 acceptance items.

## 5. Contract Phase 1 completion rule

Contract Phase 1 must not be called complete solely because the internal Build Wave A contains broader functionality.

Before Contract Phase 1 can be represented as complete / pilot-ready, the repository must close or explicitly re-scope the following quoted commitments:

1. privileged-account MFA implementation and verification;
2. API Gateway / scoped integration credential foundation;
3. integration rate limiting, request validation, and auditable client identity;
4. full correspondence lifecycle including RECEIVE/REGISTER/CLASSIFY/RELEASE/ARCHIVE states;
5. correspondence document attachment/download authorization, version/integrity/retention handling;
6. transaction classification/confidentiality enforcement in authorization;
7. legislative authorship/sponsorship structure;
8. legislative committee/referral lifecycle;
9. named voting record structure;
10. certification/attestation/mayoral-approval/effectivity chain;
11. cloud benchmark deployment and measured concurrency/latency/write/storage behavior;
12. backup/restore timing and at least one evidenced restore exercise;
13. dependency/security review evidence expected by the architecture plan;
14. representative UAT and sign-off before pilot release.

## 6. Property / asset scope rule

LGU Property & Asset Management is now a real client-emphasized internal requirement and remains part of the implementation architecture.

However, it was not itemized in the original Contract Phase 1–3 quotation. Therefore:

- engineering work already implemented must be preserved;
- client-facing commercial documents must not silently pretend it was included in the original ₱800,000 breakdown;
- it should be handled through an approved scope addendum, revised quotation, or explicit commercial decision by Kirjane Labs and the LGU.

## 7. Security / architecture invariants from the quotation

The following become explicit SSOT-level constraints:

- public/external systems never receive direct database access;
- future citizen/resident systems connect through a controlled integration layer only;
- privileged identities require stronger authentication than ordinary accounts before production/pilot exposure;
- security review, least privilege, dependency review, audit logging, backup verification, and restore evidence are release gates, not optional polish;
- cloud-first benchmark is a validation stage, not the permanent trust model;
- the target state remains segmented/hybrid: public edge in cloud, protected municipal core/private data boundary, offsite DR copy.

## 8. Release nomenclature from now on

Use both labels when relevant:

- **Internal engineering label:** `PHASE1_CANDIDATE_OPEN_GATES` (historical Build Wave A label)
- **Commercial architecture label:** `CONTRACT_PHASE_1_OPEN_GATES`

Only after the quoted core commitments and the technical verification gates are evidenced may Contract Phase 1 be upgraded to a completed / pilot-ready status.

## 9. Required repository references

Agents must read, in order:

1. `SSOT_BY_KIRCH.md`
2. `SSOT_COMMERCIAL_PHASE_AMENDMENT.md`
3. `docs/CODE_REVIEW_2026-08-22.md`
4. `AGENTS.md`
5. `docs/PHASE_1_PLAN.md`
6. relevant module documentation
7. `docs/ENGINEERING_LOG.md`

The historical `P1-M*` plan remains useful as implementation history and internal sequencing evidence, but client-facing phase claims must follow this amendment.