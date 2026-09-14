# Design: Registration and Login

## Technical Approach

Implement CQRS-based user registration and login following the existing Screaming Architecture pattern. Domain User aggregate in `Auth/Domain/` stays framework-free. Doctrine persistence model in `Infrastructure/Persistence/` maps to domain. Two commands (RegisterUser, AuthenticateUser) with handlers orchestrate the use cases. Controllers delegate to handlers via Symfony Messenger. Existing JwtAuthenticator modified to load from repository instead of in-memory provider.

## Architecture Decisions

| Decision | Choice | Alternative | Rationale |
|----------|--------|-------------|-----------|
| User identity | UUID v4 via `symfony/uid` | Auto-increment ID | Already required by spec; aligns with JWT `sub` claim; no sequential leakage |
| Password hashing | Symfony PasswordHasherInterface (auto) | bcrypt directly | Framework handles algorithm selection and future migration; auto hasher picks best available |
| Email validation | Domain value object `Email` | Inline validation in handler | Encapsulates format rules; reusable across commands; testable in isolation |
| Token generation | New `TokenGenerator` in Domain | Reuse TokenValidator | Single Responsibility — Validator validates, Generator generates; both use firebase/php-jwt |
| Repository interface | `UserRepository` in Domain | Concrete Doctrine class | Dependency Inversion — domain defines contract, infrastructure implements |
| CQRS wiring | Symfony Messenger `#[AsMessageHandler]` | Manual service wiring | Convention in project; auto-registration via attributes; matches existing pattern |

## Data Flow

### Registration Flow
```
POST /api/auth/register
        │
        ▼
RegisterController ──→ RegisterUserCommand ──→ RegisterUserHandler
        │                                            │
        │                                            ├─→ Email::fromString() (validate)
        │                                            ├─→ UserRepository::findByEmail() (uniqueness)
        │                                            ├─→ PasswordHasher::hashPassword()
        │                                            └─→ UserRepository::save()
        │
        ▼
   201 Created {id, email}
```

### Login Flow
```
POST /api/auth/login
        │
        ▼
LoginController ──→ AuthenticateUserCommand ──→ AuthenticateUserHandler
        │                                            │
        │                                            ├─→ UserRepository::findByEmail()
        │                                            ├─→ PasswordHasher::isPasswordValid()
        │                                            └─→ TokenGenerator::generate()
        │
        ▼
   200 OK {token}
```

### JWT Authentication Flow (modified)
```
Authorization: Bearer <token>
        │
        ▼
JwtAuthenticator ──→ TokenValidator::validate()
        │                    │
        │                    ▼
        │            TokenUserExtractor::extract()
        │                    │
        │                    ▼
        │            UserRepository::findById() ← NEW
        │                    │
        │                    ▼
        │            Security\User::fromDomain() ← NEW
        │
        ▼
   SelfValidatingPassport
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `api/src/Auth/Domain/User.php` | Create | Domain aggregate: id, email, hashedPassword, roles |
| `api/src/Auth/Domain/Email.php` | Create | Value object with format validation |
| `api/src/Auth/Domain/UserRepository.php` | Create | Interface: save, findByEmail, findById |
| `api/src/Auth/Domain/Exception/DuplicateEmailException.php` | Create | Domain exception for duplicate email |
| `api/src/Auth/Domain/Exception/WeakPasswordException.php` | Create | Domain exception for password < 8 chars |
| `api/src/Auth/Domain/TokenGenerator.php` | Create | Generate JWT with user claims |
| `api/src/Auth/Application/Command/RegisterUser/RegisterUserCommand.php` | Create | Command DTO: email, plainPassword |
| `api/src/Auth/Application/Command/RegisterUser/RegisterUserHandler.php` | Create | Handler: validate, hash, persist |
| `api/src/Auth/Application/Command/AuthenticateUser/AuthenticateUserCommand.php` | Create | Command DTO: email, plainPassword |
| `api/src/Auth/Application/Command/AuthenticateUser/AuthenticateUserHandler.php` | Create | Handler: validate creds, generate token |
| `api/src/Auth/Infrastructure/Persistence/DoctrineUserRepository.php` | Create | Doctrine implementation of UserRepository |
| `api/src/Auth/Infrastructure/Persistence/UserModel.php` | Create | Doctrine entity mapping (not domain) |
| `api/src/Auth/Infrastructure/Controller/RegisterController.php` | Create | POST /api/auth/register |
| `api/src/Auth/Infrastructure/Controller/LoginController.php` | Create | POST /api/auth/login |
| `api/src/Auth/Infrastructure/Security/JwtAuthenticator.php` | Modify | Add UserRepository; load user from DB |
| `api/src/Auth/Infrastructure/Security/User.php` | Modify | Add `fromDomain()` static factory |
| `api/config/packages/security.yaml` | Modify | Replace users_in_memory with custom provider |
| `api/config/services.yaml` | Modify | Wire TokenGenerator, password hasher |
| `api/migrations/Version20260913CreateUsers.php` | Create | Users table migration |

## Interfaces / Contracts

```php
// Domain: User aggregate
namespace App\Auth\Domain;

final class User
{
    private function __construct(
        private readonly string $id,
        private readonly Email $email,
        private readonly string $hashedPassword,
        private readonly array $roles
    ) {}

    public static function register(string $id, string $email, string $hashedPassword): self;
    public function id(): string;
    public function email(): string;
    public function hashedPassword(): string;
    public function roles(): array;
}

// Domain: Email value object
namespace App\Auth\Domain;

final class Email
{
    private function __construct(private readonly string $value) {}
    public static function fromString(string $email): self; // throws InvalidEmailException
    public function value(): string;
}

// Domain: UserRepository interface
namespace App\Auth\Domain;

interface UserRepository
{
    public function save(User $user): void;
    public function findByEmail(string $email): ?User;
    public function findById(string $id): ?User;
}

// Domain: TokenGenerator
namespace App\Auth\Domain;

class TokenGenerator
{
    public function __construct(private readonly string $secretKey) {}
    public function generate(User $user): string;
    // Claims: sub=id, email, roles, iat, exp (1 hour)
}
```

## Database Schema

```sql
CREATE TABLE users (
    id          UUID         PRIMARY KEY,
    email       VARCHAR(255) NOT NULL,
    password    VARCHAR(255) NOT NULL,  -- bcrypt/argon2 hash
    roles       JSON         NOT NULL DEFAULT '["ROLE_USER"]',
    created_at  TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP    NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX idx_users_email ON users (email);
```

## JWT Token Structure

```json
{
  "sub": "550e8400-e29b-41d4-a716-446655440000",
  "email": "user@example.com",
  "roles": ["ROLE_USER"],
  "iat": 1726233600,
  "exp": 1726237200
}
```
- Algorithm: HS256 (matches existing TokenValidator)
- Expiration: 1 hour (configurable via env)
- Secret: `JWT_SECRET_KEY` from .env (already configured)

## Password Hashing Strategy

- Use Symfony `UserPasswordHasherInterface` (auto algorithm)
- Hash on registration via `RegisterUserHandler`
- Verify on login via `AuthenticateUserHandler`
- Test environment: reduced cost (already configured in security.yaml `when@test`)
- Domain User stores hashed password only — never plain text

## Dependency Injection Wiring

```yaml
# services.yaml additions
App\Auth\Domain\TokenGenerator:
    arguments:
        $secretKey: '%env(JWT_SECRET_KEY)%'

# Autowiring handles:
# - UserRepository → DoctrineUserRepository (interface binding)
# - UserPasswordHasherInterface → Symfony service
# - Handlers auto-register via #[AsMessageHandler]
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | Email value object validation | PHPSpec: valid/invalid formats |
| Unit | User aggregate creation | PHPSpec: register with valid/invalid data |
| Unit | TokenGenerator claims | PHPSpec: generate returns valid JWT structure |
| Unit | RegisterUserHandler | PHPSpec: orchestrates validation, hashing, persistence |
| Unit | AuthenticateUserHandler | PHPSpec: orchestrates credential check, token generation |
| Integration | DoctrineUserRepository | PHPSpec with test DB: save/find operations |
| Acceptance | POST /api/auth/register | Behat: success, duplicate, invalid email, weak password |
| Acceptance | POST /api/auth/login | Behat: success, wrong password, unknown user |
| Acceptance | JWT → /api/me | Behat: register → login → /api/me with token |

## Migration / Rollout

1. Generate migration: `docker compose exec api bin/console doctrine:migrations:diff`
2. Migration creates `users` table with unique email index
3. No data migration needed — new table only
4. Rollback: drop `users` table via `doctrine:migrations:execute --down`

## Open Questions

- [ ] Token expiration should be configurable via env var `JWT_TOKEN_TTL`?
- [ ] Should we add `created_at`/`updated_at` to domain User or only to Doctrine model?
