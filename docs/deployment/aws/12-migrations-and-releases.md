# Chapter 12: Migrations & Releases

> Manage database migrations as one-off ECS tasks and understand the release sequence.

**Estimated duration:** 30-60 minutes | **Cost:** ~$0

---

## Why Not Run Migrations in the Container Entrypoint?

A common anti-pattern is running `doctrine:migrations:migrate` in the Docker entrypoint before starting php-fpm. This is dangerous because:

| Problem | Consequence |
|---------|-------------|
| **Race conditions** | Multiple tasks starting simultaneously run migrations concurrently |
| **Rollback complexity** | If the app crashes after migration but before serving, the DB is ahead of the code |
| **Startup time** | Migrations add unpredictable delay to task startup |
| **Idempotency** | Not all migrations are safe to run multiple times |

**The correct pattern:** Run migrations as a **separate one-off task** before updating the service.

---

## Release Sequence

```
1. Build new image (git SHA tag)
2. Push to ECR
3. Run migration task (if schema changes exist)
4. Wait for migration to complete
5. Update ECS service (rolling deployment)
6. Wait for service to stabilize
```

```text
┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐
│  Build   │───►│  Push   │───►│ Migrate │───►│ Deploy  │
│  Image   │    │  ECR    │    │  Task   │    │ Service │
└─────────┘    └─────────┘    └─────────┘    └─────────┘
                                                │
                                                ▼
                                          ┌─────────┐
                                          │  Health  │
                                          │  Check   │
                                          └─────────┘
```

---

## Run Migrations as an ECS Task

### Manual Execution

```bash
# Get the current task definition
TASK_DEF=$(aws ecs describe-task-definition \
  --task-definition aplika-api \
  --query 'taskDefinition' \
  --output json)

# Override the command to run migrations
MIGRATION_TASK_DEF=$(echo $TASK_DEF | jq '
  .containerDefinitions[0].command = [
    "php", "bin/console", "doctrine:migrations:migrate", "--no-interaction"
  ] |
  del(.taskDefinitionArn, .revision, .status, .requiresAttributes,
      .compatibilities, .registeredAt, .registeredBy)
')

# Register the migration task
aws ecs register-task-definition \
  --cli-input-json "$MIGRATION_TASK_DEF"

# Run the migration task
TASK_ARN=$(aws ecs run-task \
  --cluster aplika-cluster \
  --task-definition aplika-api \
  --network-configuration "awsvpcConfiguration={
    subnets=[SUBNET_ID],
    securityGroups=[SG_ID],
    assignPublicIp=ENABLED
  }" \
  --launch-type FARGATE \
  --query 'tasks[0].taskArn' --output text)

echo "Migration task: $TASK_ARN"

# Wait for completion
aws ecs wait tasks-stopped \
  --cluster aplika-cluster \
  --tasks $TASK_ARN

# Check result
aws ecs describe-tasks \
  --cluster aplika-cluster \
  --tasks $TASK_ARN \
  --query 'tasks[0].containers[0].[exitCode,reason]' --output table
```

### In CI/CD (GitHub Actions)

The deploy workflow (Chapter 10) already includes this step. The key is:

1. Register a migration task definition (same image, different command)
2. Run it with `ecs run-task`
3. Wait for it to stop
4. Check the exit code
5. Only proceed to service update if migration succeeded

---

## Failure Handling

### Migration Fails

If the migration task exits with a non-zero code:

1. **Do NOT update the service** — the old code stays running
2. Check the migration task logs in CloudWatch
3. Fix the migration or the data issue
4. Re-run the migration task
5. Only then update the service

### Service Deployment Fails

If the new tasks fail health checks:

1. ECS automatically rolls back to the previous task definition
2. Check CloudWatch logs for the new tasks
3. Fix the issue and push a new image

> **Exam note:** ECS rolling deployments have built-in rollback. If new tasks fail health checks, ECS stops the deployment and keeps the old tasks running. This is configured via `DeploymentConfiguration.MaximumPercent` and `MinimumHealthyPercent`.

---

## Rollback Strategy

### Rollback the Service (Code)

```bash
# List recent task definition revisions
aws ecs list-task-definitions \
  --family-prefix aplika-api \
  --sort DESC \
  --max-items 5

# Deploy the previous revision
aws ecs update-service \
  --cluster aplika-cluster \
  --service aplika-api \
  --task-definition aplika-api:PREVIOUS_REVISION
```

### Rollback the Database (Schema)

Database rollbacks are harder. Options:

1. **Doctrine migrations `down()`:** Run `doctrine:migrations:migrate PREVIOUS_VERSION`
2. **Restore from snapshot:** Point-in-time recovery from RDS automated backups
3. **Manual SQL:** Write the reverse SQL

> **Warning:** Always test rollbacks in a non-production environment first. Some migrations (e.g., dropping columns) are not safely reversible.

---

## Zero-Downtime Migration Patterns

For schema changes that are incompatible with the old code:

1. **Expand-contract pattern:**
   - Phase 1: Add new column (old code ignores it)
   - Phase 2: Deploy new code (uses new column)
   - Phase 3: Remove old column (after all tasks updated)

2. **Feature flags:**
   - Deploy code that can work with both old and new schema
   - Enable the new behavior after migration

---

## Developer Associate Exam Mapping

| Concept | Why It's on the Exam |
|---------|---------------------|
| ECS RunTask | One-off tasks |
| Task definition override | Command override |
| Rolling deployment rollback | Deployment safety |
| Health checks | Service reliability |
| Migration best practices | Operational excellence |

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| Migration task exits immediately | Wrong command or missing env vars | Check task definition; verify `DATABASE_URL` |
| `Cannot connect to database` | Security group or network | Verify RDS SG allows Task SG; check subnet |
| Migration runs but schema unchanged | Migration already applied | Check `doctrine:migrations:status` |
| Service won't update | Same task definition revision | Use `--force-new-deployment` |

## Next Step

[Chapter 13: Cost & Teardown](13-cost-and-teardown.md) — understand costs and learn to tear down everything.
