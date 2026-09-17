# W08 QA Execution Checkpoint

Checkpoint version: `3`

Logical QA run identity:

`2026-09-17_105721_PHT`

Forge role:

`KIRION FORGE: QUALITY ASSURANCE`

Execution branch:

`KIRCH-TALIBON-V1-W08-FORGE-QUALITY-ASSURANCE`

Expected current execution SHA:

`5bbfd48611b3f65e279048faa0fa1d246ba89789`

Integrated product-source anchor:

`db286142cdc5d9e793680fac933a8462deb8390d`

Governance/control-plane branch:

`KIRCH-TALIBON-V1-W08-QA-GOVERNANCE`

Current W08 phase:

`EXTERNAL_VALIDATION_RUNNING`

External wait state:

`WAITING — RUN #4 IS IN PROGRESS`

## External workflow history

### Run #1

Workflow: `Forge W08 Quality Assurance`

Run ID: `35179443998`

Head SHA: `8c606e1a45a666884a52dafc9da1615890340418`

Conclusion: `cancelled`

### Run #2

Workflow: `Forge W08 Quality Assurance`

Run ID: `35179625056`

Head SHA: `ecdb11dea2f89e5503ffa37aff0b714f9f035ff5`

Conclusion: `failure`

Artifact ID: `10480091707`

Run #2 was consumed and classified before replacement. Its credential-login locator, mobile Appearance locator, server-port reuse and generated-secret masking issues were classified as QA harness/environment defects. No product defect was established by run #2.

### Run #3

Workflow: `Forge W08 Quality Assurance`

Run ID: `35185610860`

Head SHA: `b1aab464ee2da02bf8994a2165352344c04cd895`

Status: `completed`

Conclusion: `failure`

Artifact:

`ONE-TALIBON-W08-QA-2026-09-17_105721_PHT`

Artifact ID:

`10482451981`

Artifact digest:

`sha256:ef9179c6f596127519e99d928dbe0ffe6fbb6776b6e424004c3e2753a0741b2e`

Run #3 step truth:

- authority checkout and ancestry verification: success;
- dependency/bootstrap: success;
- source validation: success;
- bounded compatibility overlay generation: success;
- H1 browser step: GitHub step conclusion `success` because `continue-on-error` normalized the step, while the H1 machine report itself recorded an unsuccessful harness execution;
- W08 browser step: GitHub step conclusion `success` because `continue-on-error` normalized the step, while the W08 machine report itself recorded an unsuccessful harness execution;
- evidence packaging: success;
- artifact upload: success;
- final W08 workflow gate: failure, correctly preserving the underlying H1/W08 failure outcomes.

Run #3 retained machine evidence:

- H1 exact head verified: `b1aab464ee2da02bf8994a2165352344c04cd895`;
- H1 isolated database verified: `talibon_h1_mutations`;
- H1 scenarios completed: `0`;
- H1 failure: timeout selecting the `Department Head` Showcase card;
- W08 exact head verified and product-anchor ancestry verified;
- W08 completed one W01 shell scenario: `PASS` with `12` checks, `0` page errors, `0` server 5xx and `0` fatal console errors;
- W08 screenshot count: `1` (`w01-showcase-shell-390-light.png`);
- W08 later failed during Engineering persona session bootstrap on the same `Department Head` selector;
- the earlier W01 PASS remains a valid partial observation and is not erased by the later fatal harness failure.

## Run #3 failure classification

### HARNESS DEFECT — Showcase PersonaCard locator contract

The run #3 repair used exact accessible-name matching for the `Department Head` button. Product source renders each `PersonaCard` as a button containing label, description and context text, so the button accessible name is not exactly the short label. In addition, after selecting Department Head the same dialog changes its accessible heading from `Choose your workspace` to `Choose office context`, making a locator permanently constrained to the old dialog name unsuitable for the second step.

This is a QA harness defect, not a product defect. The browser-visible gateway itself was already proven to work in W01, and the product implementation is internally coherent with its card structure.

Bounded repair commit:

`5bbfd48611b3f65e279048faa0fa1d246ba89789`

Commit message:

`KIRCH-FORGE-QA-W08-REPAIR-SHOWCASE-PERSONA-LOCATORS`

Direct parent:

`b1aab464ee2da02bf8994a2165352344c04cd895`

Repair scope:

- QA harness overlay only;
- stable role=`dialog` scope independent of the changing heading;
- PersonaCard selection by visible contained card text rather than exact composite accessible name;
- explicit wait for `Choose office context` before selecting Engineering/Budget office;
- no product-source change.

## Current execution authority verification

Remote execution branch head independently re-read after repair:

`5bbfd48611b3f65e279048faa0fa1d246ba89789`

Git comparison against immutable product anchor reports:

- status: `ahead`;
- behind: `0`;
- merge base: exactly `db286142cdc5d9e793680fac933a8462deb8390d`.

Therefore product-anchor ancestry remains intact.

### Run #4

Workflow: `Forge W08 Quality Assurance`

Run ID: `35189429744`

Expected/head SHA:

`5bbfd48611b3f65e279048faa0fa1d246ba89789`

Observed status at checkpoint:

`in_progress`

Conclusion:

`pending`

## Outstanding evidence

Still outstanding before a valid W08 PASS/PASS WITH RECORDED LIMITATIONS:

- run #4 conclusion and retained artifact reconciliation;
- successful H1 browser mutation evidence;
- successful complete final W08 browser matrix;
- all required personas;
- all required viewports including >=1536;
- light/dark coverage;
- W03 browser mutation/return continuity;
- W04 responsive Planning;
- W05 utility breakpoint live regression;
- W06 read-only Messages behavior;
- W07 role/admin/HR/error checks;
- keyboard/focus/reflow/accessibility mechanics;
- complete screenshot set and screenshot manifest;
- complete machine report;
- required final evidence ZIP contents;
- complete QA dossier;
- exact final 23-part QA Return.

## Next resume action

On `resume QA`:

1. read this checkpoint first;
2. reverify execution branch SHA `5bbfd48611b3f65e279048faa0fa1d246ba89789` and product-anchor ancestry;
3. fetch run `35189429744` and verify its head equals the exact execution SHA;
4. inspect conclusion, jobs and retained artifact without assuming success;
5. reconcile machine reports and screenshots;
6. independently classify any new failure under HARNESS / QA ENVIRONMENT / PRODUCT law;
7. if queued/in-progress and no result-independent work remains, return `W08 QA: WAITING FOR EXTERNAL VALIDATION` rather than polling.

A human `resume QA` message is a wake-up signal only and is not evidence.
