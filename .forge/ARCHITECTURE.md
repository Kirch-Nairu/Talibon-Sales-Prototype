# ONE TALIBON V1 — UI/UX Correction Architecture

## Scope

This architecture record governs the UI/UX correction program only. It does not redefine the production architecture of `Kirch-Nairu/Talibon-Intra-Office-Portal`.

## Existing application boundary

The repository is a Laravel/Inertia/React/TypeScript municipal workspace using the existing backend/domain foundations. The correction program is frontend-led and must reuse real backend behavior where it exists.

Do not introduce a second authorization system, workflow engine, database architecture, realtime messaging stack, microservice split, AI subsystem, or external weather dependency as incidental UI work.

## Shell

The shared shell remains the central coordination layer:

- desktop/mobile navigation;
- route identity and municipal context;
- notifications;
- real Records search;
- workspace/persona switching where Showcase behavior is active;
- responsive utility access.

Preserve the existing strong structural pattern of a scrollable navigation body and separate footer. Correct priority and size rather than replacing the shell wholesale.

## Density architecture

Density changes should flow through shared primitives where possible:

- PageFrame spacing;
- PageHeader scale/spacing;
- municipal panels/cards/rows;
- shell header/footer geometry;
- table/list density;
- responsive control sizing.

Do not globally reduce touch targets or form readability to achieve compactness.

## Dashboard architecture

Dashboard composition is role-aware. The target information hierarchy is:

1. ACT NOW
2. NEXT / SOON
3. CURRENT OPERATING PICTURE
4. REFERENCE / HISTORY

Persistent-awareness content that does not require full dashboard width may move into the utility surface when doing so reduces duplication and preserves context.

## Detail-flow architecture

List/detail flows should maintain locality:

- selected record remains close to the interaction that opened it;
- Back/breadcrumb recovery is explicit;
- filter/list context is preserved where technically practical;
- real relationships may expose related-work navigation;
- drawers/panels are tools, not universal replacements for route-level detail pages.

## Planning architecture

Desktop Planning may remain register/table oriented.

Narrow-screen Planning must adapt the interaction model rather than merely allow a very wide table to scroll. Primary record opening must remain directly reachable. Detail presentation should use adjacent/sticky presentation on wide screens and drawer/sheet or similarly local presentation on narrow screens.

Maintain the planning relationship model:

`Development Plans → PPAs → Projects → Project Monitoring`

## Utility architecture

A right-side persistent utility surface is authorized for real read-oriented data:

- next Calendar items;
- brief announcements;
- recent Messages/channel summaries;
- notification awareness where non-duplicative.

Wide desktop may show it expanded. Ordinary laptop should bias toward collapsed/on-demand. Tablet/mobile should use overlay/drawer behavior.

No external weather provider is part of initial architecture.

## Messaging architecture

Current quick-access scope is read-oriented. It may show recent threads/history, channel/office context, and navigation to full Messages.

Do not create unsupported composer, presence, typing, delivery, or realtime behavior.

## Role architecture

Keep one municipal shell. Improve role-specific discoverability and dashboard/shortcut composition for:

- Municipal Executive;
- Department Head — Engineering;
- Department Head — Budget;
- Employee;
- Human Resources;
- Legislative Office;
- System Administration.

A new bespoke shell/experience type requires separate Maintainer decision and evidence.

## Error and accessibility architecture

Error surfaces are part of the same visual system and must support light/dark modes, clear recovery, and non-implementation-facing copy.

Accessibility requirements apply across shared primitives and domain waves. A corrected surface is not complete if its primary actions are unreachable by keyboard, unnamed programmatically, lost under reflow, or inaccessible through responsive overflow.
