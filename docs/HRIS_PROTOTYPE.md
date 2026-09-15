# HRIS Prototype Boundary

## Working in this prototype

- employee self-service HR dashboard;
- electronic leave credit display for balance-tracked leave types;
- entitlement-labelled leave types for categories that should not be treated as a simple accumulated balance;
- leave request submission;
- HR-only pending request queue;
- approval with atomic leave-credit deduction where applicable;
- rejection;
- leave credit transaction ledger foundation;
- attendance event records designed for later biometric import;
- server-side HR administration restriction;
- denied HR administration access recorded in the audit trail.

## Deliberately not implemented tonight

- government payroll calculation;
- actual biometric hardware protocol/vendor integration;
- automated attendance-to-payroll computation;
- final CSC leave-rule engine;
- maternity/paternity policy calculation;
- production personnel-file storage.

The seeded balances and attendance events are synthetic demonstration data, not actual LGU employee records.

## Security demonstration

A non-HR account can visit `/hris/admin` to demonstrate enforcement. The server returns a dedicated access-denied page and records `hris.admin.access` with outcome `denied`. An HR account can access the same route successfully.