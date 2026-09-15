<?php

declare(strict_types=1);

namespace spec\App\Shared\Infrastructure\RateLimiting;

use App\Shared\Infrastructure\RateLimiting\RateLimitDecider;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class RateLimitDeciderSpec extends ObjectBehavior
{
    function let(TokenStorageInterface $tokenStorage)
    {
        $this->beConstructedWith($tokenStorage);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(RateLimitDecider::class);
    }

    function it_returns_ip_based_key_for_unauthenticated_request(
        TokenStorageInterface $tokenStorage,
        Request $request
    ) {
        $request->getClientIp()->willReturn('192.168.1.1');
        $tokenStorage->getToken()->willReturn(null);

        $result = $this->decide($request);

        $result->shouldBeArray();
        $result->shouldHaveKey('key');
        $result->shouldHaveKey('limit');
        $result->shouldHaveKey('interval');
        $result['key']->shouldBe('ip_192.168.1.1');
        $result['limit']->shouldBe(15);
        $result['interval']->shouldBe(60);
    }

    function it_returns_user_based_key_for_authenticated_request(
        TokenStorageInterface $tokenStorage,
        Request $request,
        TokenInterface $token,
        UserInterface $user
    ) {
        $tokenStorage->getToken()->willReturn($token);
        $token->getUser()->willReturn($user);
        $user->getUserIdentifier()->willReturn('user-123');

        $result = $this->decide($request);

        $result->shouldBeArray();
        $result['key']->shouldBe('user_user-123');
        $result['limit']->shouldBe(50);
        $result['interval']->shouldBe(60);
    }

    function it_falls_back_to_ip_when_token_has_no_user(
        TokenStorageInterface $tokenStorage,
        Request $request,
        TokenInterface $token
    ) {
        $request->getClientIp()->willReturn('10.0.0.1');
        $tokenStorage->getToken()->willReturn($token);
        $token->getUser()->willReturn(null);

        $result = $this->decide($request);

        $result['key']->shouldBe('ip_10.0.0.1');
        $result['limit']->shouldBe(15);
        $result['interval']->shouldBe(60);
    }

    function it_handles_ipv6_addresses(
        TokenStorageInterface $tokenStorage,
        Request $request
    ) {
        $request->getClientIp()->willReturn('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
        $tokenStorage->getToken()->willReturn(null);

        $result = $this->decide($request);

        $result['key']->shouldBe('ip_2001:0db8:85a3:0000:0000:8a2e:0370:7334');
        $result['limit']->shouldBe(15);
    }
}
