# AGENTS.md — ONE TALIBON SALES BUILD RULES

## Authority

This repository is the independent One Talibon municipal workspace build.

Repository: `Kirch-Nairu/Talibon-Sales-Prototype`

Baseline branch: `main`

Integration branch: `KIRCH-TALIBON-SALES-V1`

Maintainer / technical authority: Kirch Ivan Balite.

Writer branches are isolated work areas. A writer must work only inside the files and domain assigned by the maintainer. Do not merge another writer, rebase onto another writer, move the integration branch, deploy, or modify `Kirch-Nairu/Talibon-Intra-Office-Portal`.

## Product direction

Build One Talibon as a dense internal municipal digital workspace. The application should read like a system the Municipality of Talibon already uses for day-to-day office coordination.

Visible content should answer practical municipal questions:

- what is this record or work item;
- which office owns it;
- who is responsible;
- what is its status;
- when is it due or scheduled;
- what action is required;
- which document, plan, program, project, office or meeting it relates to.

Do not put sales copy inside the application. Do not write large marketing paragraphs. Do not use generic filler or lorem ipsum.

## No AI product language

Do not add AI features, AI assistants, AI branding, AI wording, recommendation language, or generated-looking product copy.

Do not introduce visible language such as:

- AI powered;
- smart recommendations;
- revolutionary;
- transformative;
- next generation;
- future ready;
- premium;
- upgrade;
- pitch;
- showcase;
- demo;
- prototype;
- best in class;
- unlock;
- empower;
- seamless;
- effortless;
- innovative solution.

## Municipal visual language

Retain the Municipal Operations Console identity:

- municipal navy;
- neutral backgrounds;
- dense but readable information;
- clear office context;
- tables, records and lists before decorative cards;
- restrained status indicators and progress treatment;
- consistent typography;
- restrained shadows;
- complete light and dark compatibility.

Do not introduce glassmorphism, neon, decorative gradients, floating shapes, sparkles, startup-style landing layouts, giant empty hero areas, fake charts, fake efficiency metrics, random animation, or oversized whitespace.

## Visible security presentation

Do not advertise Audit & Security as a product surface in this build. Do not add security posture navigation, MFA posture cards, authentication-event summaries, or security walkthrough blocks.

Do not weaken or remove underlying authentication, authorization, sessions, CSRF, policies, audit logging, private file controls, or other security behavior.

## Existing backend authority

Reuse working backend behavior. Do not replace real implementation with frontend mocks when a domain already exists.

Existing foundations include authentication, users, departments, employees, correspondence, records, memoranda, notifications, calendar, work queues, Travel Orders, Legislative foundations, reports and administration.

For newly introduced read-only presentation domains, prefer typed fixtures under `resources/js/data/municipal/` rather than unnecessary backend work.

Do not introduce microservices, a second authorization system, another workflow engine, realtime messaging infrastructure, video-conferencing infrastructure, or another database architecture.

## Data consistency

Do not invent summary values independently from the records shown on screen. Derive totals, statuses, deadlines and progress summaries from the displayed collection wherever practical.

Synthetic municipal records must remain clearly fictional and must not be represented as certified official records.

Never commit secrets, private credentials, personal IDs, private employee details, private correspondence, signatures, phone numbers or other sensitive material.

## Interaction rule

Primary actions must work.

If an action is not implemented, remove it or use an honest read-only action such as `View details`, `Open record`, `View agenda` or `Open document`.

Do not display fake `Send`, `Approve`, `Submit`, `Save` or `Join Meeting` controls.

## Parallel writer ownership

Stay inside the maintainer-assigned ownership block.

- W01 owns `AGENTS.md`, `resources/js/navigation/`, `resources/js/components/shell/`, `resources/js/layouts/`, and shell wording directly required for navigation.
- Other writers own their assigned municipal domains.
- Only W01 edits shared navigation.
- Only W12 edits unrelated shared CSS.
- Do not edit another writer's fixtures.
- Do not opportunistically repair another writer's page.

If route wiring is not yet available for another writer's surface, prepare navigation metadata without creating a dead primary link. Report the required route to the integrator.

## Navigation contract

The intended top-level information architecture is:

### Home
- Home

### Work
- My Work
- Correspondence
- Records
- Memoranda
- Announcements
- Calendar
- Meetings
- Messages

### Municipal Organization
- Executive Departments
- Employee Directory
- Legislative
- Local Special Bodies

### Planning
- Development Plans
- PPAs
- Project Monitoring

### Administration
- Users
- Departments
- System Administration

### Municipal Systems
- Municipal Systems

Audit & Security is deliberately absent from visible navigation.

## Commit discipline

Commit aggressively, but every commit must be a real atomic improvement.

Use descriptive messages such as:

- `feat(nav): add municipal organization group`
- `feat(shell): align mobile navigation sections`
- `fix(shell): preserve focus after drawer close`

Do not create empty, temporary, whitespace-only, revert-for-count, placeholder or meaningless commits.

Never force push.

Before publishing a batch, fetch the remote branch and stop if it moved unexpectedly.

## Validation

At regular checkpoints run the relevant frontend type/lint checks. Run the production frontend build before final handoff. If an existing PHP-backed behavior is changed, run focused PHP tests.

Only report checks that actually ran. Do not convert source inspection into a passing runtime claim.

## Final boundary

Writers build their assigned candidate and stop.

Writers do not integrate `KIRCH-TALIBON-SALES-V1`, do not update `main`, do not deploy, and do not start another writer's scope.