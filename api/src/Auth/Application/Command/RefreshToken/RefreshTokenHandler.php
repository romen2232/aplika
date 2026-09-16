<?php

declare(strict_types=1);

namespace App\Auth\Application\Command\RefreshToken;

use App\Auth\Application\TokenGeneratorInterface;
use App\Auth\Domain\Exception\InvalidTokenException;
use App\Auth\Domain\Exception\RefreshTokenExpiredException;
use App\Auth\Domain\Exception\RefreshTokenReuseException;
use App\Auth\Domain\RefreshToken;
use App\Auth\Domain\RefreshTokenRepository;
use App\Auth\Domain\UserRepository;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class RefreshTokenHandler
{
    public function __construct(
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly UserRepository $userRepository,
        private readonly TokenGeneratorInterface $tokenGenerator,
    ) {
    }

    /**
     * @return array{accessToken: string, refreshToken: string}
     */
    public function __invoke(RefreshTokenCommand $command): array
    {
        $tokenHash = hash('sha256', $command->refreshTokenPlaintext);
        $existingToken = $this->refreshTokenRepository->findByTokenHash($tokenHash);

        if (null === $existingToken) {
            throw InvalidTokenException::invalid();
        }

        // Reuse detection: if already revoked, the whole family is compromised
        if ($existingToken->isRevoked()) {
            $this->refreshTokenRepository->revokeFamily($existingToken->familyId());
            throw RefreshTokenReuseException::detected($existingToken->familyId());
        }

        if ($existingToken->isExpired()) {
            throw RefreshTokenExpiredException::expired();
        }

        // Revoke old token (rotation)
        $existingToken->revoke();
        $this->refreshTokenRepository->save($existingToken);

        // Generate new refresh token
        $newRefreshPlaintext = bin2hex(random_bytes(32));
        $newRefreshHash = hash('sha256', $newRefreshPlaintext);

        $newRefreshToken = RefreshToken::create(
            Uuid::v4()->toRfc4122(),
            $existingToken->userId(),
            $newRefreshHash,
            $existingToken->familyId(),
            new DateTimeImmutable('+7 days'),
        );

        $this->refreshTokenRepository->save($newRefreshToken);

        // Generate new access token (JWT)
        $user = $this->userRepository->findById($existingToken->userId());

        if (null === $user) {
            throw InvalidTokenException::invalid();
        }

        $newAccessToken = $this->tokenGenerator->generate($user);

        return [
            'accessToken' => $newAccessToken,
            'refreshToken' => $newRefreshPlaintext,
        ];
    }
}
