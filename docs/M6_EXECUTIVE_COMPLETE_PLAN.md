# M6 Executive Complete Prototype Plan

## Objective
Deliver a presentation-ready municipal intra-office prototype that maps directly to LGU Talibon's stated needs while preserving the working M5 foundation.

M6 is not a rewrite. It extends the existing Laravel + Inertia + React + PostgreSQL modular monolith.

> Historical plan note: current implementation authority is defined by `SSOT_CURRENT_INTRA_OFFICE_PORTAL_SCOPE.md` and its later amendments. Where this historical M6 plan conflicts with current authority, the current authority controls.

## Demonstration goals
The prototype must visibly demonstrate:

1. Department-scoped employee access.
2. Inter-office transaction creation, routing, assignment, review, and Mayor's Office approval.
3. Near-real-time shared state over LAN for laptop and phone users.
4. Municipality-wide executive visibility for workload, aging, bottlenecks, projects, procurement, fund utilization, and compliance.
5. Central access to memoranda, ordinances, resolutions, executive orders, office orders, and circulars.
6. HR self-service with electronic leave credits, attendance activity, and a payroll prototype boundary.
7. A municipal workforce dataset sized around 350 synthetic employee records.
8. Audit and security visibility for privileged actions and denied access.
9. Mobile-first usability for the Mayor's Office and other approval workflows.

## Architecture

Browser / Phone / Office Terminal
-> HTTP over LAN
-> Laravel application
-> Inertia bridge
-> React + TypeScript UI
-> PostgreSQL single source of truth

Current near-real-time behavior uses short polling. WebSockets are intentionally deferred until after the presentation candidate is stable.

## Scope lanes

### M6.1 Municipality structure and workforce
- Expand the seeded office directory to the Talibon municipal structure represented in the public directory and meeting requirements.
- Seed approximately 350 synthetic employees.
- Keep only representative demo identities as portal login accounts.
- Add an employee directory surface.

### M6.2 Transaction accountability
- Use existing assigned_to_user_id.
- Add due_at, received_at, completed_at.
- Expose assigned officer, age in current office, due-soon, and overdue state.
- Preserve append-only transaction events.

### M6.3 Executive municipal overview
- Municipality-wide active workload.
- Department workload and bottlenecks.
- Executive attention queue.
- Overdue and high-priority indicators.

### M6.4 Central records and notifications
- Aggregate memoranda and legislative issuances.
- Expand issuance types.
- Unified in-app notification center.

### M6.5 Operations monitoring
One shared operational item model for:
- Projects
- Procurement
- Fund utilization
- Compliance requirements

### M6.6 HRIS completion boundary
- Existing leave and attendance remain authoritative prototype modules.
- Add payroll periods and employee payroll entries.
- Add biometric integration status rather than pretending physical hardware is connected.

### M6.7 Reports
- Executive transaction summary
- Department workload
- Aging / overdue
- Memo acknowledgement
- Leave and attendance summaries
- Payroll summary
- Projects / procurement / funds / compliance

## Explicitly deferred
- RHU / patient records system
- Production biometric drivers
- Full government payroll computation rules
- Native push notifications
- Cloud deployment migration
- WebSocket infrastructure rewrite
- Digital signatures
- Production disaster recovery implementation

## Demo identities

The historical M6 prototype used shared demo credentials. The shared password is deliberately not retained in the current tree. Under the current One Talibon authority, any known prototype password must be supplied privately through `PROTOTYPE_DEMO_PASSWORD`; the Login UI starts blank and does not expose demo credentials.

Featured synthetic identities:

- admin@talibon.demo
- mayor@talibon.demo
- engineering@talibon.demo
- budget@talibon.demo
- hr@talibon.demo
- legislative@talibon.demo
- employee@talibon.demo

## Presentation definition of done
- ~28 configured offices
- ~350 synthetic employees
- representative demo identities available in the seeded dataset without exposing credentials on the Login screen
- successful Engineering -> receiving office -> Mayor route
- Mayor phone sees routed work without manual refresh
- Mayor can approve and originating device sees updated state
- employee directory shows realistic municipal scale
- no horizontal overflow on phone
- migrations and deterministic seeding succeed
- production frontend build succeeds
- feature tests pass before release candidate freeze
