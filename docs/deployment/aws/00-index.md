# Aplika AWS Deployment Guide

> A learning-first, CLI-driven guide to deploying Aplika on AWS — built to prepare you for the AWS Certified Developer Associate exam.

## What This Guide Is

This guide walks you through deploying the full Aplika stack (Symfony API, Next.js frontend SSR, background worker, PostgreSQL, SQS, SES) on AWS using ECS Fargate, RDS, ALB, Route 53, and CloudWatch.

Every step explains the **why**. Every AWS concept is defined on first use. Every chapter maps to exam topics. The goal is not just "get it running" — it's **understand every decision** so you can answer exam questions about these services with confidence.

## Two Operating Modes

This guide uses a **sleep/wake model** — not a "leave it running" model. For the Developer Associate objective, the `deploy → use → inspect → teardown → rebuild` loop teaches more than paying AWS for an idle app.

### RUNNING Mode

```
  Route 53 ──► ALB ──► ECS [API, Worker, Frontend] ──► RDS
  You use Aplika. You learn AWS. ~$87/mo.
```

### SLEEPING Mode

```
  KEEP:  Route 53, ECR, SQS, SES, ACM, SSM, VPC, CFN templates
  STOP:  RDS (final snapshot)
  DELETE: ALB, ECS cluster/services, RDS instance, Route 53 records
  Cost: ~$1-3/mo. Wake = recreate infrastructure with CloudFormation.
```

See [Chapter 13: Cost, Sleep/Wake, and Teardown](13-cost-and-teardown.md) for the full two-stack architecture, `make sleep`/`make wake` specification, and cost breakdowns.

## Architecture Overview

```
                         ┌─────────────────────────────────────────────┐
                         │              AWS Account (us-east-1)        │
                         │                                             │
  Browser ──────────────►│  Route 53 (aplika.work)                    │
  (HTTPS)                │       │                                     │
                         │       ▼                                     │
                         │  ACM Certificate (*.aplika.work)            │
                         │       │                                     │
                         │       ▼                                     │
                         │  Application Load Balancer (ALB)            │
                         │    ├─ HTTPS :443 listener                   │
                         │    └─ HTTP :80 → 301 redirect               │
                         │       │           │                         │
                         │    app.aplika.work  api.aplika.work         │
                         │       │           │                         │
                         │       ▼           ▼                         │
                         │  ┌─────────┐  ┌──────────┐                 │
                         │  │ Frontend │  │ API +    │                 │
                         │  │ (SSR)   │  │ Nginx    │                 │
                         │  │ :3000   │  │ :9000/80 │                 │
                         │  └────┬────┘  └────┬─────┘                 │
                         │       │           │                         │
                         │       │     ┌─────▼─────┐                  │
                         │       │     │  Worker    │                  │
                         │       │     │ Messenger  │                  │
                         │       │     └─────┬─────┘                  │
                         │       │           │           │             │
                         │       ▼           ▼           ▼             │
                         │  ┌─────────┐ ┌────────┐ ┌────────┐        │
                         │  │ RDS PG  │ │  SQS   │ │  SES   │        │
                         │  │ t4g.μ   │ │ + DLQ  │ │ Email  │        │
                         │  └─────────┘ └────────┘ └────────┘        │
                         │                                             │
                         │  CloudWatch Logs + Alarms + Budgets         │
                         └─────────────────────────────────────────────┘
```

### Three ECS Fargate Services

| Service | Image | Purpose | CPU / Memory |
|---------|-------|---------|-------------|
| `api` | `aplika-api` (nginx + php-fpm) | HTTP API | 512 / 1024 MiB |
| `worker` | `aplika-api` (same image, different CMD) | Messenger consumer | 256 / 512 MiB |
| `frontend` | `aplika-frontend` (Next.js standalone) | SSR web app | 256 / 512 MiB |

## Decision Log

All key decisions with rationale. Numbers reference the user's locked decisions.

| # | Decision | Choice | Rationale |
|---|----------|--------|-----------|
| 1 | Purpose | Learning + AWS cert prep | Guide teaches every concept, not just deployment |
| 2 | Budget | Minimal — "Option A" | ECS Fargate (no EC2 management), single-AZ RDS, no CloudFront/ElastiCache/NAT |
| 3 | AWS account | Free Plan → Paid Plan | Free Plan credits cover initial learning; Paid Plan needed for Fargate (no free tier) |
| 4 | Region | us-east-1 | Cheapest region; ~120ms latency from Spain (acceptable for learning) |
| 5 | Environments | prod only | Simplifies learning; no multi-env complexity |
| 6 | Redis | Dropped for now | Filesystem/APCu cache + rate-limit store on single Fargate task; ElastiCache appendix |
| 7 | SQS | In main guide | Doctrine → SQS migration; heavily exam-relevant (queues, DLQ, visibility timeout) |
| 8 | VPC | Public subnets only, 2 AZ | No NAT ($32/mo saved); private subnets documented as theory |
| 9 | Domain | aplika.work | Route 53 hosted zone + NS delegation; ACM cert for TLS |
| 10 | Email | SES sandbox → production | Domain verification (DKIM); API transport recommended |
| 11 | IaC | Raw CloudFormation YAML | Teaching-first: every resource/property explained inline |
| 12 | CI/CD | GitHub Actions + OIDC | No long-lived keys; OIDC trust policy is exam topic |
| 13 | DB migrations | One-off ECS RunTask | Not in container entrypoint — explained why |
| 14 | Observability | CloudWatch Logs + Alarms + Budgets | 14-day retention; Budgets alert earns $20 credit |
| 15 | Deploy strategy | ECS rolling update | Blue/green as theory only |
| 16 | Teardown | First-class chapter | Full CFN teardown order; rebuild-as-practice loop |
| 17 | Exam mapping | Every chapter | Services/concepts covered + why they're on the exam |
| 18 | Sleep/wake model | Two-mode CFN split | `aplika-persistent` (cheap/stateful) + `aplika-runtime` (billable); user-directed for cert study loop |

## Total Cost Estimate

> **Estimate — verify in AWS Pricing Calculator.** Prices are for us-east-1 on-demand Linux/x86.

### RUNNING Mode (~$87/mo)

| Service | Configuration | Monthly Estimate |
|---------|--------------|-----------------|
| ECS Fargate (api) | 0.5 vCPU, 1 GB, 24/7 | ~$29.50 |
| ECS Fargate (worker) | 0.25 vCPU, 0.5 GB, 24/7 | ~$8.70 |
| ECS Fargate (frontend) | 0.25 vCPU, 0.5 GB, 24/7 | ~$8.70 |
| RDS PostgreSQL | db.t4g.micro, Single-AZ, 20 GB gp3 | ~$12.20 |
| ALB | 1 ALB + ~1 LCU avg | ~$22.00 |
| Public IPv4 (4-5 addresses) | $0.005/hr per address (since Feb 2024) | ~$14.60 |
| ECR | ~500 MB storage | ~$0.05 |
| Route 53 | 1 hosted zone | ~$0.50 |
| SQS | ~10K requests/month | ~$0.00 (Always Free) |
| SES | ~100 emails/month | ~$0.00 (sandbox free) |
| CloudWatch Logs | ~5 GB/month, 14-day retention | ~$3.60 |
| CloudWatch Alarms | 5 alarms | ~$0.50 |
| Data Transfer | ~10 GB outbound | ~$0.90 |
| **Total** | | **~$86.65/mo** |

### SLEEPING Mode (~$1-3/mo)

Route 53 ($0.50) + ECR (~$0.05) + RDS snapshot (~$0.23) + CloudWatch Logs (decaying) = **~$0.78/mo**.

### FULL TEARDOWN (~$0/mo)

Only domain registration at registrar (~$3/yr) if the persistent stack is also deleted.

With $200 in Free Tier credits and sleep discipline, you can study for **months** — not 2.3 months of 24/7 drain.

## How to Use This Guide

### Recommended Learning Cadence

```
make wake  →  Study a chapter  →  Inspect live resources  →  make sleep
```

Wake, study, experiment, sleep. Each cycle costs pennies and reinforces every exam concept. Don't leave the stack running overnight — the sleep/wake loop IS the study method.

### Prerequisites Checklist

- [ ] No AWS account yet (Chapter 02 covers signup)
- [ ] Domain `aplika.work` purchased
- [ ] Docker and Docker Compose installed locally
- [ ] Git, Make, and a terminal
- [ ] GitHub repository for Aplika (for CI/CD chapter)
- [ ] Basic familiarity with Docker, HTTP, and SQL

### Recommended Order

Read chapters sequentially. Each chapter builds on the previous one.

| Chapter | Title | Duration | Cumulative Cost |
|---------|-------|----------|----------------|
| 01 | Code Prerequisites | 2-4 hours (local only) | $0 |
| 02 | AWS Account & IAM | 1-2 hours | $0 (credits) |
| 03 | VPC & Networking | 30-60 min | ~$0 |
| 04 | RDS PostgreSQL | 30-60 min | ~$12/mo |
| 05 | ECR & Images | 30-60 min | ~$0.05/mo |
| 06 | ECS Services | 2-3 hours | ~$47/mo |
| 07 | SQS & Worker | 1-2 hours | ~$0 |
| 08 | Route 53 & TLS | 1-2 hours | ~$23/mo |
| 09 | SES Email | 30-60 min | ~$0 |
| 10 | CI/CD with GitHub OIDC | 1-2 hours | ~$0 |
| 11 | Observability | 1-2 hours | ~$4/mo |
| 12 | Migrations & Releases | 30-60 min | ~$0 |
| 13 | Cost, Sleep/Wake, and Teardown | 30-60 min | $87→$1-3/mo (sleep!) |
| 14 | Appendix: ElastiCache | Reference only | +$12/mo when added |
| 15 | Appendix: Exam Map | Reference only | $0 |

### Parameterization

Throughout this guide, these parameters are used consistently. Change them for your own deployment:

| Parameter | Default Value | Where to Change |
|-----------|--------------|-----------------|
| `domain` | `aplika.work` | Route 53, ACM, ECS env vars |
| `region` | `us-east-1` | AWS CLI `--region`, CFN parameters |
| `stack-prefix` | `aplika` | CloudFormation stack names |
| `ecr-prefix` | `<account-id>.dkr.ecr.us-east-1.amazonaws.com/aplika` | ECR image URIs |

## Next Step

Start with [Chapter 01: Code Prerequisites](01-phase0-code-prerequisites.md) — make every code change needed before touching AWS.
