<?php

declare(strict_types=1);

namespace App\Auth\Application\Query\GetMe;

interface MeReadRepositoryInterface
{
    public function findMeById(string $id): ?MeReadModel;
}
