# Tasks: Database Fixtures with Snapshot Reset

## Implementation Order

Strict TDD: Red → Green → Refactor. Each task is atomic and verifiable.

---

## T001: DatabaseSnapshot — restore() command building

**Description**: Create `DatabaseSnapshot` class with `restore()` method that builds the correct `pg_restore` shell command. Test that the command string is correct (mock exec).

**Files**:
- Create: `api/tests/Behat/DatabaseSnapshot.php`
- Create: `api/tests/Behat/DatabaseSnapshotTest.php` (unit test)

**Dependencies**: None

**Estimated lines**: ~60

**Verification**: Unit test passes — `restore()` builds correct shell command string.

---

## T002: DatabaseSnapshot — dump() command building

**Description**: Add `dump()` method to `DatabaseSnapshot` that builds the correct `pg_dump` shell command. Test command string.

**Files**:
- Modify: `api/tests/Behat/DatabaseSnapshot.php`
- Modify: `api/tests/Behat/DatabaseSnapshotTest.php`

**Dependencies**: T001

**Estimated lines**: ~30

**Verification**: Unit test passes — `dump()` builds correct shell command string.

---

## T003: DatabaseSnapshot — exists() and error handling

**Description**: Add `exists()` method to check if snapshot file exists. Add error handling in `restore()` for missing snapshot file (throw RuntimeException with clear message).

**Files**:
- Modify: `api/tests/Behat/DatabaseSnapshot.php`
- Modify: `api/tests/Behat/DatabaseSnapshotTest.php`

**Dependencies**: T002

**Estimated lines**: ~25

**Verification**: Unit tests pass — `exists()` returns correct boolean, `restore()` throws on missing file.

---

## T004: DatabaseSnapshot — integration test (real pg_dump/pg_restore)

**Description**: Integration test that runs real `pg_dump` and `pg_restore` against `joblog_test` database. Verify snapshot cycle works end-to-end.

**Files**:
- Create: `api/tests/Behat/DatabaseSnapshotIntegrationTest.php`

**Dependencies**: T003

**Estimated lines**: ~40

**Verification**: Integration test passes — snapshot created, restored, data matches.

---

## T005: FixtureContext — direct insert step definition

**Description**: Create `FixtureContext` with step definition for direct DB insert: `Given the database has directly the following {entity}:`. Use PDO to INSERT rows. Map entity name to table name (e.g., "users" → "users" table).

**Files**:
- Create: `api/tests/Behat/FixtureContext.php`
- Create: `api/features/fixtures.feature` (empty or minimal)

**Dependencies**: T003

**Estimated lines**: ~50

**Verification**: Behat scenario with direct insert step passes — rows inserted into DB.

---

## T006: FixtureContext — API-driven insert step definition

**Description**: Add step definition for API-driven insert: `Given the following {entity} are registered via API:`. Use `KernelBrowser` to call endpoints (e.g., `POST /api/auth/register` for users). Verify response status 201.

**Files**:
- Modify: `api/tests/Behat/FixtureContext.php`

**Dependencies**: T005

**Estimated lines**: ~40

**Verification**: Behat scenario with API insert step passes — users created via API.

---

## T007: FixtureContext — BeforeSuite hook (idempotent)

**Description**: Add `@BeforeSuite` hook to `FixtureContext` that loads `api/features/fixtures.feature` once. Make it idempotent: check a flag in `BehatState` or a DB marker to skip if already loaded.

**Files**:
- Modify: `api/tests/Behat/FixtureContext.php`
- Modify: `api/features/fixtures.feature` (add fixture data)

**Dependencies**: T006

**Estimated lines**: ~35

**Verification**: Run Behat suite twice — fixtures loaded only once (idempotent).

---

## T008: FixtureContext — BeforeScenario hook (snapshot restore)

**Description**: Add `@BeforeScenario` hook to `FixtureContext` that calls `DatabaseSnapshot::restore()` before each scenario. This replaces the hard-coded `DELETE FROM users`.

**Files**:
- Modify: `api/tests/Behat/FixtureContext.php`

**Dependencies**: T007, T004

**Estimated lines**: ~15

**Verification**: Behat scenario starts with clean snapshot state — previous scenario's changes are reverted.

---

## T009: AuthContext — remove DELETE FROM users

**Description**: Remove the hard-coded `DELETE FROM users` block from `AuthContext::resetState`. Keep `BehatState::reset()` for in-memory state cleanup.

**Files**:
- Modify: `api/tests/Behat/Auth/AuthContext.php`

**Dependencies**: T008

**Estimated lines**: ~-10 (net removal)

**Verification**: Existing Behat scenarios still pass — snapshot restore handles DB cleanup.

---

## T010: Behat config — register FixtureContext

**Description**: Register `FixtureContext` in `api/behat.php` and `api/config/services_test.yaml`. Wire `DatabaseSnapshot` as a service dependency.

**Files**:
- Modify: `api/behat.php`
- Modify: `api/config/services_test.yaml`

**Dependencies**: T008

**Estimated lines**: ~20

**Verification**: Behat suite runs without "context not found" errors.

---

## T011: Makefile — db, db-dev, db-test targets

**Description**: Add `db`, `db-dev`, `db-test` targets to Makefile. `db` orchestrates both. `db-dev` drops/creates `joblog`, runs migrations. `db-test` drops/creates `joblog_test`, runs migrations, takes initial snapshot via `pg_dump`.

**Files**:
- Modify: `Makefile`

**Dependencies**: T004

**Estimated lines**: ~25

**Verification**: `make db-test` creates snapshot file at `api/var/test-snapshot.dump`.

---

## T012: Base fixture file — populate fixtures.feature

**Description**: Populate `api/features/fixtures.feature` with base fixture data: admin user, test user. Use both direct insert (pre-hashed password) and API-driven insert modes.

**Files**:
- Modify: `api/features/fixtures.feature`

**Dependencies**: T007

**Estimated lines**: ~20

**Verification**: Fixtures loaded before test suite — data exists in DB.

---

## T013: Integration test — full fixture + snapshot cycle

**Description**: End-to-end test: run `make db-test`, execute Behat suite with fixtures, verify scenario isolation (each scenario starts from same state).

**Files**: None (verification only)

**Dependencies**: T009, T010, T011, T012

**Estimated lines**: 0

**Verification**: Full Behat suite passes — fixtures loaded, snapshot restore works, no regressions.

---

## Review Workload Forecast

| Metric | Value |
|--------|-------|
| **Total estimated changed lines** | ~360 |
| **400-line budget risk** | Low |
| **Chained PRs recommended** | No |
| **Decision needed before apply** | No |

**Breakdown**:
- New files: ~280 lines (DatabaseSnapshot, FixtureContext, tests, fixtures.feature)
- Modified files: ~80 lines (AuthContext, behat.php, services_test.yaml, Makefile)
- Total: ~360 lines (under 400-line budget)

**Recommendation**: Single PR is feasible. No chained PRs needed.
