<?php

declare(strict_types=1);

namespace App\Auth\Domain;

interface UserRepository
{
    public function save(User $user): void;

    public function findByEmail(string $email): ?User;

    public function findById(string $id): ?User;
}
