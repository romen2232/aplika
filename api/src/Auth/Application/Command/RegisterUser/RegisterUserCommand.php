<?php

declare(strict_types=1);

namespace App\Auth\Application\Command\RegisterUser;

class RegisterUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $plainPassword,
    ) {
    }
}
