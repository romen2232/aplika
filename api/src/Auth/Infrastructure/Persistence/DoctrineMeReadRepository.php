<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Persistence;

use App\Auth\Application\Query\GetMe\MeReadModel;
use App\Auth\Application\Query\GetMe\MeReadRepositoryInterface;
use Doctrine\DBAL\Connection;

final class DoctrineMeReadRepository implements MeReadRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function findMeById(string $id): ?MeReadModel
    {
        $sql = <<<'SQL'
            SELECT id, email, roles
            FROM users
            WHERE id = :id
        SQL;

        $row = $this->connection->fetchAssociative($sql, ['id' => $id]);

        if (false === $row) {
            return null;
        }

        return new MeReadModel(
            id: $row['id'],
            email: $row['email'],
            roles: \is_string($row['roles']) ? json_decode($row['roles'], true) : $row['roles'],
        );
    }
}
