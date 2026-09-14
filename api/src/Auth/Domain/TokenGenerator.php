<?php

declare(strict_types=1);

namespace App\Auth\Domain;

use Firebase\JWT\JWT;

class TokenGenerator
{
    private const TOKEN_TTL = 3600; // 1 hour in seconds

    public function __construct(private readonly string $secretKey)
    {
    }

    public function generate(User $user): string
    {
        $now = time();

        $payload = [
            'subject' => $user->id(),
            'email' => $user->email(),
            'roles' => $user->roles(),
            'iat' => $now,
            'exp' => $now + self::TOKEN_TTL,
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }
}
