<?php

declare(strict_types=1);

namespace App\Auth\Application\Command\RefreshToken;

class RefreshTokenCommand
{
    public function __construct(
        public readonly string $refreshTokenPlaintext,
    ) {
    }
}
