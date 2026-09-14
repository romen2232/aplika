<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Persistence;

use App\Auth\Domain\User;
use App\Auth\Domain\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineUserRepository implements UserRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(User $user): void
    {
        $model = UserModel::fromDomain($user);
        $this->entityManager->persist($model);
        $this->entityManager->flush();
    }

    public function findByEmail(string $email): ?User
    {
        $model = $this->entityManager
            ->getRepository(UserModel::class)
            ->findOneBy(['email' => $email]);

        return $model?->toDomain();
    }

    public function findById(string $id): ?User
    {
        $model = $this->entityManager->find(UserModel::class, $id);

        return $model?->toDomain();
    }
}
