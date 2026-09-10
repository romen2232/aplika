---
name: ddd-symfony
description: "Trigger: domain, aggregate, entity, value object, module, business rule, Symfony, PHP backend. Enforce screaming architecture modules, rich domain model, framework-free domain layer."
license: Apache-2.0
metadata:
  author: "romen2232"
  version: "1.0"
---

# ddd-symfony

## Activation Contract

Apply when creating or modifying PHP backend code: domain models, modules, aggregates, entities, value objects, repositories, or any business rule.

## Hard Rules

- Organize by Screaming Architecture module: `src/{Module}/Domain`, `src/{Module}/Application/Command`, `src/{Module}/Application/Query`, `src/{Module}/Infrastructure`. Current modules: Job, Application, Candidate, Company, Interview, Shared — canonical enumeration lives in the readme.md Architecture section.
- The Domain layer is framework-free: never import Symfony, Doctrine, HTTP, or infrastructure concerns into Domain classes.
- Model explicit behavior, not setters: `$application->scheduleInterview($interview)` — never `$application->setStatus('interview')`.
- Aggregates protect their own invariants; reject invalid state transitions with domain exceptions.
- Business rules live in Domain — never in controllers, services, or framework event listeners.
- Dependency inversion: Domain defines contracts (e.g. repository interfaces); Infrastructure implements them with Doctrine/PostgreSQL adapters. Infrastructure never dictates the contract.

## Decision Gates

| Situation | Placement |
|---|---|
| Business rule, invariant, state transition | `{Module}/Domain` |
| Use-case orchestration (command/query handler) | `{Module}/Application` |
| Doctrine mapping, HTTP, external APIs, AI providers | `{Module}/Infrastructure` |
| Behavior shared across modules (IDs, value objects) | `Shared` module |
| New noun with its own lifecycle | Default: place under the owning module (e.g. Contact under Company). A new top-level module requires an accepted ADR (see `adr-writer` skill) |
| New behavior of an existing aggregate | Extend that module |

Anemic-model smells — reject and rewrite: public setters, primitive-obsessed status strings, transaction scripts in handlers, domain logic in controllers.

## Execution Steps

1. Identify module and layer before writing any code.
2. Define domain behavior and invariants first via a failing spec (see `phpspec-tdd` skill).
3. Expose the behavior through a Command or Query + handler (see `cqrs-symfony` skill).
4. Add the Infrastructure adapter implementing the domain port last.

## Output Contract

Report: module and files created/modified, invariants enforced, ports defined, and confirmation that Domain contains no framework imports.

## References

- `readme.md` — Architecture, Domain-Driven Design, and Engineering Principles sections.
