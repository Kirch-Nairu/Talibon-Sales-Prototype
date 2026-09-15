# M6 Build Status

Branch: `KIRCH-PROTOTYPE-M6-EXECUTIVE-COMPLETE`

## Implemented

### Authentication and workforce
- 350 synthetic active employee records.
- 350 seeded portal identities distributed across the municipal structure.
- Seven featured presentation accounts retain known demo credentials.
- Login screen exposes all seven featured account emails and the demonstration password and allows one-tap credential loading.
- Searchable employee directory with office filtering and mobile cards.

### Municipal organization
- Expanded municipal office structure to approximately 29 offices/functions based on the Talibon municipal organization represented for the prototype.
- Department membership remains the authorization/workspace anchor.

### Inter-office workflow accountability
- Existing transaction routing preserved.
- Responsible employee assignment.
- Received timestamp.
- Due date with priority-based defaults.
- Current-office aging.
- Due-soon / overdue / completed state.
- Assignment, routing, review, Mayor decision, and audit events remain append-only.
- Mayor approver is separated from generic office-routing actions.

### Mayor / executive visibility
- Municipality-wide active transaction count.
- Executive queue count.
- Overdue and high-priority counts.
- Workforce and office scale.
- Department workload and bottleneck table.
- Live Mayor queue remains short-polling over the shared database.

### Central records
- Dashboard aggregates memoranda and municipal issuances.
- Repository supports ordinances, resolutions, executive orders, office orders, administrative orders, circulars, and other records.
- Additional synthetic issuance records seeded.
- Memorandum popup / acknowledgement behavior retained.

### Live activity notifications
- Header notification center combines active office transactions and unread memoranda.
- Global short polling updates the activity list.
- High/urgent items and acknowledgment-required memoranda are surfaced as action items.

### Operations monitoring
- Unified operational-item domain for projects, procurement, fund utilization, and compliance.
- Executive monitoring surface with responsibility, target dates, progress, financial utilization, and overdue state.
- Synthetic representative operational records seeded.
- Access restricted to executive roles.

### HRIS / attendance / payroll
- Existing electronic leave-credit and leave-request workflow retained.
- Attendance simulation now uses plausible completed workday timestamps.
- Biometric integration status explicitly represented as a simulation boundary.
- Payroll periods and payroll entries added for all synthetic employees.
- Employee payroll view and HR-level period summary added.
- Payroll values are explicitly prototype values and are not represented as official government payroll formulas.

### Reports
- Report hub for workload, aging, records, HR evidence, payroll, and operations.
- CSV exports for department workload, transaction aging, employee directory, operations, and payroll summary.
- Payroll export restricted to HR/system administration; operations/workload exports restricted to executive roles.
- Print-friendly browser report path available.

## Deferred by design
- RHU / medical-record system.
- Production payroll rules and statutory computation engine.
- Physical biometric device driver.
- Native push notifications.
- WebSocket infrastructure.
- Production cloud/hybrid deployment.
- Digital signatures.
- Production backup/disaster-recovery implementation.

## Validation gate
M6 must not be promoted to presentation candidate until the following pass locally:

1. `php artisan migrate:fresh --seed`
2. Workforce count equals 350.
3. Seven featured credentials authenticate.
4. Engineering -> receiving office -> Mayor -> approval workflow succeeds.
5. Mayor account shows executive actions only, not generic department routing controls.
6. Employee assignment and deadline states update correctly.
7. Operations and Reports authorization boundaries return 403 for unauthorized accounts.
8. Payroll is visible to the employee and HR summary works.
9. `npm run build` succeeds.
10. `php artisan test --testsuite=Feature` succeeds against `talibon_portal_test`.
11. Phone LAN rendering has no horizontal overflow.
12. Mayor queue and transaction detail update without manual refresh.

## Test database
`phpunit.xml` targets a dedicated PostgreSQL database named `talibon_portal_test` so automated feature tests cannot wipe the presentation database.
