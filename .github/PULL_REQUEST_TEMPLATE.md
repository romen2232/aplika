## Summary

<!-- One to three bullet points describing what this PR does and why. -->

-

## Related work

<!-- Link the issue/ticket this PR addresses. Branches follow TICKET_snake_case_feature. -->

- Closes #
- Branch:
- Target: `staging`

## Type of change

<!-- Check exactly one. Match the dominant commit type. -->

- [ ] `feat` — new capability
- [ ] `fix` — corrects broken behavior
- [ ] `perf` — faster, same behavior
- [ ] `refactor` — restructure, same behavior
- [ ] `test` — tests only
- [ ] `ui` — visual or layout only
- [ ] `build` / `ci` — dependencies, Docker, CI/CD
- [ ] `docs` — documentation only
- [ ] `chore` / `style` — maintenance or formatting

## Bounded context

<!-- Which module(s) does this touch? -->

- [ ] `Job`
- [ ] `Application`
- [ ] `Candidate`
- [ ] `Company`
- [ ] `Interview`
- [ ] `Shared`
- [ ] `frontend`
- [ ] `infrastructure`

## Changes

| File | Change |
|------|--------|
| `path/to/file` | What changed |

## How to test

<!-- Exact commands, run through Docker Compose. -->

```bash
# Backend
docker compose exec api vendor/bin/phpspec run
docker compose exec api vendor/bin/behat

# Frontend
docker compose exec frontend npm run test

# End-to-end
docker compose exec frontend npx playwright test
```

- [ ] Backend unit/domain tests pass (PHPSpec)
- [ ] Backend acceptance tests pass (Behat)
- [ ] Frontend tests pass (Vitest)
- [ ] E2E covered where behavior is user-facing (Playwright)
- [ ] Manually verified the affected flow

## Architecture checklist

- [ ] Domain layer stays free of framework and infrastructure code
- [ ] Business rules live in the domain, not controllers or repositories
- [ ] Commands/Queries separated where CQRS applies
- [ ] Dependencies point inward (dependency inversion)
- [ ] New behavior is driven by tests (Red → Green → Refactor)
- [ ] ADR added/updated if an architectural decision was made

## Reviewer notes

<!-- Screenshots, migrations, breaking changes, follow-ups, or open questions. -->
