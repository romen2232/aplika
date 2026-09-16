# Frontend ⇄ Backend Connection

> How the Next.js app and the Symfony API actually talk to each other — the network hops, the same-origin proxy, the cookie auth contract, and the code that holds it together.

The browser never calls the API directly. It always calls the Next.js origin (`app.aplika.test`), and Next.js transparently proxies `/api/*` to Symfony. That single decision — a **same-origin rewrite proxy** — is the spine of the integration. Everything else (cookies, typing, silent refresh) hangs off it.

## At a glance

| Concern | Mechanism |
|---------|-----------|
| Browser → frontend | nginx TLS (`web:443`) → `frontend:3000` |
| Browser → API | same-origin `fetch('/api/...')` → Next.js rewrite → `http://web:80` → PHP-FPM |
| Cross-origin / CORS | Not used by the app (Nelmio CORS stays for other consumers) |
| Auth transport | Two host-only `httpOnly` cookies (`access_token`, `refresh_token`) |
| API contract | Nelmio OpenAPI spec → `openapi-typescript` → `frontend/src/api/generated/types.ts` |
| Client | `ApiClient` in `frontend/src/api/client.ts` (single HTTP entry point) |
| Token refresh | Transparent single retry on `401` inside `ApiClient` |
| Route guard | `frontend/proxy.ts` (Next 16 middleware) — cookie presence only |

---

## Quick path: one API call

```
1. Browser:  fetch('/api/me', { credentials: 'include' })   ← same origin, app.aplika.test
2. Next.js:  rewrite /api/:path* → http://web:80/api/:path*
3. nginx:    fastcgi_pass api:9000
4. Symfony:  JwtAuthenticator reads the access_token cookie → controller → query bus
5. Response: JSON flows back up the same chain; Set-Cookie attaches to app.aplika.test
```

---

## 1. Network topology

```
Browser (https://app.aplika.test)
        │
        ▼
┌──────────────────┐   nginx `web` container (docker/web/default.conf)
│  :443 TLS        │   server_name app.aplika.test → proxy_pass http://frontend:3000
└────────┬─────────┘
         ▼
┌──────────────────┐   Next.js `frontend` container (:3000)
│  Next.js runtime │   rewrites /api/* → http://web:80/api/*
└────────┬─────────┘
         ▼  (internal plain HTTP)
┌──────────────────┐   nginx `web` again, internal server block
│  :80 HTTP        │   server_name api.aplika.test web → fastcgi_pass api:9000
└────────┬─────────┘
         ▼
┌──────────────────┐
│  PHP-FPM `api`   │   Symfony kernel → Messenger bus → DDD/CQRS
└────────┬─────────┘
         ▼
   PostgreSQL
```

Two domains are exposed in `.env`: `app.aplika.test` (Next.js) and `api.aplika.test` (Symfony). nginx serves both TLS certificates and also runs a **second, internal, plain-HTTP server on port 80** whose `server_name` includes `web` — that is the hop Next.js calls. The internal block is deliberately TLS-free because it only exists on the trusted Docker network.

| Container | Role | Reached from |
|-----------|------|--------------|
| `web` (nginx) | TLS reverse proxy + internal FastCGI gateway | Browser (`:443`), Next.js (`web:80`) |
| `frontend` (Next.js) | SSR app + API rewrite proxy | `web` → `frontend:3000` |
| `api` (PHP-FPM) | Symfony HTTP kernel | nginx → `api:9000` |
| `database` (PostgreSQL) | Persistence | `api`/`worker` |
| `worker` | Messenger `async` consumer | — (not on the request path today) |

---

## 2. The same-origin proxy

`frontend/next.config.ts`:

```ts
const apiUpstream = process.env.API_UPSTREAM ?? 'http://web:80';

const nextConfig: NextConfig = {
  async rewrites() {
    return {
      beforeFiles: [
        { source: '/api/:path*', destination: `${apiUpstream}/api/:path*` },
      ],
    };
  },
};
```

`beforeFiles` runs **before** filesystem and route matching, so `/api/...` can never accidentally hit a Next.js page or the locale middleware.

### Why not call `api.aplika.test` directly?

A cross-origin call from `app.aplika.test` to `api.aplika.test` would force:

- CORS preflights on every non-simple request,
- a much harder cookie story (`SameSite`, `Domain`, third-party-cookie restrictions),
- exposing the raw API surface to the browser.

With the rewrite, the browser treats the API as first-party, so:

- Symfony's cookies are stored as **host-only, first-party** cookies for `app.aplika.test`,
- no CORS is needed for the app,
- the browser never learns the internal hostname `web`, the PHP-FPM port, or the upstream topology.

Nelmio CORS (`api/config/packages/nelmio_cors.yaml`) is still configured for `^/api/` with `allow_credentials: true`, but that targets non-proxied consumers (curl, future clients, Swagger UI). The browser app does not rely on it.

---

## 3. The HTTP contract (OpenAPI-first)

The two codebases are coupled through a generated contract, not hand-written DTOs.

1. Controllers carry `#[OA\...]` attributes — e.g. `RegisterController.php:33-62`.
2. `api/config/routes/nelmio_api_doc.yaml` exposes `/api/openapi.json` (spec) and `/api/docs` (Swagger UI).
3. The frontend codegen script reads the spec through the internal nginx hop:

   ```json
   "codegen": "openapi-typescript http://web:80/api/openapi.json -o src/api/generated/types.ts"
   ```

4. `frontend/src/api/client.ts` derives types from the generated operations instead of duplicating them:

   ```ts
   type MeResponse = operations['get_api_me']['responses'][200]['content']['application/json'];
   export type User = Required<Pick<MeResponse, 'id' | 'email' | 'roles'>>;
   ```

So a backend route/schema change flows: **PHP attributes → OpenAPI JSON → `npm run codegen` → TypeScript types → compiler errors in the UI if the contract breaks.**

---

## 4. The frontend data layer

`frontend/src/api/client.ts` is the only place that speaks HTTP.

- `baseUrl` defaults to `''` → every path is a **same-origin relative** URL (`/api/auth/login`).
- `credentials: 'include'` on every request so cookies travel automatically. No tokens in JS, no `Authorization` header, no `localStorage`.
- Errors are normalized into `ApiError` (status + backend error envelope) and `NetworkError` (fetch threw).
- **Transparent single retry on `401`**: if a request 401s and the path is not `/api/auth/login`, `/api/auth/register`, or `/api/auth/refresh`, the client calls `refresh()` first and replays the original request once.

That retry is what makes short-lived access tokens invisible to the UI code.

---

## 5. The auth model

Two cookies, both **httpOnly, Secure, SameSite=Lax, host-only** (`api/src/Auth/Infrastructure/Cookie/CookieHelper.php`):

| Cookie | Boundary | TTL |
|--------|----------|-----|
| `access_token` | JWT (HS256) | `AUTH_ACCESS_TOKEN_TTL=900` (15 min) |
| `refresh_token` | Opaque, server-side record | `AUTH_REFRESH_TOKEN_TTL=604800` (7 days) |

### Backend authentication

`api/src/Auth/Infrastructure/Security/JwtAuthenticator.php`:

- `supports()` is true if there is a `Bearer` header **or** the `access_token` cookie — header-first, cookie-fallback. The same firewall serves the browser today and future API clients.
- `authenticate()` validates the JWT, extracts `id`/`email`, loads the domain user, and wraps it in the Symfony `User`.
- Failure returns a JSON `401` that matches the frontend error envelope.

### Refresh with rotation and reuse detection

`api/src/Auth/Infrastructure/Controller/RefreshController.php`:

- Reads the `refresh_token` cookie, dispatches `RefreshTokenCommand`, rotates the token, and reissues both cookies.
- Replaying an already-rotated token revokes the whole token family (`RefreshTokenReuseException`) and clears both cookies.
- Logout is idempotent: it clears cookies even when revocation fails (`LogoutController.php`).
- Register auto-logs-in by dispatching `AuthenticateUserCommand` immediately after `RegisterUserCommand` and setting the same cookies (`RegisterController.php:81-91`).

---

## 6. React state

`frontend/src/contexts/AuthContext.tsx` sits at the top of the `[lang]` layout (`app/[lang]/layout.tsx`):

- On mount it calls `apiClient.me()`: valid cookie → `authenticated`, otherwise → `unauthenticated`.
- `login`/`register` call the API then store the returned `User`; `logout` calls the API and always clears local state in a `finally`.
- State is a small `useReducer` (`authReducer`), unit-tested directly in `frontend/tests/unit/auth-context.test.tsx`.

> The React context is **not** the source of truth for auth — the cookies are. The context is a render-time cache of "who am I"; a page refresh rebuilds it from `/api/me`.

---

## 7. Route guard

`frontend/proxy.ts` is the Next 16 middleware (renamed from `middleware.ts`). It runs on every non-`_next` path and does three things **in order**:

1. `pathname.startsWith('/api/')` → `NextResponse.next()` immediately. API calls must not receive a locale prefix or the auth redirect.
2. No locale prefix → redirect to `/{locale}/...`.
3. For `/[lang]/dashboard`, an **optimistic** guard: if neither `access_token` nor `refresh_token` cookie exists, redirect to `/{lang}/login?returnUrl=...`. It only checks **presence**, never validity — an expired-but-refreshable session is left to the client to heal.

So there are two auth layers: a fast, crypto-free cookie-presence guard at the edge, and the authoritative JWT validation in Symfony.

---

## 8. End-to-end walkthroughs

### Login

```
LoginForm
  → useAuth().login
  → apiClient.login  →  POST /api/auth/login  (same-origin, credentials: include)
  → Next rewrite → nginx:80 → PHP-FPM
  → LoginController → AuthenticateUserCommand → Messenger → domain + JWT + refresh record
  → Set-Cookie × 2  →  response proxied back
  → browser stores cookies under app.aplika.test
  → client calls /api/me  →  User stored in context
```

### Authenticated request with an expired access token

```
GET /api/...  →  Symfony 401
  → ApiClient sees 401 (path not in the no-refresh list)
  → POST /api/auth/refresh  (refresh cookie still valid)  →  new cookies
  → original request replayed once  →  UI sees a normal 200
```

### Logout

```
apiClient.logout  →  POST /api/auth/logout
  → refresh token revoked in DB + cookies expired
  → context → unauthenticated
  → proxy.ts guard bounces /dashboard to /login
```

---

## 9. File map

| Layer | File | Responsibility |
|-------|------|----------------|
| Proxy config | `frontend/next.config.ts` | `/api/*` rewrite to `API_UPSTREAM` |
| Edge guard | `frontend/proxy.ts` | `/api` passthrough, locale redirect, auth guard |
| HTTP client | `frontend/src/api/client.ts` | Same-origin fetch, error mapping, 401 refresh retry |
| Generated types | `frontend/src/api/generated/types.ts` | OpenAPI-derived request/response types |
| Auth state | `frontend/src/contexts/AuthContext.tsx` | `me()` bootstrap, login/register/logout |
| nginx | `docker/web/default.conf` | Public TLS + internal FastCGI gateway |
| Compose wiring | `compose.yaml` | `API_UPSTREAM`, `NEXT_PUBLIC_API_URL`, ports |
| Cookies | `api/src/Auth/Infrastructure/Cookie/CookieHelper.php` | Set/clear both auth cookies |
| Authenticator | `api/src/Auth/Infrastructure/Security/JwtAuthenticator.php` | Header-first, cookie-fallback JWT auth |
| Controllers | `api/src/Auth/Infrastructure/Controller/*.php` | Login, register, refresh, logout, me |
| OpenAPI | `api/config/routes/nelmio_api_doc.yaml` | `/api/openapi.json`, `/api/docs` |
| CORS | `api/config/packages/nelmio_cors.yaml` | For non-proxied API consumers |

---

## 10. Gotchas

- **The JWT TTL is effectively decorative.** `JwtTokenGenerator.php` sets `TOKEN_TTL = 3600` (1 h), but the access *cookie* expires at 900 s. The browser stops sending the access token after 15 minutes and the client silently refreshes. Not a bug — but the 1 h value never governs behavior; the cookie TTL does.
- **`NEXT_PUBLIC_API_URL` is dead config.** `compose.yaml` sets it, but `client.ts` defaults to `baseUrl=''` and never reads it. It is a leftover from the pre-proxy (cross-origin) design. "Fixing" the client to use it would reintroduce CORS.
- **`api.aplika.test` exists in parallel.** It serves the same API directly over TLS (and Swagger at `/api/docs`). The browser app does not use it; only direct consumers do.
- **Two nginx server blocks on port 80 do different jobs** — the internal FastCGI gateway vs. the `app.aplika.test` → HTTPS redirect. Easy to misread.
- **`domain: null` on the cookies is intentional.** It keeps them host-only for `app.aplika.test`. Setting a `Domain` (e.g. `.aplika.test`) to share them with `api.aplika.test` reintroduces cross-site cookie risk.
- **Auth commands run synchronously in HTTP.** Every controller reads the `HandledStamp` inline. The `worker` service consumes the `async` transport, but the auth use cases are handled in-request today.

---

## Verification checklist

- [ ] `https://app.aplika.test` loads and `https://app.aplika.test/api/me` returns `401` (unauthenticated) rather than `404`/CORS error.
- [ ] Login sets `access_token` and `refresh_token` as `Secure; HttpOnly; SameSite=Lax` on `app.aplika.test`.
- [ ] After deleting `access_token` (keeping `refresh_token`), the next call silently refreshes and succeeds.
- [ ] `npm run codegen` inside the frontend container regenerates `src/api/generated/types.ts` from the live spec.
- [ ] `npm run typecheck` and `npm run lint` pass in the frontend container.

## Related

- [ADR 001: Domain-Driven Design](../adr/001-domain-driven-design.md)
- [ADR 003: CQRS](../adr/003-cqrs.md)
- [`README.md` — Infrastructure](../../README.md#infrastructure)
