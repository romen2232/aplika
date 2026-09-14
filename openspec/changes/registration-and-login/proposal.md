# Proposal: Registration and Login

## Intent

Joblog currently has JWT token validation and a `/api/me` endpoint, but no way to create users or authenticate them. Users cannot register or log in, making the application unusable. This change introduces user registration and login endpoints to enable the core authentication flow.

## Scope

### In Scope
- User registration endpoint (POST /api/auth/register)
- User login endpoint (POST /api/auth/login) returning JWT token
- Domain User aggregate with email and password fields
- UserRepository interface and Doctrine implementation
- Database migration for users table
- Test database configuration for PHPSpec and Behat
- JWT token generation on successful login
- Password hashing using Symfony's auto hasher

### Out of Scope
- Email verification (deferred to future change)
- Password reset/forgot password flow
- OAuth/social login providers
- User profile management beyond email/password
- Role-based access control (RBAC)

## Capabilities

### New Capabilities
- `user-registration`: User creation with email/password validation and persistence
- `user-login`: Authentication with credential validation and JWT token generation

### Modified Capabilities
- `jwt-authentication`: Extend existing JWT infrastructure to load domain User from repository instead of in-memory provider

## Approach

Create a domain User aggregate in `api/src/Auth/Domain/User.php` with email and hashed password. The domain layer remains framework-free — no Symfony or Doctrine imports.

Implement `UserRepository` interface in the domain layer, with Doctrine implementation in `api/src/Auth/Infrastructure/Persistence/DoctrineUserRepository.php`.

Create two application commands:
- `RegisterUserCommand` + handler: validates email uniqueness, hashes password, persists user
- `LoginUserCommand` + handler: validates credentials, generates JWT using existing `TokenValidator` infrastructure

Add two controllers:
- `RegisterController`: POST /api/auth/register
- `LoginController`: POST /api/auth/login

Update `JwtAuthenticator` to load User from repository instead of `users_in_memory` provider. Update `security.yaml` to use custom user provider.

Existing `Infrastructure/Security/User.php` (Symfony UserInterface) remains as the security layer adapter, mapped from the domain User.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `api/src/Auth/Domain/User.php` | New | Domain aggregate with email, password |
| `api/src/Auth/Domain/UserRepository.php` | New | Repository interface |
| `api/src/Auth/Application/Command/RegisterUser*` | New | Registration command + handler |
| `api/src/Auth/Application/Command/LoginUser*` | New | Login command + handler |
| `api/src/Auth/Infrastructure/Persistence/DoctrineUserRepository.php` | New | Doctrine implementation |
| `api/src/Auth/Infrastructure/Controller/RegisterController.php` | New | Registration endpoint |
| `api/src/Auth/Infrastructure/Controller/LoginController.php` | New | Login endpoint |
| `api/src/Auth/Infrastructure/Security/JwtAuthenticator.php` | Modified | Load user from repository |
| `api/config/packages/security.yaml` | Modified | Custom user provider |
| `api/migrations/` | New | Users table migration |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Password hashing misconfiguration | Medium | Use Symfony's auto hasher with explicit algorithm (bcrypt/argon2i); test with known hashes |
| JWT secret not available in test environment | Low | Add JWT_SECRET_KEY to test .env; document in README |
| Domain layer accidentally imports framework | Medium | PHPSpec tests enforce framework-free domain; code review checklist |
| Duplicate email registration race condition | Low | Database unique constraint + application-level validation |

## Rollback Plan

1. Revert migration: `docker compose exec api bin/console doctrine:migrations:execute <previous_version> --down`
2. Revert code changes via git
3. Clear cache: `docker compose exec api bin/console cache:clear`
4. No data loss — users table is new; existing data unaffected

## Dependencies

- `firebase/php-jwt` (already installed)
- `symfony/security-bundle` (already installed)
- PostgreSQL database (already configured)

## Success Criteria

- [ ] POST /api/auth/register creates user and returns 201
- [ ] POST /api/auth/login returns JWT token for valid credentials
- [ ] POST /api/auth/login returns 401 for invalid credentials
- [ ] Duplicate email registration returns 409 Conflict
- [ ] JWT token from login authenticates subsequent /api/me requests
- [ ] PHPSpec tests cover domain User behavior (email validation, password hashing)
- [ ] Behat tests cover registration and login acceptance scenarios
- [ ] Test database configuration allows PHPSpec to run without affecting dev data
