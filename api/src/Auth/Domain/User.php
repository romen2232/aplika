<?php

declare(strict_types=1);

namespace App\Auth\Domain;

use InvalidArgumentException;

final class User
{
    private function __construct(
        private readonly string $id,
        private readonly Email $email,
        private readonly string $hashedPassword,
        private readonly array $roles,
    ) {
    }

    public static function register(
        string $id,
        string $email,
        string $hashedPassword,
        array $roles = ['ROLE_USER'],
    ): self {
        if (empty($id)) {
            throw new InvalidArgumentException('User ID cannot be empty');
        }

        if (empty($hashedPassword)) {
            throw new InvalidArgumentException('Password cannot be empty');
        }

        $emailVo = Email::fromString($email);

        return new self($id, $emailVo, $hashedPassword, $roles);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email->value();
    }

    public function hashedPassword(): string
    {
        return $this->hashedPassword;
    }

    public function roles(): array
    {
        return $this->roles;
    }
}
