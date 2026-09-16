# Chapter 13: Cost, Sleep/Wake, and Teardown

> Master the two-mode operating model: RUNNING (use Aplika, learn AWS) and SLEEPING (cost near zero, infrastructure preserved in IaC). Also covers full teardown for when you're done studying.

**Estimated duration:** 30-60 minutes | **Cost impact:** Learn to drop from ~$87/mo to ~$1-3/mo between study sessions

---

## The Two Modes

For the Developer Associate objective, a **deploy → use → inspect → teardown → rebuild** loop teaches more than leaving everything running 24/7. You learn CloudFormation, ECS, networking, IAM, ECR, RDS, and ALB — every time you wake.

```
┌─────────────────────────────────────────────────────────────────────┐
│                         RUNNING MODE                                │
│                                                                     │
│  Route 53 ──► ALB ──► ECS [API, Worker, Frontend] ──► RDS          │
│                                                                     │
│  You use Aplika. You learn AWS. You pay ~$87/mo.                   │
└─────────────────────────────────────────────────────────────────────┘

                              ▼ make sleep

┌─────────────────────────────────────────────────────────────────────┐
│                         SLEEPING MODE                               │
│                                                                     │
│  KEEP: Route 53, ECR, SQS, SES, ACM, SSM, VPC, CFN templates      │
│  STOP: RDS (final snapshot)                                         │
│  DELETE: ALB, ECS cluster/services, RDS instance, Route 53 records  │
│                                                                     │
│  Cost: ~$1-3/mo. Infrastructure preserved in CloudFormation.        │
└─────────────────────────────────────────────────────────────────────┘

                              ▼ make wake

┌─────────────────────────────────────────────────────────────────────┐
│                         RUNNING MODE (again)                        │
│                                                                     │
│  CloudFormation recreates: ALB, ECS services, RDS (from snapshot),  │
│  Route 53 alias records. Minutes to full stack.                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Why This Is Better Than 24/7

| Approach | Monthly Cost | Learning Value |
|----------|-------------|----------------|
| Leave everything running | ~$87/mo | Passive — you forget what's running |
| **Sleep/wake cycle** | **~$1-3/mo sleeping** | **Active — each rebuild reinforces exam knowledge** |
| Full teardown every time | ~$0.50/mo | Maximum learning, but slower wake (no snapshot) |

With sleep discipline, $200 in credits lasts **months** of active study — not 2.3 months of idle drain.

---

## Two-Stack Architecture

The sleep/wake model requires splitting CloudFormation into two stacks with different lifecycles.

### `aplika-persistent` Stack (lives forever, ~$1-3/mo)

Resources that are cheap to keep and expensive/slow to recreate:

| Resource | Why Persistent | Monthly Cost |
|----------|---------------|-------------|
| VPC, Subnets, IGW, Security Groups | $0 cost; recreating is slow and error-prone | $0 |
| Route 53 Hosted Zone | $0.50/mo; deleting loses DNS config | $0.50 |
| ECR Repositories | ~$0.05/mo; images persist | ~$0.05 |
| SQS Queue + DLQ | Always Free (1M requests/mo) | $0 |
| SES Domain Identity + DKIM | Free; re-verification takes DNS propagation | $0 |
| SSM Parameters (secrets) | Free (standard tier) | $0 |
| ACM Certificate | Free; but re-validation takes 5-30 min | $0 |
| CloudWatch Log Groups | Retain logs across sleep/wake; retention limits cost | ~$0.50 |

> **VPC placement decision:** The VPC costs $0 and has no billable resources of its own. Keeping it persistent means the runtime stack doesn't need to recreate subnets, route tables, and security groups every wake. This simplifies the runtime stack and reduces wake time. The tradeoff is a slightly larger persistent stack — but since it never changes, this is acceptable.

> **CloudWatch Logs decision:** Log groups are kept in the persistent stack with `DeletionPolicy: Retain`. When the runtime stack is deleted, the log groups survive. This preserves your logs for debugging and audit. The cost is minimal (~$0.50/mo for 14-day retention on ~5 GB). If you want zero log cost, delete log groups manually or set retention to 1 day.

### `aplika-runtime` Stack (created on wake, deleted on sleep, ~$85/mo)

Resources that are billable and only needed while using Aplika:

| Resource | Why Runtime | Monthly Cost |
|----------|------------|-------------|
| ALB + Listeners + Target Groups | ~$22/mo; only needed for live traffic | ~$22 |
| ECS Cluster + Services + Task Definitions | Fargate compute; zero tasks = ~$0 | ~$36 |
| RDS PostgreSQL (from snapshot) | ~$12/mo; only needed for live data | ~$12 |
| Route 53 Alias Records (→ ALB) | Must be recreated each wake — ALB DNS name changes | $0 |
| ECS Task/Execution IAM Roles | In persistent stack or runtime — decide based on coupling | $0 |
| CloudWatch Alarms | Die with runtime stack; re-created each wake | ~$0.50 |
| Budgets Alert | Persistent (separate from stacks) | $0 |

> **Route 53 alias records in runtime stack:** Each time you create an ALB, AWS assigns a new DNS name. The Route 53 alias records must point to this new DNS name. Therefore, alias records live in the runtime stack and are recreated on every wake.

---

## Stack Assignment Map (Cross-Reference)

Each chapter's resources are assigned to a stack. This keeps the guide coherent:

| Chapter | Resource | Stack |
|---------|----------|-------|
| 03 | VPC, Subnets, IGW, Security Groups | **persistent** |
| 04 | RDS DBInstance, DBSubnetGroup | **runtime** (with snapshot) |
| 04 | RDS Security Group | **persistent** (in VPC stack) |
| 04 | SSM Parameters | **persistent** |
| 05 | ECR Repositories | **persistent** |
| 06 | ECS Cluster, Services, Task Definitions | **runtime** |
| 06 | ALB, Listeners, Target Groups | **runtime** |
| 06 | Execution Role, Task Role | **persistent** (IAM is global, cheap) |
| 06 | CloudWatch Log Groups | **persistent** (with DeletionPolicy: Retain) |
| 07 | SQS Queue + DLQ | **persistent** |
| 08 | Route 53 Hosted Zone | **persistent** |
| 08 | Route 53 Alias Records | **runtime** (ALB DNS changes each wake) |
| 08 | ACM Certificate | **persistent** |
| 09 | SES Domain Identity | **persistent** |
| 11 | CloudWatch Alarms | **runtime** (re-created each wake) |
| 11 | Budgets Alert | **persistent** (standalone, not in either stack) |

---

## RDS Sleep Strategy — Two Approaches

### Approach 1: Stop/Start (Short Sleeps)

```bash
# Stop the RDS instance (keeps it, just pauses compute)
aws rds stop-db-instance --db-instance-identifier aplika-db

# Start it again
aws rds start-db-instance --db-instance-identifier aplika-db
```

> **WARNING: RDS auto-restarts after 7 days.** If you stop an RDS instance and don't start it within 7 consecutive days, AWS automatically restarts it — and billing resumes. This is documented in the [official RDS docs](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/USER_StopInstance.html): *"If you don't manually start your DB instance after it is stopped for seven consecutive days, RDS automatically starts your DB instance for you."*

| Aspect | Stop/Start | Snapshot/Delete |
|--------|-----------|-----------------|
| **Max sleep duration** | 7 days | Unlimited |
| **Wake time** | ~1-2 min | ~5-10 min (restore from snapshot) |
| **Storage cost during sleep** | Provisioned storage still billed (~$2.30/mo for 20 GB gp3) | Snapshot storage only (incremental, ~$0.50-1/mo) |
| **Complexity** | Simple CLI | Requires DeletionPolicy + parameter |
| **Risk** | Auto-restart after 7 days = surprise charges | None — snapshot persists indefinitely |

**Recommendation:** Use stop/start only for overnight pauses. For anything longer, use the snapshot approach.

### Approach 2: Snapshot + Delete via CloudFormation (Recommended Default)

This is the approach built into the two-stack model.

> **Exam note: `DeletionPolicy: Snapshot`** is a CloudFormation attribute that controls what happens to a resource when its stack is deleted. For RDS, the default (when no `DBClusterIdentifier` is set) is already `Snapshot` — CloudFormation creates a final snapshot before deleting the instance. The snapshot persists until you explicitly delete it.

```yaml
# In the runtime stack:
DBInstance:
  Type: AWS::RDS::DBInstance
  DeletionPolicy: Snapshot           # Create final snapshot on stack delete
  UpdateReplacePolicy: Snapshot      # Also snapshot on replacement during update
  Properties:
    DBInstanceIdentifier: !Sub '${EnvironmentName}-db'
    DBSnapshotIdentifier: !Ref DBSnapshotId   # Restore from snapshot (empty = new DB)
    Engine: postgres
    EngineVersion: '16'
    DBInstanceClass: db.t4g.micro
    AllocatedStorage: 20
    StorageType: gp3
    StorageEncrypted: true
    MasterUsername: aplika
    MasterUserPassword: !Ref DBPassword
    DBSubnetGroupName: !Ref DBSubnetGroup
    VPCSecurityGroups:
      - !ImportValue aplika-vpc-RDSSecurityGroupId
    PubliclyAccessible: true
    MultiAZ: false
    BackupRetentionPeriod: 7
    DeletionProtection: false
```

**How it works:**

1. **Sleep:** `aws cloudformation delete-stack --stack-name aplika-runtime`
   - CloudFormation sees `DeletionPolicy: Snapshot` on the RDS resource
   - It creates a final snapshot (auto-named, e.g., `rds:aplika-db-2026-09-15-14-30`)
   - Then deletes the RDS instance, ALB, ECS services, and alias records
   - Snapshot persists in S3 (incremental, billed per GB-month)

2. **Wake:** `aws cloudformation deploy --template-file runtime.yaml --parameter-overrides DBSnapshotId=<snapshot-name>`
   - CloudFormation creates a new RDS instance from the snapshot
   - Recreates ALB, ECS services, Route 53 alias records
   - Stack is live in ~5-10 minutes

> **Snapshot storage cost:** RDS snapshots are incremental — only changed blocks are stored. For a 20 GB database with ~2 GB of data, expect ~$0.23/mo (2 GB × $0.115/GB-mo for gp3 snapshot storage). AWS provides free backup storage equal to 100% of your total provisioned database storage across all instances in a region — so if you have no running RDS instances, the snapshot may be partially or fully covered.

---

## `make sleep` / `make wake` Specification

> **Documented for later implementation. NOT implemented now.** These Makefile targets will be added after the two-stack CloudFormation templates are created.

### `make sleep`

```makefile
# Target: make sleep
# Purpose: Enter SLEEPING mode — delete runtime infrastructure, preserve state
#
# Steps:
#   1. (Optional) Record the current RDS snapshot name for reference
#   2. Delete the runtime CloudFormation stack
#      - This triggers DeletionPolicy: Snapshot on RDS (creates final snapshot)
#      - Deletes: ALB, ECS services/cluster, RDS instance, Route 53 alias records, alarms
#      - Preserves: RDS snapshot, ECR images, SQS queues, SES identity, SSM params, VPC
#   3. Wait for stack deletion to complete
#   4. Verify: no ALB, no ECS tasks, no RDS instance
#   5. Print sleeping-mode cost summary
#
# Example Makefile content:
sleep: ## Enter SLEEPING mode (delete runtime stack, preserve state)
	@echo "💤 Entering SLEEPING mode..."
	@SNAPSHOT=$$(aws rds describe-db-snapshots \
		--db-instance-identifier aplika-db \
		--query 'reverse(sort_by(DBSnapshots, &SnapshotCreateTime))[0].DBSnapshotIdentifier' \
		--output text 2>/dev/null || echo "none"); \
	echo "  Latest snapshot: $$SNAPSHOT"
	aws cloudformation delete-stack --stack-name aplika-runtime
	@echo "  Waiting for stack deletion..."
	aws cloudformation wait stack-delete-complete --stack-name aplika-runtime
	@echo ""
	@echo "💤 SLEEPING. Estimated cost: ~\$1-3/mo"
	@echo "   Preserved: Route 53, ECR, SQS, SES, SSM, VPC, RDS snapshot"
	@echo "   Run 'make wake' to resume"
```

### `make wake`

```makefile
# Target: make wake
# Purpose: Enter RUNNING mode — recreate runtime infrastructure from IaC
#
# Steps:
#   1. Find the latest RDS snapshot for aplika-db
#   2. Deploy the runtime CloudFormation stack with DBSnapshotIdentifier
#      - Creates: ALB, ECS services/cluster, RDS (from snapshot), Route 53 alias records, alarms
#   3. Wait for stack creation to complete
#   4. Verify: ALB health, ECS tasks running, RDS available
#   5. Print running-mode summary with ALB DNS and service URLs
#
# Example Makefile content:
wake: ## Enter RUNNING mode (recreate runtime stack from IaC)
	@echo "☀️  Entering RUNNING mode..."
	@SNAPSHOT=$$(aws rds describe-db-snapshots \
		--db-instance-identifier aplika-db \
		--snapshot-type manual \
		--query 'reverse(sort_by(DBSnapshots, &SnapshotCreateTime))[0].DBSnapshotIdentifier' \
		--output text 2>/dev/null || echo ""); \
	if [ -z "$$SNAPSHOT" ] || [ "$$SNAPSHOT" = "None" ]; then \
		echo "  No snapshot found — creating new RDS instance"; \
		DEPLOY_ARGS=""; \
	else \
		echo "  Restoring from snapshot: $$SNAPSHOT"; \
		DEPLOY_ARGS="--parameter-overrides DBSnapshotId=$$SNAPSHOT"; \
	fi; \
	aws cloudformation deploy \
		--template-file runtime.yaml \
		--stack-name aplika-runtime \
		--region us-east-1 \
		$$DEPLOY_ARGS \
		--capabilities CAPABILITY_NAMED_IAM
	@echo ""
	@echo "☀️  RUNNING. Estimated cost: ~\$87/mo"
	@echo "   Run 'make sleep' when done studying"
```

---

## Cost Tables by Mode

### RUNNING Mode (~$87/mo)

> **Estimate — verify in AWS Pricing Calculator.**

| Service | Monthly Estimate |
|---------|-----------------|
| ECS Fargate (3 tasks) | ~$36.05 |
| RDS db.t4g.micro | ~$12.20 |
| ALB + 1 LCU | ~$22.00 |
| **Public IPv4 addresses** (4-5 × $3.65) | ~$14.60 |
| CloudWatch Logs | ~$3.60 |
| Data Transfer | ~$0.90 |
| Route 53 | $0.50 |
| ECR + CloudWatch Alarms | ~$0.55 |
| **Total** | **~$86.65/mo** |

> **Public IPv4 charges ($0.005/hr per address):** Since February 2024, AWS charges $0.005/hour ($3.65/month) for every public IPv4 address — on EC2, ALB, RDS, NAT Gateway, and ECS Fargate task ENIs. In RUNNING mode, each Fargate task gets a public IP (we're in public subnets with `AssignPublicIp: ENABLED`), plus the ALB has one. That's ~4-5 public IPs = ~$14.60-18.25/mo. This cost disappears entirely in SLEEPING mode.

### SLEEPING Mode (~$1-3/mo)

| Service | Monthly Estimate |
|---------|-----------------|
| Route 53 hosted zone | $0.50 |
| ECR storage (~500 MB) | ~$0.05 |
| RDS snapshot storage (~2 GB incremental) | ~$0.23 |
| CloudWatch Logs (14-day retention, no new ingestion) | ~$0 (decays to 0) |
| SQS (Always Free) | $0 |
| SES (Always Free tier) | $0 |
| SSM Parameters (standard, free) | $0 |
| ACM Certificate (free) | $0 |
| **Total** | **~$0.78/mo** |

> **No public IPv4 charges in sleep mode:** No ALB, no Fargate tasks, no RDS instance = no public IPs allocated.

### FULL TEARDOWN (~$0/mo)

Delete the persistent stack too. Only the domain registration at your registrar survives (~$3/yr). Route 53 hosted zone is deleted. ECR images are gone. Everything starts from zero to rebuild.

---

## Full Teardown (Graduation Rehearsal)

When you're done studying for good, delete both stacks:

```bash
# 1. Delete runtime stack (if not already sleeping)
aws cloudformation delete-stack --stack-name aplika-runtime
aws cloudformation wait stack-delete-complete --stack-name aplika-runtime

# 2. Delete persistent stack
aws cloudformation delete-stack --stack-name aplika-persistent
aws cloudformation wait stack-delete-complete --stack-name aplika-persistent

# 3. Delete standalone resources not in either stack
aws budgets delete-budget \
  --account-id $(aws sts get-caller-identity --query Account --output text) \
  --budget-name aplika-monthly

# 4. Delete IAM roles (if not in persistent stack)
aws iam delete-role-policy --role-name aplika-task-role --policy-name SQSAccess || true
aws iam delete-role-policy --role-name aplika-task-role --policy-name SESAccess || true
aws iam delete-role --role-name aplika-task-role || true
aws iam detach-role-policy --role-name aplika-execution-role \
  --policy-arn arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy || true
aws iam delete-role-policy --role-name aplika-execution-role --policy-name SSMReadAccess || true
aws iam delete-role --role-name aplika-execution-role || true

# 5. Delete OIDC provider (if created)
aws iam delete-open-id-connect-provider \
  --open-id-connect-provider-arn $(aws iam list-open-id-connect-providers \
    --query 'OpenIDConnectProviderList[0].Arn' --output text) || true

echo "✅ Full teardown complete. Only domain registration remains."
```

---

## What Survives Each Operation

| Resource | Sleep | Full Teardown |
|----------|-------|---------------|
| Route 53 hosted zone | ✅ Keep | ❌ Deleted |
| ECR images | ✅ Keep | ❌ Deleted |
| RDS snapshot | ✅ Created automatically | ❌ Deleted (manual snapshots persist) |
| SQS queues | ✅ Keep | ❌ Deleted |
| SES identity | ✅ Keep | ❌ Deleted |
| SSM parameters | ✅ Keep | ❌ Deleted |
| VPC/subnets/SGs | ✅ Keep | ❌ Deleted |
| ACM certificate | ✅ Keep | ✅ Persists (not in stack) |
| IAM roles | ✅ Keep | ❌ Deleted |
| OIDC provider | ✅ Keep | ❌ Must delete manually |
| CloudWatch Logs | ✅ Keep (DeletionPolicy: Retain) | ❌ Deleted |
| ECS tasks/ALB/RDS | ❌ Deleted | ❌ Deleted |

---

## Rebuild-as-Practice Loop

The learning cadence:

```
1. make wake                    ← recreate everything from IaC
2. Study a chapter              ← inspect live resources in the console
3. Experiment, break things     ← learn how services interact
4. make sleep                   ← save money, preserve state
5. Repeat from step 1 with next chapter
```

Each rebuild reinforces:
- CloudFormation stack dependencies
- ECS task definition structure
- ALB target group health checks
- RDS snapshot/restore lifecycle
- IAM role trust policies
- Route 53 alias record mechanics

> **Exam tip:** After 3+ rebuild cycles, you'll know the dependency order cold. This is directly tested on the exam.

---

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| **Stack deletion stuck** | RDS snapshot in progress; ENI not released | Wait for snapshot to complete; check for orphaned ENIs in VPC console |
| **RDS restore fails** | Snapshot identifier wrong or deleted | List snapshots: `aws rds describe-db-snapshots --snapshot-type manual` |
| **Alias record points at dead ALB** | Runtime stack deleted but alias record cached | Normal — TTL expires; new alias created on next wake |
| **CI/CD pipeline fails while sleeping** | `aws ecs update-service` can't find service | Wake first (cross-ref Chapter 10) |
| **RDS auto-restarted after 7 days** | Forgot about a stopped instance | Use snapshot approach for long sleeps; set Budgets alarm to catch surprises |
| **Wake takes 15+ minutes** | RDS restore is slow for large snapshots | Normal for first wake; subsequent wakes may be faster |
| **Public IPv4 charges appear** | Fargate tasks have `AssignPublicIp: ENABLED` | Expected in RUNNING mode; disappears in SLEEPING mode |

---

## Developer Associate Exam Mapping

| Concept | Why It's on the Exam |
|---------|---------------------|
| DeletionPolicy (Delete/Retain/Snapshot) | CloudFormation lifecycle management |
| Stack dependencies | IaC ordering and cross-stack references |
| RDS snapshot/restore | Backup and recovery |
| RDS stop/start (7-day rule) | Cost optimization gotcha |
| ECS desired-count scaling | Service scaling (setting to 0 = pause) |
| Public IPv4 surcharge | Cost awareness (Feb 2024 change) |
| Cost Explorer + Budgets | Cost monitoring |

## Next Step

[Appendix A: ElastiCache](14-appendix-elasticache.md) — when and how to add Redis back.
