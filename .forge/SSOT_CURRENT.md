# ONE TALIBON V1 — Current SSOT

## Program

ONE TALIBON V1 — UI/UX CORRECTION & ENHANCEMENT PROGRAM

Repository: `Kirch-Nairu/Talibon-Sales-Prototype`

Active integration branch: `KIRCH-TALIBON-V1-UIUX-CORRECTION`

Accepted correction baseline: `0913a37f96affd2c2a681697bdf6fdb6c381a99e`

Forge pin: `Kirch-Nairu/KIRION-FORGE@44eb57e5b45b343be0033bf22a7a5e74d543c01a`

## Product purpose

One Talibon is a dense internal municipal digital workspace for day-to-day office coordination. It is not a marketing site, consumer social product, AI assistant, or security showcase.

The correction program preserves the existing municipal workspace architecture while improving operational ergonomics, density, context continuity, role relevance, responsive reachability, accessibility, and visual hierarchy.

## Governing UX principle

### Operational Compression

Reduce unnecessary scrolling, wasted whitespace, repeated information, oversized treatment, action hunting, route bouncing, context loss, horizontal task traversal, and equal visual weighting of unequal information while preserving readability and accessibility.

The target is not maximum compactness. The target is shorter operational distance between user intent, action, context, and consequence.

## Accepted pre-Nest decisions

### 1. Density system

Use shared primitives and shell/page spacing rules first. Do not randomly shrink text or locally compress pages without a system.

### 2. Sidebar hierarchy

Preserve the current structural pattern where navigation owns the scroll region and the footer remains separate. Reduce footer consumption, compress identity, demote Appearance, and keep Switch Workspace / Sign out easy to reach.

### 3. Header/search

Compact the shell header. Keep Records search semantically honest; do not relabel it as universal search. Expansion-on-focus/popover treatment is allowed where needed for space.

### 4. Dashboard hierarchy

Organize role-relevant information around:

`ACT NOW → NEXT / SOON → CURRENT OPERATING PICTURE → REFERENCE / HISTORY`

Do not solve hierarchy merely by shrinking every card.

### 5. List/detail continuity

Standardize clear Back/breadcrumb recovery, preserve list/filter state where practical, keep selected context near the trigger, and expose real related-work links where they already exist or are supported by real relationships.

### 6. Planning responsiveness

Desktop may retain dense tables. Narrow tablet/mobile must not require traversing 1000+ pixel tables to reach the primary record action. Use compact adaptive row/card/list treatment with direct record opening. Detail should remain near the selected record via adjacent panel, sticky panel, or responsive drawer/sheet rather than appearing after an arbitrarily long register.

### 7. Persistent utility architecture

A restrained right-side utility surface is authorized for real, read-oriented information such as next Calendar items, brief announcements, and recent Messages/channel summaries.

Wide desktop may expose it; ordinary laptop should default compact/collapsed; tablet/mobile should use drawer/overlay behavior. It must not materially crush the primary work canvas.

### 8. Messages boundary

Quick access is read-oriented in this program. Do not invent Send, typing indicators, presence, realtime chat, or other collaboration semantics not backed by the current system.

### 9. Weather

External weather/provider integration is deferred from the initial correction program. No API/provider should be introduced incidentally inside shell cleanup.

### 10. Human Resources

Improve HRIS discoverability and HR-relevant home/shortcut context for the Human Resources persona. A separate bespoke shell is not required unless later evidence proves it necessary.

### 11. Legislative Office

Keep the shared municipal shell. Add role-relevant Legislative home context/shortcuts where useful rather than creating a separate shell by default.

### 12. Administration / security presentation

Remove ordinary visible Audit & Security entry points from the V1 presentation while preserving underlying security behavior. Do not fake Users/Departments surfaces while routes/integration remain pending; materialize them only when real navigation targets are available.

### 13. Error-state normalization

Normalize dedicated 403/status presentation, dark-mode parity, recovery wording, and remove unnecessary implementation/security-audit language from normal user copy.

### 14. Accessibility

Accessible names, focus return, keyboard flow, reflow/zoom, contrast, semantic headings/landmarks, and responsive action reachability are acceptance concerns, not optional polish.

## Inherited confirmed correction debt

The G0 Reviewer confirmed or source-established issues including:

- Planning mobile action reachability;
- Planning list/detail locality below 2XL;
- Showcase selector focus restoration;
- Messages search/channel accessible names;
- Legislative search accessible name;
- Calendar omission of existing end/all-day/location data;
- visible Audit & Security presentation contradiction;
- HR persona/HRIS discoverability mismatch;
- HRIS dark-mode/design inconsistency and prototype/simulation wording;
- dedicated 403 dark/copy inconsistency;
- Employee Directory narrow-screen table dependence;
- Development Plans / Municipal Plans naming inconsistency.

Likely risks remain open for dashboard hierarchy, Calendar dual-schedule comprehension, sidebar footer priority, and list/filter-state preservation.

## Runtime evidence state at program start

- runtime: NOT RUN;
- build: NOT RUN by Reviewer/Acceptance;
- type check: NOT RUN by Reviewer/Acceptance;
- PHP tests: NOT RUN by Reviewer/Acceptance;
- browser harness: NOT RUN by Reviewer/Acceptance;
- exact-SHA CI: NOT OBSERVED;
- responsive acceptance: NOT PERFORMED;
- accessibility acceptance: NOT PERFORMED.

Do not reinterpret these as failures or passes. They are open evidence states.

## Non-goals

- no giant CSS rewrite;
- no backend-auth redesign merely for navigation;
- no wholesale removal of useful information;
- no all-tables-to-cards mandate;
- no decorative/fake charts;
- no fake actions/realtime behavior;
- no Facebook visual clone;
- no external weather integration in initial waves;
- no AI product surface;
- no ordinary Audit & Security marketing/presentation surface;
- no conversion into a marketing site.

## Planned correction waves

`W1 — Shell Compaction & Density Foundation`

`W2 — Dashboard Hierarchy`

`W3 — Context-Preserving Review Workflows`

`W4 — Planning Responsive UX`

`W5 — Calendar + Persistent Utility Rail`

`W6 — Messaging Quick Access`

`W7 — Role / HRIS / Admin / Error Completion`

`W8 — Cross-Product Acceptance & Harness Expansion`

Dependency shape:

`W1 → {W2, W3, W4}`

`W1 → W5 → W6`

`W1 + W2 → W7`

`W2 + W3 + W4 + W5 + W6 + W7 → W8`

No wave is authorized merely by appearing here. Each requires a bounded Maintainer handoff with exact source SHA, ownership, decisions, validation, and stop conditions.
