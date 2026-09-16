<?php

declare(strict_types=1);

namespace spec\App\Auth\Domain;

use App\Auth\Domain\RefreshToken;
use DateTimeImmutable;
use PhpSpec\ObjectBehavior;

class RefreshTokenSpec extends ObjectBehavior
{
    private const TOKEN_ID = '550e8400-e29b-41d4-a716-446655440001';
    private const USER_ID = '550e8400-e29b-41d4-a716-446655440000';
    private const TOKEN_HASH = 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3';
    private const FAMILY_ID = '550e8400-e29b-41d4-a716-446655440002';

    function let()
    {
        $this->beConstructedThrough('create', [
            self::TOKEN_ID,
            self::USER_ID,
            self::TOKEN_HASH,
            self::FAMILY_ID,
            new DateTimeImmutable('+7 days'),
        ]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(RefreshToken::class);
    }

    function it_creates_with_correct_attributes()
    {
        $this->tokenHash()->shouldReturn(self::TOKEN_HASH);
        $this->familyId()->shouldReturn(self::FAMILY_ID);
        $this->userId()->shouldReturn(self::USER_ID);
    }

    function it_is_not_expired_when_expires_at_is_in_the_future()
    {
        $this->isExpired()->shouldReturn(false);
    }

    function it_is_expired_when_expires_at_is_in_the_past()
    {
        $this->beConstructedThrough('create', [
            self::TOKEN_ID,
            self::USER_ID,
            self::TOKEN_HASH,
            self::FAMILY_ID,
            new DateTimeImmutable('-1 second'),
        ]);

        $this->isExpired()->shouldReturn(true);
    }

    function it_is_not_revoked_by_default()
    {
        $this->isRevoked()->shouldReturn(false);
    }

    function it_can_be_revoked()
    {
        $this->revoke();

        $this->isRevoked()->shouldReturn(true);
    }

    function it_rejects_empty_id()
    {
        $this->beConstructedThrough('create', [
            '',
            self::USER_ID,
            self::TOKEN_HASH,
            self::FAMILY_ID,
            new DateTimeImmutable('+7 days'),
        ]);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }

    function it_rejects_empty_user_id()
    {
        $this->beConstructedThrough('create', [
            self::TOKEN_ID,
            '',
            self::TOKEN_HASH,
            self::FAMILY_ID,
            new DateTimeImmutable('+7 days'),
        ]);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }

    function it_rejects_empty_token_hash()
    {
        $this->beConstructedThrough('create', [
            self::TOKEN_ID,
            self::USER_ID,
            '',
            self::FAMILY_ID,
            new DateTimeImmutable('+7 days'),
        ]);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }

    function it_rejects_empty_family_id()
    {
        $this->beConstructedThrough('create', [
            self::TOKEN_ID,
            self::USER_ID,
            self::TOKEN_HASH,
            '',
            new DateTimeImmutable('+7 days'),
        ]);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }
}
