<?php

declare(strict_types=1);

namespace App\Auth\Application\Command\AuthenticateUser;

use App\Auth\Application\PasswordHasherAdapter;
use App\Auth\Application\TokenGeneratorInterface;
use App\Auth\Domain\Exception\InvalidCredentialsException;
use App\Auth\Domain\RefreshToken;
use App\Auth\Domain\RefreshTokenRepository;
use App\Auth\Domain\UserRepository;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class AuthenticateUserHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TokenGeneratorInterface $tokenGenerator,
        private readonly RefreshTokenRepository $refreshTokenRepository,
    ) {
    }

    /**
     * @return array{accessToken: string, refreshToken: string}
     */
    public function __invoke(AuthenticateUserCommand $command): array
    {
        $user = $this->repository->findByEmail($command->email);

        if (null === $user) {
            throw InvalidCredentialsException::invalid();
        }

        $adapter = PasswordHasherAdapter::fromDomainUser($user);

        if (!$this->passwordHasher->isPasswordValid($adapter, $command->plainPassword)) {
            throw InvalidCredentialsException::invalid();
        }

        $accessToken = $this->tokenGenerator->generate($user);

        // Create refresh token
        $refreshPlaintext = bin2hex(random_bytes(32));
        $refreshHash = hash('sha256', $refreshPlaintext);
        $familyId = Uuid::v4()->toRfc4122();

        $refreshToken = RefreshToken::create(
            Uuid::v4()->toRfc4122(),
            $user->id(),
            $refreshHash,
            $familyId,
            new DateTimeImmutable('+7 days'),
        );

        $this->refreshTokenRepository->save($refreshToken);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshPlaintext,
        ];
    }
}
