# ADR 002: Screaming Architecture for the Frontend

## Status

Accepted

## Context

The Jobify frontend is a Next.js/React/TypeScript application that must reflect the same domain clarity as the backend. The frontend needs to handle job management, application tracking, interview workflows, CV versioning, dashboards, and analytics.

A common frontend anti-pattern is organizing code by technical role:

```text
src/
├── components/
├── hooks/
├── services/
├── utils/
└── types/
```

This structure screams "React app" but says nothing about what the application does. Finding all the code related to "applications" requires searching across multiple directories. Adding a feature means touching files scattered throughout the tree.

We needed a frontend structure that:

- Communicates what the application does at a glance.
- Keeps all code related to a feature close together.
- Aligns with the backend's domain-oriented structure.
- Scales as features grow without becoming a maze of cross-cutting folders.

## Decision

We adopted **Screaming Architecture** for the frontend, organizing the codebase around business capabilities rather than technical roles.

The top-level structure reflects the domain:

```text
frontend/
├── app/                          # Next.js App Router (routing layer)
│   ├── (auth)/                   # Authentication routes
│   ├── (dashboard)/              # Dashboard routes
│   ├── jobs/                     # Job management routes
│   ├── applications/             # Application tracking routes
│   ├── interviews/               # Interview management routes
│   ├── cvs/                      # CV management routes
│   └── companies/                # Company routes
│
├── features/                     # Feature modules (screaming structure)
│   ├── jobs/
│   │   ├── components/
│   │   ├── hooks/
│   │   ├── services/
│   │   ├── types.ts
│   │   └── index.ts
│   ├── applications/
│   │   ├── components/
│   │   ├── hooks/
│   │   ├── services/
│   │   ├── types.ts
│   │   └── index.ts
│   ├── interviews/
│   ├── cvs/
│   ├── companies/
│   └── dashboard/
│
├── shared/                       # Cross-cutting concerns
│   ├── components/               # Generic UI components (Button, Input, Modal)
│   ├── hooks/                    # Generic hooks
│   ├── lib/                      # Shared utilities
│   └── types/                    # Shared type definitions
│
└── infrastructure/               # Framework and external concerns
    ├── api/                      # API client configuration
    ├── auth/                     # Authentication infrastructure
    └── providers/                # Context providers
```

Each feature module is self-contained: it owns its components, hooks, API calls, types, and public API (via `index.ts`). The `shared/` directory holds only truly generic, domain-agnostic UI components and utilities.

Key principles:

- **Feature-first**: all code for "jobs" lives in `features/jobs/`, not scattered across `components/`, `hooks/`, `services/`.
- **Screaming intent**: opening the `features/` directory immediately communicates what Jobify does — jobs, applications, interviews, CVs, companies, dashboard.
- **Shared is small**: `shared/` contains only reusable UI primitives and cross-cutting utilities, not business logic.
- **Barrel exports**: each feature exposes a public API through `index.ts`, hiding internal implementation details.

## Consequences

### Positive

- The structure communicates business capabilities — "jobs", "applications", "interviews" are immediately visible.
- Adding a feature means creating a new feature folder, not modifying five existing directories.
- All code related to a feature is co-located, reducing cognitive load when working on it.
- Aligns with the backend's domain-oriented structure, making full-stack reasoning easier.
- Feature modules can be independently understood, tested, and refactored.
- Shared components remain truly generic — they don't accumulate business logic.

### Negative

- Requires discipline to keep features self-contained. It is tempting to put "just one more hook" in `shared/`.
- Initial setup is more thought than a flat `components/` folder.
- Cross-feature dependencies (e.g., application referencing a job) need clear import conventions.

### Mitigations

- Features import from each other through barrel exports (`features/jobs`), never from internal paths.
- `shared/` is reviewed regularly — if a component is only used by one feature, it moves back to that feature.
- The structure is enforced by convention and code review, not by tooling.
- We follow the readme's engineering principle: "The repository should communicate what the application does."

## References

- Robert C. Martin — *Screaming Architecture* (https://blog.cleancoder.com/uncle-bob/2011/09/30/Screaming-Architecture.html)
- [Jobify readme — Architecture section](../readme.md#architecture)
- [Jobify readme — Engineering Principles](../readme.md#engineering-principles)
