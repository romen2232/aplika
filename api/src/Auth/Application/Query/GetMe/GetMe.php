<?php

declare(strict_types=1);

namespace App\Auth\Application\Query\GetMe;

final class GetMe
{
    public function __construct(
        public readonly string $userId,
    ) {
    }
}
