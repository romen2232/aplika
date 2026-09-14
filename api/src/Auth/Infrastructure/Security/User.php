<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Security;

use App\Auth\Domain\User as DomainUser;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $email,
        private readonly array $roles = [],
    ) {
    }

    public static function fromDomain(DomainUser $domainUser): self
    {
        return new self(
            $domainUser->id(),
            $domainUser->email(),
            $domainUser->roles()
        );
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
