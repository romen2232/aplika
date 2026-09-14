# Database Fixtures Specification

## Purpose

Fixture loading from Gherkin feature files with dual insertion modes (direct DB and API-driven) and snapshot-based DB reset via `pg_dump`/`pg_restore`, replacing the current hard-coded `DELETE FROM users` approach.

## Requirements

### Requirement: Makefile Database Targets

The system MUST provide `make db`, `make db-dev`, and `make db-test` targets. All targets SHALL execute via `docker compose exec -T api` (never on host). `db-test` MUST: drop `joblog_test`, recreate it, run migrations, and take an initial snapshot. `db-dev` MUST: drop `joblog`, recreate it, and run migrations. `db` SHALL orchestrate both.

| Target | Steps |
|--------|-------|
| `db` | Invokes `db-dev` then `db-test` |
| `db-dev` | `DROP DATABASE IF EXISTS joblog`, `CREATE DATABASE joblog`, `doctrine:migrations:migrate` |
| `db-test` | `DROP DATABASE IF EXISTS joblog_test`, `CREATE DATABASE joblog_test`, `doctrine:migrations:migrate`, `pg_dump` → `api/var/test-snapshot.dump` |

#### Scenario: `make db` sets up both databases
- GIVEN Docker environment is running
- WHEN `make db` is executed
- THEN `joblog` database exists with applied migrations
- AND `joblog_test` database exists with applied migrations and a snapshot file at `api/var/test-snapshot.dump`

#### Scenario: `make db-test` produces a snapshot after migrations
- GIVEN `joblog_test` database does not exist
- WHEN `make db-test` is executed
- THEN `joblog_test` is created, migrations run, and `pg_dump --format=custom` writes to `api/var/test-snapshot.dump`

#### Scenario: `make db-test` is idempotent
- GIVEN `joblog_test` already exists with data
- WHEN `make db-test` is executed again
- THEN the old database is dropped, recreated, migrations run, and a fresh snapshot is produced

### Requirement: Direct DB Insert Mode

The system MUST support inserting rows directly via PDO/Doctrine DBAL. Step pattern: `Given the database has directly the following {entity}`. This mode SHALL bypass domain logic (no validation, no password hashing, no events). It is intended for pre-hashed passwords, reference data, and bulk records.

#### Scenario: Insert users with pre-hashed passwords
- GIVEN the fixture feature file contains:
  ```
  Given the database has directly the following users:
    | email              | password                     | role  |
    | admin@example.com  | $2y$13$PreHashed...          | admin |
  ```
- WHEN the step executes
- THEN a row exists in `users` table with `email=admin@example.com` and the exact hash provided
- AND no domain validation or password hashing occurred

#### Scenario: Direct insert fails on invalid column
- GIVEN a fixture table references a non-existent column `foo`
- WHEN the step executes
- THEN a `RuntimeException` is thrown indicating the invalid column

### Requirement: API-Driven Insert Mode

The system MUST support creating entities by calling existing API endpoints via `KernelBrowser`. Step pattern: `Given the following {entity} are registered via API`. This mode SHALL trigger full backend processing (validation, password hashing, domain events).

#### Scenario: Register users via API
- GIVEN the fixture feature file contains:
  ```
  Given the following users are registered via API:
    | email              | password    |
    | alice@example.com  | SecurePass1 |
  ```
- WHEN the step executes
- THEN `POST /api/auth/register` is called for each row
- AND the response status is 201 for each
- AND the user is persisted with hashed password

#### Scenario: API-driven insert fails on validation error
- GIVEN a row with `password=short` (below minimum length)
- WHEN the step executes
- THEN a `RuntimeException` is thrown with the failing email and status code

### Requirement: Snapshot-Based Scenario Isolation

The system MUST restore the database from `api/var/test-snapshot.dump` before each Behat scenario using `pg_restore --clean --if-exists`. This SHALL replace the current `DELETE FROM users` in `AuthContext::resetState`. All tables in the database are reset automatically — no per-entity cleanup code.

#### Scenario: Scenario starts with clean snapshot state
- GIVEN a previous scenario modified the database (inserted rows, updated records)
- WHEN the next scenario begins (`BeforeScenario` hook)
- THEN `pg_restore --clean --if-exists --dbname=joblog_test` is executed
- AND the database state matches the snapshot exactly

#### Scenario: New tables are covered without code changes
- GIVEN a new entity table `jobs` is added via migration
- WHEN `make db-test` is run after migration
- THEN the snapshot includes `jobs` table
- AND subsequent scenario isolation covers `jobs` without fixture code changes

#### Scenario: Snapshot file is missing
- GIVEN `api/var/test-snapshot.dump` does not exist
- WHEN Behat starts a scenario
- THEN a clear error message is thrown indicating `make db-test` must be run first

### Requirement: Fixture File Structure

Fixtures MUST live in `api/features/fixtures/` as `.feature` files. They SHALL be loaded by a `BeforeSuite` hook or a dedicated suite. A `FixtureContext` class MUST implement step definitions for both insertion modes.

#### Scenario: Fixture files are loaded before test suite
- GIVEN fixture files exist in `api/features/fixtures/`
- WHEN Behat suite starts
- THEN all fixture scenarios execute in order before test scenarios run

### Requirement: AuthContext Cleanup Removal

`AuthContext::resetState` MUST be modified to remove the hard-coded `DELETE FROM users` block. The `BehatState::reset()` call SHALL remain for in-memory state cleanup. Database reset SHALL be delegated entirely to the snapshot mechanism.

#### Scenario: AuthContext no longer executes DELETE queries
- GIVEN `AuthContext` is initialized with the updated code
- WHEN `resetState` is called before a scenario
- THEN no `DELETE FROM users` SQL is executed
- AND `BehatState::reset()` is still called
