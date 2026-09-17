# W08 QA Notes

Logical QA session: `2026-09-17_105721_PHT`

Role: `KIRION FORGE: QUALITY ASSURANCE`

Target: `Kirch-Nairu/Talibon-Sales-Prototype`

Immutable product input: `db286142cdc5d9e793680fac933a8462deb8390d`

Final QA execution head observed: `5bbfd48611b3f65e279048faa0fa1d246ba89789`

## Session chronology

W08 remained one continuous logical QA session despite conversational timeouts and external CI partitions.

1. Run #1 — `35179443998` at `8c606e1a45a666884a52dafc9da1615890340418`: completed / cancelled.
2. Run #2 — `35179625056` at `ecdb11dea2f89e5503ffa37aff0b714f9f035ff5`: completed / failure; failed-run artifact `10480091707` consumed before replacement.
3. Run #3 — `35185610860` at `b1aab464ee2da02bf8994a2165352344c04cd895`: completed / failure after bounded QA bootstrap repairs; retained as chronology.
4. Run #4 — `35189429744` at `5bbfd48611b3f65e279048faa0fa1d246ba89789`: completed / failure; source validation passed, H1 and W08 browser execution both ran, evidence packaged and uploaded as artifact `10483479162`.

No product source was repaired or modified by W08 QA. The execution branch remains descended from product anchor `db286142...`; QA-only additions are workflow, harness and dossier material.

## Run #2 classifications and QA-authorized repair

Run #2 exposed four QA-side defects: obsolete Email/Password login locators against the accepted Showcase gateway; an ambiguous W01 mobile Appearance locator; H1/W08 server port reuse; and an unmasked ephemeral synthetic QA password in job output. These were classified as HARNESS / QA ENVIRONMENT defects and repaired without changing product source.

## Run #3 and run #4 harness evolution

The Showcase compatibility overlay was refined to select persona cards using stable visible text/dialog scope rather than strict accessible-name assumptions that included description/context text. H1 and W08 use separate isolated ports, generated secrets are masked, and runtime harness copies are preserved in retained artifacts.

## Run #4 browser evidence

Source validation completed before browser evidence: TypeScript check passed, production build passed, and Laravel suite passed `370 tests / 5,088 assertions`.

H1 executed 20 browser mutation scenarios: 16 passed and 4 failed. All transaction, memorandum, travel-order, validation and authorization scenarios passed. The four correspondence failures originate from one stale exact-case harness locator looking for `Register Correspondence`; the actual browser button is `Register correspondence`. Classification: HARNESS DEFECT. The three later correspondence failures are dependency fallout from that first harness miss.

W08 executed 21 cross-product scenarios: 8 passed and 13 failed, recording 168 checks, 7 W08 screenshots, 0 page errors, 0 HTTP 5xx responses and 0 fatal console runtime errors.

Confirmed passing live evidence includes W01 Showcase shell continuity, W03 Correspondence return continuity, W03 Travel Order return continuity, the full mandatory W05 utility breakpoint round-trip, normalized W07 403 presentation, and representative accessibility mechanics on Municipal Executive and Legislative surfaces.

## W08 failure classification

### HARNESS DEFECTS

- W02 hierarchy order probe used `document.body.innerText.indexOf(...)`; the visible text checks passed first, while the order probe returned `[-1,-1,-1,-1]`. Accepted product source exposes ordered section IDs `dashboard-act-now`, `dashboard-next`, `dashboard-current`, and `dashboard-reference`.
- W03 transaction continuity searched an unassigned Budget-office fixture through `view=all`. Product work-queue semantics intentionally personal-scope `view=all`; the fixture belongs in `office_queue` / `unassigned` for a department-head check.
- Mobile Appearance automation attempted to find the sidebar Appearance control without opening mobile navigation. W01 already proved the visible mobile Appearance trigger inside the open navigation dialog.
- The representative Human Resources `390x844 dark` case was labeled dark without actually switching mobile appearance; its screenshot is therefore not valid dark-theme evidence.
- Heading contrast probes returned `null` on several surfaces because the harness parser only recognizes comma-form `rgb()/rgba()` values. `null` is a measurement failure, not evidence of low contrast.
- H1 correspondence registration expected exact text `Register Correspondence`; the rendered action is `Register correspondence`. Later correspondence failures are dependent on this locator miss.

### PRODUCT DEFECT — BLOCKING W08

W06 read-only Messages produced a browser-level semantics failure on the visible Quick messages `<select>`:

`unlabeled: ["SELECT:"]`

The immutable product source `resources/js/pages/Messages/Index.tsx` renders that visible `<select>` with `value`, `onChange` and classes, but without an associated `<label>`, `aria-label`, or `aria-labelledby`.

Exact reproduction evidence:

- persona: Department Head — Engineering;
- route: `/messages`;
- observed browser semantics: one H1, one main landmark, no duplicate IDs, no broken `aria-labelledby`, but one visible unnamed SELECT control;
- product source anchor: `db286142cdc5d9e793680fac933a8462deb8390d`;
- run: `35189429744`;
- run head: `5bbfd48611b3f65e279048faa0fa1d246ba89789`;
- machine report: `w08-cross-product-readiness-report.json`.

This is a genuine product-source accessibility defect. QA authority does not permit repairing it. W08 therefore terminates as `W08 QA: REWORK REQUIRED`.

## Evidence limitation

Because a product defect was confirmed, QA did not manufacture a pass by weakening assertions and did not launch another heavyweight replacement run. The full green final matrix, complete dark-theme coverage, remaining W02/W04/W06/W07 checks, repaired H1 correspondence chain and final passing screenshot set remain unobserved until product rework is explicitly authorized and W08 is reopened against the new product anchor.

No UAT, deployment, production runtime acceptance or final program Acceptance is claimed.
