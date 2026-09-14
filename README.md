# Joblog

> A personal job-search management platform built to make the entire job-search process easier to organize, understand, and improve.

Joblog is a full-stack application designed around a simple problem:

**Finding a job is not just about finding job listings.**

A serious job search involves discovering opportunities, evaluating them, preparing applications, managing different CV versions, tracking conversations, preparing for interviews, following up, and learning from the results.

Joblog brings those activities together in one place.

We are building Joblog as a real product for our own job search while using it as an opportunity to practice and demonstrate professional software engineering: **Domain-Driven Design, CQRS, Screaming Architecture, TDD, clean boundaries, automated testing, CI/CD, and cloud infrastructure.**

---

## What is Joblog?

Joblog is a **job-search operating system**.

Instead of treating a job application as a single record with a status, Joblog models the journey around it.

```text
Discover
   ↓
Evaluate
   ↓
Prepare
   ↓
Apply
   ↓
Communicate
   ↓
Interview
   ↓
Follow up
   ↓
Learn
```

The goal is to give us a complete picture of our job search.

For example, when looking at a particular position, Joblog should help answer:

* What is this job?
* Where did we find it?
* Which company is hiring?
* Why are we interested?
* How well does it match our experience?
* Which CV did we use?
* When did we apply?
* Who have we spoken with?
* What happened during the interviews?
* What do we need to do next?
* What can we learn from the outcome?

---

# Getting Started

## Requirements

* Docker
* Docker Compose
* Git
* Make

Clone the repository:

```bash
git clone <repository-url>
cd joblog
```

Add the local domains to your hosts file (one-time setup):

```bash
make hosts
```

Or manually add this line to `/etc/hosts`:

```text
127.0.0.1 joblog.dev api.joblog.dev
```

Start the development environment:

```bash
make init
```

This will:

1. Start all Docker containers (API, frontend, database, nginx)
2. Install backend and frontend dependencies
3. Configure git hooks
4. Run database migrations

Once finished, the application is available at:

| Service  | URL                          |
| -------- | ---------------------------- |
| Frontend | <http://joblog.dev:3000>     |
| API      | <http://api.joblog.dev>      |

## Common commands

```bash
make help            # Show all available commands
make up              # Start containers
make down            # Stop containers
make test            # Run all test suites
make lint            # Run linters
make check           # Run tests, lint, and type-check
make logs            # Tail service logs
make shell-api       # Open a shell in the API container
make shell-frontend  # Open a shell in the frontend container
```

---

# Core Features

## Job Management

Save and organize interesting job opportunities.

A job can contain information such as:

* Position
* Company
* Location
* Remote / hybrid / on-site
* Salary
* Employment type
* Experience requirements
* Required skills
* Job description
* Source
* Original URL
* Date discovered
* Application deadline

Jobs can be imported from a URL so that users don't have to manually enter every piece of information.

---

## Application Tracking

Every application has its own lifecycle.

Example:

```text
Interested
    ↓
Preparing
    ↓
Applied
    ↓
Screening
    ↓
Interview
    ↓
Final Round
    ↓
Offer
```

An application can also end in states such as:

```text
Rejected
Withdrawn
Expired
```

The important part is that the current status is only one part of the story.

Joblog also maintains an **application history** so we can understand what happened over time.

For example:

```text
Job saved
    ↓
CV prepared
    ↓
Application submitted
    ↓
Recruiter contacted us
    ↓
Technical interview scheduled
    ↓
Technical interview completed
    ↓
Follow-up sent
    ↓
Offer received
```

---

# Application Timeline

Instead of overwriting important information, Joblog records meaningful events.

Examples:

* Job saved
* Application created
* CV selected
* Application submitted
* Recruiter contacted
* Interview scheduled
* Interview completed
* Follow-up sent
* Status changed
* Rejection received
* Offer received

This gives every application a timeline that can be used to understand the complete journey.

It also gives us an interesting domain problem to model and test.

---

# CV & Resume Management

Job searches often require different versions of a CV.

Joblog allows us to manage multiple versions rather than constantly replacing the same document.

For example:

```text
CV
├── Backend Developer
├── PHP / Symfony
├── Full Stack
└── Software Engineer
```

Each application can reference the version that was actually used.

This makes it possible to answer questions such as:

> Which CV did I send for this position?

and:

> Which version produces the best results?

---

# AI-Assisted Features

AI is intentionally **not the center of Joblog**.

The product should still be useful without AI.

AI is used where it can reduce repetitive work or provide useful analysis.

Potential examples include:

### Job Analysis

Given a job description, Joblog can identify:

* Required skills
* Preferred skills
* Experience requirements
* Technologies
* Responsibilities
* Potential gaps

### Job ↔ CV Analysis

Compare a CV against a particular job and highlight:

```text
Strong match
- Symfony
- PHP
- PostgreSQL
- REST APIs

Potential gap
- Kubernetes

Not clearly demonstrated
- AWS
```

The goal is not to produce a meaningless percentage.

The goal is to provide information that helps us decide:

> **Should I apply?**

### CV Assistance

AI can suggest improvements to a CV for a specific position.

Importantly, suggestions should not silently modify the user's CV.

The user remains in control.

### Interview Preparation

Based on the job and company, Joblog can help generate:

* Technical questions
* Behavioral questions
* Questions about the company
* STAR-style preparation prompts
* Areas worth revising

---

# Interview Management

Interviews become part of the application rather than a separate calendar of disconnected events.

An interview can contain:

* Date and time
* Interview type
* Interviewers
* Meeting URL
* Notes
* Questions asked
* Answers
* Topics discussed
* What went well
* What could have gone better
* Next steps

After an interview, we can record what actually happened.

Over time, this creates a useful personal knowledge base for future interviews.

---

# Companies

Companies have their own context.

A company can be associated with multiple jobs and applications.

We can keep track of:

* Company information
* Website
* Industry
* Locations
* Open positions
* Contacts
* Applications
* Interviews
* Personal notes

This avoids treating every job posting as an isolated entity.

---

# Contacts & Networking

Job searching often involves people, not just applications.

Joblog can associate contacts with companies and applications.

For example:

```text
Company
   │
   ├── Job
   │
   ├── Recruiter
   │
   ├── Engineering Manager
   │
   └── Referral Contact
```

This provides a place to record interactions and follow-ups.

---

# Dashboard & Analytics

The dashboard provides an overview of the current job search.

For example:

```text
Applications       42
Interviews          8
Final Rounds        3
Offers              1
Response Rate      19%
```

But the more interesting goal is understanding **why** those numbers look the way they do.

Potential insights include:

* Which types of positions receive more responses?
* Which CV version performs better?
* Which companies respond faster?
* How long do applications remain in each stage?
* How many applications lead to interviews?
* Which skills appear most frequently in successful applications?
* Where are we losing candidates in the process?

The purpose of analytics isn't to create pretty charts.

It's to help improve the job search.

---

# Architecture

Joblog is designed around the domain rather than around technical infrastructure.

The architecture follows:

* Domain-Driven Design
* Screaming Architecture
* CQRS
* Dependency Inversion
* TDD
* Explicit domain behavior

The codebase should make the business capabilities obvious when someone opens the repository.

```text
src/
├── Job/
│   ├── Domain/
│   ├── Application/
│   │   ├── Command/
│   │   └── Query/
│   └── Infrastructure/
│
├── Application/
│   ├── Domain/
│   ├── Application/
│   │   ├── Command/
│   │   └── Query/
│   └── Infrastructure/
│
├── Candidate/
│   ├── Domain/
│   ├── Application/
│   │   ├── Command/
│   │   └── Query/
│   └── Infrastructure/
│
├── Company/
│   ├── Domain/
│   ├── Application/
│   │   ├── Command/
│   │   └── Query/
│   └── Infrastructure/
│
├── Interview/
│   ├── Domain/
│   ├── Application/
│   │   ├── Command/
│   │   └── Query/
│   └── Infrastructure/
│
└── Shared/
```

The structure is intentionally organized around **what the system does**, rather than around frameworks or technical layers alone.

---

# Domain-Driven Design

The domain is the center of the application.

We want business rules to live in the domain rather than being scattered across controllers, repositories, services, and framework code.

For example, changing an application's status should represent a meaningful business operation.

Rather than treating the application as a data structure:

```php
$application->setStatus('interview');
```

we prefer explicit behavior:

```php
$application->scheduleInterview($interview);
```

The model should protect its own invariants.

This makes the code easier to reason about and allows the domain to be tested independently from Symfony, Doctrine, HTTP, or infrastructure.

---

# CQRS

Joblog uses **Command Query Responsibility Segregation** where it provides meaningful value.

Commands represent actions that change the system.

Examples:

```text
CreateJob
ImportJob
CreateApplication
SubmitApplication
ChangeApplicationStatus
ScheduleInterview
CompleteInterview
AddInterviewNote
CreateResumeVersion
```

Queries retrieve information without changing state.

Examples:

```text
GetJob
GetJobDetails
ListJobs
GetApplication
ListApplications
GetApplicationTimeline
GetDashboard
GetUpcomingInterviews
GetJobSearchStatistics
```

The basic flow is:

### Command

```text
HTTP
 ↓
Command
 ↓
Command Handler
 ↓
Domain
 ↓
Repository
 ↓
PostgreSQL
```

### Query

```text
HTTP
 ↓
Query
 ↓
Query Handler
 ↓
Read Repository
 ↓
Read Model
 ↓
Response
```

We don't introduce unnecessary infrastructure simply because a pattern exists.

Initially, commands and queries can use the same PostgreSQL database.

The separation happens at the **application level**.

If the system eventually requires independent read infrastructure, caching, replicas, or specialized projections, those options remain available without redesigning the domain.

---

# Example: Dashboard

The dashboard is a good example of where CQRS becomes useful.

A dashboard might need:

* Number of applications
* Applications by status
* Response rate
* Interview count
* Offers
* Upcoming interviews
* Follow-ups
* Recent activity

We don't want to load dozens of domain aggregates just to display a dashboard.

Instead:

```text
GetDashboard
      ↓
DashboardQueryHandler
      ↓
DashboardReadRepository
      ↓
DashboardReadModel
```

The query can retrieve exactly the data required by the UI.

---

# Technology Stack

## Backend

* **PHP**
* **Symfony**
* **PostgreSQL**
* **Doctrine**
* **Domain-Driven Design**
* **CQRS**

## Frontend

* **Next.js**
* **React**
* **TypeScript**
* **Vitest**
* **Playwright**

## Testing

* **PHPSpec**
* **Behat**
* **Vitest**
* **Playwright**

## Infrastructure

* **Docker**
* **AWS**
* **GitHub Actions**

---

# Testing Strategy

Testing is a fundamental part of the development process rather than something added at the end.

We use different testing tools for different levels of the system.

```text
                 Playwright
              End-to-End Tests
                    │
                 Behat
          Acceptance / Behavior
                    │
                PHPSpec
          Domain / Unit Tests
                    │
                Vitest
         Frontend Unit Tests
```

## PHPSpec

Used primarily for domain behavior and isolated backend logic.

Example:

```text
Application
 ├── can be submitted
 ├── cannot be submitted twice
 ├── can schedule an interview
 └── records relevant state changes
```

## Behat

Used for business-facing application behavior.

Example:

```gherkin
Feature: Submit a job application

  Scenario: Submitting an application
    Given I have saved a job
    And I have prepared my CV
    When I submit my application
    Then the application should be marked as submitted
    And the submission should appear in the application timeline
```

## Vitest

Used for frontend unit and component-level behavior.

## Playwright

Used for real end-to-end workflows.

For example:

```text
Login
 ↓
Save a job
 ↓
Create application
 ↓
Select CV
 ↓
Submit application
 ↓
View application timeline
```

---

# TDD

We use Test-Driven Development where appropriate:

```text
Red
 ↓
Green
 ↓
Refactor
```

The intention is not to maximize test coverage for its own sake.

Instead, tests help us:

* Define behavior before implementation
* Design better domain APIs
* Protect business rules
* Refactor safely
* Document expected behavior
* Catch regressions

---

# Infrastructure

Everything required for local development should be reproducible through Docker.

A simplified local environment looks like:

```text
┌───────────────────────┐
│       Browser         │
└───────────┬───────────┘
            │
      ┌─────▼─────┐
      │  Next.js  │
      └─────┬─────┘
            │
      ┌─────▼─────┐
      │  Symfony  │
      │    API    │
      └─────┬─────┘
            │
      ┌─────▼─────┐
      │ PostgreSQL│
      └───────────┘
```

Additional infrastructure can include:

```text
Symfony
   │
   ├── PostgreSQL
   ├── AWS S3
   ├── AWS SQS
   └── External APIs
```

Background processing can be used for tasks such as:

* Job description analysis
* Document processing
* AI requests
* Email processing
* Notifications

This keeps long-running work away from normal HTTP requests.

---

# AWS

Joblog is designed to run in AWS using managed services where they provide a clear operational benefit.

Potential infrastructure includes:

* Containerized application workloads
* Managed PostgreSQL
* S3 for documents
* SQS for asynchronous jobs
* IAM for permissions
* CloudWatch for logging and monitoring

The goal is not to use every AWS service available.

The goal is to understand how a production application can be deployed, monitored, secured, and operated in the cloud.

---

# CI/CD

GitHub Actions runs automated checks on every change.

A typical pipeline looks like:

```text
Pull Request
     │
     ├── Backend dependencies
     ├── Static analysis
     ├── PHPSpec
     ├── Behat
     ├── Frontend tests
     ├── Frontend build
     ├── Docker build
     └── Playwright
             │
             ▼
          Deploy
```

The exact pipeline will evolve with the application.

The important principle is that a change should pass automated checks before being considered production-ready.

---

# AI Architecture

AI integrations are kept behind application-level abstractions.

The domain should not know which AI provider is being used.

For example:

```text
JobAnalyzer
ResumeAnalyzer
InterviewQuestionGenerator
```

can represent application capabilities.

The implementation can then communicate with an external AI provider.

This keeps the rest of the application independent from a particular vendor and makes AI functionality easier to test and replace.

For example:

```text
Application
    │
    ▼
JobAnalyzer
    │
    ▼
AI Provider
```

rather than:

```text
Domain Model
    │
    └── AIProviderSDK
```

The second approach would couple business logic directly to infrastructure.

---

# Security

Security is considered part of the application design.

Areas we care about include:

* Authentication
* Authorization
* Secure password handling
* Input validation
* API security
* File upload validation
* Secrets management
* Least-privilege AWS permissions
* Protection of personal data
* Rate limiting
* Secure handling of third-party API credentials

Joblog contains personal career information, so protecting that information is especially important.

---

# Engineering Principles

Our development decisions are guided by a few principles.

### Domain first

Business rules should not depend on frameworks.

### Screaming Architecture

The repository should communicate what the application does.

### CQRS where useful

Separate commands and queries when doing so makes the system easier to understand or optimize.

### Explicit behavior

Prefer meaningful domain operations over generic setters.

### Dependency inversion

Infrastructure should implement application/domain contracts rather than dictate them.

### TDD

Use tests to drive behavior and protect important business rules.

### Boring infrastructure

Use complexity only when it solves a real problem.

### Build for the real user

Joblog exists because we actually have the problem it solves.

---

# Development Workflow

We aim to work in small, understandable increments.

A typical feature might look like:

```text
Problem
   ↓
Domain behavior
   ↓
Test
   ↓
Implementation
   ↓
API
   ↓
UI
   ↓
E2E test
   ↓
Review
   ↓
Deploy
```

This lets us continuously validate both the product and the architecture.

---

# Collaboration

Joblog is being developed by two developers:

### @romen2232

Developer and technical mentor.

Responsible for contributing to:

* Architecture
* Domain modeling
* Development
* Code reviews
* Technical decisions
* Mentoring and knowledge sharing

### @victrespy

Developer and collaborator.

Contributing to:

* Feature development
* Domain modeling
* Testing
* Architecture discussions
* Code reviews
* Continuous improvement

The mentoring aspect is intentional.

We want the project to be an environment where both developers contribute real production-quality code while sharing knowledge and improving engineering practices.

---

# Project Roadmap

The project is being developed incrementally.

## Phase 1 — Foundation

* [ ] Docker environment
* [ ] Symfony API
* [ ] Next.js application
* [ ] PostgreSQL
* [ ] Authentication
* [ ] CI pipeline
* [ ] Initial domain structure

## Phase 2 — Jobs

* [ ] Create job
* [ ] Edit job
* [ ] Save job URL
* [ ] Import job information
* [ ] Job details
* [ ] Job search/listing

## Phase 3 — Applications

* [ ] Create application
* [ ] Application statuses
* [ ] Application timeline
* [ ] Notes
* [ ] Follow-up dates
* [ ] Application dashboard

## Phase 4 — CV Management

* [ ] Upload CV
* [ ] CV versions
* [ ] Associate CV with application
* [ ] CV comparison
* [ ] AI-assisted CV analysis

## Phase 5 — Interviews

* [ ] Schedule interview
* [ ] Interview details
* [ ] Interview notes
* [ ] Questions
* [ ] Interview preparation

## Phase 6 — Intelligence

* [ ] Job ↔ CV analysis
* [ ] Application insights
* [ ] Search analytics
* [ ] AI-assisted interview preparation

## Phase 7 — Integrations

* [ ] Email integration
* [ ] Calendar integration
* [ ] Browser extension
* [ ] External job sources

---

# Architectural Decisions

Important architectural decisions are documented as ADRs.

Examples:

```text
docs/
└── adr/
    ├── 001-domain-driven-design.md
    ├── 002-screaming-architecture.md
    ├── 003-cqrs.md
    ├── 004-postgresql.md
    └── ...
```

This allows us to document not only **what** we decided, but **why**.

Architecture is expected to evolve as we learn more from the actual application.

---

# Why We Are Building Joblog

Joblog started from a practical problem.

We are looking for jobs, and managing that process involves a surprising amount of information.

Spreadsheets can track applications.

Notes can store interview preparation.

Bookmarks can save job postings.

Email contains recruiter conversations.

Calendars contain interviews.

CV files live somewhere else.

AI tools can help with individual tasks.

But none of these tools necessarily provide a coherent picture of the entire process.

We wanted to build something that does.

At the same time, Joblog gives us a realistic environment in which to practice software engineering beyond isolated tutorials and small coding exercises.

The project gives us opportunities to work with:

* Domain modeling
* APIs
* Databases
* Authentication
* Frontend architecture
* Distributed/background processing
* Cloud infrastructure
* CI/CD
* Automated testing
* AI integrations
* Observability
* Security
* Collaborative development

Most importantly, **we use the product ourselves**.

That means product decisions can be driven by real problems rather than hypothetical requirements.

---

# Project Philosophy

Joblog is not intended to be the biggest job platform.

It is not intended to replace LinkedIn, Indeed, or other job boards.

And it is not an excuse to add AI to every feature.

The objective is much simpler:

> **Build a genuinely useful product while demonstrating how we approach software engineering.**

We want the codebase to show how we think about:

```text
Problem
  ↓
Domain
  ↓
Architecture
  ↓
Implementation
  ↓
Tests
  ↓
Deployment
  ↓
Real-world feedback
  ↓
Iteration
```

That feedback loop is at the heart of the project.

---

# Repository Structure

At a high level:

```text
joblog/
├── api/
│   ├── src/
│   ├── tests/
│   └── ...
│
├── frontend/
│   ├── app/
│   ├── components/
│   ├── tests/
│   └── ...
│
├── infrastructure/
│   └── ...
│
├── docs/
│   └── adr/
│
├── docker/
│   └── ...
│
├── .github/
│   └── workflows/
│
├── compose.yaml
└── README.md
```

The exact structure will evolve alongside the architecture.

---

# License

This project is currently developed as a personal engineering and learning project.

License information will be added as the project matures.

---

## Built by

**@romen2232** · **@victrespy**

Two developers building a real application, learning from each other, and using the project to turn a real problem into an opportunity to practice professional software engineering.
