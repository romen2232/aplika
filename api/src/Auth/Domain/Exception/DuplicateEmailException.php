<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use DomainException;

class DuplicateEmailException extends DomainException
{
    public static function forEmail(string $email): self
    {
        return new self(\sprintf('Email already registered: "%s"', $email));
    }
}
