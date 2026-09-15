# TALIBON INTERNAL MUNICIPAL PLATFORM — SINGLE SOURCE OF TRUTH

Owner: Kirch Ivan Balite  
Role: Lead of Technology and Backend Systems Engineer  
Organization: Kirjane Labs  
Client: Local Government Unit of Talibon, Bohol  
Status: ACTIVE / BUILD AUTHORITY  
Branch: `KIRCH-PHASE1-INTERNAL-OPS-HRIS`  
Historical Internal Build Wave A target: **2026-08-21 12:00 PHT (UTC+08:00)**

---

## 0. Authority and read order

This file is the canonical product and engineering reference for the Talibon codebase.

Every human developer and coding agent MUST read this file before planning, editing, migrating, testing, reviewing, or committing code.

The explicit commercial phase/nomenclature amendment in `SSOT_COMMERCIAL_PHASE_AMENDMENT.md`, authorized by Kirch Ivan Balite on 2026-08-22, controls client-facing quotation phase mapping, billing/commercial scope, and commercial phase-completion language. It does not remove this file's engineering/security rules.

Required read order:

1. `SSOT_BY_KIRCH.md`
2. `SSOT_COMMERCIAL_PHASE_AMENDMENT.md`
3. `docs/CODE_REVIEW_2026-08-22.md`
4. `AGENTS.md`
5. `docs/PHASE_1_PLAN.md`
6. relevant module documentation
7. `docs/ENGINEERING_LOG.md`

Prototype documents remain historical evidence only. They are not allowed to silently narrow or override the active SSOT and its explicit amendment.

---

## 1. Project state

Talibon is treated as a major Kirjane Labs client.

The project has moved from prototype demonstration into active internal platform implementation.

Existing implementation baseline:

- Laravel 13
- PHP 8.3+
- Inertia + React 19 + TypeScript
- Tailwind CSS
- PostgreSQL
- modular monolith
- authentication, department identity, routing, Mayor queue, memoranda, legislative repository/workspace foundations, HR lifecycle, attendance/DTR/payroll context, property/asset management, reports, audit evidence, and synthetic 350-employee benchmark/demo population

The prototype and accelerated internal build are implementation baselines, not automatic evidence that the client-facing Contract Phase 1 is complete.

No agent may claim a module is production-complete only because a screen or schema exists.

---

## 2. Internal Build Wave A product boundary

The historical repository `P1-M0` through `P1-M12` milestones represent an accelerated internal municipal operating-platform build wave. They are NOT a one-to-one mapping to the quotation's Contract Phase 1. See `SSOT_COMMERCIAL_PHASE_AMENDMENT.md`.

The following internal capabilities are in the accelerated build scope:

1. Identity and access control
2. Municipal organization / office directory
3. Office workspaces for routing nodes
4. Inter-office transaction and document routing
5. Records and document management foundation
6. Executive / Mayor workspace
7. Legislative workspace foundation
8. Packaged HRIS foundation
9. Employee onboarding
10. Employee employment lifecycle and movement
11. Employee offboarding and clearance
12. Employee master profile / 201 record foundation
13. Leave / CTO / overtime / travel workflow foundation
14. Attendance / DTR / biometric integration boundary
15. Payroll administration/context foundation
16. Benefits / deductions / contribution tracking foundation
17. Performance / training / competency / eligibility records
18. Restricted employee health / medical vault
19. LGU property and asset tracking
20. Calendar and municipal events
21. Notification engine, alerts, popups, acknowledgement, and escalation foundations
22. Reports and management monitoring
23. Audit and security controls
24. Cloud benchmark readiness when infrastructure credentials are available

The following remain later/external work unless explicitly re-scoped:

- public citizen portal
- `talibon.gov.ph` resident registration integration
- public citizen identity registry rollout
- public payment ecosystem
- eBOSS / MultiSYS takeover or migration
- public GAD-SDD rollout
- barangay encoder rollout
- RHU clinical / patient medical records system
- public project dashboard
- full external website rebuild

Deferred does not mean rejected. These later systems must connect through a controlled integration boundary and must not receive direct access to protected internal database/file storage.

---

## 3. Core product principle

The internal platform must behave as **one municipal operating platform**, not a collection of unrelated CRUD modules.

Shared platform primitives must be reused across modules:

- identity
- organization
- employee
- office
- workflow
- assignment
- document
- property
- calendar
- notification
- audit

Cross-module actions must propagate where required.

Example — employee transfer:

`HR movement -> office membership -> permissions -> open-work reassignment -> property review -> calendar access -> supervisor notification -> audit event`

Example — offboarding:

`HR separation -> clearance -> open-work reassignment -> property return -> payroll finalization -> access revocation -> record archival -> audit event`

---

## 4. Canonical organization model

Do not reduce the organization to only `employee.department_id`.

Required hierarchy concepts:

- Municipality
- Branch
- Office
- Unit / Section
- Position
- Employee
- Reporting relationship
- Acting / delegated assignment

Primary branches:

- Executive / Administrative
- Legislative

The current routing baseline is **33 internal routing nodes**: 30 executive/administrative and 3 legislative, subject to official organizational-chart validation.

### 4.1 Executive / administrative routing nodes

1. Office of the Municipal Mayor
2. Office of the Municipal Administrator
3. Municipal Planning and Development Office / MPDC
4. Municipal Engineering Office
5. Municipal Assessor's Office
6. Municipal Budget Office
7. Internal Audit
8. Municipal Accounting Office
9. Municipal Treasurer's Office
10. Municipal Agriculture Office
11. Municipal Health Office — organizational routing only; RHU clinical system is separate
12. Municipal Social Welfare and Development Office
13. Municipal Civil Registrar / LCR — normalize duplicate directory naming during discovery
14. Municipal General Services Office
15. Human Resource Management Office
16. Municipal Disaster Risk Reduction and Management Office
17. Municipal Market
18. Public Employment Service Office
19. Municipal Environment and Natural Resources Office
20. Talibon Integrated Transport Terminal
21. Municipal Tourism Office
22. Talibon Polytechnic College — connected institutional routing node; deeper school system remains separate
23. Municipal Information Office
24. Population Office
25. Local Economic Development and Investment Promotion Office
26. Data Protection Office
27. Zoning Administration
28. Bids and Awards Committee
29. Business Permit and Licensing / BPLS
30. Community Tax Certificate / CTC function

### 4.2 Legislative routing nodes

31. Office of the Municipal Vice Mayor
32. Sangguniang Bayan Office
33. Office of the Secretary to the Sangguniang Bayan

External agency focal roles are not automatically municipal departments.

---

## 5. Office workspace contract

Every routable office should receive a workspace backed by shared platform services.

Minimum target capabilities:

- Overview
- Incoming
- Outgoing
- For Action
- Overdue
- Assigned to Me
- Office Employees
- Documents
- Office Calendar
- Office Property
- Reports

Office-specific modules may extend this workspace but must not fork the core routing, notification, document, employee, or audit logic.

---

## 6. Routing / correspondence engine

Routing is the central nervous system of the internal platform, but the **client-facing Contract Phase 1 correspondence completion contract is deeper than the currently implemented routing subset**.

Internal transaction foundation must support at least:

- reference number
- category / type
- subject
- priority
- classification / confidentiality
- origin office
- current office
- assigned employee
- required action
- supporting documents
- received timestamp
- due timestamp
- status
- complete append-only event history

The quotation-level correspondence lifecycle that must be closed before Contract Phase 1 completion is:

`RECEIVE -> REGISTER -> CLASSIFY -> ROUTE -> ACT -> RELEASE -> ARCHIVE`

with return/deficiency/escalation paths, protected documents, version/checksum/retention handling, and classification-aware authorization.

Required action capabilities, subject to policy and authorization:

- receive
- register
- classify
- acknowledge
- assign
- reassign
- review
- return
- request information
- forward
- recommend
- approve
- disapprove
- release
- archive / close
- reopen where policy permits
- escalate

Authorization must consider more than role:

`role + office + assignment + transaction type + workflow state + delegation + classification`

Routing must be configurable. Do not hardcode one Engineering -> Budget -> Mayor path as the municipal workflow model.

---

## 7. Executive / Mayor workspace

The Mayor experience is an executive control surface, not a normal employee dashboard.

Required concepts:

- Needs Decision
- Urgent
- Overdue Across LGU
- Returned / Unresolved
- High priority
- municipality-wide active workload
- office bottlenecks
- office accountability
- decision queue
- executive actions only where appropriate

The executive dashboard should optimize for: **What requires executive attention now?**

---

## 8. Legislative workspace

The legislative branch is a functional workspace, not only a repository.

Internal foundation includes / targets:

- Vice Mayor workspace
- Sangguniang Bayan workspace
- SB Secretary workspace
- sessions
- calendar
- agenda
- attendance
- committee referrals
- draft measures
- ordinances
- resolutions
- executive communications
- supporting documents
- action / publication state
- archive

Before client-facing Contract Phase 1 legislative completion, the domain must also structurally support:

- author / sponsor / co-sponsors
- committee/referral lifecycle
- ordinance legal-structure metadata where required
- named affirmative / negative / abstain / absent votes
- certifying Secretary
- attesting Vice Mayor
- approving Mayor
- signature/action dates
- effectivity / publication state
- repeal / supersession relationship

Cross-branch routing must preserve one auditable history.

---

## 9. HRIS product definition

The accelerated internal build contains a packaged employee-lifecycle foundation that materially overlaps the quotation's later Contract Phase 2 and partial Contract Phase 3.

Canonical lifecycle:

`Pre-employment -> Onboarding -> Active Employment -> Movement / Development -> Separation -> Offboarding -> Archived Employment Record`

Internal HRIS domains include:

- Employee Master
- 201 File foundation
- Position / Plantilla foundation
- Appointment
- Onboarding
- Employment Movement
- Promotion
- Transfer
- Contract Monitoring
- Leave
- CTO
- Overtime
- Travel Orders
- Attendance
- Biometrics integration boundary
- DTR
- Payroll context/foundation
- Benefits
- Deductions / Contributions
- Performance
- Training
- Competency / Eligibility
- Restricted Medical / Health Vault
- Service Record
- Certifications
- Retirement / Separation
- Offboarding

Prototype payroll and attendance values remain synthetic until production rules and hardware are validated. The original quotation explicitly excludes full statutory payroll/disbursement and physical biometric integration from Contract Phase 2.

---

## 10. Onboarding contract

Onboarding must coordinate multiple modules.

Minimum workflow:

1. HR creates / validates employment appointment
2. create employee record
3. generate / record employee number
4. assign office / unit / position / supervisor
5. create portal identity
6. assign roles
7. initialize leave / HR accounts
8. create biometric enrollment task
9. initialize payroll setup
10. request property issuance when applicable
11. track required documents
12. track orientation / policy acknowledgement
13. record authorized occupational-health requirements
14. mark onboarding complete only when required tasks pass

Onboarding must expose incomplete blockers instead of silently activating an incomplete employee record.

---

## 11. Offboarding contract

Offboarding is a clearance workflow, not `employee.active = false`.

Minimum checks:

- department clearance
- open transaction reassignment
- document / records handover
- GSO property return
- financial / payroll clearance
- leave finalization
- biometric access disablement
- portal role revocation
- account deactivation
- final service / employment record archival

Offboarding cannot be finalized while required blockers remain unresolved.

---

## 12. Employee profile

The employee profile is a shared authoritative internal view assembled from governed domains.

Expected sections:

- identity / contact
- employment
- office / position / supervisor
- government identifiers under protected access
- emergency contacts
- leave
- attendance / DTR
- payroll context
- performance
- training / competency
- service record
- documents / 201 file
- assigned property
- active workflow assignments
- restricted health section

Never expose all profile sections to all roles.

---

## 13. Employee health / medical vault

The internal HRIS may contain employment-relevant medical / occupational-health records, but this is NOT the RHU patient system.

Potential record classes:

- blood type
- emergency medical contact
- medical certificate
- fit-to-work / fitness status
- occupational-health examination
- authorized accommodation / restriction
- vaccination record where formally required
- health-related leave attachment
- medical clearance history

Security rules:

- highly restricted access
- explicit authorization
- access logging
- normal department heads do not receive clinical detail
- normal system administration does not automatically grant medical-content access
- RHU clinical consultation and patient histories remain outside HRIS

---

## 14. LGU Property & Asset Management

Property is a client-emphasized internal domain and primarily supports GSO accountability with Accounting reconciliation and office/employee visibility.

Required foundation:

- property / asset master
- property number
- QR / barcode capability
- category / description / serial number
- acquisition data
- acquisition cost / funding source
- supplier / warranty
- current office
- physical location
- accountable employee
- PAR / ICS reference foundation
- condition
- transfer
- return
- repair / maintenance
- inventory count
- discrepancy / missing-item workflow
- disposal lifecycle foundation
- supporting documents
- audit history
- GSO <-> Accounting reconciliation status

Property must integrate with employee onboarding, movement, and offboarding.

**Commercial note:** Property & Asset Management was not separately itemized in the original ₱800,000 Contract Phase 1–3 quotation. Preserve the implemented work, but treat its commercial inclusion through an approved scope addendum, revised quotation, or explicit Kirjane Labs/LGU decision rather than silently rewriting the original quote.

---

## 15. Calendar

Calendar is a shared platform service.

Event classes include:

- municipality-wide events
- Mayor / executive events
- office events
- SB sessions
- committee meetings
- trainings
- HR events
- approved leave / availability
- workflow deadlines
- project / procurement / compliance deadlines
- onboarding activities
- contract expirations
- property inventory schedules

Domain modules should publish calendar events rather than require duplicate manual encoding.

---

## 16. Notification and popup engine

Notifications are a backend domain, not only a bell icon.

Required concepts:

- recipient resolution
- priority
- delivery channel
- read state
- acknowledgement
- escalation
- expiration
- source domain / event

Priority classes:

- INFO
- ACTION REQUIRED
- URGENT
- CRITICAL
- ACKNOWLEDGEMENT REQUIRED

Automatic popup candidates:

- urgent executive request
- acknowledgement-required memorandum
- critical HR action
- property discrepancy
- deadline escalation
- security warning
- offboarding blocker

Routine updates should normally use the notification center rather than disruptive automatic popups.

Production target: domain events + queue + real-time transport (Laravel Reverb / WebSocket) with polling fallback. PostgreSQL remains authoritative.

---

## 17. Documents and records

Do not create independent upload systems in each module.

Use a shared protected document service supporting:

- document ID
- owner / office
- business-domain link
- classification
- confidentiality
- version
- protected storage reference
- checksum / integrity metadata
- retention metadata
- access log

A document may be linked to a workflow transaction, employee record, property asset, HR action, or legislative record subject to access policy.

For Contract Phase 1 closure, metadata alone is insufficient: protected binary upload/download authorization, version handling, checksum verification, retention rules, and physical-original location/reference must be evidenced for correspondence/legislative records.

---

## 18. Security and integration baseline

Mandatory principles:

- server-side authorization
- least privilege
- no security based only on hidden UI controls
- office + role + assignment + state + classification-aware access
- append-only business history for critical workflow events
- audit denied access where appropriate
- protected file access through application authorization
- secrets never committed
- no production database passwords in docs or code
- no browser/client direct database access
- no RHU clinical data in the HRIS boundary
- privileged MFA before cloud/pilot exposure
- explicit login/rate-limit hardening
- automated dependency/security review before pilot

Any public/external portal added in later phases must **never** directly connect to protected internal tables or file storage.

The client-facing Contract Phase 1 requires a controlled integration layer with:

- scoped / revocable credentials per integration
- request validation
- rate limiting
- auditable client identity
- versioned API conventions
- failure/retry/idempotency contracts for writes

---

## 19. Cloud benchmark and target deployment model

The architecture plan defines cloud-first deployment as a benchmark/validation stage, not the permanent trust model.

Before Contract Phase 1 can be called pilot-ready, measure and record at minimum:

- peak concurrent-session behavior
- database write/load behavior
- application/API latency
- representative document-storage growth
- backup time
- restore time
- at least one successful restore/recovery exercise

Target-state principle:

- protected internal operations core / HR / correspondence / legislative / primary database: private core / on-premise target or equivalent protected municipal boundary
- public-facing employee/citizen edge: cloud-facing only through controlled integration boundary
- offsite backup / disaster-recovery copy: cloud/offsite

The cloud benchmark deployment should be reusable as public edge / DR rather than discarded after validation.

---

## 20. Quality / pilot-readiness rule

A module is not done because code exists.

Internal release-green and commercial Contract Phase 1 completion are separate gates.

Internal release-green requires observed exact-HEAD evidence including:

- migrations apply cleanly against the intended database boundary
- seed/demo environment reproducible
- authorization boundaries tested
- key routing flows tested
- onboarding/offboarding blockers tested
- property accountability flow tested
- calendar/notification behavior tested
- TypeScript check succeeds
- production frontend build succeeds
- full Feature suite passes or failures are explicitly documented

Commercial Contract Phase 1 pilot-ready status additionally requires:

- privileged MFA verified
- integration/scoped-client layer verified
- full correspondence lifecycle and protected documents at quoted depth
- full legislative lifecycle at quoted depth
- dependency/security review
- backup/restore evidence
- cloud benchmark evidence
- structured representative UAT and sign-off
- zero critical/high-severity defects before pilot, with only documented acceptable lower-severity issues

Current labels:

- internal: `PHASE1_CANDIDATE_OPEN_GATES`
- commercial: `CONTRACT_PHASE_1_OPEN_GATES`

Do not use stronger labels without evidence.

---

## 21. Engineering and commit documentation policy

Every codebase commit must be documented.

Every commit must:

1. use a descriptive commit message
2. update `docs/ENGINEERING_LOG.md` in the same commit
3. state the intent / requirement addressed
4. list important files / modules changed
5. identify migrations / schema effects
6. state tests or verification actually performed
7. state known gaps / risks / follow-up
8. avoid claiming tests passed if they were not observed

Agents must not create undocumented "small" commits.

---

## 22. Agent operating rules

Before coding:

- read this SSOT
- read `SSOT_COMMERCIAL_PHASE_AMENDMENT.md`
- read `docs/CODE_REVIEW_2026-08-22.md`
- read `docs/PHASE_1_PLAN.md`
- inspect existing implementation before replacing it
- preserve proven behavior unless the architecture requires an intentional migration

During coding:

- prefer shared domain services over duplicated module logic
- use database transactions/locking where invariants require them
- preserve auditability
- write tests around permissions and lifecycle blockers
- do not silently fake production integrations

Before commit:

- run relevant narrow tests first
- run broader verification when practical
- update `docs/ENGINEERING_LOG.md`
- document unverified areas

Never:

- expose secrets
- point destructive tests at presentation/production data
- represent synthetic payroll as statutory payroll
- represent simulated biometrics as live hardware integration
- mix RHU patient clinical data into HRIS
- give external systems direct database access
- claim cloud benchmark/restore/UAT evidence without actually performing it
- claim commercial Contract Phase 1 complete because later-phase internal features happen to exist

---

## 23. Current build priority — Contract Phase 1 closure

Do not add unrelated feature breadth until the client-facing core is closed.

Priority order:

1. privileged MFA + login hardening
2. API Gateway / scoped integration-client foundation
3. correspondence full lifecycle + classification/confidentiality
4. protected document upload/download/version/checksum/retention path
5. legislative authorship / committee / voting / certification / attestation / approval / effectivity lifecycle
6. authorization-policy consolidation
7. CI dependency/static/security gates
8. exact-HEAD local regression
9. cloud benchmark + backup/restore drill
10. structured UAT + pilot sign-off

The accelerated HRIS/property/employee-self-service work must be preserved and hardened, not thrown away.

---

## 24. Change authority

Scope changes that materially alter the platform or commercial phase mapping must be added to the SSOT/amendment by Kirch Ivan Balite or explicitly approved and documented.

Agents may propose changes in planning documentation, but must not silently redefine either the engineering SSOT or the client-facing quotation mapping.