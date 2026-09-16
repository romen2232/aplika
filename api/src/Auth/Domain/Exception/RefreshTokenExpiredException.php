<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use RuntimeException;

final class RefreshTokenExpiredException extends RuntimeException
{
    public static function expired(): self
    {
        return new self('Refresh token has expired.');
    }
}
