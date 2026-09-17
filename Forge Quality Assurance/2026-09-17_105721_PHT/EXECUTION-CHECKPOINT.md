# W08 QA Execution Checkpoint

Checkpoint version: `2`

Logical QA run identity:

`2026-09-17_105721_PHT`

Forge role:

`KIRION FORGE: QUALITY ASSURANCE`

Execution branch:

`KIRCH-TALIBON-V1-W08-FORGE-QUALITY-ASSURANCE`

Expected current execution SHA:

`b1aab464ee2da02bf8994a2165352344c04cd895`

Integrated product-source anchor:

`db286142cdc5d9e793680fac933a8462deb8390d`

Governance/control-plane branch:

`KIRCH-TALIBON-V1-W08-QA-GOVERNANCE`

Current W08 phase:

`EXTERNAL_VALIDATION_RUNNING`

External wait state:

`WAITING — RUN #3 IS IN PROGRESS`

## External workflow history

### Run #1

Workflow: `Forge W08 Quality Assurance`

Run ID: `35179443998`

Expected/head SHA: `8c606e1a45a666884a52dafc9da1615890340418`

Conclusion: `cancelled`

### Run #2

Workflow: `Forge W08 Quality Assurance`

Run ID: `35179625056`

Expected/head SHA: `ecdb11dea2f89e5503ffa37aff0b714f9f035ff5`

Status: `completed`

Conclusion: `failure`

Artifact:

`ONE-TALIBON-W08-QA-2026-09-17_105721_PHT`

Artifact ID:

`10480091707`

Artifact digest:

`sha256:f979dd92844b0719c205ed697cb3e78d9dc94c29ec8cc357a24cab637fc6e86f`

### Run #3

Workflow: `Forge W08 Quality Assurance`

Run ID: `35185610860`

Expected/head SHA: `b1aab464ee2da02bf8994a2165352344c04cd895`

Observed status at checkpoint: `in_progress`

Conclusion: `pending`

## Run #2 reconciliation and defect classification

Run #2 was fully consumed before replacement. GitHub run metadata, job steps, retained artifact `10480091707`, machine-readable H1/W08 reports, server logs, current product source and current harness/workflow source were inspected.

### HARNESS DEFECT — obsolete credential-login locator

Both H1 and W08 attempted `getByLabel('Email')` and `getByLabel('Password')` against the current `/login` surface. The accepted product surface is the Showcase municipal workspace gateway (`Enter Workspace` → persona/office selection) and intentionally contains no credential form. The timeout therefore does not establish a product defect.

Bounded repair: the W08 QA workflow now generates runtime harness copies that enter the accepted browser-visible Showcase gateway for the synthetic QA personas. Product source is not modified.

### HARNESS DEFECT — W01 mobile Appearance selector ambiguity

The partial W01 run observed the workspace dialog, Sign out and Switch Workspace successfully, then failed only the Appearance locator. The assertion used an unscoped `getByText('Appearance').last()` lookup while `SidebarAppearanceMenu` contains both the visible summary trigger and hidden closed-details content. Product source explicitly renders a mobile footer Appearance summary trigger with `title="Appearance"`.

Bounded repair: the runtime W08 harness scopes the assertion to the visible Appearance summary trigger inside the open mobile navigation dialog. This reclassifies the run #2 observation as a locator/harness defect, not a confirmed product defect. The replacement run must still exercise the behavior live.

### QA ENVIRONMENT DEFECT — browser server port reuse

Run #2 `w08-cross-product-server.log` records `Failed to listen on 127.0.0.1:8000 (reason: Address already in use)`. The H1 server remained bound after the failed H1 step, so the W08 step executed against the lingering server instead of its own clean server process.

Bounded repair: H1 and W08 now use isolated ports `8001` and `8002` respectively, with matching step-local `APP_URL` and `QA_BASE_URL` values.

### QA WORKFLOW / ENVIRONMENT DEFECT — generated secret log masking

The generated isolated QA demo password was observable unmasked in run #2 job environment output. The value is intentionally not reproduced in this dossier. It was ephemeral to the isolated CI run and is not a production credential, but exposing it violates the W08 evidence-handling contract.

Bounded repair: the workflow registers the generated value with GitHub Actions `add-mask` before exporting it to later steps.

## Repair authority

Execution repair commit:

`b1aab464ee2da02bf8994a2165352344c04cd895`

Commit message:

`KIRCH-FORGE-QA-W08-REPAIR-HARNESS-BOOTSTRAP`

Direct parent:

`ecdb11dea2f89e5503ffa37aff0b714f9f035ff5`

The immutable W03–W07 product-source anchor remains an ancestor. The repair changes QA workflow/harness machinery only.

## Last completed evidence

- exact QA branch ancestry: verified through the current execution lineage;
- run #2 source validation: PASS (`types:check`, production build, Laravel suite 370 passed / 5,088 assertions);
- run #2 browser execution: attempted but invalidated/incomplete by the classified harness/environment defects above;
- run #2 retained artifact: consumed and preserved as failed-run evidence;
- run #2 screenshots: `0`;
- no product defect is confirmed from run #2;
- replacement external validation has been launched as run #3 against exact execution SHA `b1aab464ee2da02bf8994a2165352344c04cd895`.

## Outstanding evidence

Still outstanding before a valid W08 PASS/PASS WITH RECORDED LIMITATIONS:

- run #3 conclusion and retained artifact reconciliation;
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
- screenshots and complete screenshot manifest;
- complete machine report;
- required final evidence ZIP contents;
- complete QA dossier;
- exact final 23-part QA Return.

## Next resume action

On `resume QA`:

1. read this checkpoint first;
2. reverify execution branch SHA `b1aab464ee2da02bf8994a2165352344c04cd895` and product-anchor ancestry;
3. fetch run `35185610860` and verify its head equals the exact execution SHA;
4. inspect conclusion, jobs and artifact without assuming success;
5. reconcile machine reports and screenshots;
6. classify any new failure under HARNESS / QA ENVIRONMENT / PRODUCT law;
7. if queued/in-progress and no result-independent work remains, return `W08 QA: WAITING FOR EXTERNAL VALIDATION` rather than polling.

A human `resume QA` message is a wake-up signal only and is not evidence.
