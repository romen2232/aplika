<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use DomainException;

class InvalidCredentialsException extends DomainException
{
    public static function invalid(): self
    {
        return new self('Invalid credentials');
    }
}
