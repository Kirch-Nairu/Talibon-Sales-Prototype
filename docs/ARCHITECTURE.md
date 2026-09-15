# Architecture

## Architecture style

The prototype is a modular monolith. We are intentionally not introducing microservices. At the expected municipal user count, system risk is dominated by authorization, workflow correctness, records integrity, auditability, deployment, and operational recovery rather than horizontal service scaling.

## Stack

- Laravel 13 / PHP 8.3+
- Inertia + React 19 + TypeScript
- Tailwind CSS
- PostgreSQL
- Laravel notifications
- Laravel Reverb / broadcasting where realtime behavior is stable enough for the prototype

## Logical modules

- Identity
- Organization
- Workflow
- Records
- Memoranda
- Legislation
- HRIS
- Attendance
- Notifications
- Audit
- Administration

Module boundaries are logical boundaries inside one deployable application. Cross-module writes should occur through application services or explicit domain actions rather than arbitrary controller-to-table coupling.

## Runtime model

One application instance and one authoritative PostgreSQL database run on the prototype host. LAN clients connect through their browsers. Clients never receive database credentials and never connect directly to PostgreSQL.

```text
Laptop / Phone / Workstation
          |
        HTTP(S)
          |
     Laravel App
   /      |       \
Auth   Workflow   HRIS
          |
      PostgreSQL
```

For the LAN prototype, the development server may listen on `0.0.0.0` so other devices on the same network can connect. Production deployment must use an approved web server/reverse proxy, HTTPS, backup strategy, firewall rules, and managed secrets.

## Identity and organization model

A login account is not the employee record. `users` authenticates the person; `employees` represents their municipal employment identity. An employee belongs to a department and position and may carry one or more authorization roles.

Authorization decisions should consider:

- authenticated user;
- employee status;
- department;
- role/permission;
- ownership or assignment;
- document/record classification where applicable.

## Workflow model

The main business object is a municipal transaction, not a hard-coded department ticket. Transaction types can represent project endorsements, internal requests, document reviews, procurement-related requests, leave-related routing, and other future workflows.

A transaction stores its current state for efficient querying. Every transition also appends a `transaction_event`, preserving the history.

No workflow event should be edited or deleted through normal application operation.

## Realtime model

Realtime is an enhancement, not the source of truth. Database state is authoritative. Broadcast events notify connected clients to refresh or update state. If WebSocket infrastructure is unavailable, clients must still obtain correct state through normal requests.

## Records model

Files are not coupled directly to database blobs. PostgreSQL stores record metadata and protected storage stores file content. A future storage adapter can target local protected storage, NAS, or approved object storage without changing records-domain rules.

## Health system boundary

RHU patient records are explicitly outside this system and must use a separate application/database boundary because health information has substantially different privacy, clinical workflow, retention, access, and interoperability requirements.