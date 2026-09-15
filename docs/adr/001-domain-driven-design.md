# ADR 001: Domain-Driven Design for the Backend

## Status

Accepted

## Context

Aplika is a job-search management platform with a rich domain: jobs, applications, interviews, CVs, companies, contacts, timelines, and analytics. The business logic is not trivial — applications have lifecycles, statuses carry business meaning, interviews are part of an application (not standalone events), and CVs are versioned documents associated with specific applications.

Without a clear architectural approach, business rules tend to scatter across controllers, services, and framework code. This makes the system hard to understand, difficult to test in isolation, and fragile to change.

We needed an architectural approach that:

- Places business rules at the center of the application.
- Keeps the domain independent from frameworks (Symfony, Doctrine, HTTP).
- Makes the codebase communicate what the system does, not how it is wired.
- Allows domain behavior to be tested without infrastructure.

## Decision

We adopted **Domain-Driven Design (DDD)** as the foundational architecture for the backend.

The domain layer is the core of the application. Each bounded context (Job, Application, Interview, Company, Candidate, Shared) is organized into three layers:

```text
src/
├── Job/
│   ├── Domain/
│   ├── Application/
│   │   ├── Command/
│   │   └── Query/
│   └── Infrastructure/
```

Key DDD building blocks used:

- **Entities** with identity and lifecycle (e.g., `Application`, `Job`, `Interview`).
- **Value Objects** for immutable concepts (e.g., `Salary`, `JobUrl`, `ApplicationStatus`).
- **Aggregates** that protect business invariants through explicit behavior.
- **Domain Events** to record meaningful state changes (e.g., `ApplicationSubmitted`, `InterviewScheduled`).
- **Repositories** as contracts defined by the domain, implemented by infrastructure.
- **Application Services** (Command/Query Handlers) that orchestrate use cases without containing business logic.

Business rules live in the domain, not in controllers or services. For example, changing an application's status is not a generic setter:

```php
// Not this:
$application->setStatus('interview');

// This:
$application->scheduleInterview($interview);
```

The domain model protects its own invariants and can be tested independently from Symfony, Doctrine, and HTTP.

## Consequences

### Positive

- Business rules are explicit, testable, and isolated from infrastructure.
- The codebase communicates business capabilities — opening the repository reveals what Aplika does.
- Domain models can be tested with PHPSpec without booting Symfony or connecting to a database.
- Framework changes (e.g., swapping Doctrine for another ORM) do not affect business logic.
- New team members can understand the domain by reading the model, not by reverse-engineering controllers.
- Dependency Inversion is natural: infrastructure implements domain contracts.

### Negative

- DDD has a learning curve. Both developers need to understand aggregates, value objects, and bounded contexts.
- Initial setup requires more upfront thinking about the domain model before writing code.
- Over-engineering is a risk — not every concept needs to be an aggregate or domain event.

### Mitigations

- We start with a single bounded context and split only when the domain demands it.
- We use TDD (PHPSpec) to drive domain design from behavior, not from infrastructure assumptions.
- We follow the principle of "boring infrastructure" — complexity is added only when it solves a real problem.
- The readme serves as a living domain reference, keeping both developers aligned on business concepts.

## References

- Eric Evans — *Domain-Driven Design: Tackling Complexity in the Heart of Software*
- Vaughn Vernon — *Implementing Domain-Driven Design*
- [Aplika readme — Domain-Driven Design section](../readme.md#domain-driven-design)
