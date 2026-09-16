<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Persistence;

use App\Auth\Domain\RefreshToken;
use App\Auth\Domain\RefreshTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRefreshTokenRepository implements RefreshTokenRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(RefreshToken $token): void
    {
        $model = RefreshTokenModel::fromDomain($token);
        $existing = $this->em->find(RefreshTokenModel::class, $model->getId());

        if (null !== $existing) {
            $existing->setRevokedAt($model->getRevokedAt());
        } else {
            $this->em->persist($model);
        }

        $this->em->flush();
    }

    public function findByTokenHash(string $hash): ?RefreshToken
    {
        $model = $this->em->getRepository(RefreshTokenModel::class)->findOneBy(['tokenHash' => $hash]);

        return null !== $model ? $model->toDomain() : null;
    }

    public function revokeFamily(string $familyId): void
    {
        $now = new DateTimeImmutable();

        $this->em->createQueryBuilder()
            ->update(RefreshTokenModel::class, 'rt')
            ->set('rt.revokedAt', ':now')
            ->where('rt.familyId = :familyId')
            ->andWhere('rt.revokedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('familyId', $familyId)
            ->getQuery()
            ->execute();
    }

    public function revokeAllForUser(string $userId): void
    {
        $now = new DateTimeImmutable();

        $this->em->createQueryBuilder()
            ->update(RefreshTokenModel::class, 'rt')
            ->set('rt.revokedAt', ':now')
            ->where('rt.userId = :userId')
            ->andWhere('rt.revokedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }
}
