---
name: phpspec-tdd
description: "Trigger: phpspec, phpspec spec file, TDD, domain test, backend unit test, red green refactor. Drive backend domain behavior with PHPSpec first and pick the correct Joblog test level."
license: Apache-2.0
metadata:
  author: "romen2232"
  version: "1.0"
---

# phpspec-tdd

## Activation Contract

Apply when writing backend tests, driving domain behavior with TDD, or deciding which test level covers a change.

## Hard Rules

- TDD cycle: Red → Green → Refactor. Write the failing spec BEFORE implementing domain behavior.
- PHPSpec covers domain behavior and isolated backend logic only. Specs run without Symfony, Doctrine, HTTP, or a database.
- Describe behavior, not implementation: "can be submitted", "cannot be submitted twice", "can schedule an interview", "records relevant state changes".
- Coverage for its own sake is not the goal; tests exist to define behavior, design domain APIs, protect business rules, and catch regressions.
- Run specs inside Docker: `docker compose exec api vendor/bin/phpspec run` (see `docker-workflow` skill).

## Decision Gates

Pick exactly one level per testing need:

| Need | Tool |
|---|---|
| Domain rule, invariant, aggregate behavior | PHPSpec |
| Business-facing acceptance flow (Given/When/Then) | Behat |
| Frontend unit/component behavior | Vitest |
| Real end-to-end user journey across the stack | Playwright |

| Situation | Action |
|---|---|
| New domain behavior | Failing spec first, then implement |
| Bug in domain logic | Reproduction spec first, then fix |
| Handler orchestration logic | Not covered by PHPSpec's domain mandate; cover it at the Behat level until the team records a different decision in an ADR |
| Cross-module workflow | Behat, not PHPSpec |

## Execution Steps

1. Locate or create the spec mirroring the target class path, in the directory configured by the api `phpspec.yml` (PHPSpec default: `spec/`); once the repo is bootstrapped, the config file is the tiebreaker.
2. Write one failing expectation for one behavior.
3. Run the spec via Docker; confirm Red.
4. Implement the minimal domain code; confirm Green.
5. Refactor while green; re-run the full suite before reporting completion.

## Output Contract

Report: specs added/modified, behaviors covered, Red→Green evidence (final suite output), and the chosen test level with justification.

## References

- `readme.md` — Testing Strategy, PHPSpec, and TDD sections.
