<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use RuntimeException;

final class RefreshTokenReuseException extends RuntimeException
{
    public static function detected(string $familyId): self
    {
        return new self(\sprintf('Refresh token reuse detected for family "%s". All tokens revoked.', $familyId));
    }
}
