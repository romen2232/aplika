# Chapter 10: CI/CD with GitHub OIDC

> Automate deployments using GitHub Actions with OIDC authentication — no long-lived AWS credentials.

**Estimated duration:** 1-2 hours | **Cost:** ~$0

> **Prerequisite:** The CI/CD pipeline requires RUNNING mode. The deploy workflow updates ECS services and runs migration tasks — both need a live ECS cluster and RDS instance. If you're in SLEEPING mode, run `make wake` before triggering a deploy. Alternatively, add a pre-deploy wake step to the workflow (see Troubleshooting at the end of this chapter).

---

## What is OIDC?

**OpenID Connect (OIDC)** allows GitHub Actions to assume an IAM role without storing AWS access keys. GitHub generates a short-lived token that AWS validates against a trust policy.

> **Exam note:** OIDC for CI/CD is a modern security best practice. Know the difference between long-lived keys and federated identity, and understand trust policies.

### Why OIDC Instead of Access Keys?

| Approach | Security | Rotation | Complexity |
|----------|----------|----------|------------|
| Access keys in GitHub Secrets | Keys can leak; manual rotation | Every 90 days | Simple |
| **OIDC** | **No stored keys; short-lived tokens** | **Automatic** | **Moderate** |

---

## Create the OIDC Identity Provider

```bash
# Create the OIDC provider for GitHub
aws iam create-open-id-connect-provider \
  --url https://token.actions.githubusercontent.com \
  --thumbprint-list 6938fd4d98bab03faadb97b34396831e3780aea1 \
  --client-id-list sts.amazonaws.com
```

> **Thumbprint:** The thumbprint verifies the identity provider's TLS certificate. The value above is GitHub's current root CA thumbprint.

---

## Create the IAM Role with Trust Policy

The trust policy defines **who** can assume the role (your GitHub repo) and **when** (on specific branches):

```json
# github-oidc-trust-policy.json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Principal": {
        "Federated": "arn:aws:iam::ACCOUNT_ID:oidc-provider/token.actions.githubusercontent.com"
      },
      "Action": "sts:AssumeRoleWithWebIdentity",
      "Condition": {
        "StringEquals": {
          "token.actions.githubusercontent.com:aud": "sts.amazonaws.com"
        },
        "StringLike": {
          "token.actions.githubusercontent.com:sub": "repo:OWNER/REPO:ref:refs/heads/main"
        }
      }
    }
  ]
}
```

> **Exam note — Trust Policy Deep Dive:**
> - `Principal.Federated`: The OIDC provider (GitHub)
> - `Action: sts:AssumeRoleWithWebIdentity`: Allow federated identity assumption
> - `Condition.StringEquals:aud`: The audience must be `sts.amazonaws.com` (AWS STS)
> - `Condition.StringLike:sub`: The subject must match your repo and branch
> - `refs/heads/main`: Only allow deployments from the `main` branch

Create the role:
```bash
aws iam create-role \
  --role-name aplika-github-actions \
  --assume-role-policy-document file://github-oidc-trust-policy.json \
  --description "GitHub Actions deployment role for Aplika"

# Attach deployment permissions
aws iam put-role-policy \
  --role-name aplika-github-actions \
  --policy-name deploy-permissions \
  --policy-document '{
    "Version": "2012-10-17",
    "Statement": [
      {
        "Effect": "Allow",
        "Action": [
          "ecr:GetAuthorizationToken",
          "ecr:BatchCheckLayerAvailability",
          "ecr:GetDownloadUrlForLayer",
          "ecr:BatchGetImage",
          "ecr:PutImage",
          "ecr:InitiateLayerUpload",
          "ecr:UploadLayerPart",
          "ecr:CompleteLayerUpload"
        ],
        "Resource": "*"
      },
      {
        "Effect": "Allow",
        "Action": [
          "ecs:UpdateService",
          "ecs:DescribeServices",
          "ecs:DescribeTaskDefinition",
          "ecs:RegisterTaskDefinition",
          "ecs:RunTask"
        ],
        "Resource": "*"
      },
      {
        "Effect": "Allow",
        "Action": [
          "iam:PassRole"
        ],
        "Resource": [
          "arn:aws:iam::ACCOUNT_ID:role/aplika-execution-role",
          "arn:aws:iam::ACCOUNT_ID:role/aplika-task-role"
        ]
      },
      {
        "Effect": "Allow",
        "Action": [
          "ssm:GetParameters",
          "ssm:GetParameter"
        ],
        "Resource": "arn:aws:ssm:us-east-1:ACCOUNT_ID:parameter/aplika/prod/*"
      }
    ]
  }'
```

---

## GitHub Actions Deploy Workflow

```yaml
# .github/workflows/deploy.yml
name: Deploy to AWS

on:
  push:
    branches:
      - main

env:
  AWS_REGION: us-east-1
  ECR_REPOSITORY_API: aplika/api
  ECR_REPOSITORY_FRONTEND: aplika/frontend
  ECS_CLUSTER: aplika-cluster
  ECS_SERVICE_API: aplika-api
  ECS_SERVICE_WORKER: aplika-worker
  ECS_SERVICE_FRONTEND: aplika-frontend

permissions:
  id-token: write    # Required for OIDC
  contents: read

jobs:
  deploy:
    name: Build, Push, Migrate, Deploy
    runs-on: ubuntu-latest

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      # -----------------------------------------------------------
      # Authenticate to AWS via OIDC (no stored keys!)
      # -----------------------------------------------------------
      - name: Configure AWS credentials
        uses: aws-actions/configure-aws-credentials@v4
        with:
          role-to-assume: arn:aws:iam::ACCOUNT_ID:role/aplika-github-actions
          aws-region: ${{ env.AWS_REGION }}

      # -----------------------------------------------------------
      # Login to ECR
      # -----------------------------------------------------------
      - name: Login to Amazon ECR
        id: login-ecr
        uses: aws-actions/amazon-ecr-login@v2

      # -----------------------------------------------------------
      # Build and push API image
      # -----------------------------------------------------------
      - name: Build, tag, and push API image
        env:
          ECR_REGISTRY: ${{ steps.login-ecr.outputs.registry }}
          IMAGE_TAG: ${{ github.sha }}
        run: |
          docker build -f docker/api/Dockerfile.prod \
            -t $ECR_REGISTRY/$ECR_REPOSITORY_API:$IMAGE_TAG \
            -t $ECR_REGISTRY/$ECR_REPOSITORY_API:latest \
            .
          docker push $ECR_REGISTRY/$ECR_REPOSITORY_API:$IMAGE_TAG
          docker push $ECR_REGISTRY/$ECR_REPOSITORY_API:latest

      # -----------------------------------------------------------
      # Build and push Frontend image
      # -----------------------------------------------------------
      - name: Build, tag, and push Frontend image
        env:
          ECR_REGISTRY: ${{ steps.login-ecr.outputs.registry }}
          IMAGE_TAG: ${{ github.sha }}
        run: |
          docker build \
            --build-arg NEXT_PUBLIC_API_URL=https://api.aplika.work \
            -f docker/frontend/Dockerfile.prod \
            -t $ECR_REGISTRY/$ECR_REPOSITORY_FRONTEND:$IMAGE_TAG \
            -t $ECR_REGISTRY/$ECR_REPOSITORY_FRONTEND:latest \
            .
          docker push $ECR_REGISTRY/$ECR_REPOSITORY_FRONTEND:$IMAGE_TAG
          docker push $ECR_REGISTRY/$ECR_REPOSITORY_FRONTEND:latest

      # -----------------------------------------------------------
      # Run database migrations (one-off task)
      # -----------------------------------------------------------
      - name: Run database migrations
        env:
          ECR_REGISTRY: ${{ steps.login-ecr.outputs.registry }}
          IMAGE_TAG: ${{ github.sha }}
        run: |
          # Register a one-off task definition for migrations
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
            --cluster $ECS_CLUSTER \
            --task-definition aplika-api \
            --network-configuration "awsvpcConfiguration={
              subnets=[SUBNET_ID],
              securityGroups=[SG_ID],
              assignPublicIp=ENABLED
            }" \
            --launch-type FARGATE \
            --query 'tasks[0].taskArn' --output text)

          # Wait for migration to complete
          aws ecs wait tasks-stopped \
            --cluster $ECS_CLUSTER \
            --tasks $TASK_ARN

          # Check exit code
          EXIT_CODE=$(aws ecs describe-tasks \
            --cluster $ECS_CLUSTER \
            --tasks $TASK_ARN \
            --query 'tasks[0].containers[0].exitCode' --output text)

          if [ "$EXIT_CODE" != "0" ]; then
            echo "Migration failed with exit code $EXIT_CODE"
            exit 1
          fi

      # -----------------------------------------------------------
      # Deploy new task definitions to ECS services
      # -----------------------------------------------------------
      - name: Deploy to ECS
        env:
          ECR_REGISTRY: ${{ steps.login-ecr.outputs.registry }}
          IMAGE_TAG: ${{ github.sha }}
        run: |
          # Update all services to use the new task definition
          aws ecs update-service \
            --cluster $ECS_CLUSTER \
            --service $ECS_SERVICE_API \
            --force-new-deployment

          aws ecs update-service \
            --cluster $ECS_CLUSTER \
            --service $ECS_SERVICE_WORKER \
            --force-new-deployment

          aws ecs update-service \
            --cluster $ECS_CLUSTER \
            --service $ECS_SERVICE_FRONTEND \
            --force-new-deployment

      # -----------------------------------------------------------
      # Wait for deployment to stabilize
      # -----------------------------------------------------------
      - name: Wait for services to stabilize
        run: |
          aws ecs wait services-stable \
            --cluster $ECS_CLUSTER \
            --services $ECS_SERVICE_API $ECS_SERVICE_WORKER $ECS_SERVICE_FRONTEND
```

---

## GitHub Environments & Protections

Add deployment protections in GitHub:

1. Go to **Settings** → **Environments** → **New environment**: `production`
2. Enable **Required reviewers** (add yourself)
3. Enable **Wait timer** (e.g., 5 minutes — gives time to cancel)
4. Add the environment to the workflow:

```yaml
jobs:
  deploy:
    environment: production    # Requires approval
```

---

## Rollback

To rollback to a previous version:

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

---

## Developer Associate Exam Mapping

| Concept | Why It's on the Exam |
|---------|---------------------|
| OIDC federation | Modern authentication for CI/CD |
| Trust policies | IAM policy structure |
| sts:AssumeRoleWithWebIdentity | Federated identity |
| ECR image push | Container deployment |
| ECS service update | Rolling deployments |
| RunTask | One-off tasks (migrations) |

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| `id-token` permission denied | Missing `permissions.id-token: write` | Add to workflow |
| Role assumption fails | Trust policy doesn't match repo/branch | Check `sub` condition in trust policy |
| Migration task exits non-zero | Database connection or migration error | Check task logs in CloudWatch |
| Service not updating | Same task definition revision | Use `--force-new-deployment` |
| **Deploy fails — ECS service not found** | **Stack is in SLEEPING mode** | **Run `make wake` first, then re-trigger the workflow** |

## Next Step

[Chapter 11: Observability](11-observability.md) — set up logging, metrics, and alarms.
