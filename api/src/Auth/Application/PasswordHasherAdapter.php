<?php

declare(strict_types=1);

namespace App\Auth\Application;

use App\Auth\Domain\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class PasswordHasherAdapter implements PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly string $email,
        private readonly string $hashedPassword,
    ) {
    }

    public static function fromDomainUser(User $user): self
    {
        return new self($user->email(), $user->hashedPassword());
    }

    public static function forHashing(string $email, string $plainPassword): self
    {
        return new self($email, $plainPassword);
    }

    public function getPassword(): string
    {
        return $this->hashedPassword;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
