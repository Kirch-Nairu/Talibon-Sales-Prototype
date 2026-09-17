# ONE TALIBON V1 — DIRECT UX RECOVERY V2

## SENIOR MAINTAINER / IMPLEMENTER HANDOFF

Repository:
`Kirch-Nairu/Talibon-Sales-Prototype`

Technical Authority:
**Kirch Ivan Balite**

Execution mode:
**DIRECT ENGINEERING — NO KIRION FORGE BOOTSTRAP**

Work only on:
`KIRCH-TALIBON-V1-DIRECT-UX-RECOVERY-V2`

Exact starting authority:
`d03e90c3b7a675670ecbe94414593558b7f78be8`

Do not modify `main`.
Do not modify `KIRCH-TALIBON-V1-UIUX-CORRECTION` directly.
Do not deploy until Kirch accepts the visual result.
Do not force push.

---

# 1. MISSION

The current product is functionally strong enough to preserve, but the latest UI/UX correction program did not solve the actual operator experience.

This recovery is not another process exercise.

Your job is to turn the current One Talibon build into a compact, ergonomic municipal workspace that feels practical to use for an LGU employee throughout a full workday.

The current problem is not missing features. The problem is that too much information is presented with oversized cards, excessive vertical space, weak hierarchy, awkward shell proportions, and too much navigation travel.

The target experience is:

> calm, dense, obvious, fast, municipal, low-friction, easy on the eyes, and usable without constant page bouncing.

Preserve the current product behavior, auth, roles, data model, workflow rules, and deployment mechanics unless a tightly bounded frontend-supporting backend change is genuinely necessary.

---

# 2. PRIMARY VISUAL / UX SOURCE

Read this Notion page before editing:

`https://app.notion.com/p/One-Talibon-UI-UX-corrections-3dd29cbe9a938058a97afb05c398dc1e`

The page contains screenshots, Kirch's criticism of the current implementation, and Sir Gerry's expected visual direction.

Important instructions from that page:

- everything currently feels too large;
- excessive whitespace creates unnecessary scrolling and operational inconvenience;
- navigation must be straightforward and ergonomic;
- calendar, messages and related utilities should be available from anywhere, not trapped in separate pages;
- the left sidebar is poorly proportioned and makes secondary controls too prominent;
- appearance controls must not visually compete with actual work actions;
- workspace switching and sign-out remain useful and may stay;
- the dashboard contains useful information but its visual hierarchy is hard to digest;
- review/detail screens are too large and require too much back-and-forth;
- detail/review screens need quick access to related work;
- some workflows still lack obvious return navigation;
- the receiving-end work screen is oversized and confusing;
- the large persistent search field in the global header is visually wrong;
- prioritize actual employee use first;
- defer public/home marketing-style landing work;
- Sir Gerry's expected internal product direction is a practical LGU workspace combining department context, office status, tasks, documents, PPAs, calendar, chat/coordination and quick actions.

Git and runtime truth remain authoritative for behavior. The Notion page is authoritative for this recovery's UX intent.

---

# 3. WHAT WENT WRONG IN THE CURRENT CORRECTION

Do not repeat the previous correction strategy.

The previous branch changed many components but still optimized them as separate surfaces rather than creating one coherent interaction system.

Concrete examples in the current source:

- `AppLayout.tsx` adds a fixed 288px utility rail only at `2xl`, plus a drawer below that breakpoint. This is not yet the compact, continuously useful utility model requested.
- `MunicipalUtilities.tsx` is primarily a static preview of calendar, announcements and messages. It does not yet behave like a true daily utility workspace.
- `PortalSidebar.tsx` still gives substantial vertical real estate to grouped navigation and can force scrolling.
- `SidebarFooter.tsx` still gives appearance/profile/workspace controls strong visual presence at the bottom of the primary work navigation.
- `RecordsSearch.tsx` remains a persistent header search field when search should be secondary until invoked.
- `Dashboard.tsx` still stacks four large conceptual sections — Act now, Next / soon, Current operating picture, Reference / history — which creates a long card-heavy reading experience.
- Transaction, correspondence and travel-order detail pages still behave primarily as long full-page documents instead of efficient review workspaces with nearby related work.

Do not solve this by shrinking every font blindly. The problem is hierarchy, layout, density, information grouping and navigation distance.

---

# 4. EXPERIENCE RULES

## 4.1 Density

At desktop sizes, the first viewport must be useful.

Target these practical constraints:

- global header approximately 56–60px high;
- ordinary content gutters around 16–20px;
- normal section gaps around 12–16px;
- standard card padding around 12–16px;
- reserve 24–32px padding only for genuinely important presentation moments;
- avoid nested cards unless the nesting communicates real hierarchy;
- avoid long explanatory paragraphs in operational screens;
- make tables, lists and action rows the default for repeatable work.

At 1440x900, a user should see meaningful work immediately without scrolling through large headings and empty space.

## 4.2 Visual language

Use the Sir Gerry references as the visual direction.

Keep the municipal identity but make it easier on the eyes:

- calm navy / blue identity;
- lighter neutral work surfaces;
- restrained borders and shadows;
- high information density without visual noise;
- clear active states;
- concise labels;
- no startup/SaaS hero styling;
- no gradients, glass, neon, giant promotional cards or decorative clutter.

## 4.3 Actions over settings

Actual work actions are visually primary.

Appearance, profile context, workspace switching and sign-out are secondary utilities.

Do not let Appearance consume the same visual weight as navigation or task actions.

---

# 5. IMPLEMENTATION PLAN

Execute in this order on the same branch. Commit meaningful checkpoints, but do not create a branch-per-wave bureaucracy.

## PASS A — SHELL, LEFT NAVIGATION, HEADER, GLOBAL UTILITIES

### Left sidebar

Rework the desktop sidebar so it feels like a compact municipal navigation tool rather than a tall control panel.

Requirements:

- preserve the current information architecture and role-aware destinations;
- reduce vertical footprint of brand, section headings and rows;
- emphasize current work destinations;
- maintain clear active state;
- avoid cramped nested scrolling at common desktop heights;
- keep Collapse useful;
- keep workspace switching and sign-out;
- demote Appearance into a compact secondary control;
- keep role/office identity readable but not dominant;
- mobile navigation remains a proper drawer.

### Global header

Remove the persistent large search field as a dominant object.

Replace it with a compact search trigger, icon/button, or small expandable control. Search should become prominent only after the user invokes it.

Preserve records-search behavior.

### Right-side daily utility workspace

Replace the current static utility implementation with a real compact utility rail.

Desktop behavior:

- available from ordinary working widths, not only `2xl`;
- explicitly collapsible / expandable;
- remember open/closed state locally;
- approximately 280–320px when open;
- never squeeze primary content below a usable width;
- at narrower desktop/tablet widths, convert to a drawer/sheet.

Content priority inside the rail:

1. current date + Talibon weather summary;
2. next calendar items;
3. brief announcements;
4. coordination/messages summary;
5. links into full modules.

Weather must be non-critical. If an external API is used, failure must degrade cleanly without affecting the workspace.

### Quick Messages

Add a small bottom-right quick-messages launcher inspired by familiar desktop messaging patterns.

Current Messages is read-only; do not fake send capability.

The quick panel should provide useful read-only recent coordination / channel access and an obvious path to the full Messages page.

Do not duplicate the entire right utility rail inside the message panel.

---

## PASS B — DASHBOARD RECOMPOSITION

Do not merely restyle the existing four giant DashboardPrioritySection blocks.

Recompose the dashboard around a compact operating hierarchy.

Suggested structure:

### Top strip

- role / office context;
- 3–5 small action indicators only;
- no verbose explanatory text.

### Main workspace

Desktop two-column composition:

Left / primary:
- work requiring action;
- office / project work currently moving;
- recent relevant correspondence/documents.

Right / contextual:
- schedule / deadlines;
- role-specific overview;
- short announcements or office updates.

Do not show every available widget merely because data exists.

Use progressive disclosure, tabs, compact lists or "view all" links for secondary information.

At 1440x900, immediate attention, near-term schedule and current work context should all be visible or substantially visible in the first viewport.

---

## PASS C — REVIEW / DETAIL WORKSPACES

This is a major requirement.

Apply the pattern first to:

- `resources/js/pages/Transactions/Show.tsx`
- Correspondence detail/review surfaces
- Travel Order detail/review surfaces

Goals:

- compact record header;
- obvious Back / return-context action on every detail screen;
- sticky or consistently located primary workflow actions;
- reduce giant vertical cards and excessive padding;
- preserve all current permissions and workflow behavior;
- preserve existing return-context work;
- add a "Related work" area so the user can inspect nearby relevant records without repeatedly returning to list pages.

Related work may use a small, bounded backend query if necessary. No schema change is needed or wanted.

Good related-work signals include:

- same office;
- same workflow type;
- same correspondence/thread context;
- nearby items from the originating queue;
- active items owned by the same office.

Keep the result list short and useful.

On wide desktop, use a side panel where appropriate. On small screens, use an inline collapsible section or drawer.

Do not introduce an entirely new workflow engine.

---

## PASS D — RECEIVING / LIST WORKFLOWS + RESPONSIVE POLISH

Inspect the receiving-end work surfaces and other action-heavy pages for the same problems:

- giant cards;
- unclear next action;
- missing return path;
- excessive filter/header size;
- unnecessary whitespace;
- too much page bouncing.

Prioritize:

- Transactions / My Work
- Correspondence
- Travel Orders
- Records
- Planning registers where the same density problem exists

Prefer compact list/table rows, sticky or compact filters, and clear row actions.

Do not redesign stable domain behavior merely for visual novelty.

---

# 6. RESPONSIVE TARGETS

You must directly check at least:

- 1440x900 desktop
- 1366x768 laptop
- 1024x768 tablet-ish width
- 390x844 mobile

Acceptance expectations:

- no horizontal overflow;
- primary navigation remains usable;
- no important control is hidden behind another surface;
- utility rail becomes a drawer/sheet when width is insufficient;
- quick messages do not cover core actions;
- dialogs and drawers have correct focus behavior;
- compact density remains touch-usable on mobile;
- dark mode remains functional, but do not spend the release budget polishing theme trivia before the core layout works.

---

# 7. ACCESSIBILITY

Preserve and improve the existing accessibility fixes.

In particular, do not regress the `/messages` channel-filter accessible name.

Every select/input must have a legitimate accessible name.

Drawers/dialogs require:

- focus entry;
- Escape handling;
- focus return;
- no duplicate IDs;
- usable keyboard order.

---

# 8. BACKEND / DATA CONSTRAINTS

Preserve:

- current authentication;
- showcase persona gateway;
- authorization / policies;
- MFA boundaries;
- PostgreSQL schema;
- existing workflow transitions;
- Cloudflare trusted-proxy behavior;
- HTTPS URL-generation behavior;
- existing demo data.

No migrations unless Kirch explicitly approves one.

No new backend architecture.

No fake realtime messaging.

No AI feature.

No new framework.

---

# 9. VALIDATION — FAST, PROPORTIONATE, REAL

Do not recreate the previous W08 process overhead.

Before asking for acceptance:

1. `npm.cmd run types:check`
2. `npm.cmd run build`
3. targeted Laravel tests for any backend/controller file you changed
4. full `php artisan test` only if backend behavior changed materially or once at final candidate if time allows
5. boot the app locally
6. manually inspect the required viewports
7. test at least Executive, Department Head and Employee personas
8. test Dashboard, My Work/Transactions, Correspondence, Travel Orders, Messages
9. confirm no obvious HTTP 5xx / fatal console errors
10. capture a small set of before/after screenshots for visual review

## GitHub Actions rule

Do not create or trigger a GitHub Action and then sit polling it.

If you intentionally cause a CI run:

- immediately tell Kirch the workflow name, branch/SHA and what evidence it is expected to produce;
- stop the working session;
- ask Kirch to reply `done` when the action is finished;
- inspect the result only after he returns.

Do not burn the working window babysitting CI.

---

# 10. GIT DISCIPLINE

Starting authority must remain exactly:

`d03e90c3b7a675670ecbe94414593558b7f78be8`

Work only on:

`KIRCH-TALIBON-V1-DIRECT-UX-RECOVERY-V2`

Do not force push.

Do not merge yourself into the accepted correction branch.

Do not deploy yourself unless Kirch explicitly tells you to.

Use meaningful commits grouped around real UX improvements.

Do not create empty commits, filler commits, process-only churn or another governance tree.

---

# 11. DEFINITION OF DONE

This candidate is ready for Kirch's visual acceptance when all of the following are true:

- the app visibly feels materially different from `d03e90c3...`, not like a minor spacing patch;
- left navigation is compact, clear and action-oriented;
- the header no longer contains an awkward dominant search field;
- a useful collapsible daily utility system is available across work pages;
- quick Messages access exists without pretending messaging is writable;
- the dashboard is materially easier to scan and substantially less vertically bloated;
- review/detail workflows are compact and have reliable return navigation;
- related work can be reached without repetitive list/detail bouncing;
- receiving/list workflows are denser and clearer;
- mobile remains usable;
- auth, roles, workflow rules and deployment behavior remain intact;
- typecheck/build are green;
- no obvious runtime regression exists.

The objective is not to prove that many files changed.

The objective is for Kirch to open the deployed candidate and immediately see that the operator experience was actually redesigned around how people work.

---

# 12. REQUIRED RETURN

Return:

- exact branch + final SHA;
- concise architectural summary of the UX changes;
- files changed grouped by Shell / Dashboard / Detail Workspaces / Lists;
- backend changes, if any, with justification;
- exact local validation executed and results;
- screenshots or screenshot paths used for visual verification;
- known remaining UX debt;
- explicit statement that you did not merge or deploy.

Do the work. Do not create another process experiment.
