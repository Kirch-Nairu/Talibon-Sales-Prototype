# Memoranda Module

## Prototype behavior

Authorized Mayor's Office accounts can publish a memorandum to:

- all active employee accounts;
- selected departments;
- selected employees.

Recipient membership is resolved at publication time and written to `memo_recipients`. This preserves delivery evidence even if an employee later moves departments.

Each recipient has separate timestamps for delivery, first view, and acknowledgement.

## Website notification behavior

The portal checks for unseen memoranda on a short interval while an authenticated page is open. A newly published memo therefore appears as a portal popup on another LAN device without requiring the employee to manually refresh.

The database remains authoritative. Polling is an intentionally low-risk prototype transport. Laravel Reverb/WebSocket broadcasting may replace the polling transport later without changing memorandum or recipient records.

## Phone behavior

The responsive website can be opened from a phone on the same LAN and receives the same in-portal popup behavior. Native OS push notifications, SMS, and a native mobile application are not claimed by this prototype.

## Audit behavior

Publication and acknowledgement create application audit events. Production will require stronger audit retention and privacy controls before deployment.