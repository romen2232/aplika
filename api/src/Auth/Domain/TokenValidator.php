<?php

declare(strict_types=1);

namespace App\Auth\Domain;

use App\Auth\Domain\Exception\InvalidTokenException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenValidator
{
    public function __construct(
        private readonly string $secretKey
    ) {
    }

    public function validate(string $token): array
    {
        if (empty($token)) {
            throw new InvalidTokenException('Token cannot be empty');
        }

        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new InvalidTokenException('Invalid token: ' . $e->getMessage(), 0, $e);
        }
    }
}
