# Prototype Scope

## Goal

Produce a minimal working prototype that demonstrates the actual municipal workflow requested by LGU Talibon. The prototype is not a static dashboard mockup. The core demonstration must perform real writes against one shared database and show department-aware authorization.

## Required working capabilities

1. Employee login with an employee identity, department, position, role, and account status.
2. Distinct department workspaces. Engineering, Budget, HRMO, Legislative, and the Mayor's Office must not behave like the same account with different labels.
3. Department-aware transaction inboxes showing work visible to the authenticated office.
4. Transaction creation and inter-office routing with status, priority, remarks, and append-only routing history.
5. Mayor's Office queue for review, request-for-information, return, approval, and disapproval actions.
6. Memorandum publication to all employees, selected departments, or selected employees with delivered/viewed/acknowledged state.
7. Central legislative records for ordinances and resolutions with searchable metadata.
8. HRIS prototype: employee directory, electronic leave credit ledger, leave request submission, and HR review.
9. Attendance prototype records designed for later biometric integration. No hardware-specific biometric dependency is permitted in the prototype core.
10. Audit events for security-relevant and workflow-relevant actions, including denied HR administration access.
11. Responsive browser UI usable from laptops and phones on the same LAN.
12. A municipality office directory showing configured departments, prototype employee counts, and active workflow counts.

## Deliberately not implemented in the first prototype

- transaction/document file upload and archival ingestion;
- full government payroll computation;
- direct biometric device integration;
- production cloud deployment;
- SMS gateway or native OS push notifications;
- native mobile application;
- procurement/accounting/fund-utilization engines;
- legal digital-signature integration;
- OCR and bulk archival migration;
- RHU / medical record functions.

These may be represented as future modules, but the prototype must not imply they are production-complete.

## Demonstration acceptance path

The prototype is considered convincing when the following can be demonstrated on multiple LAN devices:

Engineering creates a transaction -> Budget receives and reviews it -> Budget routes it to the Mayor's Office -> Mayor's Office approves it -> Engineering sees the approved state -> the complete routing history remains visible.

A second acceptance path is:

Mayor's Office publishes a memorandum -> an employee receives it through the open portal -> the employee acknowledges it -> Mayor's Office sees acknowledgement statistics.

A security proof must also be shown:

Engineering attempts to access confidential HR administration -> access is denied server-side -> the denied action appears in the audit trail -> an authorized HR account can access the same route.

## Data honesty

All prototype people, balances, attendance events, transactions, memoranda, and legislative records are synthetic demonstration data. They must not be represented as actual LGU Talibon operational records.