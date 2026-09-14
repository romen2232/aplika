<?php

declare(strict_types=1);

namespace spec\App\Auth\Domain;

use App\Auth\Domain\Email;
use App\Auth\Domain\Exception\InvalidEmailException;
use PhpSpec\ObjectBehavior;

class EmailSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Email::class);
    }

    function it_creates_email_from_valid_string()
    {
        $this->beConstructedThrough('fromString', ['user@example.com']);
        $this->value()->shouldReturn('user@example.com');
    }

    function it_creates_email_with_plus_addressing()
    {
        $this->beConstructedThrough('fromString', ['user+tag@example.com']);
        $this->value()->shouldReturn('user+tag@example.com');
    }

    function it_creates_email_with_subdomain()
    {
        $this->beConstructedThrough('fromString', ['user@mail.example.com']);
        $this->value()->shouldReturn('user@mail.example.com');
    }

    function it_rejects_empty_string()
    {
        $this->beConstructedThrough('fromString', ['']);
        $this->shouldThrow(InvalidEmailException::class)->duringInstantiation();
    }

    function it_rejects_string_without_at_symbol()
    {
        $this->beConstructedThrough('fromString', ['userexample.com']);
        $this->shouldThrow(InvalidEmailException::class)->duringInstantiation();
    }

    function it_rejects_string_without_domain()
    {
        $this->beConstructedThrough('fromString', ['user@']);
        $this->shouldThrow(InvalidEmailException::class)->duringInstantiation();
    }

    function it_rejects_string_without_local_part()
    {
        $this->beConstructedThrough('fromString', ['@example.com']);
        $this->shouldThrow(InvalidEmailException::class)->duringInstantiation();
    }

    function it_rejects_string_with_spaces()
    {
        $this->beConstructedThrough('fromString', ['user @example.com']);
        $this->shouldThrow(InvalidEmailException::class)->duringInstantiation();
    }

    function it_converts_to_string()
    {
        $this->beConstructedThrough('fromString', ['user@example.com']);
        $this->__toString()->shouldReturn('user@example.com');
    }
}
