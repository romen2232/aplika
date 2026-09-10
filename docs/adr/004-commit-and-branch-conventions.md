# ADR 004: Commit and Branch Conventions

- Status: accepted
- Date: 2026-09-10

## Context

Jobify is built by two developers, with AI agents contributing code and commits. Without an explicit contract, commit messages drift, history becomes hard to scan, and review and changelog automation lose their input. We also run a two-stage delivery flow — production and a pre-production testing branch — and we practice TDD, which changes how work is naturally split into commits.

We need a convention that is simple enough to follow without thinking, strict enough to be enforced by review (and later by tooling), and that keeps history meaningful for humans and machines.

## Decision

We will use **Conventional Commits** with a fixed type set, **one type per commit**, small-but-not-atomic commits, a **two-stage branch model**, and **one commit per TDD step**.

### Commit message format

```text
<type>(<scope>): <description>
```

- `type` is mandatory and must be one of the allowed types below.
- `scope` is optional, lower-case, and names the bounded context or area (e.g. `application`, `job`, `interview`, `auth`, `api`, `ui`).
- `description` is imperative mood, lower-case, no trailing period, 72 characters or fewer.
- Add a body only when the *why* is non-obvious. Reference the ticket code when relevant.

Examples:

```text
feat(application): add schedule interview command
fix(job): reject duplicate source urls
test(application): add failing spec for double submission
ui(dashboard): align interview statistics cards
```

### Allowed types

| Type | Use for |
|---|---|
| `feat` | A new user-facing or domain capability. |
| `fix` | A correction to broken behavior. |
| `perf` | A change that improves performance. |
| `refactor` | A behavior-preserving restructure. |
| `test` | Adding or correcting tests only (non-TDD work). |
| `build` | Build system or dependencies (Composer, npm, Docker, bundler). |
| `ci` | CI/CD pipeline configuration. |
| `style` | Formatting, whitespace, or lint fixes with no behavior change. |
| `ui` | Visual or presentation changes only — layout, styling, visual states. |
| `docs` | Documentation only (readme, ADR, comments). |
| `chore` | Maintenance that changes neither source nor tests. |

`ui` is a Jobify extension, not part of the base Conventional Commits set. Commit tooling (e.g. `commitlint`) MUST be configured to accept it; until that exists, reviewers enforce it.

Boundary between `feat`, `ui`, and `style`:

- Changes what the user can **do** -> `feat`.
- Changes only how something **looks** -> `ui`.
- Changes only how code is **written/formatted**, with no visual or behavioral effect -> `style`.

### One type per commit

A commit carries exactly one type. This is the goal, not a wish: if a change spans several types, **split it**.

When a change genuinely cannot be split, use the **dominant type** — the type that best describes the primary intent a reviewer sees — and use `scope` to name the secondary area. If intent is still ambiguous, fall back to this precedence:

```text
feat > fix > perf > refactor > test > build > ci > ui > docs > style > chore
```

Rationale: behavior-affecting types outrank non-behavior types; user-visible capability or defect outranks internal work.

### Commit granularity

Commits are small but not atomic to the point of being meaningless.

- One commit = one coherent, self-contained step that leaves the repo green.
- Too large: multiple unrelated concerns in one commit.
- Too small: a one-liner that cannot stand alone or leaves the repo broken.
- Heuristic: if the message needs "and", or a reviewer cannot describe the commit in one sentence, split it.

### Branches

```text
main            production
  ^
staging         pre-production testing
  ^
TICKET_feature  one branch per feature
```

- `main` is production. It only receives changes once they have been validated on `staging`.
- `staging` is the integration and testing branch.
- Feature branches are named `[ticket-code]_[snake_case_feature]` and are cut from `staging`, e.g. `JOB-123_add_application_timeline`.
- A feature branch is merged back into `staging` through a pull request. Once validated, `staging` is merged into `main`.
- Never commit directly to `main` or `staging`.

### TDD commits

When doing TDD, commit one step per cycle:

```text
test(scope): add failing spec for <behavior>   # Red
feat(scope): implement <behavior>              # Green  (or fix:)
refactor(scope): <cleanup>                      # Refactor (optional)
```

This keeps the Red commit separate from the implementation on purpose: the failing test proves the spec captures the missing behavior, and the Red/Green boundary stays visible in history. This is the **explicit exception** to the general "keep tests with the code" rule; outside TDD, tests ship with the code they verify.

## Consequences

### Positive

- History is scannable: types group changes by intent.
- Changelog and release automation can consume commits directly later.
- The Red/Green/Refactor boundary is visible in `git log`, which supports learning and review.
- Branch names encode the ticket and the feature, linking work to the tracker.
- The two-stage model keeps untested changes off production.

### Negative

- One type per commit requires discipline; mixed changes must be split manually.
- `ui` breaks tooling compatibility by default and requires explicit configuration.
- One commit per TDD step produces more commits than people are used to.
- A long-lived `staging` branch can drift from `main`, making the final merge large. Because `staging` carries only validated work, rollback granularity on `main` is coarser than per-feature.

### Mitigations

- Reviewers reject mixed-type commits; the dominant-type rule is the documented escape hatch.
- Configure `commitlint` with `type-enum` including `ui` as soon as tooling is introduced.
- Keep feature branches short-lived and merge them into `staging` frequently.
- Follow `work-unit-commits` for reviewable units; this ADR specializes it for Jobify.

## Alternatives Considered

| Alternative | Why rejected |
|---|---|
| Free-form commit messages | No consistent history, no automation input, review friction. |
| Standard Conventional Commits types only (drop `ui`) | Forces visual work into `feat` or `style`, losing the distinction; we accepted the tooling cost instead. |
| Squash each feature into one commit | Loses the TDD Red/Green boundary and the reviewable story; violates work-unit thinking. |
| Commit per file or per technical layer | Produces commits that do not stand alone and do not tell a story. |
| Feature branches into `main`, with `staging` as a deploy target only | Simpler, no branch drift; rejected because we explicitly want validation on `staging` before production. |
| Split every commit down to one line of code | Unreviewable and noisy; a commit should be a coherent step, not a keystroke. |

## References

- [Conventional Commits 1.0.0](https://www.conventionalcommits.org/en/v1.0.0/)
- [Jobify readme — Development Workflow](../readme.md#development-workflow)
- [Jobify readme — TDD](../readme.md#tdd)
- `work-unit-commits` skill — reviewable commit units
- `commit-workflow` skill — operational contract for this ADR
