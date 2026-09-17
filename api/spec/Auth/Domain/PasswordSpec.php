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

    function it_accepts_password_at_minimum_length_with_letters_and_numbers()
    {
        $this->beConstructedThrough('fromPlainText', ['abcdefg1']);
        $this->plainText()->shouldReturn('abcdefg1');
    }

    function it_rejects_password_shorter_than_minimum_length()
    {
        $this->beConstructedThrough('fromPlainText', ['abcde1']);
        $this->shouldThrow(WeakPasswordException::class)->duringInstantiation();
    }

    function it_rejects_empty_password()
    {
        $this->beConstructedThrough('fromPlainText', ['']);
        $this->shouldThrow(WeakPasswordException::class)->duringInstantiation();
    }

    function it_rejects_password_without_letters()
    {
        $this->beConstructedThrough('fromPlainText', ['12345678']);
        $this->shouldThrow(WeakPasswordException::class)->duringInstantiation();
    }

    function it_rejects_password_without_numbers()
    {
        $this->beConstructedThrough('fromPlainText', ['abcdefgh']);
        $this->shouldThrow(WeakPasswordException::class)->duringInstantiation();
    }

    function it_accepts_password_with_letters_and_numbers()
    {
        $this->beConstructedThrough('fromPlainText', ['SecurePass123']);
        $this->plainText()->shouldReturn('SecurePass123');
    }
}
