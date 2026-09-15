# Aplika — Agent Instructions

Aplika is a job-search management platform: PHP/Symfony API + Next.js/TypeScript frontend + PostgreSQL, built with DDD, CQRS, Screaming Architecture, and TDD. `README.md` is the source of truth for product and engineering conventions.

## Hard rule

All local commands run through Docker Compose — never on the host (see the `docker-workflow` skill).

## Project skills

Load the matching skill BEFORE working on the corresponding context. The `description` frontmatter in each SKILL.md is the authoritative trigger list; this table is a summary:

| Skill | Trigger (summary) | Path |
|---|---|---|
| `ddd-symfony` | domain, aggregate, entity, value object, module, business rule, Symfony backend | `.opencode/skills/ddd-symfony/SKILL.md` |
| `cqrs-symfony` | command, query, handler, read model, dashboard, use case | `.opencode/skills/cqrs-symfony/SKILL.md` |
| `phpspec-tdd` | phpspec, phpspec spec file, TDD, backend tests, red green refactor | `.opencode/skills/phpspec-tdd/SKILL.md` |
| `docker-workflow` | run tests, install deps, composer, npm, any local command | `.opencode/skills/docker-workflow/SKILL.md` |
| `adr-writer` | ADR, architectural decision, decision record, docs/adr | `.opencode/skills/adr-writer/SKILL.md` |
| `commit-workflow` | commit, commit message, conventional commit, feature branch, branch naming, staging, TDD commit | `.opencode/skills/commit-workflow/SKILL.md` |

## Artifact language

Generated code, comments, tests, docs, and UI copy default to English.
