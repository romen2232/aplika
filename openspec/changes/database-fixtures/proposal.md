# Proposal: Database Fixtures with Snapshot Reset

## Intent

Behat scenarios currently reset state with a hard-coded `DELETE FROM users` in `AuthContext::resetState`. This does not scale — every new entity requires manual cleanup code, and scenarios cannot share rich pre-loaded data. We need a fixture system where Behat feature files define test data declaratively, and a snapshot mechanism guarantees clean isolation between scenarios regardless of how many tables exist.

## Scope

### In Scope
- `make db`, `make db-dev`, `make db-test` Makefile targets
- Behat feature files as fixture definitions (Gherkin native tables)
- Two data insertion modes: API-driven (full backend processing) and direct DB insert (bulk reference data)
- PostgreSQL snapshot-based reset (`pg_dump` / `pg_restore`) replacing hard-coded `DELETE FROM`
- Automatic scalability: snapshot covers all tables without per-entity code

### Out of Scope
- Fixture loading for dev/seeding (dev seed data is a separate concern)
- Parallel scenario execution (Behat runs sequentially by default)
- Migration of existing `there is a user` step — it already works via API mode
- Playwright/E2E fixture sharing

## Capabilities

### New Capabilities
- `database-fixtures`: Fixture loading from Gherkin feature files with dual insertion modes and snapshot-based DB reset

### Modified Capabilities
None — this is test infrastructure, no domain spec behavior changes.

## Approach

### Makefile Targets

| Target | Purpose |
|--------|---------|
| `db` | Orchestrates `db-dev` + `db-test` |
| `db-dev` | Drops/creates `joblog` DB, runs migrations, loads dev fixtures |
| `db-test` | Drops/creates `joblog_test` DB, runs migrations, takes initial snapshot |

All targets run via `docker compose exec` / `docker compose exec -T` — never on host.

### Fixture File Structure

Fixtures live in `api/features/fixtures/` as `.feature` files loaded by a dedicated Behat suite or a `BeforeSuite` hook. Example:

```gherkin
Feature: Base fixtures

  Scenario: Load reference data
    Given the database has directly the following users:
      | email              | password                          | role  |
      | admin@example.com  | $2y$13$PreHashedForTestingOnly... | admin |
      | test@example.com   | $2y$13$PreHashedForTestingOnly... | user  |

  Scenario: Load data via API
    Given the following users are registered via API:
      | email              | password     |
      | alice@example.com  | SecurePass1  |
      | bob@example.com    | SecurePass2  |
```

- **Direct insert steps**: Use PDO/Doctrine DBAL to INSERT rows. For data that doesn't need domain processing (pre-hashed passwords, reference enums, bulk records).
- **API-driven steps**: Reuse existing `KernelBrowser` to call endpoints (e.g., `POST /api/auth/register`). Ensures validation, events, password hashing all run.

### Snapshot Mechanism

1. **Initial snapshot**: After `make db-test` runs migrations + loads base fixtures, take a `pg_dump --format=custom` of `joblog_test` → stored as `api/var/test-snapshot.dump`.
2. **Before each scenario**: `pg_restore --clean --if-exists --dbname=joblog_test` from the snapshot file. This replaces the current `DELETE FROM users`.
3. **Why pg_dump/pg_restore**: Native PostgreSQL tooling, handles all tables automatically, no per-entity code needed. New tables are included without any code change.

### Scalability

The snapshot approach is inherently scalable — `pg_dump` captures the entire database schema and data. When new entities/tables are added (Job, Company, etc.), they are automatically included in the snapshot with zero fixture-infrastructure changes.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `Makefile` | Modified | Add `db`, `db-dev`, `db-test` targets |
| `api/features/fixtures/` | New | Fixture feature files |
| `api/tests/Behat/FixtureContext.php` | New | Steps for direct-insert and API-driven fixture loading |
| `api/tests/Behat/DatabaseSnapshot.php` | New | Snapshot/restore service wrapping pg_dump/pg_restore |
| `api/tests/Behat/Auth/AuthContext.php` | Modified | Remove hard-coded `DELETE FROM users`, delegate to snapshot |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| `pg_restore --clean` drops and recreates objects, may fail on first run if schema doesn't exist | Low | Initial setup creates DB + runs migrations before first snapshot |
| Snapshot file grows large with many fixtures | Low | Fixtures are small reference data; monitor and document size expectations |
| `pg_dump`/`pg_restore` not available in Alpine container | Low | `postgres16-client` package included in api Docker image or use `database` container for dump/restore |
| API-driven fixtures are slow (HTTP overhead per row) | Medium | Use direct insert for bulk data; API mode only for data requiring domain processing |

## Rollback Plan

1. Remove `db`, `db-dev`, `db-test` Makefile targets
2. Remove `FixtureContext.php` and `DatabaseSnapshot.php`
3. Restore `AuthContext::resetState` to previous `DELETE FROM users` implementation
4. No data loss — only test infrastructure changes

## Dependencies

- PostgreSQL 16 client tools (`pg_dump`, `pg_restore`) must be available in the `api` or `database` container
- Existing Behat + FriendsOfBehat/SymfonyExtension setup (already in place)
- `make db-test` must run after `migrate` to ensure schema exists before snapshot

## Success Criteria

- [ ] `make db` successfully orchestrates dev and test DB setup
- [ ] `make db-test` creates `joblog_test`, runs migrations, loads fixtures, takes snapshot
- [ ] Each Behat scenario starts from the same clean snapshot state
- [ ] Hard-coded `DELETE FROM users` is removed from `AuthContext`
- [ ] New entity tables are automatically handled by snapshot without code changes
- [ ] Both insertion modes (API-driven and direct insert) work correctly
- [ ] Existing Behat scenarios continue to pass without modification
