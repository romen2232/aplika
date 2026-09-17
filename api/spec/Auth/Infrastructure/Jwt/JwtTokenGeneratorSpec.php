<?php

declare(strict_types=1);

namespace spec\App\Auth\Infrastructure\Jwt;

use App\Auth\Domain\User;
use App\Auth\Infrastructure\Jwt\JwtTokenGenerator;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PhpSpec\ObjectBehavior;

class JwtTokenGeneratorSpec extends ObjectBehavior
{
    private const SECRET_KEY = 'test-secret-key-for-generation-that-is-long-enough-for-hs256';
    private const USER_ID = '550e8400-e29b-41d4-a716-446655440000';
    private const USER_EMAIL = 'user@example.com';
    private const USER_PASSWORD = '$2y$13$abcdefghijklmnopqrstuvwxyz01234567890123456789012345678901234';

    function let()
    {
        $this->beConstructedWith(self::SECRET_KEY);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(JwtTokenGenerator::class);
    }

    function it_generates_jwt_token_for_user()
    {
        $user = User::register(self::USER_ID, self::USER_EMAIL, 'Jane Doe', self::USER_PASSWORD);
        
        $token = $this->generate($user);
        $token->shouldBeString();
        $token->shouldNotBe('');
    }

    function it_generates_token_with_correct_claims()
    {
        $user = User::register(self::USER_ID, self::USER_EMAIL, 'Jane Doe', self::USER_PASSWORD);
        
        $token = $this->generate($user)->getWrappedObject();
        
        // Decode and verify claims
        $decoded = JWT::decode($token, new Key(self::SECRET_KEY, 'HS256'));
        
        if ($decoded->subject !== self::USER_ID) {
            throw new \RuntimeException('subject claim mismatch');
        }
        if ($decoded->email !== self::USER_EMAIL) {
            throw new \RuntimeException('email claim mismatch');
        }
        if ($decoded->roles !== ['ROLE_USER']) {
            throw new \RuntimeException('roles claim mismatch');
        }
        if (!is_int($decoded->iat)) {
            throw new \RuntimeException('iat claim missing or invalid');
        }
        if (!is_int($decoded->exp)) {
            throw new \RuntimeException('exp claim missing or invalid');
        }
    }

    function it_generates_token_with_expiration()
    {
        $user = User::register(self::USER_ID, self::USER_EMAIL, 'Jane Doe', self::USER_PASSWORD);
        
        $token = $this->generate($user)->getWrappedObject();
        $decoded = JWT::decode($token, new Key(self::SECRET_KEY, 'HS256'));
        
        // exp should be approximately 1 hour after iat
        $timeDiff = $decoded->exp - $decoded->iat;
        if ($timeDiff !== 3600) {
            throw new \RuntimeException(sprintf('Expected 3600 seconds, got %d', $timeDiff));
        }
    }

    function it_generates_token_with_hs256_algorithm()
    {
        $user = User::register(self::USER_ID, self::USER_EMAIL, 'Jane Doe', self::USER_PASSWORD);
        
        $token = $this->generate($user)->getWrappedObject();
        
        // Decode header to check algorithm
        $parts = explode('.', $token);
        $header = json_decode(base64_decode($parts[0]), true);
        
        if ($header['alg'] !== 'HS256') {
            throw new \RuntimeException(sprintf('Expected HS256, got %s', $header['alg']));
        }
    }
}
