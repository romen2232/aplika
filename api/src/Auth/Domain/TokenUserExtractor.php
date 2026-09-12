<?php

declare(strict_types=1);

namespace App\Auth\Domain;

use App\Auth\Domain\Exception\InvalidTokenException;

class TokenUserExtractor
{
    public function extract(array $payload): array
    {
        if (!isset($payload['sub'])) {
            throw new InvalidTokenException('Token missing required claim: sub');
        }

        if (!isset($payload['email'])) {
            throw new InvalidTokenException('Token missing required claim: email');
        }

        return [
            'id' => $payload['sub'],
            'email' => $payload['email'],
            'roles' => $payload['roles'] ?? [],
        ];
    }
}
