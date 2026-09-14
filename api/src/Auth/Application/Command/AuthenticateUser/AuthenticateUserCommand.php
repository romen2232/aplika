<?php

declare(strict_types=1);

namespace App\Auth\Application\Command\AuthenticateUser;

class AuthenticateUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $plainPassword,
    ) {
    }
}
