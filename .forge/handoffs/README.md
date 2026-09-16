# ONE TALIBON V1 — Forge Handoff Continuity

This directory stores durable handoff packets or status returns when the Maintainer decides they are useful to preserve in-repository.

A writer handoff must include at minimum:

- Forge role;
- target repository;
- exact source branch and SHA;
- writer branch name;
- owned files/directories;
- explicit non-owned boundaries;
- accepted product/UX decisions relevant to the wave;
- required behavior;
- required validation;
- known evidence limitations;
- stop conditions;
- expected return format.

A writer return must include:

- exact candidate SHA;
- changed scope summary;
- tests/checks actually run;
- checks not run and why;
- known limitations;
- authority returned to Maintainer.

Handoff text does not override directly observed Git authority. If the source branch moved unexpectedly, stop and return to Maintainer instead of silently rebasing.
