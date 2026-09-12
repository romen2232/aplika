<?php

declare(strict_types=1);

namespace spec\App\Auth\Domain;

use App\Auth\Domain\Exception\InvalidTokenException;
use App\Auth\Domain\TokenUserExtractor;
use PhpSpec\ObjectBehavior;

class TokenUserExtractorSpec extends ObjectBehavior
{
    function it_is_initializable(): void
    {
        $this->shouldHaveType(TokenUserExtractor::class);
    }

    function it_extracts_user_identity_from_valid_payload(): void
    {
        $payload = [
            'subject' => 'user-123',
            'email' => 'test@example.com',
            'roles' => ['ROLE_USER'],
            'exp' => time() + 3600,
        ];

        $result = $this->extract($payload);
        $result->shouldBeArray();
        $result['id']->shouldBe('user-123');
        $result['email']->shouldBe('test@example.com');
        $result['roles']->shouldBe(['ROLE_USER']);
    }

    function it_rejects_payload_without_subject(): void
    {
        $payload = [
            'email' => 'test@example.com',
            'roles' => ['ROLE_USER'],
        ];

        $this->shouldThrow(InvalidTokenException::class)->during('extract', [$payload]);
    }

    function it_rejects_payload_without_email(): void
    {
        $payload = [
            'subject' => 'user-123',
            'roles' => ['ROLE_USER'],
        ];

        $this->shouldThrow(InvalidTokenException::class)->during('extract', [$payload]);
    }

    function it_defaults_to_empty_roles_when_missing(): void
    {
        $payload = [
            'subject' => 'user-123',
            'email' => 'test@example.com',
        ];

        $result = $this->extract($payload);
        $result['roles']->shouldBe([]);
    }
}
