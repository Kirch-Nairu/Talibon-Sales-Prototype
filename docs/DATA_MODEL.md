# Initial Data Model

This document describes the first prototype schema. Names may evolve, but the boundaries should remain stable.

## Identity / Organization

### users
Authentication account. Does not itself represent municipal employment metadata.

### departments
Municipal offices/departments. Configurable; seed data is representative only.

### positions
Position titles optionally scoped to a department.

### employees
Employee number, user link, department, position, supervisor, employment status, contact metadata required for prototype notification/display.

### roles / permissions
Authorization roles and capabilities. Prototype may implement these with first-party tables/policies rather than coupling the domain to a third-party package.

## Workflow

### transactions
Reference number, type, title, description, priority, current department, current assignee, current status, origin department, creator, timestamps.

### transaction_events
Append-only workflow history: action, previous/new state, from/to department, actor, remarks, event time.

### transaction_comments
Non-transition collaboration notes where required.

### transaction_attachments
Metadata for protected attachment storage.

## Memoranda

### memoranda
Number, title, body, issuer, publication window, acknowledgement requirement, classification/status.

### memo_recipients
Resolved user/employee recipient records so delivery state remains stable even if department membership later changes.

### memo_acknowledgements
Viewed/acknowledged timestamps and actor identity.

## Records / Legislation

### legislative_records
Record type, number, title, summary, year/date, status, issuing body, keywords, file metadata/link.

Future record/document versioning will be added without requiring legislative data to become an unstructured file dump.

## HRIS

### leave_types
Configurable leave categories and rule metadata.

### leave_credit_accounts
Employee + leave type account.

### leave_credit_transactions
Append-only credit/debit/adjustment ledger with source and reason.

### leave_requests
Requested dates, type, reason, current approval state, employee, timestamps.

### attendance_logs
Employee, event timestamp, event type/source, biometric external identifier if imported.

## Audit

### audit_logs
Append-only application audit evidence for important allowed/denied/failed actions.

## Data rule

No prototype seed should contain real sensitive personal information. Demonstration identities and documents must be fictional or clearly synthetic.