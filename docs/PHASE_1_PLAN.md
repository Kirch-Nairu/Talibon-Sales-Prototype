# PHASE 1 EXECUTION PLAN — INTERNAL MUNICIPAL OPERATIONS + PACKAGED HRIS

Authority: `SSOT_BY_KIRCH.md`  
Owner: Kirch Ivan Balite — Lead of Technology and Backend Systems Engineer  
Active branch: `KIRCH-PHASE1-INTERNAL-OPS-HRIS`  
Start reference: 2026-08-19 PHT  
Hard target: **2026-08-21 12:00 PHT (UTC+08:00)**

---

## 1. Objective

Deliver an integrated Phase 1 candidate of the Talibon Internal Municipal Platform covering the client-emphasized internal fields:

- routable executive and legislative offices
- shared office workspaces
- packaged HRIS
- onboarding
- employment movement
- offboarding / clearance
- employee profile / 201-file foundation
- leave / attendance / DTR / payroll foundations
- performance / training / competency / benefits
- restricted employee health vault
- LGU property and asset accountability
- calendar / events
- notification / popup / acknowledgement / escalation
- records / documents
- executive oversight
- legislative workspace
- reports / audit / security

Phase 1 is one integrated internal system. Public citizen services are not part of this deadline.

---

## 2. Deadline strategy

The available delivery window is extremely compressed. The engineering strategy is therefore:

1. preserve proven M6 behavior
2. evolve existing tables/services where safe instead of performing broad rewrites
3. establish shared contracts first
4. implement client-emphasized vertical slices end-to-end
5. parallelize independent domains after schema ownership is assigned
6. keep every commit documented
7. freeze features before final verification
8. label any failed gate honestly rather than hiding it

The target is an **integrated Phase 1 candidate by 2026-08-21 12:00 PHT**. Production verification remains evidence-gated.

---

## 3. Architecture decisions for the deadline

### 3.1 Compatibility-first organization migration

The prototype currently treats `departments` as the workspace / authorization anchor.

Do NOT rename or replace that table during the deadline window unless inspection proves it is necessary.

Preferred migration path:

- preserve `departments` as the routable office compatibility anchor
- add branch / office metadata and hierarchy around it
- add aliases / office codes / office type / parent relationships where needed
- add units / positions / employee assignment structures without breaking existing foreign keys
- migrate to a more normalized organization model later only if the compatibility layer becomes a blocker

This protects existing routing, policies, seeders, HR relationships, reports, and demo flows.

### 3.2 Modular monolith remains the target

Do not split Phase 1 into microservices.

Use bounded Laravel modules/services inside the existing application:

- Organization
- Workflow
- Records
- Notifications
- Calendar
- HR
- Property
- Executive
- Legislative
- Reporting
- Audit

### 3.3 PostgreSQL remains authoritative

Realtime UI delivery, WebSockets, polling, notifications, and popups are transport / presentation behavior.

All business truth must remain reconstructable from PostgreSQL records and auditable domain events.

### 3.4 Realtime target

Preferred:

- domain events
- queued notification dispatch
- Laravel Reverb / WebSocket when environment permits
- polling fallback

Do not block Phase 1 correctness on Reverb infrastructure. The database notification/event model must work independently.

---

## 4. Workstreams

### Workstream A — Organization + Identity + Routing

Owns:

- branch / office model
- all routing nodes
- office membership
- acting / delegated assignments foundation
- universal office workspaces
- generalized routing policies
- transaction assignment / SLA / return / forward / executive route

### Workstream B — HR Lifecycle

Owns:

- employee master/profile
- 201-file metadata foundation
- onboarding
- movement / transfer / promotion / contract status
- leave / CTO / overtime / travel foundation
- attendance / DTR / biometric boundary
- payroll / benefits foundation
- performance / training / competency
- restricted health vault
- offboarding / clearance

### Workstream C — Property

Owns:

- asset master
- accountability
- PAR / ICS references
- transfer / return
- maintenance / condition
- inventory / QR foundation
- discrepancy
- disposal foundation
- onboarding / movement / offboarding hooks

### Workstream D — Calendar + Notifications + Documents

Owns:

- shared calendar
- event publication from domains
- notification domain
- popup priority rules
- acknowledgement / escalation
- shared document metadata / protected link foundation

### Workstream E — Executive + Legislative + Reports

Owns:

- Mayor executive dashboard
- office bottleneck / pending-decision views
- legislative workspace foundation
- SB sessions / agenda / records integration as achievable
- reports / summary surfaces

### Workstream F — QA + Security + Documentation

Owns:

- authorization test matrix
- migration / seed checks
- lifecycle blockers
- cross-module integration tests
- responsive validation
- engineering log enforcement
- acceptance evidence

---

## 5. Migration / schema ownership rule

Parallel agents must not independently create overlapping schema concepts.

Before adding a migration, identify the owning workstream.

Primary ownership:

- Organization tables / fields -> Workstream A
- HR / employee lifecycle -> Workstream B
- Property -> Workstream C
- Calendar / notification / documents -> Workstream D
- Legislative session-specific schema -> Workstream E

Cross-domain foreign keys must be agreed against existing tables before migration creation.

Avoid two competing concepts for the same thing such as `office`, `department`, `employee_assignment`, `notification`, or `document`.

---

## 6. Phase 1 milestone map

### P1-M0 — Scope / codebase inspection / organization freeze

Target window: **Aug 19, 20:15–21:30 PHT**

Goals:

- inspect existing models, migrations, policies, controllers, routes and frontend navigation
- confirm how `departments`, `employees`, `users`, transactions, memoranda, legislative records, leave, attendance, payroll, operations and audit currently connect
- reconcile the 33-routing-node SSOT baseline against the current 29-office prototype seeder
- decide aliases / merged office names without destructive refactors
- define migration ownership

Exit gate:

- no unidentified duplicate core entity
- organization compatibility strategy documented
- implementation tasks allocated

### P1-M1 — Organization + universal office routing

Target window: **Aug 19, 21:30–Aug 20, 00:30 PHT**

Deliver:

- executive / legislative branch metadata
- routable office baseline
- office code / type / hierarchy metadata
- employee office membership compatibility
- universal office workspaces
- routing to any authorized office
- assignment / received / due / return / forward / request-info / executive-route behavior preserved
- acting/delegation foundation if achievable without destabilizing policies

Acceptance flow:

`Office A -> Office B -> assigned employee -> return/request info -> re-forward -> Mayor / executive decision -> complete history`

### P1-M2 — Shared documents + notification + calendar foundation

Target window: **Aug 20, 00:30–03:30 PHT**

Deliver:

- shared document metadata/linking foundation
- notification records with source event, recipient, priority, read/ack state
- popup priority rules
- deduplication / recency behavior
- shared calendar events / attendees / reminders foundation
- transaction due dates publish calendar reminders where appropriate
- memo / urgent action integration

Acceptance flows:

- newly routed transaction appears once as a fresh notification
- urgent action can trigger popup
- acknowledgement-required memo preserves acknowledgement
- routine updates do not create repeated disruptive popups
- due event appears in relevant calendar view

### P1-M3 — Employee master + profile + 201 foundation

Target window: **Aug 20, 03:30–06:30 PHT**

Deliver:

- consolidated employee profile surface
- employment / office / position / supervisor fields
- protected identifiers / emergency contacts
- HR tabs for leave / attendance / payroll / performance / training / documents / property / active assignments
- 201-file document metadata foundation
- authorization split between self, HR privileged users, department heads, and admins

Acceptance:

- normal employee sees only authorized self data
- department head does not automatically receive sensitive HR/medical detail
- HR/admin boundary remains server-side

### P1-M4 — Onboarding + movement + basic property accountability

Target window: **Aug 20, 06:30–10:30 PHT**

Deliver:

- onboarding case
- onboarding tasks / completion blockers
- portal-account / role task
- office / position / supervisor assignment
- leave / payroll / biometric setup tasks
- required documents / orientation tasks
- employment movement record
- transfer workflow foundation
- basic asset master + accountable employee / office assignment
- onboarding property-issuance task hook

Acceptance:

- employee cannot be marked onboarding-complete with required blocker open
- transfer updates office identity and creates required review tasks
- assigned property appears on employee profile

### P1-M5 — Leave / attendance / DTR / payroll lifecycle integration

Target window: **Aug 20, 10:30–14:30 PHT**

Deliver / preserve:

- existing leave credit ledger and approval flow
- CTO / overtime / travel request foundation where feasible
- attendance / DTR view
- biometric integration status / adapter boundary
- payroll period / entry views
- benefits / deductions / contribution data structure foundation
- explicit synthetic / non-statutory labeling remains until production rules are validated

Acceptance:

- leave mutation remains transactional
- employee sees only own payroll
- HR payroll summary remains restricted
- no live-biometric claim without hardware evidence

### P1-M6 — Performance + training + competency + restricted health vault

Target window: **Aug 20, 14:30–18:00 PHT**

Deliver:

- performance record foundation
- training / certification / competency / eligibility records
- contract / document expiry alert foundation
- restricted employee health vault
- fit-to-work / medical certificate / occupational-health record classes
- access logging for sensitive health record reads where practical

Acceptance:

- medical content is not exposed to ordinary department heads
- system administrator role does not automatically imply medical-content permission
- RHU patient clinical history is absent from HRIS

### P1-M7 — Offboarding + clearance integration

Target window: **Aug 20, 18:00–21:00 PHT**

Deliver:

- separation / offboarding case
- clearance tasks
- open-work reassignment blocker
- property-return blocker
- financial / payroll clearance foundation
- document handover
- biometric disable task
- role revocation / account deactivation scheduling foundation
- archival state

Acceptance flow:

`separation initiated -> blockers generated -> property returned -> open transactions reassigned -> clearance completed -> access deactivation state -> archived employment record`

Offboarding MUST refuse final completion while mandatory blockers remain open.

### P1-M8 — Full Property & Asset lifecycle

Target window: **Aug 20, 21:00–Aug 21, 00:30 PHT**

Deliver:

- property number
- category / serial / acquisition / cost / supplier / funding / warranty fields
- current office / physical location
- PAR / ICS reference fields
- accountable employee
- transfer / return
- condition
- repair / maintenance
- inventory session / scan foundation
- QR / barcode value generation foundation
- discrepancy / missing-item state
- disposal lifecycle foundation
- Accounting reconciliation state

Acceptance:

- register -> assign -> transfer -> inventory verify -> return / discrepancy history remains auditable
- offboarding blocker sees outstanding property

### P1-M9 — Executive + Legislative workspace completion

Target window: **Aug 21, 00:30–03:30 PHT**

Executive deliver:

- needs decision
- urgent
- overdue across LGU
- unresolved / returned
- office workload / bottleneck
- executive decision queue

Legislative deliver:

- branch workspace
- Vice Mayor / SB / SB Secretary views
- sessions / calendar / agenda foundation
- legislative records connected to routing and documents
- cross-branch transaction history

Acceptance:

- legislative nodes participate in normal routing without bypassing audit
- Mayor account does not receive generic controls merely because it is privileged

### P1-M10 — Reports + audit + authorization hardening

Target window: **Aug 21, 03:30–06:30 PHT**

Deliver:

- HR lifecycle summary
- onboarding / offboarding status reports
- office workload / aging
- property accountability / discrepancy summaries
- event / notification summary where useful
- audit event coverage for privileged actions
- denied-access checks around HR / health / property / executive / legislative functions

### P1-M11 — Integrated verification / bugfix

Target window: **Aug 21, 06:30–09:30 PHT**

Required verification sequence:

1. test DB configuration check
2. migration fresh / seed against test database
3. office / employee seed counts
4. key routing feature tests
5. onboarding / transfer / offboarding tests
6. property tests
7. calendar / notification tests
8. HR permission tests
9. health-vault permission tests
10. executive / legislative permission tests
11. TypeScript / frontend build
12. full feature suite
13. phone-width / desktop smoke check where runtime is available

Never run destructive test refreshes against the presentation database.

### P1-M12 — Benchmark readiness / freeze / handoff

Target window: **Aug 21, 09:30–12:00 PHT**

Actions:

- feature freeze
- fix only acceptance blockers
- update `docs/ENGINEERING_LOG.md`
- update current-state documentation
- record verification evidence
- identify open gates
- prepare benchmark seed / demo path
- prepare cloud benchmark deployment if credentials / infrastructure are available and it does not jeopardize acceptance verification

Final label options:

- `PHASE1_VERIFIED_CANDIDATE` — all mandatory gates observed passing
- `PHASE1_CANDIDATE_OPEN_GATES` — integrated build exists but one or more gates remain unverified / failing

Do not use a stronger label without evidence.

---

## 7. Priority cut line

If schedule pressure forces sequencing decisions, protect these first because the client explicitly emphasized them:

### Tier A — must work end-to-end

1. all-office routing
2. executive / legislative routing nodes
3. employee master / profile
4. onboarding
5. offboarding / clearance
6. property accountability
7. calendar
8. notifications / popup / acknowledgement
9. HR leave / attendance / payroll foundations preserved
10. audit / authorization

### Tier B — must have credible Phase 1 foundation

- CTO / overtime / travel
- performance
- training / competency
- benefits / deductions / contributions
- restricted employee health vault
- property maintenance / inventory / disposal depth
- legislative session workflow depth

Tier B does not mean fake screens. It means real schema, permissions, auditable CRUD/workflow foundation, and an explicit extension path if the complete domain cannot be safely finished before the hard target.

---

## 8. Key data contracts

### Organization

Prefer evolving existing `departments` rather than renaming it during the deadline window.

Potential additions / related structures after inspection:

- branch / branch_type
- office_code
- office_type
- parent_department_id / parent office
- routing_enabled
- legislative / executive classification
- units
- positions
- employee assignments / reporting relationships

### HR Lifecycle

Potential new structures after inspection:

- employee_employment_records
- employee_movements
- onboarding_cases
- onboarding_tasks
- offboarding_cases
- offboarding_tasks
- employee_documents / shared document links
- performance_records
- training_records
- competency_or_eligibility_records
- employee_health_records

Do not create duplicate tables if existing models already cover the contract.

### Property

Potential structures:

- assets
- asset_assignments
- asset_events
- asset_maintenance_records
- inventory_sessions
- inventory_scans

Use an append-only event/history trail for transfers, returns, condition changes, discrepancies, and disposal decisions.

### Calendar

Potential structures:

- calendar_events
- calendar_event_attendees
- calendar_event_reminders

A domain record should be able to link to an event rather than duplicate event metadata.

### Notifications

Prefer one notification/event contract containing:

- source domain
- source record / event key
- recipient
- priority
- title / body
- route / action target
- read state
- acknowledgement state
- emitted timestamp
- expiration / escalation state

Prevent duplicate notifications for the same domain event.

### Documents

Potential shared structures:

- documents
- document_versions
- document_links (polymorphic or equivalent)
- classification / confidentiality / retention metadata

Do not expose raw protected storage paths.

---

## 9. Cross-module event contracts

The following events are architecturally important:

### `EmployeeOnboardingStarted`

Consumers may create:

- identity/account setup task
- leave/payroll setup task
- biometric enrollment task
- GSO property issuance task
- orientation calendar event
- notifications

### `EmployeeOfficeChanged`

Consumers may trigger:

- office membership update
- permission recalculation
- supervisor update
- open-work reassignment review
- property accountability review
- calendar access changes
- notifications
- audit

### `EmployeeOffboardingStarted`

Consumers may create:

- open-work blocker
- property-return blocker
- payroll/financial clearance
- document handover
- biometric disable task
- access revocation task

### `TransactionArrivedAtOffice`

Consumers:

- notification
- popup for priority items
- calendar reminder based on due date
- office workload metrics

### `AssetAssigned` / `AssetReturned`

Consumers:

- employee profile
- onboarding/offboarding blocker state
- office property summary
- audit

### `CalendarEventCreated`

Consumers:

- attendee notification
- reminder schedule

Do not implement event propagation in a way that makes the event transport more authoritative than the database state.

---

## 10. Authorization matrix principles

### Employee

May access:

- own profile subset
- own leave / attendance / payroll subset
- own assigned property
- own assigned workflow actions
- authorized office workspace data

### Department Head / Supervisor

May access:

- office workload
- office employees operational summary
- assigned approvals / routing
- office property summary

Does NOT automatically receive:

- complete 201 file
- payroll administration
- medical / health details
- unrelated confidential transactions

### HR Privileged

May access HR administration according to role and sensitivity.

Medical / health-vault access must remain separately permissioned.

### GSO / Property Privileged

May manage property records and accountability.

Accounting receives reconciliation / financial views as authorized.

### Mayor / Executive

Receives municipality-wide operational visibility and explicit executive actions, not every module's low-level administrative mutation controls.

### Legislative Roles

Receive legislative workspace actions and ordinary routing according to office / assignment / state.

### System Administrator

Technical administration does not automatically grant business justification to view sensitive medical / confidential content. Where framework constraints require emergency technical access, it must be separately controlled and auditable.

---

## 11. Required tests

Minimum new feature-test groups:

- `OrganizationRoutingTest`
- `OfficeWorkspaceAuthorizationTest`
- `OnboardingLifecycleTest`
- `EmployeeMovementTest`
- `OffboardingClearanceTest`
- `EmployeeProfileAuthorizationTest`
- `EmployeeHealthVaultAuthorizationTest`
- `PropertyAccountabilityTest`
- `PropertyInventoryTest`
- `CalendarEventTest`
- `NotificationEventTest`
- `ExecutiveWorkspaceAuthorizationTest`
- `LegislativeWorkspaceTest`

Existing demo-critical workflow, memo, leave, HR-security and payroll tests must be preserved / adapted rather than silently deleted.

---

## 12. Mandatory acceptance scenarios

### Scenario A — universal routing

1. log in as an originating office
2. create transaction
3. route to a different office
4. receiving office gets one fresh notification
5. receiving employee is assigned
6. due date / aging visible
7. receiving office requests information / returns
8. origin responds and re-routes
9. transaction reaches executive decision when applicable
10. full history is reconstructable

### Scenario B — onboarding

1. HR starts onboarding
2. employee / employment record created
3. office / position / supervisor assigned
4. portal setup task created
5. HR setup tasks created
6. property issuance task created when applicable
7. orientation event created
8. required blocker prevents early completion
9. completing mandatory tasks allows onboarding completion

### Scenario C — employee transfer

1. HR records transfer
2. current office changes
3. permissions / membership reflect target office
4. old open-work review is generated
5. property review is generated
6. supervisor / calendar / notification state updates as designed
7. movement history remains visible

### Scenario D — offboarding

1. HR initiates separation
2. clearance blockers generated
3. outstanding property blocks completion
4. open workflow blocks completion where required
5. property returned
6. work reassigned
7. clearances complete
8. access revocation/deactivation state is recorded
9. employment record archived

### Scenario E — property

1. GSO creates asset
2. property number / QR value exists
3. asset assigned to employee / office
4. employee profile shows accountability
5. transfer creates history
6. inventory verification records location / condition
7. discrepancy is auditable
8. return/offboarding clears accountability

### Scenario F — sensitive health record

1. authorized health/HR role creates permitted employee health record
2. ordinary employee sees permitted own view only
3. department head cannot read protected medical detail
4. unauthorized admin/business role is denied and event is audited where configured

### Scenario G — calendar + notification

1. create office event
2. attendees receive appropriate notification
3. domain deadline produces reminder
4. urgent workflow arrival produces popup
5. routine update stays in notification center
6. duplicate polling/realtime delivery does not create duplicate business notification

---

## 13. UI requirements

Phase 1 UI must remain usable on desktop and phone.

Global navigation should converge toward:

- Home
- My Work
- Office
- Employees / HR (permission-aware)
- Property (permission-aware)
- Calendar
- Records
- Legislative (permission-aware)
- Executive (permission-aware)
- Reports (permission-aware)
- Audit / Admin (permission-aware)

Do not expose empty navigation items to roles that cannot use them unless there is a deliberate product reason.

Avoid dense dashboard overload. Prioritize actionable information.

---

## 14. Cloud benchmark boundary

The earlier direction to prove the internal system in the cloud remains valid, but public citizen integration is deferred.

If benchmark infrastructure becomes available during Phase 1:

- deploy the internal application in a private cloud-access pattern
- use HTTPS
- protect the database from public access
- preserve environment separation
- do not expose internal administration merely because the server is cloud-hosted

Cloud hosting does not equal public authorization.

Cloud deployment must not steal the final verification window from application acceptance unless Kirch explicitly reprioritizes it.

---

## 15. Commit plan

Recommended commit granularity:

- one coherent schema/domain foundation per commit
- one coherent vertical slice per commit
- tests in the same commit or immediately coupled with the implementation commit
- `docs/ENGINEERING_LOG.md` updated in every commit

Examples:

- `feat(org): add executive and legislative office hierarchy`
- `feat(workflow): generalize office routing and assignment`
- `feat(hr): add onboarding lifecycle and blockers`
- `feat(property): add asset accountability and history`
- `feat(hr): add offboarding clearance workflow`
- `feat(calendar): add domain-linked municipal events`
- `feat(notify): add prioritized acknowledgement notifications`
- `test(phase1): cover lifecycle and authorization gates`

No undocumented drive-by commits.

---

## 16. Stop conditions

Stop and reassess before proceeding if:

- a migration would destroy existing prototype data without an explicit migration path
- two workstreams introduce conflicting core concepts
- an authorization shortcut exposes sensitive HR/medical/property records
- test database isolation is uncertain
- a public network change would expose PostgreSQL
- a third-party / hardware integration is being represented as complete without actual access
- the only way to meet the clock is to falsify verification status

---

## 17. Phase 1 completion statement

The desired final statement is:

> Phase 1 provides one integrated internal municipal operating platform across Talibon's executive and legislative routing structure, employee lifecycle HRIS, onboarding and offboarding, property accountability, calendar, notifications, records, executive/legislative workspaces, reporting, and audit controls.

That statement may only be used as a verified claim after the required acceptance evidence has been observed.
