<?php

declare(strict_types=1);

namespace App\Auth\Application\Command\RegisterUser;

use App\Auth\Application\PasswordHasherAdapter;
use App\Auth\Domain\Exception\DuplicateEmailException;
use App\Auth\Domain\Password;
use App\Auth\Domain\User;
use App\Auth\Domain\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): User
    {
        $password = Password::fromPlainText($command->plainPassword);

        $existingUser = $this->repository->findByEmail($command->email);
        if (null !== $existingUser) {
            throw DuplicateEmailException::forEmail($command->email);
        }

        $adapter = PasswordHasherAdapter::forHashing($command->email, $password->plainText());
        $hashedPassword = $this->passwordHasher->hashPassword($adapter, $password->plainText());

        $userId = Uuid::v4()->toRfc4122();
        $user = User::register($userId, $command->email, $hashedPassword);

        $this->repository->save($user);

        return $user;
    }
}
