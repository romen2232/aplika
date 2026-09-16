<?php

declare(strict_types=1);

namespace spec\App\Auth\Domain;

use App\Auth\Domain\Exception\WeakPasswordException;
use App\Auth\Domain\Password;
use PhpSpec\ObjectBehavior;

class PasswordSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Password::class);
    }

    function it_creates_password_from_plain_text()
    {
        $this->beConstructedThrough('fromPlainText', ['SecurePass123']);
        $this->plainText()->shouldReturn('SecurePass123');
    }

    function it_accepts_password_at_minimum_length()
    {
        $this->beConstructedThrough('fromPlainText', [str_repeat('a', Password::MIN_LENGTH)]);
        $this->plainText()->shouldReturn(str_repeat('a', Password::MIN_LENGTH));
    }

    function it_rejects_password_shorter_than_minimum_length()
    {
        $this->beConstructedThrough('fromPlainText', [str_repeat('a', Password::MIN_LENGTH - 1)]);
        $this->shouldThrow(WeakPasswordException::class)->duringInstantiation();
    }

    function it_rejects_empty_password()
    {
        $this->beConstructedThrough('fromPlainText', ['']);
        $this->shouldThrow(WeakPasswordException::class)->duringInstantiation();
    }
}
