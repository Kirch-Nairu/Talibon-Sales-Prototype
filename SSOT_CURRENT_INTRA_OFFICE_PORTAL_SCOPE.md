# TALIBON INTRA-OFFICE PORTAL — CURRENT PROCUREMENT AUTHORITY

Owner / technical authority: Kirch Ivan A. Balite  
Legal / implementing entity: ALZA IT Solutions  
Project team: Team ALZA  
Technical Lead: Kirch Ivan A. Balite — System Architecture & Engineering Direction  
Client: Local Government Unit of Talibon, Bohol  
Active repository: `Kirch-Nairu/Talibon-Intra-Office-Portal`  
Authority branch: `main`
Accepted pre-R0 engineering baseline: `e781b08dcc6476c7b6ad3565a61afc41965fe33f` (`main`; preserved hardening authority `KIRCH-TALIBON-STABLE-BASELINE-HARDENING-V1`)
Effective date: 2026-08-24  
Validated-requirements amendment: 2026-08-25

## 1. Current procurement

The current immediate procurement and active development authority is:

**Municipal Digital Operations Platform — Core Intra-Office Portal**

Current formal project state:

**PRE-MOBILIZATION / WORKING PROTOTYPE PREPARATION**

The immediate engineering objective is to make the existing backend engineering usable and presentable to Department Heads for prototype evaluation and requirements validation, then implement requirements explicitly validated through that process.

This remains a Core Portal implementation. It is not authority for another broad backend architecture expansion.

## 2. Authority relationship

This file is the controlling scope authority for active work under the present Core Intra-Office Portal procurement.

Existing historical documents remain preserved, including:

- `SSOT_BY_KIRCH.md`
- `SSOT_COMMERCIAL_PHASE_AMENDMENT.md`
- historical phase plans, code reviews, release notes and architecture records

Those documents remain valid engineering and historical evidence where they do not conflict with this current procurement scope. Where an older commercial-phase assumption would expand active development beyond the present Core Intra-Office Portal TOR, this file controls current work.

This authority does not delete, roll back or invalidate broader implementation already present in the repository.

## 3. Current active scope

Current active development maps only to the present Core Intra-Office Portal TOR, centered on:

- authenticated municipal access;
- department/office-aware authorization;
- correspondence workspace and lifecycle UI for already implemented backend states;
- inter-office transactions and routing;
- work/task queue projections from existing workflow data;
- current-scope records tracking/search;
- notifications and memoranda where they support intra-office operations;
- department and executive dashboards for current portal work;
- departments/organization data required for routing;
- audit and security visibility for authorized users;
- secure supporting files and photo evidence for current Core Portal records/actions;
- current-scope operational report generation;
- LGU Calendar of Events using the shared calendar foundation;
- Approved Travel Orders as a narrow Core Portal workflow requirement;
- complete incoming-document routing/accountability traceability;
- prototype verification and Department Head evaluation readiness.

Existing foundations must be reused, including authentication, active-account enforcement, MFA, roles/office authorization, the generic workflow engine, correspondence classification authorization, audit history, notifications, shared documents/document links, shared calendar events, scoped integration clients, idempotency and the transactional outbox.

## 4. Preserved but parked implementation

The following existing or future areas are preserved but parked from active implementation unless Kirch Ivan A. Balite explicitly authorizes a scope change:

- HRIS;
- payroll;
- DTR;
- attendance;
- leave;
- employee self-service;
- health-vault expansion;
- Property expansion;
- Legislative expansion;
- GAD, which remains a separate initiative;
- public portal;
- eBOSS;
- biometric integration;
- Project Monitoring expansion;
- Procurement / PR lifecycle expansion;
- Budget-specific expansion;
- GIS;
- CBMS;
- unrelated integrations and future modules.

Existing parked routes, migrations, models, services, tests and historical documentation must not be deleted merely to simplify the prototype presentation.

The Approved Travel Orders requirement does not reactivate HRIS, payroll, DTR, attendance, leave or broad employee self-service. Operational reporting does not reactivate parked HR/payroll/property/legislative report domains.

## 5. Prototype presentation rule

Department Heads must see the system they are evaluating, not the full historical implementation breadth.

The current prototype presentation should expose current-scope surfaces such as Dashboard, My Work, Correspondence, Records, authorized Mayor/Executive work, Memoranda, Departments, current-scope Reports, LGU Calendar, Approved Travel Orders when implemented, and authorized Audit & Security.

Parked modules should be removed from the current prototype navigation/presentation while their underlying code remains intact.

Employees may remain reachable where operationally necessary for routing, assignment or approved-travel accountability dependencies but must not be presented as an active HRIS expansion.

## 6. Correspondence and document-evidence boundary

The repository already implements the correspondence backend through:

`RECEIVE -> REGISTER -> CLASSIFY -> ROUTE -> IN_ACTION`

The older prototype-wave restriction against starting attachment storage is superseded by the validated requirements amendment in this file.

The current Core Portal is now explicitly authorized to implement **secure shared document attachments and routing/action evidence** for current inter-office transactions, correspondence records and their workflow/lifecycle actions.

This authority includes upload, private persistence, shared-document linking, authorization, contextual display, authorized download/view, image preview where practical, audit evidence and regression testing. It does not authorize RELEASE, ARCHIVE, new terminal correspondence semantics, full document version management, collaborative editing, OCR, PKI signing, automated destruction or a records-retention/disposition engine.

## 7. Validated Core Portal requirements — 2026-08-25

### A. Secure supporting files and photo evidence

Attachment and photo capability is a non-negotiable Core Portal capability for relevant incoming documents, inter-office transactions, correspondence, routing actions, responses and workflow actions.

At minimum, the implementation must support server-validated:

- PDF;
- DOCX;
- JPEG;
- PNG;
- WebP.

The capability is required, but attachments are not automatically mandatory input for every operation. A transition may require evidence only when an existing rule or separately validated requirement says so.

The shared `documents` / `document_links` architecture must be reused unless a concrete integrity gap proves it insufficient. Photos remain Documents rather than a separate subsystem. Official evidence must remain private and retrieval must pass through authenticated, server-authorized application endpoints that authorize against the linked parent transaction/correspondence/action context.

### B. Current-scope operational report generation

The Core Portal must retain operational report-generation capability using existing report/query/export foundations where appropriate.

Authorized current-scope reporting includes operational concepts such as:

- office workload;
- active and completed transactions;
- overdue transactions and transaction aging;
- incoming-document states;
- document movement/routing history;
- office accountability;
- correspondence counts/states;
- routing turnaround where safely derivable from existing timestamps/events;
- management summaries grounded in existing data.

Existing historical HRIS, payroll, property and Legislative report code does not make those parked domains part of the current report presentation. Do not invent official performance KPIs without validated client rules.

### C. LGU Calendar of Events

The Core Portal must provide an LGU Calendar of Events by reusing the existing `CalendarEvent` platform foundation.

Current Core Portal calendar behavior may represent, subject to existing authorization:

- municipality-wide events;
- department/office events;
- executive events;
- official schedules;
- approved travel dates;
- transaction/deadline events where appropriate;
- linked source/action URLs where useful.

Where an authoritative source module already knows the event, it should publish/update the calendar event exactly once rather than require duplicate manual encoding.

### D. Approved Travel Orders

The client-validated **Approved Travel Orders** menu is authorized as a narrow Core Portal workflow requirement. It must not be implemented by reactivating broad HRIS.

Minimum implementation direction is:

`travel order request -> review/approval -> approved state -> Approved Travel Orders menu`

The implementation must support supporting documents/photo evidence, auditable history, server-side approval authority and optional/automatic `CalendarEvent` publication for approved travel. Payroll, DTR, leave and broad employee self-service remain outside this requirement.

### E. Complete incoming-document traceability

The client acceptance requirement that all incoming documents have traces showing where they went is a Core Portal requirement.

Incoming-document detail must expose chronological routing/accountability history using the existing append-only `CorrespondenceEvent` and linked workflow history, including where applicable:

- receipt date/time and receiving office;
- registration;
- classification;
- routing action;
- from office;
- to office;
- actor/user;
- action date/time;
- remarks/action;
- current office;
- current responsible employee;
- route/action evidence attachments.

Do not create a second document-tracking subsystem to satisfy this requirement.

### F. Department Heads collaboration space — authorized future slice

A built-in Department Heads collaboration workspace is an authorized client requirement, including group messages, tasks, photo sharing and file sharing.

It is **not** part of the attachments implementation, Reports/Calendar/Travel Orders slice, or current transaction evidence architecture. It must be planned and delivered as a separate later subsystem after the current Core Portal requirements above are stabilized.

Before that future implementation, room membership, Department Head qualification, server-side membership authorization, message/read state, task assignment/status, audit/retention, notification integration, moderation/deletion rules and transport strategy must be resolved. Shared Document infrastructure should be reused for collaboration files/media where appropriate; do not create a second file-storage architecture.

## 8. Pre-mobilization and completion language

Existing pre-mobilization implementation is engineering progress and prototype evidence. It is not contractual completion, production acceptance, UAT sign-off or final deployment.

No existing module is automatically complete merely because code, schema, tests or a screen exists.

The correct current client-facing posture is:

**working prototype ready for evaluation and requirements validation**

once the applicable verification gates have actually been passed.

## 9. Required project sequence

The governing sequence is:

1. corrected Core Portal baseline verification;
2. performance-hardening integration and verification;
3. validated-requirements authority update;
4. secure Core document attachments and routing/action evidence;
5. current-scope Reports, LGU Calendar and Approved Travel Orders;
6. Department Head prototype evaluation / consolidated feedback as applicable;
7. confirmed implementation baseline;
8. UAT;
9. production deployment;
10. orientation and turnover.

Department Heads collaboration remains a later isolated delivery slice and does not automatically begin as part of this sequence.

Do not encode unresolved assumptions prematurely merely to make the system appear broader.

## 10. Engineering invariants

Maintain the modular monolith.

Do not introduce:

- microservices;
- a new workflow engine;
- a new Task engine for existing transaction work;
- a new notification architecture;
- Kafka or RabbitMQ;
- a giant generic records framework;
- a second document subsystem;
- business rules in React;
- frontend role-name authorization.

Keep PostgreSQL authoritative, reuse existing services/domain boundaries, keep server-side authorization authoritative, preserve append-only/auditable workflow evidence and keep sensitive/classified data from leaking through list counts, search, filters, pagination or page props.

`TransactionWorkflowService` is already in the repository review band and must not be expanded casually. New substantial query/read-model/application logic should use focused services rather than controller bloat.

New file/evidence implementation must preserve performance hardening: do not poll file metadata, embed file blobs in normal Inertia props, eagerly load full attachment histories into dashboard/list projections or reintroduce full-page `router.reload()` background polling.

## 11. Verification and honesty

Every implementation commit must update `docs/ENGINEERING_LOG.md` in the same commit.

Only tests and verification actually observed may be recorded as PASS.

H0-H5 hardening is integrated and verified at `e781b08dcc6476c7b6ad3565a61afc41965fe33f`. New Core Portal implementation work must branch from `main` at or after this accepted lineage unless an explicit later authority states otherwise.

Production/UAT completion must not be claimed until the applicable implementation, regression, UAT and deployment gates are actually complete.

## 12. One Talibon public prototype presentation amendment — 2026-08-26

This amendment narrowly authorizes a **One Talibon public prototype presentation shell integrated with the existing Core Intra-Office Portal** for client-facing prototype deployment. It does not activate a broader public-government-system product scope.

### Authorized now

- public One Talibon landing surface;
- static/config-backed prototype Transparency presentation;
- static/config-backed public project, dashboard, news, event and advisory presentation;
- Employee Login entry point into the existing authenticated Core Portal;
- concept-only employee activation presentation with no lookup or mutation;
- internet-safe prototype login presentation using the existing Laravel authentication and MFA boundaries.

All non-factual public metrics/content must be clearly labeled as prototype/sample presentation data. Public presentation must not query internal Reports, workflow transactions, correspondence, employees, audit logs, private documents or other protected Core Portal data.

### Not authorized by this amendment

- public service transaction engines;
- citizen accounts or public self-registration;
- eBOSS, GIS or CBMS;
- tax or civil-registry integrations;
- public publication/CMS workflow;
- Google OAuth or external identity linkage;
- real employee account activation;
- real user-administration/account provisioning;
- account-lifecycle or identity schema expansion;
- any broader parked module not already authorized separately.

Calendar work remains parked for tonight and Approved Travel Orders remains not started under this presentation amendment. Existing internal Core Portal authorization, active-account enforcement and MFA boundaries must not be weakened.