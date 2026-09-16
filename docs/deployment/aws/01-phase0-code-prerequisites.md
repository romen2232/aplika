# Phase 0: Code Prerequisites

> Every code and configuration change you must make **before** any AWS work. Each item includes the exact file affected, why it matters, and how to verify it.

## Overview

The current codebase is configured for local development. Before deploying to AWS, we need production-ready Dockerfiles, health endpoints, proper secret handling, and transport changes. This chapter covers all of them.

**Estimated duration:** 2-4 hours (all local work, $0 cost)

---

## 1. Production API Dockerfile (Multi-Stage)

### File: `docker/api/Dockerfile.prod` (NEW)

**Why:** The current `docker/api/Dockerfile` is dev-only — it installs xdebug, has OPcache disabled, and runs as a plain php-fpm container. Production needs: OPcache ON, xdebug OFF, nginx as a sidecar or built-in, and a non-root user.

**Serving Pattern Decision: nginx sidecar vs nginx-in-image vs ALB→php-fpm**

| Option | Pros | Cons | Decision |
|--------|------|------|----------|
| nginx sidecar (separate container in same task) | Clean separation; nginx config independent | More complex task definition; 2 containers = more memory | ❌ Over-engineered for this scale |
| nginx-in-image (nginx + php-fpm in one container) | Single container; simpler ECS task; nginx handles static files + FastCGI | Runs two processes (needs supervisord or entrypoint script) | ✅ **Chosen** |
| ALB → php-fpm directly | Simplest | php-fpm doesn't speak HTTP; ALB can't FastCGI-pass; would need php-fpm configured differently | ❌ Not viable |

**Rationale for nginx-in-image:** For a learning project, a single container with both nginx and php-fpm is the simplest approach. We'll use a shell entrypoint that starts php-fpm in the background and nginx in the foreground.

> **Exam note:** ECS tasks can contain multiple containers. The "sidecar" pattern (e.g., nginx + app in one task) is common on the exam. We're using a single container here for simplicity, but know that multi-container tasks exist.

```dockerfile
# docker/api/Dockerfile.prod
# Multi-stage build: composer install in builder, copy to runtime.
# Stage 1: Composer dependencies
FROM composer:2 AS builder
WORKDIR /app
COPY api/composer.json api/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist
COPY api/ .
RUN composer dump-autoload --optimize --classmap-authoritative

# Stage 2: Production runtime
FROM php:8.4-fpm-alpine AS runtime

# Install nginx, php extensions, and supervisor
RUN apk add --no-cache nginx supervisor \
    && docker-php-ext-install intl pdo_pgsql zip opcache

# OPcache production settings
COPY docker/api/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# nginx config
COPY docker/api/nginx.conf /etc/nginx/http.d/default.conf

# Supervisor config (starts php-fpm + nginx)
COPY docker/api/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application
COPY --from=builder /app /app
WORKDIR /app

# Ensure storage directories are writable
RUN mkdir -p var/cache var/log var/share \
    && chown -R www-data:www-data var

USER www-data

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

### Supporting Files

**`docker/api/opcache.ini`:**
```ini
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.save_comments=1
```

**`docker/api/nginx.conf`:**
```nginx
server {
    listen 8080;
    server_name _;
    root /app/public;
    index index.php;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /dev/stderr;
    access_log /dev/stdout;
}
```

**`docker/api/supervisord.conf`:**
```ini
[supervisord]
nodaemon=true

[program:php-fpm]
command=php-fpm
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

### Acceptance Criteria

- [ ] `docker build -f docker/api/Dockerfile.prod -t aplika-api-test .` succeeds
- [ ] Container starts and responds on port 8080
- [ ] OPcache is enabled (check with `php -i | grep opcache.enable`)
- [ ] xdebug is NOT installed
- [ ] Runs as `www-data` (non-root)

---

## 2. Production Frontend Dockerfile

### File: `docker/frontend/Dockerfile.prod` (NEW)

**Why:** The current `docker/frontend/Dockerfile` runs `npm run dev` — the Next.js development server. Production needs `next build` with `output: 'standalone'` and a minimal Node.js runtime.

**Next.js 16 `output: 'standalone'` specifics:** Next.js 16.3.4 (our version) still supports `output: 'standalone'`. When enabled, `next build` creates `.next/standalone/` with only the files needed to run the server — no `node_modules` required. A minimal `server.js` is generated. You must manually copy `public/` and `.next/static/` into the standalone directory for static assets.

> **Critical gotcha — `NEXT_PUBLIC_*` build-time inlining:** Next.js inlines `NEXT_PUBLIC_*` variables at **build time**, not runtime. This means `NEXT_PUBLIC_API_URL` must be set during `docker build`, not in the ECS task environment. The value is baked into the client-side JavaScript bundle. If you change the API URL later, you must rebuild the image.

```dockerfile
# docker/frontend/Dockerfile.prod
# Multi-stage build for Next.js standalone output.

# Stage 1: Dependencies
FROM node:22-bookworm-slim AS deps
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci --ignore-scripts

# Stage 2: Build
FROM node:22-bookworm-slim AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY frontend/ .

# NEXT_PUBLIC_API_URL is baked into the client bundle at build time.
ARG NEXT_PUBLIC_API_URL
ENV NEXT_PUBLIC_API_URL=$NEXT_PUBLIC_API_URL

RUN npm run build

# Stage 3: Production runtime
FROM node:22-bookworm-slim AS runner
WORKDIR /app

# Don't run as root
RUN addgroup --system --gid 1001 nodejs \
    && adduser --system --uid 1001 nextjs

# Copy standalone output
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static
COPY --from=builder /app/public ./public

USER nextjs

EXPOSE 3000

ENV PORT=3000
ENV HOSTNAME="0.0.0.0"
ENV NODE_ENV=production

CMD ["node", "server.js"]
```

### Acceptance Criteria

- [ ] `NEXT_PUBLIC_API_URL=https://api.aplika.work` set during build
- [ ] `docker build --build-arg NEXT_PUBLIC_API_URL=https://api.aplika.work -f docker/frontend/Dockerfile.prod -t aplika-frontend-test .` succeeds
- [ ] Container starts on port 3000
- [ ] Runs as `nextjs` (non-root)
- [ ] Static assets (CSS, JS) load correctly

---

## 3. Health Endpoint

### Files: NEW controller + route

**Why:** ECS uses health checks to determine if a task is healthy. ALB target groups need an HTTP endpoint to ping. Without `/health`, ECS can't auto-recover failed tasks, and ALB will mark targets as unhealthy.

> **Exam note:** ALB health checks are a common exam topic. Know the difference between `healthCheckPath`, `healthCheckIntervalSeconds`, `healthyThresholdCount`, and `unhealthyThresholdCount`.

Create `api/src/Shared/Infrastructure/Controller/HealthController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController
{
    #[Route('/health', name: 'health', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new JsonResponse(['status' => 'ok'], Response::HTTP_OK);
    }
}
```

### Acceptance Criteria

- [ ] `GET /health` returns `{"status":"ok"}` with HTTP 200
- [ ] No authentication required (public endpoint)

---

## 4. Trusted Proxies

### File: `api/config/packages/framework.yaml` (MODIFY)

**Why:** When running behind an ALB, Symfony sees the ALB's IP as the client IP, not the real user IP. `TRUSTED_PROXIES` tells Symfony to trust `X-Forwarded-For`, `X-Forwarded-Proto`, and `X-Forwarded-Port` headers.

> **Exam note:** This is a common gotcha when deploying behind load balancers. Without this, rate limiting based on IP won't work, and generated URLs may use `http://` instead of `https://`.

Add to `framework.yaml`:

```yaml
framework:
    trusted_proxies: '%env(TRUSTED_PROXIES)%'
    trusted_headers: ['x-forwarded-for', 'x-forwarded-proto', 'x-forwarded-port']
```

Add to `.env.prod` (see item 7):

```
TRUSTED_PROXIES=10.0.0.0/16
```

> Use the VPC CIDR range so only traffic from within your VPC is trusted. Never set `TRUSTED_PROXIES=0.0.0.0/0` — that trusts everyone.

### Acceptance Criteria

- [ ] With `TRUSTED_PROXIES` set, `$request->getClientIp()` returns the real client IP (from `X-Forwarded-For`)
- [ ] URLs generated by Symfony use `https://` when `X-Forwarded-Proto: https` is present

---

## 5. CORS Configuration Fix

### File: `api/.env` (MODIFY)

**Why:** The current `.env` has `CORS_ALLOW_ORIGIN` defined **twice** — lines 44 and 56. The second definition wins in Symfony's dotenv parsing, and it's more restrictive (only `localhost`, missing `aplika.test`). In production, we need to allow `app.aplika.work`.

**Current state (problematic):**
```
# Line 44: CORS_ALLOW_ORIGIN='^https?://(aplika\.test|api\.aplika\.test|localhost|127\.0\.0\.1)(:[0-9]+)?$'
# Line 56: CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'  ← THIS WINS
```

**Fix:** Remove the duplicate. In `.env.prod`, set the production value:

```
CORS_ALLOW_ORIGIN='^https://(app\.aplika\.work|api\.aplika\.work)$'
```

### Acceptance Criteria

- [ ] Only ONE `CORS_ALLOW_ORIGIN` definition in `.env`
- [ ] Production regex allows `https://app.aplika.work` and `https://api.aplika.work`
- [ ] CORS headers appear in API responses when `Origin: https://app.aplika.work` is sent

---

## 6. Redis Removal (Cache + Rate Limiter)

### Files: `api/config/packages/cache.yaml`, `api/config/packages/rate_limiter.yaml` (MODIFY)

**Why:** We're dropping Redis to save ~$12/mo (ElastiCache). On a single Fargate task, APCu works for cache and rate limiting. Rate limiter state is per-process (acceptable for a learning project with low traffic).

**`cache.yaml` — change from Redis to APCu + filesystem:**
```yaml
framework:
    cache:
        app: cache.adapter.filesystem
        pools:
            cache.app:
                adapter: cache.adapter.filesystem
```

**`rate_limiter.yaml` — change cache_pool to default:**
```yaml
framework:
    rate_limiter:
        api_public:
            policy: 'sliding_window'
            limit: 15
            interval: '1 minute'
        api_authenticated:
            policy: 'sliding_window'
            limit: 50
            interval: '1 minute'
```

> **Note:** Without `cache_pool` pointing to Redis, the rate limiter uses the default cache (filesystem). On a single task, this is fine. If you scale to multiple tasks, each task has its own rate-limit counter — see the ElastiCache appendix.

**Remove `predis/predis` from `composer.json`** (optional — keeps the dependency clean):
```bash
docker compose exec api composer remove predis/predis
```

### Acceptance Criteria

- [ ] API starts without Redis
- [ ] Rate limiter works (16th request in 1 minute returns 429)
- [ ] Cache works (`php bin/console cache:clear` succeeds)

---

## 7. Messenger SQS Transport

### Files: `composer.json`, `api/config/packages/messenger.yaml`, `.env.prod` (MODIFY)

**Why:** SQS is AWS's managed message queue. It's exam-relevant, has an Always Free tier (1M requests/month), and removes the dependency on PostgreSQL's `messenger_messages` table for async processing.

**Package:** `symfony/amazon-sqs-messenger` (official Symfony bridge, currently v8.1.5)

```bash
docker compose exec api composer require symfony/amazon-sqs-messenger
```

**`messenger.yaml` — replace Doctrine transport with SQS:**
```yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2
            failed:
                dsn: 'doctrine://default?queue_name=failed'

        routing:
            'App\Job\Application\Command\AnalyzeJobDescription\AnalyzeJobDescription': async
            'App\Shared\Application\Command\SendEmail\SendEmail': async

        failure_transport: failed
```

**`.env.prod` — SQS DSN format:**
```
MESSENGER_TRANSPORT_DSN=sqs://default?queue_name=aplika-messages
```

> **Exam note — SQS concepts:**
> - **Queue**: A buffer that stores messages until a consumer processes them.
> - **Dead-letter queue (DLQ):** A queue for messages that fail processing after N retries. We'll configure this in Chapter 07.
> - **Visibility timeout:** After a consumer reads a message, it's hidden from other consumers for this period. If processing takes longer, the message becomes visible again — leading to duplicate processing.
> - **Long polling:** Reduces empty responses by waiting up to 20 seconds for a message to arrive. Cheaper and more efficient than short polling.

The `failed` transport stays on Doctrine — failed messages are rare and don't need SQS. The `messenger_messages` table migration should be kept for this purpose.

### Acceptance Criteria

- [ ] `symfony/amazon-sqs-messenger` in `composer.lock`
- [ ] `MESSENGER_TRANSPORT_DSN` points to SQS in production config
- [ ] Messages are dispatched to SQS (verified in Chapter 07)

---

## 8. Production `.env.prod` Template

### File: `api/.env.prod` (NEW)

**Why:** Symfony loads `.env`, then `.env.local`, then `.env.{APP_ENV}`, then `.env.{APP_ENV}.local`. In production (`APP_ENV=prod`), `.env.prod` provides environment-specific values. Secrets should be injected via ECS task environment variables (from SSM Parameter Store), not committed.

```bash
# api/.env.prod
# Production environment defaults.
# Secrets (APP_SECRET, JWT_SECRET_KEY, DATABASE_URL) are injected by ECS
# from SSM Parameter Store — do NOT put real values here.

APP_ENV=prod
APP_SECRET=placeholder-injected-by-ecs
DATABASE_URL=placeholder-injected-by-ecs
JWT_SECRET_KEY=placeholder-injected-by-ecs

DEFAULT_URI=https://api.aplika.work
CORS_ALLOW_ORIGIN='^https://(app\.aplika\.work|api\.aplika\.work)$'
MESSENGER_TRANSPORT_DSN=sqs://default?queue_name=aplika-messages
MAILER_DSN=ses+api://default?region=us-east-1
TRUSTED_PROXIES=10.0.0.0/16
CACHE_BUST=1
```

### Secret Hygiene Checklist

- [ ] Rotate `APP_SECRET` — generate with `openssl rand -hex 32`
- [ ] Rotate `JWT_SECRET_KEY` — generate with `openssl rand -hex 64` (must be ≥32 bytes for HS256)
- [ ] Generate a strong `DATABASE_URL` password (20+ chars, mixed case, numbers, symbols)
- [ ] Never commit `.env.local` or `.env.prod.local` — add them to `.gitignore`
- [ ] Store real secrets in AWS SSM Parameter Store (encrypted with KMS)

### Acceptance Criteria

- [ ] `.env.prod` exists with placeholder values
- [ ] Real secrets are NOT in the file
- [ ] `.gitignore` includes `.env*.local`

---

## 9. `.dockerignore` Files

### Files: `api/.dockerignore`, `frontend/.dockerignore` (NEW)

**Why:** Without `.dockerignore`, Docker copies everything into the build context — including `node_modules`, `vendor`, `.git`, and test files. This makes builds slow and images large.

**`api/.dockerignore`:**
```
.git
.github
.env*
docker/
docs/
frontend/
infrastructure/
locust/
var/
vendor/
*.md
Makefile
compose.yaml
```

**`frontend/.dockerignore`:**
```
.git
.github
.env*
api/
docker/
docs/
infrastructure/
node_modules/
.next/
*.md
Makefile
compose.yaml
playwright-report/
test-results/
```

### Acceptance Criteria

- [ ] Build context is < 50 MB (check with `docker build` output)
- [ ] `vendor/` and `node_modules/` are NOT in the image

---

## 10. Graceful SIGTERM Handling (Worker)

### File: `compose.yaml` (worker service — reference only)

**Why:** When ECS stops a task (during deployment or scaling), it sends SIGTERM. The worker must finish processing the current message before exiting. Symfony Messenger's `--time-limit=3600` already handles this — it checks for SIGTERM between messages.

> **Exam note:** ECS sends SIGTERM, waits 30 seconds (default), then sends SIGKILL. Your application must handle SIGTERM gracefully. The `stopTimeout` property in the task definition controls the grace period.

The current worker command is already correct:
```yaml
command: php bin/console messenger:consume async --time-limit=3600 --memory-limit=128M
```

The `--time-limit` flag causes the worker to exit after 3600 seconds, and SIGTERM is handled between message processing. No code change needed — just document the behavior.

For the ECS task definition, set `stopTimeout` to 30 seconds (the default, but explicit is better):
```json
"stopTimeout": 30
```

### Acceptance Criteria

- [ ] Worker exits cleanly on SIGTERM (not mid-message)
- [ ] ECS task definition has `stopTimeout: 30`

---

## Verification Summary

After completing all items, run the full local test suite:

```bash
make test
```

All tests should pass. Then build the production images:

```bash
# API
docker build -f docker/api/Dockerfile.prod -t aplika-api-prod .

# Frontend
docker build --build-arg NEXT_PUBLIC_API_URL=https://api.aplika.work \
  -f docker/frontend/Dockerfile.prod -t aplika-frontend-prod .
```

Both images should build successfully and start without errors.

---

## Developer Associate Exam Mapping

| Concept | Where It Appears |
|---------|-----------------|
| Docker container best practices | ECS task definitions, image optimization |
| Environment variable management | ECS task environment, SSM Parameter Store |
| Health checks | ALB target groups, ECS service health |
| Graceful shutdown | ECS stopTimeout, SIGTERM handling |
| Build-time vs runtime config | NEXT_PUBLIC_API_URL gotcha |

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| Image build fails at `composer install` | Missing `.dockerignore` — context too large | Add `.dockerignore` files |
| `NEXT_PUBLIC_API_URL` is empty at runtime | Not set at build time | Pass `--build-arg` during `docker build` |
| OPcache not caching | `opcache.validate_timestamps=1` in dev | Set to `0` in production `opcache.ini` |
| CORS errors in production | Duplicate `CORS_ALLOW_ORIGIN` in `.env` | Remove duplicate, set prod value in `.env.prod` |
| Worker stops processing | No SIGTERM handling | Verify `--time-limit` is set; check ECS `stopTimeout` |

## Next Step

[Chapter 02: AWS Account & IAM](02-aws-account-and-iam.md) — create your AWS account and configure CLI access.
