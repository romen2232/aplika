---
name: adr-writer
description: "Trigger: ADR, architectural decision, decision record, docs/adr. Write numbered decision records capturing what was decided, why, and which alternatives were rejected."
license: Apache-2.0
metadata:
  author: "romen2232"
  version: "1.0"
---

# adr-writer

## Activation Contract

Apply when an architectural, technology, or tradeoff decision is made (or backfilled) and must outlive the conversation.

## Hard Rules

- ADRs live in `docs/adr/NNN-kebab-case-title.md`, numbered sequentially with zero-padded 3 digits (e.g. `001-domain-driven-design.md`).
- One decision per ADR. Never bundle decisions.
- Document the WHY: context, constraints, alternatives rejected, and why they were rejected.
- Status is required and one of: `proposed` | `accepted` | `deprecated` | `superseded by NNN`.
- Keep each ADR to one screen. If it needs more, the decision is too big — split it.
- Never rewrite history: supersede the old ADR with a new one and update the old ADR's status.

## Decision Gates

| Situation | Artifact |
|---|---|
| Architecture, tech choice, or tradeoff with lasting impact | ADR |
| Product or feature description | `readme.md` update |
| Local implementation detail | Code comment |
| Decision already made in `readme.md` | Backfill ADR candidate |

Backfill candidates from `readme.md` (do not invent others): 001 Domain-Driven Design, 002 Screaming Architecture, 003 CQRS, 004 PostgreSQL, 005 Docker-first local environment, 006 AI behind application ports.

## Execution Steps

1. Check `docs/adr/` for the next free number and for any ADR this one supersedes.
2. Copy `assets/adr-template.md` to `docs/adr/NNN-kebab-case-title.md`.
3. Fill Title, Status, Date, Context, Decision, Consequences, Alternatives Considered.
4. When superseding, set the old ADR's status to `superseded by NNN`.

## Output Contract

Report: ADR path and number, status set, decisions superseded (if any), and a one-line summary of the decision.

## References

- `assets/adr-template.md` — the ADR template (relative to this skill directory).
- `readme.md` — Architectural Decisions section.
