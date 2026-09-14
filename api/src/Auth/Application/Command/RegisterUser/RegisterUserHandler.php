<?php

declare(strict_types=1);

namespace App\Auth\Application\Command\RegisterUser;

use App\Auth\Application\PasswordHasherAdapter;
use App\Auth\Domain\Exception\DuplicateEmailException;
use App\Auth\Domain\Exception\WeakPasswordException;
use App\Auth\Domain\User;
use App\Auth\Domain\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class RegisterUserHandler
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): User
    {
        if (\strlen($command->plainPassword) < self::MIN_PASSWORD_LENGTH) {
            throw WeakPasswordException::tooShort(self::MIN_PASSWORD_LENGTH);
        }

        $existingUser = $this->repository->findByEmail($command->email);
        if (null !== $existingUser) {
            throw DuplicateEmailException::forEmail($command->email);
        }

        $adapter = PasswordHasherAdapter::forHashing($command->email, $command->plainPassword);
        $hashedPassword = $this->passwordHasher->hashPassword($adapter, $command->plainPassword);

        $userId = Uuid::v4()->toRfc4122();
        $user = User::register($userId, $command->email, $hashedPassword);

        $this->repository->save($user);

        return $user;
    }
}
