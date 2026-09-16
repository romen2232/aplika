# ADR 005: JobOffer as Unified Aggregate

## Status

Accepted

## Context

The original README design suggested separating "Job" (a job listing) from "Application" (the user's application to that job). This separation is common in job platforms where:
- A single job posting can receive multiple applications from different users
- A user can apply to the same job multiple times (e.g., re-applying after a year)
- The job listing has its own lifecycle independent of applications

However, Aplika is a **personal job-search management tool**, not a multi-user job board. The primary use case is:
- One user managing their own job search
- Each job offer represents a unique opportunity the user is tracking
- The "application" is inseparable from the job itself in the user's mental model

Maintaining separate Job and Application aggregates would add complexity without clear benefit:
- Extra joins and queries to link them
- Confusing UI decisions (do I show jobs or applications?)
- Unnecessary abstraction for a single-user context

## Decision

We adopted a **unified JobOffer aggregate** that combines the job listing and the user's application into a single entity.

The JobOffer aggregate contains:
- Job information (title, company, description, URL, etc.)
- Application state (status, applied date, recruiter info)
- Related entities (notes, tasks, timeline events)
- CV association

This design reflects the user's mental model: "I'm tracking this job opportunity" — not "I have a job listing and a separate application record."

## Consequences

### Positive

- **Simpler domain model**: One aggregate instead of two, fewer relationships to manage
- **Clearer UI**: The Kanban board shows job offers, not abstract applications
- **Fewer queries**: No need to join Job and Application tables for basic operations
- **Matches user mental model**: Users think "I'm tracking this job", not "I have an application to this job"
- **Easier to reason about**: Status transitions, timeline events, and notes all belong to one entity

### Negative

- **Cannot support multiple applications per job**: If the user wants to re-apply to the same company after a year, they must create a new JobOffer (or reopen the closed one)
- **Cannot share job listings**: If we later want to allow users to share job listings with each other, the current design doesn't support it
- **Tighter coupling**: Job information and application state are in the same aggregate, which could become large

### Mitigations

- **Reopen closed jobs**: The status transition `Closed → Saved` allows users to "re-apply" by reopening the job
- **Future refactoring**: If the product evolves to support multi-user scenarios or job sharing, the aggregate can be split into Job and Application at that time
- **Read model separation**: CQRS allows us to create lightweight read models for the board view, avoiding loading the full aggregate

## Alternatives Considered

### Alternative 1: Separate Job and Application aggregates

**Design**:
```
Job (Aggregate Root)
├── id, title, company, description, url, ...
└── (shared job listing)

Application (Aggregate Root)
├── id, jobId, userId, status, appliedAt, ...
├── notes[], tasks[], timelineEvents[]
└── cvId
```

**Pros**:
- Supports multiple applications per job
- Supports job sharing between users
- Clear separation of concerns

**Cons**:
- Extra complexity for a single-user tool
- Confusing UI (do I show jobs or applications?)
- More queries and joins

**Why rejected**: Over-engineering for the current product scope. The user's mental model is "I'm tracking this job", not "I have an application to this job".

### Alternative 2: JobOffer with embedded Application value object

**Design**:
```
JobOffer (Aggregate Root)
├── id, title, company, description, url, ...
├── application: Application (value object)
│   ├── status, appliedAt, recruiterInfo, ...
│   └── notes[], tasks[], timelineEvents[]
└── cvId
```

**Pros**:
- Clear separation within the aggregate
- Application can be replaced or versioned

**Cons**:
- Adds complexity without clear benefit
- Value objects are immutable, but application state changes frequently

**Why rejected**: Unnecessary abstraction. The application state is integral to the job offer, not a separate concept.

## Future Considerations

If the product evolves to support:
- **Multiple applications per job**: Split JobOffer into Job and Application
- **Job sharing**: Create a separate JobListing aggregate that can be shared, with Application referencing it
- **Re-applying after a long time**: Add a "version" or "cycle" concept to JobOffer

Until then, the unified JobOffer aggregate is the right choice for simplicity and clarity.

## References

- [Job Pipeline Spec](../specs/job-pipeline.md)
- [ADR 001: Domain-Driven Design](./001-domain-driven-design.md)
- [ADR 003: CQRS](./003-cqrs.md)
