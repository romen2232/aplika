<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\RateLimiting;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RateLimitDecider
{
    private const PUBLIC_LIMIT = 15;
    private const AUTHENTICATED_LIMIT = 50;
    private const INTERVAL_SECONDS = 60;

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function decide(Request $request): array
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if ($user !== null) {
            return [
                'key' => 'user_' . $user->getUserIdentifier(),
                'limit' => self::AUTHENTICATED_LIMIT,
                'interval' => self::INTERVAL_SECONDS,
            ];
        }

        return [
            'key' => 'ip_' . $request->getClientIp(),
            'limit' => self::PUBLIC_LIMIT,
            'interval' => self::INTERVAL_SECONDS,
        ];
    }
}
