# API Rate Limiting - Locust Load Testing

This directory contains Locust load tests for verifying API rate limiting.

## Rate Limits

- **Unauthenticated users**: 15 requests/minute per IP
- **Authenticated users**: 50 requests/minute per user ID

## Running Locust Tests

### 1. Start the main stack with Redis

```bash
docker compose up -d
```

### 2. Start Locust

```bash
docker compose -f compose.locust.yaml up -d
```

### 3. Open Locust Web UI

Navigate to: http://localhost:8089

### 4. Configure the test

- **Number of users**: Start with 10-20 users
- **Spawn rate**: 1-2 users per second
- **Host**: http://web:80 (already configured)

### 5. Run the test

Click "Start swarming" and monitor:
- Response times
- Failure rates (429 responses indicate rate limiting is working)
- Requests per second

## Expected Behavior

### In dev/test environments
- Rate limiting is **disabled**
- All requests should succeed (200, 401, or 404)
- No 429 responses

### In prod environment
- Rate limiting is **enabled**
- After 15 requests/minute from same IP → 429 response
- After 50 requests/minute from same user → 429 response
- 429 responses include `Retry-After` header

## Testing Rate Limiting Locally

To test rate limiting locally, you need to run the API in production mode:

```bash
# Set environment to prod
docker compose exec api APP_ENV=prod php bin/console cache:clear

# Or restart the API container with APP_ENV=prod
docker compose stop api
docker compose run -e APP_ENV=prod -d api
```

Then run Locust and you should see 429 responses after exceeding limits.

## Locust Test Scenarios

### PublicApiUser (weight: 3)
- Simulates unauthenticated users
- Hits `/api/me` without authentication
- Should be rate limited at 15 req/min per IP

### AuthenticatedApiUser (weight: 1)
- Simulates authenticated users
- Registers and logs in to get JWT token
- Hits `/api/me` with Bearer token
- Should be rate limited at 50 req/min per user

## Monitoring

Check Redis keys to see rate limit counters:

```bash
docker compose exec redis redis-cli KEYS "*limiter*"
docker compose exec redis redis-cli GET "limiter/api_public/ip_172.18.0.1"
```
