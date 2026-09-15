# Current Repository State

## Authority

- Product: **Municipal Digital Operations Platform — Core Intra-Office Portal**
- Controlling scope: `SSOT_CURRENT_INTRA_OFFICE_PORTAL_SCOPE.md`
- Authoritative development branch: `main`
- Accepted pre-R0 engineering baseline: `e781b08dcc6476c7b6ad3565a61afc41965fe33f`
- Preserved hardening authority: `KIRCH-TALIBON-STABLE-BASELINE-HARDENING-V1` at the same SHA

## Engineering state

H0-H5 engineering hardening is **CLOSED / INTEGRATED / GREEN** at the accepted pre-R0 baseline. The current repository has passed the observed Platform, H0 runtime, H1 mutation and H5 memorandum-concurrency gates for that baseline.

The repository is now in build-reproducibility/baseline-freeze work before Department Head prototype evaluation. This is not UAT, contractual acceptance or production deployment.

## Active Core Portal implementation

Current active implementation includes authenticated municipal access, office/department-aware authorization, inter-office transactions and My Work projections, correspondence lifecycle/workspace, records search, memoranda/notifications, current-scope dashboards/reports, LGU Calendar, Approved Travel Orders, organization data, authorized audit/security visibility, incoming-document traceability, and secure shared supporting documents/photo evidence.

Secure document evidence is implemented through the shared private `documents` / `document_links` architecture and server-authorized download paths. The older statement that transaction/document uploads do not exist is obsolete.

## Parked historical implementation

Broader HRIS, payroll, DTR, attendance, leave, employee self-service, Property expansion, Legislative expansion, GAD, public-service engines, eBOSS, biometric integration, Project Monitoring expansion, Procurement/PR expansion, Budget expansion, GIS, CBMS and other future modules remain preserved but parked unless the controlling SSOT explicitly re-authorizes them. Their presence in code/history does not make them part of the current procurement presentation.

## Current product posture

- Department Head prototype evaluation: **PREPARATION**
- Confirmed implementation baseline: **PENDING R0 / evaluation sequence**
- UAT: **NOT STARTED**
- Production deployment: **NO**
- Orientation/turnover: **NOT STARTED**

Only observed verification may be represented as passing. Future client findings must be classified and implemented as bounded requirements/defects rather than used as authority for broad architecture expansion.
