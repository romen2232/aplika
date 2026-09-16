<?php

declare(strict_types=1);

namespace App\Auth\Application\Command\RevokeRefreshToken;

class RevokeRefreshTokenCommand
{
    public function __construct(
        public readonly string $refreshTokenPlaintext,
    ) {
    }
}
