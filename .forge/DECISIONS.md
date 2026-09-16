# ONE TALIBON V1 — Accepted Maintainer Decisions

These decisions close the PRE-NEST design questions required before writer decomposition. Reopening an accepted decision requires explicit Maintainer authority and evidence.

## UXD-001 — Baseline authority

STATUS: ACCEPTED

Use `0913a37f96affd2c2a681697bdf6fdb6c381a99e` as the immutable UI/UX correction baseline.

## UXD-002 — Density strategy

STATUS: ACCEPTED

Correct density through shared shell/page primitives first. Avoid indiscriminate font shrinking and one-off compression.

## UXD-003 — Sidebar priority

STATUS: ACCEPTED

Preserve separate navigation/footer scrolling architecture. Compress identity. Demote Appearance. Keep Switch Workspace and Sign out directly reachable.

## UXD-004 — Dashboard hierarchy

STATUS: ACCEPTED

Use `ACT NOW → NEXT / SOON → CURRENT OPERATING PICTURE → REFERENCE / HISTORY` as the governing dashboard hierarchy, adapted per persona.

## UXD-005 — Search semantics

STATUS: ACCEPTED

Records search remains Records search. Compact/expandable presentation is allowed; fake universal-search semantics are not.

## UXD-006 — Detail continuity

STATUS: ACCEPTED

Use explicit Back/breadcrumb recovery, preserve list/filter context where practical, and keep related/selected context near the user's action.

## UXD-007 — Planning mobile contract

STATUS: ACCEPTED

Dense desktop registers may remain. Narrow tablet/mobile must expose direct record opening without traversing the full table width. Use compact adaptive list/row/card treatment and responsive local detail presentation.

## UXD-008 — Persistent utility surface

STATUS: ACCEPTED

Authorize a restrained right utility surface for Calendar next-up, announcements, and Messages summaries. Wide desktop may expand; laptop defaults compact; tablet/mobile uses overlay/drawer.

## UXD-009 — Messages boundary

STATUS: ACCEPTED

Initial quick-access messaging is read-oriented only. No fake send/realtime/presence behavior.

## UXD-010 — Weather

STATUS: DEFERRED

No external weather provider/API in initial correction waves. Separate bounded decision required later.

## UXD-011 — HR and Legislative role treatment

STATUS: ACCEPTED

Keep the shared shell. Improve HRIS discoverability and HR/Legislative home shortcuts/context before considering bespoke experiences.

## UXD-012 — Administration/security IA

STATUS: ACCEPTED

Remove visible ordinary Audit & Security entry points from V1 presentation. Preserve backend security. Do not expose fake Users/Departments destinations while integration remains pending.

## UXD-013 — Error-state normalization

STATUS: ACCEPTED

Normalize 403/status visual treatment, dark mode, recovery wording, and implementation-facing copy.

## UXD-014 — Acceptance matrix

STATUS: ACCEPTED

Final acceptance must cover representative Executive, Engineering Department Head, Budget Department Head, Employee, HR, Legislative, and System Administration experiences across desktop/laptop/mobile, with accessibility and task-semantic checks rather than root overflow alone.
