# P1-M8 Property Lifecycle Acceptance

Authority: `SSOT_BY_KIRCH.md`

This milestone extends the existing LGU property accountability foundation without replacing the established `assets`, `asset_assignments`, and append-only `asset_events` contracts.

Mandatory acceptance paths:

1. Register -> assign -> return remains compatible.
2. Maintenance start -> asset enters repair state -> maintenance completion restores an accountable/available lifecycle state and appends events.
3. Inventory session -> asset scan/observation -> verified or discrepancy status -> session close.
4. Accounting reconciliation -> reference/status/book-value context recorded without replacing the official accounting ledger.
5. Disposal recommendation -> no active employee accountability -> documented authority/reference -> approved/rejected decision -> append-only event history.
6. Unauthorized ordinary employees cannot mutate property lifecycle records.
7. Offboarding continues to detect active `asset_assignments` as blockers until property is returned.

Integration boundaries:

- QR/reference values are supported, but camera/barcode hardware integration is not claimed.
- Disposal authority is represented as a required authority/reference record pending Talibon's formally validated approval chain.
- Accounting reconciliation is a platform control/visibility layer and not a replacement government ledger.
