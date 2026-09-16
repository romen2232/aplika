<?php

declare(strict_types=1);

namespace App\Auth\Application\Query\GetMe;

use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetMeHandler
{
    public function __construct(
        private readonly MeReadRepositoryInterface $readRepository,
    ) {
    }

    public function __invoke(GetMe $query): MeReadModel
    {
        $readModel = $this->readRepository->findMeById($query->userId);

        if (null === $readModel) {
            throw new RuntimeException(\sprintf('User with id "%s" not found', $query->userId));
        }

        return $readModel;
    }
}
