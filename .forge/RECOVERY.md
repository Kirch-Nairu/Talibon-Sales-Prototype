# ONE TALIBON V1 — Recovery Policy

## Default rule

Preserve evidence and history. Force push is prohibited by default.

## Authority drift

If a required source/integration branch moves unexpectedly:

- stop mutation;
- record observed SHA;
- compare against expected authority;
- classify whether the movement is authorized, stale handoff state, or collision;
- return to Maintainer before continuing.

Do not silently rebase onto the new head.

## Writer collision

If two active waves require the same shared file outside explicit ownership:

- stop the later writer;
- do not opportunistically merge both designs;
- Maintainer resolves ownership/dependency order;
- reissue an exact handoff if needed.

## Failed candidate

A failed or rejected candidate remains evidence. Correct through a new commit/candidate or a newly authorized branch rather than deleting history.

## Integration failure

If integration produces regressions or invalid evidence:

- stop promotion;
- preserve the failed integration state if already published;
- use a forward corrective/revert commit where safe;
- return to the last explicitly accepted authority only through a documented non-destructive transition.

## Destructive actions

Branch deletion, history rewrite, force updates, destructive data changes, or production-impacting recovery require explicit human/Maintainer authority and an evidence-preserving rationale.

## Runtime evidence gaps

Tool absence narrows claims. If browser/runtime/build execution is unavailable, mark it unavailable or not run. Do not compensate with stronger source-level claims.
