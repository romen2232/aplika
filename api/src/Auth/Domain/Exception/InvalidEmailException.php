<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use InvalidArgumentException;

class InvalidEmailException extends InvalidArgumentException
{
    public static function forEmail(string $email): self
    {
        return new self(\sprintf('Invalid email format: "%s"', $email));
    }
}
