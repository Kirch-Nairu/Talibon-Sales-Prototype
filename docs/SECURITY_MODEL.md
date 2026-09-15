# Security Model

Security is a functional requirement of the prototype, not a presentation label.

## Core principles

1. Least privilege.
2. Deny by default for sensitive functions.
3. Department-aware access control.
4. No direct database access from employee workstations.
5. No shared administrator credentials in production.
6. Workflow and security events are auditable.
7. Sensitive information is not exposed merely because a user knows a URL.
8. UI hiding is not authorization. Every protected action must be enforced server-side.

## Prototype role set

- System Administrator
- Mayor's Office Approver
- Mayor's Office Staff
- Department Head
- Department Staff
- HR Officer
- Legislative Staff
- Employee

The final municipality permission matrix will be configurable and must reflect Talibon's actual organization and delegation rules.

## Initial authorization examples

- Department staff can view their department inbox and transactions assigned/routed to their department.
- Department heads can perform additional endorsement/routing actions.
- Mayor's Office staff can view the Mayor's Office queue but final approval can be permissioned separately.
- HR officers can manage employee and leave-administration information.
- Legislative staff can create/manage legislative metadata.
- Ordinary employees can view public/internal memoranda addressed to them and their own leave information.
- Users outside HR are denied access to confidential HR administration routes unless explicitly granted.

## Audit event minimum fields

- actor user ID;
- actor employee ID if present;
- actor department ID if present;
- action code;
- entity type and entity ID where applicable;
- outcome: allowed / denied / failed;
- human-readable summary;
- request IP;
- user agent/session context where available;
- correlation/request ID;
- event timestamp.

## Security demo

The demonstration should intentionally attempt one unauthorized HR action from a non-HR account. The server must return an access-denied response and create an audit event. The same action should succeed when performed by the authorized HR account.

## Production hardening backlog

- MFA for privileged users;
- HTTPS only;
- secure session cookie configuration;
- production rate limiting and lockout policy;
- secrets management;
- encrypted backups;
- database network isolation;
- centralized logs and alerting;
- approved backup/restore drills;
- vulnerability/dependency scanning;
- formal privacy and records-retention review;
- disaster recovery plan;
- production audit retention and tamper-resistance controls.