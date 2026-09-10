---
name: cqrs-symfony
description: "Trigger: command, query, handler, CQRS, read model, read repository, dashboard, use case. Enforce command/query separation, handler flows, and read-model conventions on one PostgreSQL."
license: Apache-2.0
metadata:
  author: "romen2232"
  version: "1.0"
---

# cqrs-symfony

## Activation Contract

Apply when creating or modifying any use case in the Symfony API: commands, queries, handlers, read models, or dashboard/analytics reads.

## Hard Rules

- Commands change state; queries never do. Classify by whether the operation writes.
- Command flow: HTTP → Command → Command Handler → Domain → Repository → PostgreSQL.
- Query flow: HTTP → Query → Query Handler → Read Repository → Read Model → Response.
- Both sides use the SAME PostgreSQL. Separation happens at the application level only.
- Boring infrastructure: do NOT introduce event buses, separate read databases, caches, replicas, or projections until a real, measured problem demands it.
- Never load domain aggregates to render list or dashboard views; build a Read Model that selects exactly the data the UI needs.
- Long-running work (job analysis, document processing, AI requests, email) runs via background processing — never inside an HTTP request path or a synchronous handler.
- Commands and queries live in `{Module}/Application/Command` and `{Module}/Application/Query`; handlers are colocated with them (skill convention — readme.md does not fix handler placement).

## Decision Gates

| Situation | Choice |
|---|---|
| Naming a state change | Verb+Noun command: `CreateJob`, `SubmitApplication`, `ScheduleInterview` |
| Naming a read | Get/List+Noun query: `GetJob`, `ListApplications`, `GetApplicationTimeline` |
| Single-aggregate fetch by id | Follow the canonical query flow; deviating (e.g. reusing the domain repository) requires an ADR (see `adr-writer` skill) |
| Aggregations, filters, cross-aggregate joins (dashboard, statistics) | Dedicated Read Repository + Read Model |
| Temptation to add new infrastructure | Stop; document the problem first — ADR candidate (see `adr-writer` skill) |

## Execution Steps

1. Classify the operation: command or query.
2. Write the handler contract first (input DTO; output DTO or read model).
3. Command: orchestrate domain behavior, persist via the repository port. Query: select directly into the read model.
4. Keep HTTP controllers thin: translate request → command/query, dispatch, map response.
5. Cover handler behavior with tests (see `phpspec-tdd` skill to pick the level).

## Output Contract

Report: commands/queries created, handler flow used, read models introduced, and confirmation that no new infrastructure was added.

## References

- `readme.md` — CQRS and Example: Dashboard sections.
