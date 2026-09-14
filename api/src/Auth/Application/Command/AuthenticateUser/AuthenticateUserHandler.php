<?php

declare(strict_types=1);

namespace App\Auth\Application\Command\AuthenticateUser;

use App\Auth\Application\PasswordHasherAdapter;
use App\Auth\Domain\Exception\InvalidCredentialsException;
use App\Auth\Domain\TokenGenerator;
use App\Auth\Domain\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
class AuthenticateUserHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TokenGenerator $tokenGenerator,
    ) {
    }

    public function __invoke(AuthenticateUserCommand $command): string
    {
        $user = $this->repository->findByEmail($command->email);

        if (null === $user) {
            throw InvalidCredentialsException::invalid();
        }

        $adapter = PasswordHasherAdapter::fromDomainUser($user);

        if (!$this->passwordHasher->isPasswordValid($adapter, $command->plainPassword)) {
            throw InvalidCredentialsException::invalid();
        }

        return $this->tokenGenerator->generate($user);
    }
}
