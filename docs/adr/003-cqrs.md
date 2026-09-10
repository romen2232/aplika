# ADR 003: CQRS (Command Query Responsibility Segregation)

## Status

Accepted

## Context

Jobify has two fundamentally different types of operations:

**Commands** — actions that change the system and must enforce business rules:
- Create a job, import a job
- Create and submit an application
- Schedule and complete an interview
- Change application status
- Create a CV version

**Queries** — read operations that retrieve data without changing state:
- List jobs, get job details
- List applications, get application timeline
- Get dashboard with statistics
- Get upcoming interviews
- Get job search analytics

These two types of operations have different concerns. Commands need to enforce invariants, trigger domain events, and maintain consistency. Queries need to be fast, return exactly the data the UI requires, and avoid loading heavy aggregate graphs.

Mixing both in the same service layer leads to:

- Handlers that are hard to understand because they mix read and write logic.
- Queries that load full aggregates when they only need a few fields.
- Commands that are slowed down by read-model optimizations.
- Difficulty optimizing reads independently (caching, denormalization, projections).

We needed a pattern that separates these concerns at the application level while keeping the implementation pragmatic.

## Decision

We adopted **CQRS (Command Query Responsibility Segregation)** at the application level.

Each bounded context has a clear separation between commands and queries:

```text
src/
├── Application/
│   ├── Domain/
│   ├── Application/
│   │   ├── Command/
│   │   │   ├── CreateApplication/
│   │   │   │   ├── CreateApplicationCommand.php
│   │   │   │   └── CreateApplicationHandler.php
│   │   │   ├── SubmitApplication/
│   │   │   │   ├── SubmitApplicationCommand.php
│   │   │   │   └── SubmitApplicationHandler.php
│   │   │   └── ...
│   │   └── Query/
│   │       ├── GetApplication/
│   │       │   ├── GetApplicationQuery.php
│   │       │   ├── GetApplicationHandler.php
│   │       │   └── ApplicationReadModel.php
│   │       ├── ListApplications/
│   │       └── GetDashboard/
│   └── Infrastructure/
```

### Command Flow

```text
HTTP Request
    ↓
Command (DTO)
    ↓
Command Handler (Application Service)
    ↓
Domain Model (Aggregate)
    ↓
Repository (write)
    ↓
PostgreSQL
```

Commands:
- Represent intent: `CreateJob`, `SubmitApplication`, `ScheduleInterview`.
- Are handled by the domain, which enforces business rules and protects invariants.
- May produce domain events.
- Use write repositories that return aggregates.

### Query Flow

```text
HTTP Request
    ↓
Query (DTO)
    ↓
Query Handler
    ↓
Read Repository
    ↓
Read Model (DTO)
    ↓
HTTP Response
```

Queries:
- Represent read intent: `GetJob`, `ListApplications`, `GetDashboard`.
- Bypass the domain model entirely — they go straight to the database.
- Return read models shaped for the UI, not domain aggregates.
- Can be optimized independently (caching, denormalized views, custom SQL).

### Key Principles

- **Separation at the application level**: commands and queries are distinct classes with distinct handlers.
- **Same database initially**: both commands and queries use PostgreSQL. The separation is logical, not physical.
- **Queries bypass the domain**: queries do not load aggregates. They use read repositories that return exactly the data needed.
- **Commands go through the domain**: all state changes are handled by domain models that enforce business rules.
- **No unnecessary infrastructure**: we do not introduce event sourcing, separate read databases, or projections unless the system actually needs them.

## Consequences

### Positive

- Commands and queries have distinct responsibilities, making each easier to understand and test.
- Queries can be optimized without affecting command logic (e.g., denormalized read models for the dashboard).
- The dashboard can retrieve exactly the data it needs without loading dozens of aggregates.
- Business rules are enforced only in command handlers, reducing the risk of inconsistent state.
- The pattern scales: if read performance becomes a bottleneck, we can introduce separate read models, caching, or replicas without redesigning the domain.
- Testing is clearer: command tests verify business rules, query tests verify data retrieval.

### Negative

- More boilerplate: each command and query needs its own class, handler, and potentially read model.
- Developers need to understand the distinction between commands and queries and where each flows.
- Over-separation is a risk — simple CRUD operations may not benefit from full CQRS.

### Mitigations

- We apply CQRS where it provides meaningful value. Simple read operations do not need complex read models.
- The structure is consistent: every bounded context follows the same Command/Query pattern, reducing cognitive load.
- We start with the same database for both sides. Physical separation (separate read database, event projections) is only introduced when the system actually requires it.
- PHPSpec tests drive command handler behavior; query handlers are tested with integration tests against a test database.

## References

- Greg Young — *CQRS, Task Based UIs, Event Sourcing* (https://www.youtube.com/watch?v=JHGkaShoyNs)
- Martin Fowler — *CQRS* (https://martinfowler.com/bliki/CQRS.html)
- [Jobify readme — CQRS section](../readme.md#cqrs)
- [Jobify readme — Dashboard example](../readme.md#example-dashboard)
