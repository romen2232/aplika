<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Jwt;

use App\Auth\Domain\Exception\InvalidTokenException;

class JwtTokenUserExtractor
{
    public function extract(array $payload): array
    {
        if (!isset($payload['subject'])) {
            throw new InvalidTokenException('Token missing required claim: subject');
        }

        if (!isset($payload['email'])) {
            throw new InvalidTokenException('Token missing required claim: email');
        }

        return [
            'id' => $payload['subject'],
            'email' => $payload['email'],
            'roles' => $payload['roles'] ?? [],
        ];
    }
}
