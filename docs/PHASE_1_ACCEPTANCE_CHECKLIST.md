# Phase 1 Integrated Acceptance Checklist

Authority: `SSOT_BY_KIRCH.md`

Status: implementation candidate; local release gates still required.

## Required environment gates

Run only against the intended local/test environment. Do not run destructive reset commands against the presentation database.

```powershell
php artisan migrate
npm run types:check
npm run build
php artisan test --testsuite=Feature
```

For destructive clean-room verification use only the dedicated `talibon_portal_test` database.

## Acceptance domains

### Organization and routing
- 33 routable nodes remain seeded: 30 executive/administrative and 3 legislative.
- Disabled/non-routable offices are rejected server-side.
- Cross-office routing preserves transaction history, current responsibility, deadlines, and audit evidence.
- Executive and legislative offices use the same auditable routing foundation.

### Shared platform services
- Persistent notifications deduplicate per recipient/event key.
- Action-required notifications can be acknowledged where supported.
- Calendar receives workflow deadlines, approved leave, HR expiry events, and legislative sessions.
- Shared document metadata remains reusable across workflow, HR, property, and legislative domains.

### HRIS lifecycle
- Employee master/profile and 201 metadata obey projection permissions.
- Onboarding cannot activate until mandatory blockers are resolved.
- Employment movement triggers access/work/property review tasks.
- Leave approval preserves atomic credit deduction and ledger evidence.
- DTR is evidence-derived and does not invent attendance policy.
- Payroll linkage consumes locked DTR context without silently recalculating monetary values.
- Performance/development and credential-expiry monitoring remain governed HR records.
- Health-vault content requires explicit purpose-bound access; ordinary HR/system-admin access is not implicit.
- Offboarding cannot finalize with open work, accountable property, or unresolved mandatory clearances.
- Separation deactivates access and archives employment without deleting historical records.

### Property and accountability
- Register, issue, return, and append-only asset-event history remain compatible.
- Maintenance lifecycle preserves condition/history evidence.
- Inventory session observations require the exact QR/property reference and reject mismatches.
- Disposed assets cannot be scanned into active inventory.
- Accounting reconciliation records references/state without claiming to replace the official accounting ledger.
- Disposal requires documented authority and cannot proceed while active employee accountability remains.

### Executive workspace
- Mayor's Office sees its decision queue plus municipality-wide open work, overdue work, returned/information-requested work, and office bottlenecks.
- Non-executive accounts cannot access the executive workspace.

### Legislative workspace
- Legislative sessions can be scheduled by authorized managers.
- Session scheduling publishes a municipal calendar event.
- Agenda sequence is unique within a session.
- Routed work currently held by legislative offices is visible in the legislative workspace.
- Non-legislative/non-admin accounts cannot access the legislative workspace.

### Reporting, audit, and security
- Reports remain restricted to executive/HR roles according to data sensitivity.
- Payroll exports remain HR-only.
- Audit/security surface remains restricted to system administration / authorized executive oversight.
- Audit filters can isolate denied activity, action families, and department context.
- Sensitive employee/health fields are not serialized through broad listings or general report exports.

## Final release evidence required

Before Phase 1 is called release-green, record:
- exact commit SHA tested;
- migration result;
- TypeScript result;
- Vite production-build result;
- full Feature-test count/assertion count/duration;
- any manual browser/mobile/LAN checks;
- known limitations accepted for benchmark deployment;
- rollback/backup state.

Until those results are recorded, use the release label `PHASE1_CANDIDATE_OPEN_GATES`.
