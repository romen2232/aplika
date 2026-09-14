# Joblog frontend

Next.js (App Router) + React + TypeScript client for Joblog.

All commands run through Docker Compose from the repository root:

```bash
docker compose exec frontend npm run dev          # dev server on http://joblog.dev
docker compose exec frontend npm run test         # Vitest unit/component tests
docker compose exec frontend npm run typecheck    # TypeScript
docker compose exec frontend npm run lint         # ESLint
docker compose exec frontend npx playwright test  # end-to-end tests
```

End-to-end tests need the Playwright browsers once per environment:

```bash
docker compose exec frontend npx playwright install --with-deps chromium
```

See the root `README.md` for product and architecture context.
