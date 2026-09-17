# W08 Artifact Manifest

Status: `PARTIAL FAILURE ARTIFACT RECORDED — FINAL PACKAGE INCOMPLETE`

Required final ZIP:

`ONE-TALIBON-W08-QA-2026-09-17_105721_PHT.zip`

## Existing external artifact

Workflow:

`Forge W08 Quality Assurance`

Run:

`#2`

Run ID:

`35179625056`

Exact run head:

`ecdb11dea2f89e5503ffa37aff0b714f9f035ff5`

Workflow conclusion:

`failure`

Artifact name:

`ONE-TALIBON-W08-QA-2026-09-17_105721_PHT`

Artifact ID:

`10480091707`

Artifact digest reported by GitHub:

`sha256:f979dd92844b0719c205ed697cb3e78d9dc94c29ec8cc357a24cab637fc6e86f`

GitHub expiry metadata:

`2026-10-01T04:02:04Z`

## Recovered artifact contents

Outer uploaded artifact contains:

- `ONE-TALIBON-W08-QA-2026-09-17_105721_PHT.zip`;
- `ONE-TALIBON-W08-QA-2026-09-17_105721_PHT.zip.sha256`;
- `h1-mutation-readiness-report.json`;
- `w08-cross-product-readiness-report.json`.

Recovered inner ZIP contains:

- `h1-mutation-readiness-report.json`;
- `w08-cross-product-readiness-report.json`;
- `w08-h1-server.log`;
- `w08-cross-product-server.log`.

Observed screenshot count in the recovered machine report/package:

`0`

Therefore this existing artifact is FAILURE/RECONCILIATION EVIDENCE ONLY. It does not satisfy the final W08 PASS ZIP contract.

## Still required for final W08 ZIP

The eventual valid final ZIP must contain:

- all W08 screenshots;
- `QA-NOTES.md`;
- completed `FORGE-SESSION-JOURNEY.md`;
- completed `W08-RESULT.md`;
- completed `SCREENSHOT-MANIFEST.md`;
- final machine-readable W08 report JSON;
- relevant non-secret runtime diagnostics;
- artifact manifest;
- final checksums where practical.

The final QA role must also record:

- exact final QA execution SHA;
- browser workflow name/run ID/conclusion;
- normal Forge UIUX Validation state for the relevant product/QA state;
- screenshot count;
- final artifact identity and usable navigation/download reference;
- final W08 disposition.

A later successful artifact does not erase run #1, run #2, or this partial artifact from the session chronology.
