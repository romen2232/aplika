<?php

declare(strict_types=1);

namespace App\Auth\Application\Command\AuthenticateUser;

use Symfony\Component\Validator\Constraints as Assert;

class AuthenticateUserCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Invalid email format')]
        public readonly string $email,
        #[Assert\NotBlank(message: 'Password is required')]
        public readonly string $plainPassword,
    ) {
    }
}
