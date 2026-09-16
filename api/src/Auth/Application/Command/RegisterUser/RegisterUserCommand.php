<?php

declare(strict_types=1);

namespace App\Auth\Application\Command\RegisterUser;

use App\Auth\Domain\Password;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Invalid email format')]
        public readonly string $email,
        #[Assert\NotBlank(message: 'Password is required')]
        #[Assert\Length(min: Password::MIN_LENGTH, minMessage: 'Password must be at least {{ limit }} characters')]
        public readonly string $plainPassword,
    ) {
    }
}
