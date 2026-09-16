<?php

declare(strict_types=1);

namespace App\Auth\Domain;

interface RefreshTokenRepository
{
    public function save(RefreshToken $token): void;

    public function findByTokenHash(string $hash): ?RefreshToken;

    public function revokeFamily(string $familyId): void;

    public function revokeAllForUser(string $userId): void;
}
