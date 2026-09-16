<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use InvalidArgumentException;

class InvalidTokenException extends InvalidArgumentException
{
    public static function invalid(): self
    {
        return new self('Invalid or missing token.');
    }
}
