# Design: Database Fixtures with Snapshot Reset

## Technical Approach

Replace hard-coded `DELETE FROM users` with PostgreSQL snapshot-based reset (`pg_dump`/`pg_restore`). Fixture data loaded from Gherkin files via two modes: direct PDO insert and API-driven via KernelBrowser. All database commands execute through the `database` container (postgres:16-alpine) since the API container (php:8.4-fpm) lacks PostgreSQL client tools.

## Architecture Decisions

### Decision: pg_dump/pg_restore execution location

**Choice**: Run via `docker compose exec database` (postgres:16-alpine container)
**Alternatives considered**: Install `postgresql16-client` in API container; use Doctrine DBAL schema tools
**Rationale**: API container is php:8.4-fpm with no PostgreSQL client tools. Adding them increases image size and maintenance burden. The `database` container already has `pg_dump`/`pg_restore` as part of the PostgreSQL installation. Shell exec from PHP to `docker compose exec database` is the standard pattern in this project (see Makefile).

### Decision: DatabaseSnapshot service uses shell exec

**Choice**: `DatabaseSnapshot::restore()` calls `exec()` to run `docker compose exec -T database pg_restore`
**Alternatives considered**: Use PHP's `pg_restore` extension; use Doctrine schema drop/recreate
**Rationale**: PHP doesn't have a native `pg_restore` extension. Doctrine schema tools don't preserve data. Shell exec matches the project's Docker-first convention and the existing `$(API)` Makefile pattern.

### Decision: Fixture loading via BeforeSuite hook

**Choice**: `FixtureContext` implements `BeforeSuite` to load fixture files programmatically
**Alternatives considered**: Dedicated Behat suite with fixture feature files; separate PHP seed script
**Rationale**: A dedicated suite adds configuration complexity. A separate script breaks the Gherkin-native approach. `BeforeSuite` in `FixtureContext` can iterate `api/features/fixtures/*.feature` files and execute their steps programmatically using Behat's internal API, keeping fixtures in Gherkin format.

### Decision: Direct insert uses PDO (not DBAL)

**Choice**: Raw PDO for direct INSERT statements
**Alternatives considered**: Doctrine DBAL; Symfony EntityManager
**Rationale**: Direct insert mode bypasses domain logic intentionally. PDO is already used in `AuthContext::resetState` for the DELETE statement. DBAL/EntityManager would add unnecessary abstraction for raw SQL. Follow existing pattern.

## Data Flow

```
Scenario Start
      │
      ▼
┌─────────────────────────────┐
│  DatabaseSnapshot::restore()│
│  └─ exec("docker compose   │
│     exec -T database        │
│     pg_restore --clean      │
│     --if-exists             │
│     --dbname=joblog_test")  │
└─────────────────────────────┘
      │
      ▼
┌─────────────────────────────┐
│  FixtureContext::loadFixtures│ (BeforeSuite - once)
│  └─ Parse fixture .feature  │
│  └─ Execute steps           │
│     ├─ Direct: PDO INSERT   │
│     └─ API: KernelBrowser   │
└─────────────────────────────┘
      │
      ▼
┌─────────────────────────────┐
│  AuthContext::resetState()  │ (BeforeScenario)
│  └─ BehatState::reset()     │
│  └─ NO database operations  │
└─────────────────────────────┘
      │
      ▼
  Test Scenario Runs
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `api/tests/Behat/DatabaseSnapshot.php` | Create | Service wrapping pg_dump/pg_restore via shell exec to database container |
| `api/tests/Behat/FixtureContext.php` | Create | Behat context with fixture step definitions and BeforeSuite hook |
| `api/features/fixtures/base.feature` | Create | Base fixture file with reference data (admin user, test user) |
| `api/config/services_test.yaml` | Modify | Register FixtureContext with fob.context_service tag |
| `api/behat.php` | Modify | Add FixtureContext to default suite |
| `api/tests/Behat/Auth/AuthContext.php` | Modify | Remove DELETE FROM users from resetState, keep BehatState::reset() |
| `Makefile` | Modify | Add db, db-dev, db-test targets |

## Interfaces / Contracts

```php
// api/tests/Behat/DatabaseSnapshot.php
namespace App\Tests\Behat;

final class DatabaseSnapshot
{
    public function __construct(
        private readonly string $snapshotPath,    // api/var/test-snapshot.dump
        private readonly string $databaseName,    // joblog_test
        private readonly string $composeProject,  // joblog
    ) {}

    public function restore(): void;  // pg_restore --clean --if-exists
    public function dump(): void;     // pg_dump --format=custom
    public function exists(): bool;   // checks snapshot file exists
}

// api/tests/Behat/FixtureContext.php
namespace App\Tests\Behat;

use Behat\Behat\Context\Context;

final class FixtureContext implements Context
{
    public function __construct(
        private readonly DatabaseSnapshot $snapshot,
        private readonly BehatState $state,
    ) {}

    /** @BeforeSuite */
    public static function loadFixtures(): void;

    /** @BeforeScenario */
    public function restoreSnapshot(): void;

    // Step definitions:
    #[Given('the database has directly the following :entity:')]
    public function directInsert(string $entity, TableNode $table): void;

    #[Given('the following :entity are registered via API:')]
    public function apiInsert(string $entity, TableNode $table): void;
}
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | DatabaseSnapshot command building | Mock exec(), verify shell command string |
| Integration | Snapshot restore/dump cycle | Real pg_dump/pg_restore against test DB |
| E2E | Fixture loading + scenario isolation | Behat scenarios using fixture steps |

## Migration / Rollout

No migration required. Steps:
1. Implement DatabaseSnapshot + FixtureContext
2. Create base fixture file
3. Wire into behat.php and services_test.yaml
4. Refactor AuthContext (remove DELETE FROM users)
5. Add Makefile targets
6. Run existing Behat suite to verify no regressions

## Open Questions

- [ ] Should `BeforeSuite` fixture loading be idempotent (skip if already loaded)?
- [ ] How to handle fixture ordering when multiple fixture files exist?
