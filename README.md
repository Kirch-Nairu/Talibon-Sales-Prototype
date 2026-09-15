# Talibon Core Intra-Office Portal

Municipal Digital Operations Platform — Core Intra-Office Portal for the Local Government Unit of Talibon, Bohol.

## Current authority

- Controlling scope: `SSOT_CURRENT_INTRA_OFFICE_PORTAL_SCOPE.md`
- Authoritative development branch: `main`
- Accepted pre-R0 engineering baseline: `e781b08dcc6476c7b6ad3565a61afc41965fe33f`
- H0-H5 engineering hardening: **CLOSED / INTEGRATED / GREEN**
- Current state: **working prototype preparation / Department Head evaluation preparation**
- UAT: **NOT STARTED**
- Production deployment: **NO**

## Engineering baseline

- Laravel 13 / PHP 8.3+
- Inertia + React 19 + TypeScript
- Tailwind CSS
- PostgreSQL
- modular monolith

PostgreSQL is authoritative. Server-side authorization is authoritative. Realtime delivery and frontend visibility are not sources of truth.

## Active Core Portal scope

The active procurement is the Core Intra-Office Portal described by the controlling SSOT. Current surfaces include authenticated municipal access, office-aware workflow, correspondence, records, memoranda and notifications, current-scope reports, LGU Calendar, Approved Travel Orders, secure supporting documents/evidence, dashboards, organization data, and authorized audit/security visibility.

Historical HRIS, payroll, DTR, attendance, leave, Property, Legislative expansion and other broader modules remain preserved but parked unless explicitly re-authorized by the controlling scope.

## Repository rules

Read `AGENTS.md` and `SSOT_CURRENT_INTRA_OFFICE_PORTAL_SCOPE.md` before making changes. Every implementation/test/code-comment commit must append `docs/ENGINEERING_LOG.md` in the same commit. Never claim CI, UAT, deployment or production readiness without observed evidence.
