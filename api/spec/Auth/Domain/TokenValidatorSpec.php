<?php

declare(strict_types=1);

namespace spec\App\Auth\Domain;

use App\Auth\Domain\Exception\InvalidTokenException;
use App\Auth\Domain\TokenValidator;
use Firebase\JWT\JWT;
use PhpSpec\ObjectBehavior;

class TokenValidatorSpec extends ObjectBehavior
{
    private const SECRET_KEY = 'test-secret-key-for-validation-that-is-long-enough-for-hs256';

    function let(): void
    {
        $this->beConstructedWith(self::SECRET_KEY);
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(TokenValidator::class);
    }

    function it_validates_a_valid_token_and_returns_payload(): void
    {
        $payload = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
            'exp' => time() + 3600,
        ];

        $token = JWT::encode($payload, self::SECRET_KEY, 'HS256');

        $result = $this->validate($token);
        $result->shouldBeArray();
        $result['sub']->shouldBe('user-123');
        $result['email']->shouldBe('test@example.com');
    }

    function it_rejects_an_expired_token(): void
    {
        $payload = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
            'exp' => time() - 3600,
        ];

        $token = JWT::encode($payload, self::SECRET_KEY, 'HS256');

        $this->shouldThrow(InvalidTokenException::class)->during('validate', [$token]);
    }

    function it_rejects_a_token_with_invalid_signature(): void
    {
        $payload = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
            'exp' => time() + 3600,
        ];

        $token = JWT::encode($payload, 'wrong-secret-key-that-is-long-enough-for-hs256', 'HS256');

        $this->shouldThrow(InvalidTokenException::class)->during('validate', [$token]);
    }

    function it_rejects_a_malformed_token(): void
    {
        $this->shouldThrow(InvalidTokenException::class)->during('validate', ['not.a.valid.jwt']);
    }

    function it_rejects_an_empty_token(): void
    {
        $this->shouldThrow(InvalidTokenException::class)->during('validate', ['']);
    }
}
