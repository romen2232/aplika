<?php

declare(strict_types=1);

namespace App\Auth\Application\Query\GetMe;

final class MeReadModel
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $fullName,
        public readonly array $roles,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'fullName' => $this->fullName,
            'roles' => $this->roles,
        ];
    }
}
