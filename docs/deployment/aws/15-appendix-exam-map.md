# Appendix B: AWS Certified Developer Associate Exam Map

> Full topic matrix mapping exam domains to guide chapters, plus next learning paths.

---

## Exam Domains

The AWS Certified Developer Associate (DVA-C02) exam covers 5 domains:

| Domain | Weight | Description |
|--------|--------|-------------|
| **Domain 1** | 32% | Development with AWS Services |
| **Domain 2** | 26% | Security |
| **Domain 3** | 24% | Deployment |
| **Domain 4** | 18% | Troubleshooting and Optimization |

---

## Topic Matrix

### Domain 1: Development with AWS Services (32%)

| Topic | Guide Chapter | What You Learned |
|-------|--------------|------------------|
| **Lambda** | 02 (side-quest) | Runtime, handler, invocation, IAM execution role |
| **API Gateway** | Not covered | Next learning chapter |
| **ECS / Fargate** | 06 | Clusters, tasks, services, task definitions, Fargate sizing |
| **SQS** | 07 | Queues, DLQ, visibility timeout, long polling, redrive policy |
| **SNS** | 11 | Alarm notifications, subscriptions |
| **SES** | 09 | Domain verification, DKIM, sandbox vs production, SMTP vs API |
| **DynamoDB** | Not covered | Next learning chapter |
| **S3** | Not covered | Planned for file storage |
| **Step Functions** | Not covered | Next learning chapter |
| **EventBridge** | Not covered | Next learning chapter |
| **CloudWatch** | 11 | Logs, metrics, alarms, dashboards |
| **X-Ray** | 11 | Distributed tracing, segments |

### Domain 2: Security (26%)

| Topic | Guide Chapter | What You Learned |
|-------|--------------|------------------|
| **IAM users, groups, roles** | 02 | Least privilege, policies, trust policies |
| **IAM policies** | 02, 06, 10 | Inline vs managed, resource-based |
| **STS / AssumeRole** | 10 | OIDC federation, AssumeRoleWithWebIdentity |
| **KMS** | 04 | RDS encryption at rest |
| **SSM Parameter Store** | 04 | SecureString, parameter paths |
| **Secrets Manager** | Not covered | Next learning chapter (rotation) |
| **ACM** | 08 | Certificate validation, ALB integration |
| **Security groups** | 03 | Stateful firewalls, SG chaining |
| **NACLs** | 03 (theory) | Stateless firewalls, subnet-level |
| **Cognito** | Not covered | Next learning chapter |
| **WAF** | Not covered | Next learning chapter |

### Domain 3: Deployment (24%)

| Topic | Guide Chapter | What You Learned |
|-------|--------------|------------------|
| **CloudFormation** | 03, 04, 06, 07, 11, 13 | Templates, stacks, parameters, outputs, dependencies, DeletionPolicy, stack lifecycle |
| **ECS deployment** | 06, 13 | Rolling updates, MaximumPercent, MinimumHealthyPercent, desired-count scaling (0 = pause) |
| **Blue/green** | 06 (theory) | CodeDeploy integration |
| **CI/CD** | 10 | GitHub Actions, OIDC, build-push-deploy pipeline |
| **Elastic Beanstalk** | Not covered | Next learning chapter |
| **SAM** | Not covered | Next learning chapter |
| **CDK** | Not covered | Next learning chapter |
| **Docker** | 01, 05 | Multi-stage builds, .dockerignore, image optimization |

### Domain 4: Troubleshooting and Optimization (18%)

| Topic | Guide Chapter | What You Learned |
|-------|--------------|------------------|
| **CloudWatch Logs** | 11 | Log groups, retention, filtering |
| **CloudWatch Metrics** | 11 | Custom metrics, dashboards |
| **X-Ray** | 11 | Tracing, annotations |
| **Cost Explorer** | 13 | Cost visibility, filtering |
| **Budgets** | 02, 11, 13 | Cost alerts, thresholds, sleep-mode guard |
| **Public IPv4 surcharge** | 13 | $0.005/hr per address (Feb 2024 change) |
| **RDS snapshots & restore** | 04, 13 | DeletionPolicy: Snapshot, DBSnapshotIdentifier, 7-day stop rule |
| **Trusted Advisor** | Not covered | Console exploration |
| **Performance** | 06 | Fargate sizing, right-sizing |

---

## Services Covered in This Guide

| Service | Depth | Exam Relevance |
|---------|-------|----------------|
| ECS / Fargate | Deep | ⭐⭐⭐⭐⭐ |
| IAM | Deep | ⭐⭐⭐⭐⭐ |
| SQS | Deep | ⭐⭐⭐⭐⭐ |
| CloudFormation | Deep | ⭐⭐⭐⭐⭐ |
| CloudWatch | Medium | ⭐⭐⭐⭐ |
| RDS | Medium | ⭐⭐⭐⭐ |
| Route 53 | Medium | ⭐⭐⭐⭐ |
| SES | Medium | ⭐⭐⭐ |
| ACM | Medium | ⭐⭐⭐ |
| ECR | Medium | ⭐⭐⭐ |
| SSM Parameter Store | Medium | ⭐⭐⭐ |
| Lambda | Shallow | ⭐⭐⭐ |
| SNS | Shallow | ⭐⭐ |
| X-Ray | Shallow | ⭐⭐ |
| Budgets | Shallow | ⭐⭐ |

### Key Concepts Beyond Service Names

| Concept | Depth | Guide Chapter |
|---------|-------|--------------|
| DeletionPolicy (Delete/Retain/Snapshot) | Deep | 13 |
| Stack lifecycle and cross-stack dependencies | Deep | 13 |
| RDS snapshot/restore vs stop/start (7-day rule) | Deep | 04, 13 |
| ECS desired-count scaling (0 = pause) | Deep | 13 |
| Public IPv4 surcharge ($0.005/hr since Feb 2024) | Medium | 13 |
| IAM trust policies (OIDC federation) | Deep | 10 |
| Task role vs execution role | Deep | 06 |

---

## Next Learning Chapters

After mastering this guide, expand your knowledge with these topics:

### 1. Private Subnets + NAT Gateway
- **Why:** Production security best practice
- **Cost:** +$32/month (NAT gateway)
- **Topics:** Private subnets, NAT gateway, route tables, VPC endpoints

### 2. Blue/Green with CodeDeploy
- **Why:** Zero-downtime deployments with instant rollback
- **Cost:** ~$0 (CodeDeploy is free for ECS)
- **Topics:** CodeDeploy, deployment groups, traffic shifting, rollback

### 3. Lambda + API Gateway
- **Why:** Serverless patterns for specific workloads
- **Cost:** Always Free tier available
- **Topics:** Lambda runtime, API Gateway REST/HTTP, authorizers, stages

### 4. CloudFront + S3
- **Why:** CDN for static assets; S3 for file storage
- **Cost:** CloudFront has free tier; S3 is cheap
- **Topics:** Distributions, origins, behaviors, signed URLs, S3 policies

### 5. Secrets Manager Rotation
- **Why:** Automatic credential rotation for production
- **Cost:** $0.40/secret/month
- **Topics:** Secrets, rotation Lambda, RDS integration

### 6. Multi-Account Strategy
- **Why:** Production isolation, security boundaries
- **Cost:** Free (Organizations)
- **Topics:** AWS Organizations, SCPs, cross-account roles, landing zone

### 7. DynamoDB
- **Why:** Serverless NoSQL for specific access patterns
- **Cost:** Always Free tier (25 GB)
- **Topics:** Tables, items, partition keys, GSI/LSI, DynamoDB Streams

### 8. EventBridge
- **Why:** Event-driven architecture
- **Cost:** Always Free tier
- **Topics:** Rules, targets, event patterns, custom events

### 9. Cognito
- **Why:** Managed user authentication
- **Cost:** 50,000 MAUs free
- **Topics:** User pools, identity pools, JWT tokens, hosted UI

### 10. WAF
- **Why:** Web application firewall for ALB/CloudFront
- **Cost:** $5/web ACL/month + $1/rule/month
- **Topics:** Rules, rule groups, IP sets, rate limiting

---

## Study Tips

1. **Rebuild the stack 3+ times** — each rebuild reinforces dependencies and ordering
2. **Break things on purpose** — delete a security group, stop a task, corrupt a migration
3. **Use AWS Skill Builder** — free official courses and labs
4. **Practice with AWS FAQs** — exam questions often come from FAQ content
5. **Know the "why"** — the exam tests understanding, not memorization
6. **Focus on IAM and ECS** — these are the highest-weighted topics covered in depth

---

## Recommended Resources

| Resource | Type | Cost |
|----------|------|------|
| [AWS Skill Builder](https://skillbuilder.aws) | Free courses | Free |
| [AWS Documentation](https://docs.aws.amazon.com) | Reference | Free |
| [AWS FAQs](https://aws.amazon.com/faqs) | Exam prep | Free |
| [AWS Well-Architected](https://aws.amazon.com/architecture/well-architected) | Best practices | Free |
| [Tutorials Dojo](https://tutorialsdojo.com) | Practice exams | ~$15 |
| [Stephane Maarek](https://www.udemy.com/user/stephane-maarek/) | Video course | ~$15 |

---

## Final Notes

This guide covered the deployment of a real application on AWS. Every decision was made with both cost and learning in mind. The architecture is intentionally simple — no private subnets, no NAT, no multi-AZ — but every concept taught here scales to production.

The most important thing you learned isn't how to deploy Aplika. It's **how AWS services work together**. That understanding is what the Developer Associate exam tests, and what makes you effective as a cloud developer.

Good luck on the exam. 🎯
