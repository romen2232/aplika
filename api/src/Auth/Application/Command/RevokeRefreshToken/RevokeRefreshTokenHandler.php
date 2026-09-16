<?php

declare(strict_types=1);

namespace App\Auth\Application\Command\RevokeRefreshToken;

use App\Auth\Domain\RefreshTokenRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RevokeRefreshTokenHandler
{
    public function __construct(
        private readonly RefreshTokenRepository $refreshTokenRepository,
    ) {
    }

    public function __invoke(RevokeRefreshTokenCommand $command): void
    {
        $tokenHash = hash('sha256', $command->refreshTokenPlaintext);
        $token = $this->refreshTokenRepository->findByTokenHash($tokenHash);

        if (null === $token) {
            return; // Idempotent: nothing to revoke
        }

        $token->revoke();
        $this->refreshTokenRepository->save($token);
    }
}
