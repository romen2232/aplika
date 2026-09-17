<?php

declare(strict_types=1);

namespace spec\App\Auth\Domain;

use App\Auth\Domain\Email;
use App\Auth\Domain\Exception\WeakPasswordException;
use App\Auth\Domain\User;
use PhpSpec\ObjectBehavior;

class UserSpec extends ObjectBehavior
{
    private const USER_ID = '550e8400-e29b-41d4-a716-446655440000';
    private const VALID_EMAIL = 'user@example.com';
    private const VALID_FULL_NAME = 'Jane Doe';
    private const VALID_HASHED_PASSWORD = '$2y$13$abcdefghijklmnopqrstuvwxyz01234567890123456789012345678901234';

    function it_is_initializable()
    {
        $this->shouldHaveType(User::class);
    }

    function it_registers_with_valid_data()
    {
        $this->beConstructedThrough('register', [
            self::USER_ID,
            self::VALID_EMAIL,
            self::VALID_FULL_NAME,
            self::VALID_HASHED_PASSWORD,
        ]);

        $this->id()->shouldReturn(self::USER_ID);
        $this->email()->shouldReturn(self::VALID_EMAIL);
        $this->fullName()->shouldReturn(self::VALID_FULL_NAME);
        $this->hashedPassword()->shouldReturn(self::VALID_HASHED_PASSWORD);
        $this->roles()->shouldReturn(['ROLE_USER']);
    }

    function it_registers_with_custom_roles()
    {
        $this->beConstructedThrough('register', [
            self::USER_ID,
            self::VALID_EMAIL,
            self::VALID_FULL_NAME,
            self::VALID_HASHED_PASSWORD,
            ['ROLE_USER', 'ROLE_ADMIN'],
        ]);

        $this->roles()->shouldReturn(['ROLE_USER', 'ROLE_ADMIN']);
    }

    function it_has_default_role_user()
    {
        $this->beConstructedThrough('register', [
            self::USER_ID,
            self::VALID_EMAIL,
            self::VALID_FULL_NAME,
            self::VALID_HASHED_PASSWORD,
        ]);

        $this->roles()->shouldContain('ROLE_USER');
    }

    function it_rejects_empty_id()
    {
        $this->beConstructedThrough('register', [
            '',
            self::VALID_EMAIL,
            self::VALID_FULL_NAME,
            self::VALID_HASHED_PASSWORD,
        ]);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }

    function it_rejects_invalid_email()
    {
        $this->beConstructedThrough('register', [
            self::USER_ID,
            'not-an-email',
            self::VALID_FULL_NAME,
            self::VALID_HASHED_PASSWORD,
        ]);

        $this->shouldThrow(\App\Auth\Domain\Exception\InvalidEmailException::class)->duringInstantiation();
    }

    function it_rejects_empty_full_name()
    {
        $this->beConstructedThrough('register', [
            self::USER_ID,
            self::VALID_EMAIL,
            '',
            self::VALID_HASHED_PASSWORD,
        ]);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }

    function it_rejects_empty_password()
    {
        $this->beConstructedThrough('register', [
            self::USER_ID,
            self::VALID_EMAIL,
            self::VALID_FULL_NAME,
            '',
        ]);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }
}
