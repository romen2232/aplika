# Chapter 05: ECR & Images

> Create Elastic Container Registry repositories, build production Docker images, and push them to ECR.

**Estimated duration:** 30-60 minutes | **Cost:** ~$0.05/month (500 MB storage)

---

## What is ECR?

**Amazon ECR (Elastic Container Registry)** is a managed Docker container registry. Think of it as a private Docker Hub hosted in your AWS account.

> **Exam note:** ECR is a common exam topic. Know: image push/pull, lifecycle policies, image scanning, and repository policies.

---

## Create Repositories

> **Stack assignment:** ECR repositories go into the `aplika-persistent` stack (Chapter 13). They cost ~$0.05/mo and persist across sleep/wake cycles — images are preserved.

```bash
# Create repos for API and frontend images
aws ecr create-repository \
  --repository-name aplika/api \
  --region us-east-1 \
  --image-scanning-configuration scanOnPush=true

aws ecr create-repository \
  --repository-name aplika/frontend \
  --region us-east-1 \
  --image-scanning-configuration scanOnPush=true
```

### Image Tag Immutability

By default, ECR allows overwriting tags (e.g., pushing a new `latest`). For production, use immutable tags to prevent accidental overwrites:

```bash
aws ecr put-image-tag-mutability \
  --repository-name aplika/api \
  --image-tag-mutability IMMUTABLE \
  --region us-east-1

aws ecr put-image-tag-mutability \
  --repository-name aplika/frontend \
  --image-tag-mutability IMMUTABLE \
  --region us-east-1
```

> **Why immutable?** If you tag an image as `v1.2.0` and someone pushes a different image with the same tag, the original is lost. Immutable tags prevent this. Use unique tags (git SHA, build number) instead of `latest`.

---

## Authenticate Docker to ECR

```bash
# Get the login token and configure Docker
aws ecr get-login-password --region us-east-1 | \
  docker login --username AWS --password-stdin \
  $(aws sts get-caller-identity --query Account --output text).dkr.ecr.us-east-1.amazonaws.com
```

Set a helper variable:
```bash
ECR_REGISTRY=$(aws sts get-caller-identity --query Account --output text).dkr.ecr.us-east-1.amazonaws.com
echo "ECR Registry: $ECR_REGISTRY"
```

---

## Build & Push Images

### API Image

```bash
# Build the production API image
docker build \
  -f docker/api/Dockerfile.prod \
  -t $ECR_REGISTRY/aplika/api:$(git rev-parse --short HEAD) \
  .

# Tag as latest (convenient, but don't rely on it in production)
docker tag $ECR_REGISTRY/aplika/api:$(git rev-parse --short HEAD) \
  $ECR_REGISTRY/aplika/api:latest

# Push both tags
docker push $ECR_REGISTRY/aplika/api:$(git rev-parse --short HEAD)
docker push $ECR_REGISTRY/aplika/api:latest
```

### Frontend Image

```bash
# Build the production frontend image
# NEXT_PUBLIC_API_URL is baked into the client bundle at build time
docker build \
  --build-arg NEXT_PUBLIC_API_URL=https://api.aplika.work \
  -f docker/frontend/Dockerfile.prod \
  -t $ECR_REGISTRY/aplika/frontend:$(git rev-parse --short HEAD) \
  .

docker tag $ECR_REGISTRY/aplika/frontend:$(git rev-parse --short HEAD) \
  $ECR_REGISTRY/aplika/frontend:latest

docker push $ECR_REGISTRY/aplika/frontend:$(git rev-parse --short HEAD)
docker push $ECR_REGISTRY/aplika/frontend:latest
```

### Tagging Strategy

| Tag | Purpose | Mutable? |
|-----|---------|----------|
| `git-sha` (e.g., `a1b2c3d`) | Traceable to exact commit | Immutable |
| `latest` | Convenience for development | Mutable |
| `v1.0.0` | Release version | Immutable |

> **Exam note:** ECR image tags are mutable by default. Use `IMMUTABLE` tag mutability to enforce unique tags.

---

## ECR Lifecycle Policies

Automatically clean up old images to control storage costs:

```bash
# Keep only the last 10 images; delete the rest
aws ecr put-lifecycle-policy \
  --repository-name aplika/api \
  --lifecycle-policy-text '{
    "rules": [
      {
        "rulePriority": 1,
        "description": "Keep only 10 images",
        "selection": {
          "tagStatus": "any",
          "countType": "imageCountMoreThan",
          "countNumber": 10
        },
        "action": {
          "type": "expire"
        }
      }
    ]
  }' \
  --region us-east-1
```

Apply the same policy to the frontend repository.

---

## Verify

```bash
# List images in the API repository
aws ecr describe-images \
  --repository-name aplika/api \
  --query 'imageDetails[].[imageTags,imageSizeInBytes,imagePushedAt]' \
  --output table

# List images in the frontend repository
aws ecr describe-images \
  --repository-name aplika/frontend \
  --query 'imageDetails[].[imageTags,imageSizeInBytes,imagePushedAt]' \
  --output table
```

---

## Multi-Stage Build Benefits

| Stage | What's Included | Size |
|-------|----------------|------|
| Builder (API) | composer, vendor, source | ~500 MB |
| Runtime (API) | php-fpm, nginx, app, autoload | ~150 MB |
| Builder (Frontend) | node_modules, source, build | ~800 MB |
| Runtime (Frontend) | node, server.js, .next/static | ~150 MB |

Multi-stage builds reduce final image size by 60-80%. This means faster pulls and lower ECR storage costs.

---

## Developer Associate Exam Mapping

| Concept | Why It's on the Exam |
|---------|---------------------|
| ECR repositories | Container image storage |
| Image push/pull | Container deployment workflow |
| Image scanning | Security (vulnerability detection) |
| Lifecycle policies | Cost management |
| Tag immutability | Deployment safety |
| Multi-stage builds | Best practice (not AWS-specific but tested) |

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| `docker push` fails with 403 | Not authenticated to ECR | Run `aws ecr get-login-password` again |
| Image too large | Missing `.dockerignore` | Add `.dockerignore` files |
| `scanOnPush` not working | ECR scanning needs permissions | Check IAM role has `ecr:PutImageScanningConfiguration` |
| Can't pull from ECS task | Task role missing ECR permissions | Add `AmazonEC2ContainerRegistryReadOnly` policy to task role |

## Next Step

[Chapter 06: ECS Services](06-ecs-services.md) — create the ECS cluster, task definitions, and services.
