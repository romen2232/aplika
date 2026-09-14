<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use DomainException;

class WeakPasswordException extends DomainException
{
    public static function tooShort(int $minLength): self
    {
        return new self(\sprintf('Password must be at least %d characters', $minLength));
    }
}
