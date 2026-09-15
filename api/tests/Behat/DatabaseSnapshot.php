<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use RuntimeException;

/**
 * Manages PostgreSQL database snapshots for Behat scenario isolation.
 *
 * Uses pg_dump/pg_restore to capture and restore the full database state,
 * ensuring each scenario starts from a clean, known state.
 */
final class DatabaseSnapshot
{
    private readonly string $databaseName;
    private readonly string $databaseHost;
    private readonly string $databasePort;
    private readonly string $databaseUser;
    private readonly string $databasePassword;

    public function __construct(
        private readonly string $snapshotPath,
        private readonly string $databaseUrl,
    ) {
        $parsed = parse_url($databaseUrl);
        if (!$parsed || !isset($parsed['host'], $parsed['path'])) {
            throw new RuntimeException('Invalid DATABASE_URL format.');
        }

        $this->databaseName = ltrim($parsed['path'], '/');
        $this->databaseHost = $parsed['host'];
        $this->databasePort = (string) ($parsed['port'] ?? 5432);
        $this->databaseUser = $parsed['user'] ?? 'aplika';
        $this->databasePassword = $parsed['pass'] ?? 'aplika';
    }

    /**
     * Restore the database from the snapshot file.
     *
     * Uses pg_restore with --clean --if-exists to drop and recreate all objects.
     *
     * @throws RuntimeException if the snapshot file does not exist
     */
    public function restore(): void
    {
        if (!$this->exists()) {
            throw new RuntimeException(\sprintf('Snapshot file "%s" does not exist. Run "make db" first.', $this->snapshotPath));
        }

        $command = \sprintf(
            'PGPASSWORD=%s pg_restore --host=%s --port=%s --username=%s --clean --if-exists --dbname=%s %s 2>&1',
            escapeshellarg($this->databasePassword),
            escapeshellarg($this->databaseHost),
            escapeshellarg($this->databasePort),
            escapeshellarg($this->databaseUser),
            escapeshellarg($this->databaseName),
            escapeshellarg($this->snapshotPath)
        );

        exec($command, $output, $returnCode);

        $outputStr = implode("\n", $output);

        // pg_restore may return non-zero on warnings (e.g., unrecognized config parameters)
        // Only fail on critical errors that prevent restore
        if (0 !== $returnCode && !str_contains($outputStr, 'errors ignored on restore')) {
            throw new RuntimeException(\sprintf('pg_restore failed (exit code %d): %s', $returnCode, $outputStr));
        }
    }

    /**
     * Create a snapshot of the current database state.
     *
     * Uses pg_dump with --format=custom for efficient storage and restore.
     */
    public function dump(): void
    {
        $command = \sprintf(
            'PGPASSWORD=%s pg_dump --host=%s --port=%s --username=%s --format=custom --file=%s %s 2>&1',
            escapeshellarg($this->databasePassword),
            escapeshellarg($this->databaseHost),
            escapeshellarg($this->databasePort),
            escapeshellarg($this->databaseUser),
            escapeshellarg($this->snapshotPath),
            escapeshellarg($this->databaseName)
        );

        exec($command, $output, $returnCode);

        if (0 !== $returnCode) {
            throw new RuntimeException(\sprintf('pg_dump failed (exit code %d): %s', $returnCode, implode("\n", $output)));
        }
    }

    /**
     * Check if the snapshot file exists.
     */
    public function exists(): bool
    {
        return file_exists($this->snapshotPath);
    }
}
