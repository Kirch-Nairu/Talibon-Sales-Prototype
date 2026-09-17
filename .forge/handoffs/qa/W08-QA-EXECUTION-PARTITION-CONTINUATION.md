# KIRION FORGE — ONE TALIBON V1

## W08 QA EXECUTION-PARTITION CONTINUATION / RECOVERY HANDOFF

Role call:

`KIRION FORGE: QUALITY ASSURANCE`

This handoff is ADDITIVE to:

`.forge/handoffs/qa/W08-FORGE-QUALITY-ASSURANCE.md`

The original W08 scope, failure law, evidence requirements, screenshot ZIP requirement, allowed final dispositions, and exact 23-part final QA Return remain authoritative and unchanged.

This continuation changes only the execution/orchestration model so the logical W08 QA session can survive asynchronous external work and ephemeral ChatGPT execution.

---

# 1. AUTHORITIES AND CONTINUITY

Forge authority:

`Kirch-Nairu/KIRION-FORGE@44eb57e5b45b343be0033bf22a7a5e74d543c01a`

Integrated W03-W07 product-source anchor:

`db286142cdc5d9e793680fac933a8462deb8390d`

W08 QA execution branch:

`KIRCH-TALIBON-V1-W08-FORGE-QUALITY-ASSURANCE`

Recovered execution HEAD before this continuation:

`ecdb11dea2f89e5503ffa37aff0b714f9f035ff5`

Durable W08 run identity / dossier:

`Forge Quality Assurance/2026-09-17_105721_PHT/`

Governance/control-plane branch:

`KIRCH-TALIBON-V1-W08-QA-GOVERNANCE`

The governance branch exists so checkpoint, orchestration, ledger and continuation records can be persisted without causing the heavyweight W08 execution workflow to start merely because governance text changed.

The governance branch is NOT a new QA run, NOT a product branch, NOT an integration branch, and NOT a replacement for the QA execution branch. The logical W08 QA identity remains `2026-09-17_105721_PHT` and the QA execution lineage remains on `KIRCH-TALIBON-V1-W08-FORGE-QUALITY-ASSURANCE`.

---

# 2. ONE SESSION, MULTIPLE EXECUTIONS

`ONE CONTINUOUS W08 QA SESSION` means one continuous logical identity, authority lineage, run directory, evidence chronology, defect history and final 23-part return.

It does NOT require one uninterrupted ChatGPT response or one agent process to remain alive while GitHub Actions runs.

Multiple bounded QA executions are permitted when every execution reconstructs state from durable Git/CI truth.

Do not create a new timestamped QA run directory merely because control returned to the user or a message-delivery timeout occurred.

Cancelled, failed and superseded external runs remain part of the same dossier and must not be erased after a later PASS.

---

# 3. CURRENT RECOVERED STATE

At Maintainer recovery:

- QA execution branch HEAD: `ecdb11dea2f89e5503ffa37aff0b714f9f035ff5`;
- product anchor remains ancestor: `db286142cdc5d9e793680fac933a8462deb8390d`;
- W08 kickoff commit: `36a50536f6524bdf9e4a48a5fa55a9c9af907a02`;
- workflow commit: `8c606e1a45a666884a52dafc9da1615890340418`;
- current harness commit: `ecdb11dea2f89e5503ffa37aff0b714f9f035ff5`;
- no product-source file changed above the product anchor during W08 setup;
- original W08 handoff blob is unchanged since kickoff.

External history already exists and MUST be consumed before replacement:

1. Forge W08 Quality Assurance run `#1`, ID `35179443998`, head `8c606e1a45a666884a52dafc9da1615890340418`: `CANCELLED`.
2. Forge W08 Quality Assurance run `#2`, ID `35179625056`, head `ecdb11dea2f89e5503ffa37aff0b714f9f035ff5`: `FAILURE`.
3. Run #2 uploaded artifact `ONE-TALIBON-W08-QA-2026-09-17_105721_PHT`, artifact ID `10480091707`.

Run #2 completed source validation before the final gate failed. Machine-readable H1 and W08 reports were packaged. Those reports are evidence for reconciliation, not a final QA disposition.

The reports record incomplete browser execution and login-form locator timeouts; the W08 report also records a partial W01 shell observation before the later fatal harness failure. QA must inspect and classify these facts under the existing HARNESS / QA ENVIRONMENT / PRODUCT failure law before any replacement run.

Maintainer does not pre-classify the login failure as a final defect category.

Current phase:

`FAILED_EXTERNAL_RECONCILIATION`

There is no currently active external run at this recovered checkpoint.

---

# 4. EXECUTION-WEIGHT MAP

## LIGHT

Work expected to complete in a short QA execution without external waiting:

- reverify Forge, product anchor, QA execution branch and governance checkpoint authority;
- read the original W08 handoff plus this continuation;
- inspect one completed workflow/run/job/artifact metadata set;
- inspect existing H1/W08 machine-readable reports;
- classify a known failure from already-produced evidence;
- inspect a bounded harness/workflow/bootstrap source area;
- update one checkpoint, run-record, result section or evidence note;
- verify exact workflow head SHA, run status, conclusion and artifact identity;
- compare the QA branch against the product anchor to confirm product-source preservation;
- prepare a bounded repair plan without executing the heavy matrix.

## MEDIUM

Bounded QA-authorized mutation that should end immediately after durable verification and, if applicable, external-run launch:

- repair a confirmed harness defect;
- repair a QA workflow defect;
- repair isolated QA fixture/bootstrap/environment logic;
- update deterministic testing-only support;
- commit and push one bounded harness/workflow/environment fix;
- verify the new exact QA execution HEAD;
- observe the external run identity created for that HEAD;
- persist a checkpoint describing the run and expected artifacts;
- update the dossier/control-plane record after a completed-run reconciliation.

A MEDIUM execution must not expand into unrelated cleanup or remain alive only to babysit the resulting CI.

## HEAVY / EXTERNAL

Work whose wall-clock duration is dominated by GitHub Actions, package installation, browser runtime or matrix execution:

- full Forge W08 Quality Assurance GitHub Actions execution;
- dependency installation and full source validation inside that workflow;
- H1 mutation browser execution;
- full W03 browser mutation/return journeys;
- seven-persona browser coverage;
- required viewport matrix including 1440, 1280, 768, 430, 390x844, 360 and >=1536;
- light/dark matrix;
- W04 responsive Planning checks;
- W05 utility breakpoint live regression;
- W06/W07 browser checks;
- keyboard/focus/reflow/accessibility mechanics;
- screenshot generation;
- full final matrix rerun after any repair;
- ZIP packaging and retained artifact upload.

HEAVY work belongs to external execution. QA may launch it, verify its identity, checkpoint, and return control instead of polling until completion.

---

# 5. QA STATE MACHINE

Valid resumable execution states:

`QA_ACTIVE_LIGHT`

`QA_ACTIVE_MEDIUM`

`WAITING_EXTERNAL`

`RECONCILING_EXTERNAL`

`FAILED_EXTERNAL_RECONCILIATION`

`PRODUCT_REWORK_REQUIRED`

`BLOCKED`

`FINALIZING_DOSSIER`

`FINAL_READY_FOR_RETURN`

Transitions:

`QA_ACTIVE_LIGHT -> QA_ACTIVE_MEDIUM` when a bounded QA-authorized repair is required.

`QA_ACTIVE_MEDIUM -> WAITING_EXTERNAL` after a repaired exact QA SHA launches heavy validation and the run identity is verified.

`WAITING_EXTERNAL -> RECONCILING_EXTERNAL` only after a later QA execution independently re-reads the recorded external run.

`RECONCILING_EXTERNAL -> QA_ACTIVE_MEDIUM` for a confirmed harness/environment repair.

`RECONCILING_EXTERNAL -> PRODUCT_REWORK_REQUIRED` for a confirmed product defect.

`RECONCILING_EXTERNAL -> FINALIZING_DOSSIER` only after the required final matrix and evidence package have actually succeeded at the demonstrated evidence level.

`FINALIZING_DOSSIER -> FINAL_READY_FOR_RETURN` only when the required ZIP, manifests, notes, reports and final dossier are complete.

---

# 6. CHECKPOINT LAW

## After authoritative mutation

After QA commits a harness repair, workflow repair, isolated QA environment repair, or another authoritative execution mutation:

1. re-read the remote QA execution branch;
2. verify the exact new HEAD and lineage;
3. determine whether the mutation started an external W08 workflow;
4. if a run exists, verify workflow name, run ID and head SHA;
5. persist the checkpoint on the governance/control-plane record;
6. if the run is queued or in progress and no result-dependent local work remains, return control.

Do not stack unrelated work after a verified mutation merely because conversational budget remains.

## Before long external waiting

If an external workflow is `queued` or `in_progress`, and no productive work is possible until it finishes, QA MUST NOT repeatedly poll it to keep the role alive.

Persist at minimum:

- Forge role;
- logical QA run identity;
- execution branch;
- exact expected QA SHA;
- product anchor;
- current W08 phase;
- workflow name;
- workflow run ID;
- expected workflow head SHA;
- expected artifacts;
- outstanding evidence;
- previous observed results;
- next reconciliation action.

Then return exactly:

`W08 QA: WAITING FOR EXTERNAL VALIDATION`

This is a non-final resumable state, not a W08 disposition.

---

# 7. WAITING_EXTERNAL DEFINITION

`WAITING_EXTERNAL` is valid only when:

- an external run has been observed to exist;
- its recorded head SHA matches the exact QA execution SHA expected by the checkpoint;
- its status is queued or in progress;
- QA has no remaining result-independent work that must happen first;
- the checkpoint has been durably persisted.

QA must not claim that the external run passed, failed, or produced artifacts while in this state.

No final W08 PASS/FAIL disposition is made from `WAITING_EXTERNAL`.

---

# 8. RESUME SEMANTICS

The human wake-up message may be only:

`resume QA`

That message is NOT evidence.

On resume, QA must independently:

1. read the latest governance checkpoint from repository truth;
2. reverify the QA execution branch exact HEAD;
3. reverify product-anchor ancestry;
4. fetch the recorded workflow run;
5. verify run head SHA == checkpoint expected QA SHA;
6. inspect run status/conclusion, jobs, step results and artifacts;
7. inspect the machine-readable reports and screenshots/diagnostics actually produced;
8. reconcile them against the prior checkpoint;
9. classify failures under the existing law;
10. continue from the recorded W08 phase instead of restarting from zero.

If branch or run identity has drifted, stop and return the authority mismatch instead of guessing.

A user statement such as `CI passed` is not authoritative unless GitHub evidence confirms it.

---

# 9. FAILED-RUN RECONCILIATION RULE

The existing cancelled and failed runs must remain visible in the dossier.

Before any new W08 run, QA must consume run `35179625056` and artifact `10480091707` sufficiently to determine whether the next action is:

- HARNESS DEFECT repair;
- QA ENVIRONMENT DEFECT repair;
- PRODUCT DEFECT -> `W08 QA: REWORK REQUIRED`;
- or a documented BLOCKED state.

Do not blindly rerun the same SHA merely because the prior workflow failed.

If the current machine reports identify multiple observations, classify them independently. Do not let a fatal harness/bootstrap failure automatically erase an earlier product-relevant observation, and do not promote an unresolved observation into a product defect without sufficient evidence.

---

# 10. TWO DISTINCT ORCHESTRATION PROBLEMS

## CI trigger inefficiency

Governance-only `.forge/**`, checkpoint and dossier transitions should not require a full browser/product run.

For this recovery, governance/checkpoint continuity is carried on the non-executing control-plane branch `KIRCH-TALIBON-V1-W08-QA-GOVERNANCE`, which does not replace the QA execution branch.

Do not move the QA execution branch solely to record a checkpoint.

## CI babysitting

When a legitimate heavy W08 run exists, QA must not hold a ChatGPT execution open by repeated sleep/poll loops.

Launch -> verify -> checkpoint -> return.

These are separate defects and must remain separately recorded.

---

# 11. FAILURE LAW REMAINS UNCHANGED

HARNESS DEFECT: QA may repair the harness, then rerun affected checks and the required final matrix.

QA ENVIRONMENT DEFECT: QA may repair isolated QA workflow/bootstrap/environment behavior without modifying product semantics.

PRODUCT DEFECT: QA may NOT repair product source. Capture exact reproduction/evidence and return `W08 QA: REWORK REQUIRED`.

Never weaken an assertion to manufacture a pass.

---

# 12. FULL W08 SCOPE REMAINS REQUIRED

The continuation model does not remove or downgrade any original requirement:

- real Playwright/browser execution;
- exact-SHA validation;
- seven-persona coverage;
- full viewport matrix;
- light/dark coverage;
- W03 browser mutation/return continuity;
- W04 responsive Planning;
- W05 utility breakpoint regression;
- W06 read-only Messages boundary;
- W07 role/admin/HR/error checks;
- keyboard/focus/reflow/accessibility mechanics;
- screenshots and manifest;
- machine-readable report;
- required evidence ZIP;
- truthful limitations;
- retained failed/cancelled history;
- exact final 23-part QA Return.

A PASS without the required ZIP is invalid.

QA still cannot self-accept the correction program.

---

# 13. FINAL RETURN CONTRACT

The original 23-part final QA Return in `.forge/handoffs/qa/W08-FORGE-QUALITY-ASSURANCE.md` remains exact and unchanged.

Intermediate resumable returns such as `W08 QA: WAITING FOR EXTERNAL VALIDATION` do not replace or shorten that contract.

`QUALITY ASSURANCE AUTHORITY RETURNED TO MAINTAINER.` remains item 23 of the eventual final return.
