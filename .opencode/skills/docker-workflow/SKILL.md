---
name: docker-workflow
description: "Trigger: run tests, install dependencies, composer, npm, start environment, execute command, behat, playwright. All Joblog local commands run through Docker Compose, never on the host."
license: Apache-2.0
metadata:
  author: "romen2232"
  version: "1.0"
---

# docker-workflow

## Activation Contract

Apply before running ANY local development command: starting the environment, installing dependencies, running tests, builds, or scripts.

## Hard Rules

- Every local dev command runs through Docker Compose. Never assume host PHP, Composer, Node, or npm.
- Never fall back to host execution when a container command fails. Inspect `compose.yaml` service names and container state first.
- If `compose.yaml` does not exist yet (repo not bootstrapped), report that the environment is unavailable instead of guessing commands.

## Decision Gates

Canonical commands (verify service names in `compose.yaml` before use):

| Task | Command |
|---|---|
| Start environment | `docker compose up -d` |
| Install backend deps | `docker compose exec api composer install` |
| Install frontend deps | `docker compose exec frontend npm install` |
| Domain/unit tests | `docker compose exec api vendor/bin/phpspec run` |
| Acceptance tests | `docker compose exec api vendor/bin/behat` |
| Frontend tests | `docker compose exec frontend npm run test` |
| End-to-end tests | `docker compose exec frontend npx playwright test` |

Local stack: Browser → Next.js (frontend) → Symfony API (api) → PostgreSQL.

| Failure | First check |
|---|---|
| `no such service` | Service names in `compose.yaml` |
| Command not found in container | Dependencies installed? Image rebuilt? |
| Service unhealthy | `docker compose ps`, then that service's logs |

## Execution Steps

1. Read `compose.yaml` to confirm services and any command overrides.
2. Ensure the stack is up: `docker compose up -d`.
3. Run the needed command via `docker compose exec <service> ...`.
4. On failure, diagnose inside the container; do not move execution to the host.

## Output Contract

Report: commands executed verbatim, services targeted, output summary, and any environment gap found (missing `compose.yaml`, service down, dependencies not installed).

## References

- `readme.md` — Infrastructure and Getting Started sections.
