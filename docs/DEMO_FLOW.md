# Prototype Demo Flow

The presentation should demonstrate outcomes rather than enumerate menus.

## Device roles

Recommended LAN demo:

- Device A: Engineering account
- Device B: Budget / Mayor's Office account
- Device C or phone: Regular employee account

## Scenario 1: Inter-office transaction

1. Log in as Engineering.
2. Show Engineering-specific dashboard and inbox.
3. Create `TAL-2026-DEMO-001` - Road Rehabilitation Funding Request.
4. Add priority, description, and sample attachment metadata.
5. Route the transaction to Budget.
6. On the Budget device, receive/open the transaction.
7. Mark it `FOR REVIEW` and add a review note.
8. Endorse/route it to the Mayor's Office.
9. Open the Mayor's Office command queue.
10. Review prior routing and endorsements.
11. Approve the transaction.
12. Return to Engineering and show the approved state.
13. Open the immutable routing timeline.

## Scenario 2: Memorandum delivery

1. Log in as Mayor's Office staff.
2. Publish `MEMO-2026-DEMO-001` to all employees and require acknowledgement.
3. On the employee device, show the new memo notification/modal.
4. Open and acknowledge it.
5. Return to Mayor's Office and show delivered/viewed/acknowledged counts.

## Scenario 3: Legislative records

1. Open Legislative Records.
2. Search for `waste`, `infrastructure`, or another seeded keyword.
3. Show matching ordinances and resolutions in one repository.
4. Open a record and show number, title, date, status, issuing body, and related metadata.

## Scenario 4: Leave credit

1. Log in as a regular employee.
2. Show electronic leave balances.
3. Submit a leave request.
4. Log in as HR.
5. Review the request.
6. Approve it and show the ledger/balance change.

## Scenario 5: Cybersecurity

1. While logged in as Engineering, attempt to open an HR confidential administration page.
2. Show `Access denied`.
3. Open Audit & Security using an authorized account.
4. Show the denied access event with actor, department, resource, outcome, and time.
5. Log in as HR and open the protected page successfully.

## Presentation rule

Do not claim prototype-only integrations as complete. In particular: payroll, biometric hardware, cloud deployment, digital signatures, and medical records are future implementation areas.