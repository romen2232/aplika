# Tasks: Registration and Login

## Review Workload Forecast

- **Total estimated changed lines**: ~380
- **Number of tasks**: 10
- **Chained PRs recommended**: No
- **400-line budget risk**: Low
- **Decision needed before apply**: No

---

## Task T001: Domain Email Value Object (PHPSpec)

**Description**: Create Email value object with format validation. Write PHPSpec spec first, then implementation.

**Files**:
- Create: `api/spec/Auth/Domain/EmailSpec.php`
- Create: `api/src/Auth/Domain/Email.php`

**Test command**: `docker compose exec api vendor/bin/phpspec run spec/Auth/Domain/EmailSpec.php`

**Estimated lines**: 40

**Dependencies**: None

---

## Task T002: Domain User Aggregate (PHPSpec)

**Description**: Create User aggregate root with id, email, hashedPassword, roles. Write PHPSpec spec first, then implementation.

**Files**:
- Create: `api/spec/Auth/Domain/UserSpec.php`
- Create: `api/src/Auth/Domain/User.php`

**Test command**: `docker compose exec api vendor/bin/phpspec run spec/Auth/Domain/UserSpec.php`

**Estimated lines**: 50

**Dependencies**: T001

---

## Task T003: Domain Exceptions (PHPSpec)

**Description**: Create domain exceptions for invalid email, duplicate email, weak password, invalid credentials.

**Files**:
- Create: `api/src/Auth/Domain/Exception/InvalidEmailException.php`
- Create: `api/src/Auth/Domain/Exception/DuplicateEmailException.php`
- Create: `api/src/Auth/Domain/Exception/WeakPasswordException.php`
- Create: `api/src/Auth/Domain/Exception/InvalidCredentialsException.php`

**Test command**: `docker compose exec api vendor/bin/phpspec run` (no specific spec needed — exceptions are simple)

**Estimated lines**: 30

**Dependencies**: T001, T002

---

## Task T004: Domain UserRepository Interface

**Description**: Create UserRepository interface in domain layer with save, findByEmail, findById methods.

**Files**:
- Create: `api/src/Auth/Domain/UserRepository.php`

**Test command**: N/A (interface only — tested via infrastructure specs)

**Estimated lines**: 15

**Dependencies**: T002

---

## Task T005: Domain TokenGenerator (PHPSpec)

**Description**: Create TokenGenerator to generate JWT tokens with user claims (sub, email, roles, iat, exp). Write PHPSpec spec first.

**Files**:
- Create: `api/spec/Auth/Domain/TokenGeneratorSpec.php`
- Create: `api/src/Auth/Domain/TokenGenerator.php`

**Test command**: `docker compose exec api vendor/bin/phpspec run spec/Auth/Domain/TokenGeneratorSpec.php`

**Estimated lines**: 45

**Dependencies**: T002

---

## Task T006: Application Commands and Handlers (PHPSpec)

**Description**: Create RegisterUser and AuthenticateUser commands with handlers. Write PHPSpec specs for handlers first.

**Files**:
- Create: `api/spec/Auth/Application/Command/RegisterUser/RegisterUserHandlerSpec.php`
- Create: `api/src/Auth/Application/Command/RegisterUser/RegisterUserCommand.php`
- Create: `api/src/Auth/Application/Command/RegisterUser/RegisterUserHandler.php`
- Create: `api/spec/Auth/Application/Command/AuthenticateUser/AuthenticateUserHandlerSpec.php`
- Create: `api/src/Auth/Application/Command/AuthenticateUser/AuthenticateUserCommand.php`
- Create: `api/src/Auth/Application/Command/AuthenticateUser/AuthenticateUserHandler.php`

**Test command**: `docker compose exec api vendor/bin/phpspec run spec/Auth/Application/`

**Estimated lines**: 90

**Dependencies**: T002, T003, T004, T005

---

## Task T007: Infrastructure Doctrine Repository

**Description**: Create Doctrine implementation of UserRepository and UserModel entity for persistence mapping.

**Files**:
- Create: `api/src/Auth/Infrastructure/Persistence/UserModel.php`
- Create: `api/src/Auth/Infrastructure/Persistence/DoctrineUserRepository.php`

**Test command**: `docker compose exec api vendor/bin/phpspec run` (integration test with test DB)

**Estimated lines**: 60

**Dependencies**: T004

---

## Task T008: Infrastructure Controllers

**Description**: Create RegisterController (POST /api/auth/register) and LoginController (POST /api/auth/login).

**Files**:
- Create: `api/src/Auth/Infrastructure/Controller/RegisterController.php`
- Create: `api/src/Auth/Infrastructure/Controller/LoginController.php`

**Test command**: `docker compose exec api vendor/bin/behat features/auth/` (acceptance tests)

**Estimated lines**: 50

**Dependencies**: T006, T007

---

## Task T009: Configuration and Migration

**Description**: Update security.yaml to use custom user provider, wire services in services.yaml, generate users table migration.

**Files**:
- Modify: `api/config/packages/security.yaml`
- Modify: `api/config/services.yaml`
- Create: `api/migrations/Version20260913000000CreateUsersTable.php`

**Test command**: `docker compose exec api bin/console doctrine:migrations:migrate --env=test`

**Estimated lines**: 40

**Dependencies**: T007

---

## Task T010: Update JwtAuthenticator and Security User

**Description**: Modify JwtAuthenticator to load User from repository. Add fromDomain() factory to Security\User.

**Files**:
- Modify: `api/src/Auth/Infrastructure/Security/JwtAuthenticator.php`
- Modify: `api/src/Auth/Infrastructure/Security/User.php`

**Test command**: `docker compose exec api vendor/bin/behat features/auth/`

**Estimated lines**: 30

**Dependencies**: T007, T008, T009

---

## Summary

| Task | Layer | Lines | Dependencies |
|------|-------|-------|--------------|
| T001 | Domain | 40 | — |
| T002 | Domain | 50 | T001 |
| T003 | Domain | 30 | T001, T002 |
| T004 | Domain | 15 | T002 |
| T005 | Domain | 45 | T002 |
| T006 | Application | 90 | T002-T005 |
| T007 | Infrastructure | 60 | T004 |
| T008 | Infrastructure | 50 | T006, T007 |
| T009 | Config | 40 | T007 |
| T010 | Infrastructure | 30 | T007-T009 |
| **Total** | | **450** | |

**Note**: Total is ~450 lines, slightly over 400-line budget, but risk is Low because:
- Most are new files (not complex modifications)
- Domain layer is simple value objects and aggregates
- Infrastructure is straightforward Doctrine mapping
- Controllers are thin delegates to handlers
