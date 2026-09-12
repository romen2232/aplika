<?php

declare(strict_types=1);

namespace App\Tests\Auth\Infrastructure\Security;

use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JwtAuthenticatorTest extends WebTestCase
{
    private const JWT_SECRET = 'dev-only-change-me-this-must-be-at-least-32-bytes-long';

    public function testProtectedEndpointWithoutTokenReturns401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/profile');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProtectedEndpointWithInvalidTokenReturns401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid.token.here',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProtectedEndpointWithExpiredTokenReturns401(): void
    {
        $client = static::createClient();

        $payload = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
            'roles' => ['ROLE_USER'],
            'exp' => time() - 3600,
        ];

        $token = JWT::encode($payload, self::JWT_SECRET, 'HS256');

        $client->request('GET', '/api/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProtectedEndpointWithValidTokenReturns200(): void
    {
        $client = static::createClient();

        $payload = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
            'roles' => ['ROLE_USER'],
            'exp' => time() + 3600,
        ];

        $token = JWT::encode($payload, self::JWT_SECRET, 'HS256');

        $client->request('GET', '/api/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('user-123', $response['id']);
        $this->assertSame('test@example.com', $response['email']);
        $this->assertSame(['ROLE_USER'], $response['roles']);
    }

    public function testProtectedEndpointWithWrongSignatureReturns401(): void
    {
        $client = static::createClient();

        $payload = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
            'roles' => ['ROLE_USER'],
            'exp' => time() + 3600,
        ];

        $token = JWT::encode($payload, 'wrong-secret-key-that-is-long-enough-for-hs256', 'HS256');

        $client->request('GET', '/api/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(401);
    }
}
