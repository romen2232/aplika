<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Cookie;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class CookieHelper
{
    public function __construct(
        private readonly int $accessTokenTtl = 900,
        private readonly int $refreshTokenTtl = 604800,
        private readonly string $accessCookieName = 'access_token',
        private readonly string $refreshCookieName = 'refresh_token',
    ) {
    }

    public function setAuthCookies(Response $response, string $accessToken, string $refreshToken): void
    {
        $response->headers->setCookie(
            Cookie::create(
                $this->accessCookieName,
                $accessToken,
                time() + $this->accessTokenTtl,
                '/',
                null,
                true,
                true,
                false,
                Cookie::SAMESITE_LAX,
            )
        );

        $response->headers->setCookie(
            Cookie::create(
                $this->refreshCookieName,
                $refreshToken,
                time() + $this->refreshTokenTtl,
                '/',
                null,
                true,
                true,
                false,
                Cookie::SAMESITE_LAX,
            )
        );
    }

    public function clearAuthCookies(Response $response): void
    {
        $response->headers->clearCookie($this->accessCookieName, '/');
        $response->headers->clearCookie($this->refreshCookieName, '/');
    }

    public function getAccessCookieName(): string
    {
        return $this->accessCookieName;
    }

    public function getRefreshCookieName(): string
    {
        return $this->refreshCookieName;
    }
}
