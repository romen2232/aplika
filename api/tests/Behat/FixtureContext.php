<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use PDO;
use PDOException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Fixture context for loading test data via Gherkin feature files.
 *
 * Supports two insertion modes:
 * - Direct DB insert: bypasses domain logic, for bulk/reference data
 * - API-driven insert: full backend processing via endpoints
 */
final class FixtureContext implements Context
{
    private const COMMON_PASSWORD = '12345';
    private const COMMON_PASSWORD_HASH = '$2y$13$lbPpssXYmBJ.t4yc2aF8x.x6.pOsDcNrpyB8xwd94KTPS2W.WRVS.';

    public function __construct(
        private readonly BehatState $state,
        private readonly DatabaseSnapshot $snapshot,
        private readonly KernelInterface $kernel,
    ) {
    }

    /**
     * Restore database from snapshot before each scenario.
     *
     * This replaces the hard-coded DELETE FROM users approach and scales
     * automatically with new tables.
     *
     * Skips restore if snapshot doesn't exist (e.g., during initial fixture loading).
     */
    #[BeforeScenario]
    public function restoreSnapshot(BeforeScenarioScope $scope): void
    {
        // Skip restore if snapshot doesn't exist yet (during fixture loading)
        if (!$this->snapshot->exists()) {
            return;
        }

        $this->state->reset();
        $this->snapshot->restore();
    }

    /**
     * Insert rows directly into the database, bypassing domain logic.
     *
     * Use for pre-hashed passwords, reference data, and bulk records.
     */
    #[Given('the database has directly the following :entity:')]
    public function directInsert(string $entity, TableNode $table): void
    {
        $tableName = $this->resolveTableName($entity);
        $pdo = $this->getPdo();

        foreach ($table->getHash() as $row) {
            if (!isset($row['password'])) {
                $row['password'] = self::COMMON_PASSWORD_HASH;
            }

            $columns = array_keys($row);
            $placeholders = array_map(fn ($col) => ':'.$col, $columns);

            $sql = \sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $tableName,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            try {
                $stmt = $pdo->prepare($sql);
                foreach ($row as $column => $value) {
                    $stmt->bindValue(':'.$column, $value);
                }
                $stmt->execute();
            } catch (PDOException $e) {
                throw new RuntimeException(\sprintf('Direct insert into "%s" failed: %s', $tableName, $e->getMessage()));
            }
        }
    }

    /**
     * Create entities by calling API endpoints, triggering full backend processing.
     *
     * Use for data that needs validation, password hashing, domain events, etc.
     */
    #[Given('the following :entity are registered via API:')]
    public function apiInsert(string $entity, TableNode $table): void
    {
        $endpoint = $this->resolveEndpoint($entity);
        $client = $this->getClient();

        foreach ($table->getHash() as $row) {
            if (!isset($row['password'])) {
                $row['password'] = self::COMMON_PASSWORD;
            }

            $client->request('POST', $endpoint, [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode($row));

            $response = $client->getResponse();
            if (201 !== $response->getStatusCode()) {
                $identifier = $row['email'] ?? $row['id'] ?? json_encode($row);
                throw new RuntimeException(\sprintf('API insert for "%s" failed. Status: %d, Response: %s', $identifier, $response->getStatusCode(), $response->getContent()));
            }
        }
    }

    /**
     * Resolve entity name to database table name.
     *
     * Simple pluralization: "user" -> "users", "users" -> "users"
     */
    private function resolveTableName(string $entity): string
    {
        // Simple pluralization — extend as needed
        if (str_ends_with($entity, 's')) {
            return $entity;
        }

        return $entity.'s';
    }

    /**
     * Resolve entity name to API endpoint.
     */
    private function resolveEndpoint(string $entity): string
    {
        return match (strtolower($entity)) {
            'users', 'user' => '/api/auth/register',
            default => throw new RuntimeException(\sprintf('No API endpoint configured for entity "%s".', $entity)),
        };
    }

    private function getPdo(): PDO
    {
        static $pdo = null;
        if (null === $pdo) {
            $databaseUrl = getenv('DATABASE_URL');
            if (!$databaseUrl) {
                throw new RuntimeException('DATABASE_URL environment variable is not set.');
            }

            // Parse PostgreSQL URL: postgresql://user:pass@host:port/dbname?params
            $parsed = parse_url($databaseUrl);
            if (!$parsed || !isset($parsed['host'], $parsed['path'])) {
                throw new RuntimeException('Invalid DATABASE_URL format.');
            }

            // Replace database name with test database
            $dbName = ltrim($parsed['path'], '/');
            $testDbName = 'joblog_test';

            $dsn = \sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $parsed['host'],
                $parsed['port'] ?? 5432,
                $testDbName
            );

            $user = $parsed['user'] ?? 'joblog';
            $pass = $parsed['pass'] ?? 'joblog';

            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $pdo;
    }

    private function getClient(): KernelBrowser
    {
        $client = $this->state->getClient();
        if (null === $client) {
            $client = $this->kernel->getContainer()->get('test.client');
            $this->state->setClient($client);
        }

        return $client;
    }
}
