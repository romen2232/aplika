---
name: commit-workflow
description: "Trigger: commit, commit message, conventional commit, feature branch, branch naming, staging, TDD commit. Enforce Aplika commit types, one-type-per-commit, branch naming, and one commit per TDD step."
license: Apache-2.0
metadata:
  author: "romen2232"
  version: "1.0"
---

# commit-workflow

## Activation Contract

Apply when creating a commit, writing a commit message, naming a branch, or splitting work into commits. Companion to the user-level `work-unit-commits` skill; this skill specializes it with the Aplika contract from ADR 004.

## Hard Rules

- Message format: `<type>(<scope>): <description>`. Scope is optional, lower-case, names the bounded context (`application`, `job`, `auth`, `ui`). Description is imperative, lower-case, no trailing period, <=72 chars.
- Allowed types, none other: `feat`, `fix`, `perf`, `refactor`, `test`, `build`, `ci`, `style`, `ui`, `docs`, `chore`.
- One type per commit. If a change spans types, split it. If it cannot be split, use the dominant type by the precedence `feat > fix > perf > refactor > test > build > ci > ui > docs > style > chore`.
- Boundary: `feat` = new capability, `ui` = look only, `style` = formatting/lint only.
- Small but not atomic: one coherent step that leaves the repo green. If the message needs "and", split.
- Branches: `main` = production, `staging` = pre-prod testing, feature = `TICKET_snake_case_feature` cut from `staging`. Never commit directly to `main` or `staging`.
- TDD: one commit per step — `test:` (Red), `feat:`/`fix:` (Green), `refactor:` (Refactor). This overrides "keep tests with code" for TDD.
- No `Co-Authored-By` or AI attribution in commits.
- Run git and tooling through Docker Compose (see `docker-workflow`).

## Decision Gates

| Change | Type |
|---|---|
| New capability | `feat` |
| Corrects broken behavior | `fix` |
| Faster, same behavior | `perf` |
| Restructure, same behavior | `refactor` |
| Tests only (non-TDD) | `test` |
| Dependencies / Docker / bundler | `build` |
| CI/CD config | `ci` |
| Formatting or lint, no behavior | `style` |
| Visual or layout only | `ui` |
| Documentation only | `docs` |
| Maintenance, no source or tests | `chore` |

| Situation | Action |
|---|---|
| Change spans types | Split the commit |
| Cannot split | Use dominant type, name secondary area in scope |
| TDD Red step | Commit only the failing spec as `test` |
| Non-TDD tests | Ship tests in the same commit as the code |

## Execution Steps

1. Identify the single dominant intent; pick its type and scope.
2. Stage only the files for that unit.
3. Confirm the repo is green before committing.
4. Write `<type>(<scope>): <description>`; add a body only when the why is non-obvious.
5. Feature work: `git switch -c TICKET_feature staging`, open a PR into `staging`; `staging` merges into `main` after validation.

## Output Contract

Report: the type and scope chosen, the branch, whether the single-type and green-repo checks passed, and any commit that legitimately uses the dominant-type fallback.

## References

- `docs/adr/004-commit-and-branch-conventions.md` — the decision this skill enforces.
- `readme.md` — Development Workflow and TDD sections.
- `work-unit-commits` skill — reviewable commit units.
