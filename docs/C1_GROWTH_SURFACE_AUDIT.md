# C1 Growth Surface Audit

Repository: `Kirch-Nairu/Talibon-Sales-Prototype`
Exact source authority: `6982e7d93050ce0c940f81dbfba83ef61a6d2d1b`
Audit mode: read-only source verification
Implementation changes in this recovery: none

## Verification rule

Every `CURRENT BEHAVIOR` statement below was re-checked against the exact pinned source authority above. This audit does not use `main`, another branch, a future design, or a similarly named file as evidence.

The C1 implementation files are frozen for this recovery and are not modified:

- `resources/js/components/dashboard/AttentionQueue.tsx`
- `resources/js/components/dashboard/BoundedOperationalPanel.tsx`
- `resources/js/components/dashboard/SchedulePanel.tsx`

This document records growth behavior and deferred recommendations only.

---

## 1. Dashboard — secondary reference and history content

SCREEN:
Dashboard — Reference and history

FILE:
`resources/js/pages/Dashboard.tsx`
`resources/js/components/dashboard/RecentDocuments.tsx`
`resources/js/components/dashboard/RecentCorrespondence.tsx`
`resources/js/components/dashboard/MunicipalUpdates.tsx`

CURRENT BEHAVIOR:
The Dashboard places secondary material inside a native `<details>` section labelled "Reference and history." When expanded, it contains Recent Documents, Recent Correspondence, optional Executive History for the executive-oversight experience, municipal announcements, Recent Office Activity, and Notifications. Recent Documents renders `documents.slice(0, 4)`. Recent Correspondence combines its live overview rows when available, otherwise supplemental rows, sorts them by occurrence time, and renders `.slice(0, 4)`. The dashboard announcement mode of `MunicipalUpdates` renders `announcements.slice(0, 4)`. Recent Documents links to `/records`, and Recent Correspondence links to `/correspondence`. The reference/history container itself is not height-bounded; its collapsed `<details>` state is the current progressive-disclosure control.

GROWTH RISK:
The existing per-component preview caps protect some repeated lists, but adding more reference panels or allowing any uncapped child feed to grow can make the expanded history section increasingly tall. Preview caps can also hide older entries unless a full-register destination exists and is obvious.

RECOMMENDED PATTERN:
Keep this area as secondary progressive disclosure. Keep dashboard lists as intentionally bounded previews, with explicit full-register or full-history destinations for data that can materially grow. Do not turn the dashboard reference section into a full register.

DO NOT IMPLEMENT YET:
YES

---

## 2. Departments — Department Workspace

SCREEN:
Department Workspace

FILE:
`resources/js/pages/Departments/Workspace.tsx`

CURRENT BEHAVIOR:
The workspace has an office-workload header with drilldown links, an Office Overview metric grid, a Staff Workload table, a Status Distribution list, Recent Office Activity, and Oldest Unresolved work. `drilldowns`, `metricEntries`, `staffWorkload`, `statusOverview`, `recentActivity`, and `oldestUnresolved` are each rendered with `.map(...)` and have no component-side pagination or vertical height cap. The Staff Workload table has horizontal overflow handling only. Recent Office Activity displays the text `Latest {activityLimit} maximum`, but the component itself renders every item supplied in `recentActivity`. Oldest Unresolved renders every supplied work item as cards in normal page flow. There is no tab architecture for overview, requests, staff, work queue, documents, calendar, or settings in this pinned source.

GROWTH RISK:
Large staff rosters can lengthen the workload table; additional status groups can lengthen Status Distribution; larger supplied activity and unresolved-work arrays can substantially extend page height. A growing number of metrics or drilldown links can also increase header/overview density, although those controls already wrap or grid responsively.

RECOMMENDED PATTERN:
Use pagination or controlled loading for a large staff workload register. Treat Recent Office Activity and Oldest Unresolved as bounded operational previews with a full activity/work destination when their source grows. Keep status distribution and metrics as compact summaries; use progressive disclosure only if their category count becomes materially large.

DO NOT IMPLEMENT YET:
YES

---

## 3. Legislative Workspace

SCREEN:
Vice Mayor & Sangguniang Bayan Workspace

FILE:
`resources/js/pages/Legislation/Workspace.tsx`

CURRENT BEHAVIOR:
The page contains the Vice Mayor & Sangguniang Bayan workspace header, Sessions / Routed work / Overdue metrics, an authorized-user Schedule Session form, a Legislative Routed Work panel, and the session list. Legislative Routed Work already uses `max-h-[460px]` with `overflow-y-auto` and renders the supplied `legislativeWork` rows inside that bounded area. Sessions are rendered sequentially in normal page flow with `sessions.map(...)`. Each Session Card renders all supplied `agenda_items` inline with `session.agenda_items.map(...)`. Authorized users can reveal an inline Add agenda item form inside each card. There is no agenda / ordinances / resolutions / minutes tab architecture in this pinned source.

GROWTH RISK:
Routed work is already vertically bounded, but a long session history will continue extending the page. Agenda-heavy sessions also increase the height of each individual card, and repeated expanded cards plus inline add forms can create high interaction density.

RECOMMENDED PATTERN:
Preserve the existing bounded routed-work panel. For session history, use a temporal window plus pagination or controlled loading as the number of sessions grows. Use progressive disclosure inside older or agenda-heavy session cards so agenda detail does not force every session to remain fully expanded at once.

DO NOT IMPLEMENT YET:
YES

---

## 4. Correspondence

SCREEN:
Correspondence

FILE:
`resources/js/pages/Correspondence/Index.tsx`

CURRENT BEHAVIOR:
The page receives `records` as a paginator with `data`, current/last page metadata, totals, and previous/next URLs. Search and filter changes are submitted through `router.get('/correspondence', ...)`. The filter surface includes search, lifecycle, assigned-to-me, action-required, and advanced classification, office, and aging controls. The register renders only `records.data` for the current page and shows Previous / Next controls when `records.last_page > 1`.

GROWTH RISK:
The primary record list is already protected by paginator boundaries. Growth pressure is more likely to appear in very large filter-option sets, increasingly complex filtering needs, or inefficient server queries rather than from unbounded DOM growth on the current page.

RECOMMENDED PATTERN:
Retain server-driven filtering and pagination. If option sets become large, use searchable or controlled-loading selectors for offices/classifications. Avoid replacing the paginated register with an unbounded dashboard-style list.

DO NOT IMPLEMENT YET:
YES

---

## 5. Records

SCREEN:
Records register

FILE:
`resources/js/pages/Records/Index.tsx`

CURRENT BEHAVIOR:
The page receives a paginator and renders `records.data`. Search, record type, state, office, and optional date-range values are sent with `router.get('/records', ...)`. The date range is already placed under a `<details>` progressive-disclosure control. The register displays the current range/total and provides Previous / Next page navigation when more than one page exists.

GROWTH RISK:
The register itself is already page-bounded. Growth risk is primarily query/filter scalability, large office/state option sets, and navigation friction across a very deep result set rather than uncontrolled rendering of the complete record population.

RECOMMENDED PATTERN:
Keep server filtering and pagination as the primary growth pattern. Add stronger indexed search, page-size controls, or controlled loading only when actual record volume warrants it. Preserve progressive disclosure for less frequently used filters.

DO NOT IMPLEMENT YET:
YES

---

## 6. Travel Orders

SCREEN:
Approved Travel Orders

FILE:
`resources/js/pages/TravelOrders/Index.tsx`

CURRENT BEHAVIOR:
The page receives `travelOrders` as a paginator. Search, status, office, and travel-date filters are submitted with `router.get('/travel-orders', ...)`. Only `travelOrders.data` is rendered for the current page. Previous / Next navigation appears when `travelOrders.last_page > 1`. The page is a full approved-order registry, not a dashboard preview.

GROWTH RISK:
The record list is already protected from full-dataset DOM growth by pagination. Future growth pressure is more likely in search/filter performance and long option lists than in the number of rendered rows per page.

RECOMMENDED PATTERN:
Retain server filtering and pagination. Use searchable office selection or additional server-side query controls if the authorized registry becomes materially larger. Do not replace the register with an internally scrolling dashboard panel.

DO NOT IMPLEMENT YET:
YES

---

## 7. My Work / Transactions

SCREEN:
My Work

FILE:
`resources/js/pages/Transactions/Index.tsx`
`resources/js/components/work-queue/WorkItemList.tsx`
`resources/js/components/work-queue/StaffWorkloadTable.tsx`

CURRENT BEHAVIOR:
The page exposes work-scope controls plus search, status, priority, and current-office filters. Queue/filter changes issue `router.get('/transactions', ...)`. Normal work views render through `WorkItemList`, which maps `records.data` from a paginator and shows Previous / Next controls when `records.last_page > 1`. The special `staff_workload` view instead renders `StaffWorkloadTable`, which maps every supplied staff row and has no component-side pagination or vertical height cap.

GROWTH RISK:
Normal work queues are already protected by pagination. The staff-workload view can become long as the number of employees grows, and a growing number of work-scope choices can also add navigation density.

RECOMMENDED PATTERN:
Keep the existing paginated queue model for work items. If staff workload expands materially, use pagination or controlled loading, or expose a compact bounded workload summary that leads to a full staff-workload register.

DO NOT IMPLEMENT YET:
YES

---

## 8. Calendar

SCREEN:
Calendar — Events, deadlines & schedules

FILE:
`resources/js/pages/Calendar/Index.tsx`
`resources/js/components/meetings/MunicipalCalendarAgenda.tsx`

CURRENT BEHAVIOR:
The page receives a live `events` array and renders every supplied event sequentially in the Routed Work Schedule with `events.map(...)`; there is no client-side pagination, list-height bound, or date-range control in `Calendar/Index.tsx`. The pinned frontend alone does not establish how broad the upstream live-event time window is. Separately, `MunicipalCalendarAgenda` receives the repository planning-reference dataset and places all supplied items inside an already-bounded `max-h-[520px] overflow-y-auto` list.

GROWTH RISK:
If the upstream live event feed supplies a large period or many simultaneous deadlines, the Routed Work Schedule can make the page very long. The planning-reference list is already height-bounded, so its main risk is usability inside a dense scroll region rather than page-height growth.

RECOMMENDED PATTERN:
For the live routed-work schedule, use a clear temporal window with server filtering or date-based drill-in as volume grows. Preserve the bounded planning-reference list, with date/month navigation or drill-in if the reference dataset becomes significantly larger.

DO NOT IMPLEMENT YET:
YES

---

## 9. Meetings

SCREEN:
Meetings

FILE:
`resources/js/pages/Meetings/Index.tsx`
`resources/js/components/meetings/MeetingRegister.tsx`

CURRENT BEHAVIOR:
This pinned page imports the repository-local `municipalMeetings` dataset and filters it client-side by free-text query and status. `MeetingRegister` renders every filtered meeting in a two-column grid on large screens. Each card displays participants, related documents, and every agenda item inline. There is no pagination, controlled loading, or per-card collapse in this source.

GROWTH RISK:
The current source is a static/reference dataset, so present volume is inherently limited by that repository data. If the surface becomes a live historical register, both the number of meeting cards and the inline participant/document/agenda content can create substantial page length and visual density.

RECOMMENDED PATTERN:
If this becomes a live register, use server filtering plus pagination or controlled loading. Use progressive disclosure for agenda, participants, and related-document detail within each meeting card when those collections become large.

DO NOT IMPLEMENT YET:
YES

---

## 10. Messages

SCREEN:
Messages

FILE:
`resources/js/pages/Messages/Index.tsx`
`resources/js/components/messages/MessageHistory.tsx`

CURRENT BEHAVIOR:
This pinned page imports the repository-local `municipalMessages` dataset. Search and channel filtering are performed client-side with `useMemo`. `MessageHistory` renders every filtered message in a sequential history list, including the full body and reference. The surface is explicitly described as read-only coordination history and has no pagination, controlled loading, or virtualization.

GROWTH RISK:
The current source is a static/reference dataset. If it becomes a live coordination archive, rendering every matching full-body message will scale poorly in both page height and DOM size.

RECOMMENDED PATTERN:
For a live archive, use server search/filtering with pagination or controlled loading. Reserve bounded recent-message previews for dashboard/utility contexts, and keep the full Messages page as the drill-in destination.

DO NOT IMPLEMENT YET:
YES

---

## 11. Announcements

SCREEN:
Announcements

FILE:
`resources/js/pages/Announcements/Index.tsx`
`resources/js/components/announcements/AnnouncementRegister.tsx`

CURRENT BEHAVIOR:
This pinned page imports the repository-local `municipalAnnouncements` dataset and filters it client-side by search text and priority. `AnnouncementRegister` renders every filtered announcement as a card in a responsive grid. Each card includes title, date, issuing office, audience, summary, related document, and priority. There is no pagination or controlled loading in this source.

GROWTH RISK:
The current source is a static/reference dataset. If announcements become a long-lived live archive, the full filtered card set can make the page long and increasingly expensive to scan.

RECOMMENDED PATTERN:
For a live archive, use server filtering with pagination or controlled loading. Preserve a compact recent-announcement preview in dashboard/utility surfaces and use the full Announcements page as the archive destination.

DO NOT IMPLEMENT YET:
YES

---

## 12. Dashboard — activity and executive history feeds

SCREEN:
Dashboard — Recent office activity and executive history

FILE:
`resources/js/components/dashboard/OfficeActivityFeed.tsx`
`resources/js/components/dashboard/ExecutiveHistory.tsx`
`resources/js/components/dashboard/RecentWorkList.tsx`
`resources/js/pages/Dashboard.tsx`

CURRENT BEHAVIOR:
`OfficeActivityFeed` explicitly renders `activity.slice(0, 4)` and therefore acts as a four-item dashboard preview. `ExecutiveHistory` passes `overview.recentlyCompleted` directly to `RecentWorkList`, and `RecentWorkList` renders every supplied item without its own count cap or vertical height bound. Both appear inside the Dashboard's collapsed-by-default Reference and history `<details>` section; Executive History is only rendered for the executive-oversight experience.

GROWTH RISK:
Recent Office Activity is count-bounded but has no full-history link in the component, so records beyond the preview are not exposed there. Executive History can make the expanded Dashboard reference section increasingly tall if `overview.recentlyCompleted` grows.

RECOMMENDED PATTERN:
Keep dashboard history surfaces as bounded previews. Provide an explicit full-history destination for activity/history when the underlying dataset grows. For Executive History, cap or otherwise bound the dashboard preview while preserving a complete history in a dedicated register rather than inside Dashboard.

DO NOT IMPLEMENT YET:
YES

---

## 13. Dashboard — notifications

SCREEN:
Dashboard — Notifications

FILE:
`resources/js/components/dashboard/ActivityRail.tsx`
`resources/js/components/shell/NotificationContext.ts`

CURRENT BEHAVIOR:
`NotificationContext` exposes the current `LiveNotification[]` to consumers. `ActivityRail` copies that array, sorts action-marked (`urgent`) notifications ahead of non-urgent items, and renders only `.slice(0, 4)`. Each visible notification links to its own target URL. The component does not provide a link to a complete notification-history page.

GROWTH RISK:
The dashboard itself is protected by the four-item preview cap, but additional notifications are hidden from this surface once the array exceeds four. Without a complete notification destination, users cannot use this panel alone to inspect the remainder.

RECOMMENDED PATTERN:
Keep the dashboard notification list bounded. If notification volume becomes operationally significant, add a dedicated notification history/inbox with server filtering and pagination or controlled loading, while retaining only a short urgency-ordered preview on Dashboard.

DO NOT IMPLEMENT YET:
YES

---

## 14. Shell utilities — repeating calendar, announcement, and message previews

SCREEN:
Municipal utilities and Quick Messages

FILE:
`resources/js/components/shell/MunicipalUtilities.tsx`

CURRENT BEHAVIOR:
The utility surface uses repository-local municipal calendar, announcement, and message datasets. `calendarPreview()` returns at most three items. `announcementPreview()` sorts announcements and returns two. `messagePreview(3)` is used for the utility content, and that section renders `messages.slice(0, 2)`. Calendar, Announcements, and Messages each provide an Open link to their full page. Quick Messages uses `messagePreview(5)` for its recent-conversation list, uses a fixed-height panel with `overflow-y-auto`, and limits a selected channel thread to the latest seven messages with `.slice(-7)`. Its footer explicitly links to full Messages.

GROWTH RISK:
These utility surfaces are already structurally bounded and have full-page drill-in destinations. Their main future risk is stale or insufficient preview semantics if the data becomes live and high-volume, not uncontrolled page growth.

RECOMMENDED PATTERN:
Preserve these as bounded utility previews. Keep full Calendar, Announcements, and Messages as the authoritative drill-in surfaces. If the datasets become live, select preview records server-side or from a well-defined recent window rather than increasing the number rendered in the utility rail/panel.

DO NOT IMPLEMENT YET:
YES

---

## Growth-pattern summary

The exact pinned source supports different growth strategies by surface:

- **Dashboard and shell previews:** bounded preview/feed plus explicit full destination.
- **Correspondence, Records, Travel Orders, and normal My Work queues:** retain paginator-backed full-register behavior and request-driven filtering.
- **Department Workspace:** bound or paginate staff/activity/unresolved sections individually if their supplied datasets grow; do not invent a tab system.
- **Legislative Workspace:** preserve the already-bounded routed-work panel; use temporal pagination/controlled loading and progressive disclosure for accumulating sessions and agenda detail.
- **Calendar:** use temporal-window drill-in for the live routed-work event list; preserve the already-bounded planning-reference list.
- **Meetings, Messages, and Announcements:** the pinned source uses repository-local static/reference datasets; if converted to live historical registers, use server filtering plus pagination or controlled loading.
- **Long record/card detail:** use progressive disclosure where repeated inline detail materially increases scan cost.
- **Very large continuously loaded lists:** virtualization is a later option only after controlled loading/pagination and real volume demonstrate a need.

No recommendation in this audit is implemented by this recovery.
