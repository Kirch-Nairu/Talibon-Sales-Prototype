# Development Plan

## M0 - Foundation

- repository initialization;
- Laravel/Inertia/React project skeleton;
- PostgreSQL-first environment example;
- application layout;
- architecture/security/scope documentation;
- base test structure.

## M1 - Identity and Organization

- departments;
- positions;
- employee profiles;
- user-to-employee relationship;
- roles/permissions;
- department-aware login/dashboard;
- seeded demo accounts.

Acceptance: Engineering, Budget, HR, Legislative, and Mayor's Office accounts see distinct workspaces and unauthorized areas are server-side denied.

## M2 - Transaction Workflow

- transactions;
- transaction events;
- department inbox;
- create/forward/return/review/endorse actions;
- timeline;
- audit hooks.

Acceptance: Engineering -> Budget -> Mayor's Office flow works against one shared database.

## M3 - Mayor's Office

- dedicated command dashboard;
- review queue;
- approve/disapprove/request information/return;
- management counts.

## M4 - Memoranda

- create/publish;
- all/department/employee targeting;
- view/acknowledgement state;
- delivery statistics;
- notifications.

## M5 - Legislative Records

- ordinances;
- resolutions;
- searchable metadata;
- document detail.

## M6 - HRIS Prototype

- employee directory;
- leave types;
- leave ledger;
- leave requests;
- HR approval;
- attendance sample/import boundary.

Payroll remains outside the working prototype implementation.

## M7 - LAN and Demo Hardening

- responsive UI;
- realistic seed dataset;
- multi-device LAN runbook;
- zero dead controls on the demonstration path;
- access-denied security proof;
- reset/reseed command;
- demo smoke checklist.

## Quality gates for every milestone

- migrations are reversible where practical;
- authorization is server-side;
- workflow state changes append audit/history evidence;
- no real citizen/employee personal data in seed fixtures;
- no secrets committed;
- no medical data in this repository;
- formatter/linter/tests/build pass when dependencies are available.