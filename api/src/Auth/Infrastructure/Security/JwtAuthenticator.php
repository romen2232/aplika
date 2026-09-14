<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Security;

use App\Auth\Domain\Exception\InvalidTokenException;
use App\Auth\Domain\TokenUserExtractor;
use App\Auth\Domain\TokenValidator;
use App\Auth\Domain\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly TokenValidator $tokenValidator,
        private readonly TokenUserExtractor $userExtractor,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization', ''), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization', '');
        $token = substr($authHeader, 7);

        if (empty($token)) {
            throw new CustomUserMessageAuthenticationException('No JWT token provided');
        }

        try {
            $payload = $this->tokenValidator->validate($token);
            $userInfo = $this->userExtractor->extract($payload);

            return new SelfValidatingPassport(
                new UserBadge($userInfo['email'], function () use ($userInfo) {
                    $domainUser = $this->userRepository->findById($userInfo['id']);

                    if (null === $domainUser) {
                        throw new CustomUserMessageAuthenticationException('User not found');
                    }

                    return User::fromDomain($domainUser);
                })
            );
        } catch (InvalidTokenException $e) {
            throw new CustomUserMessageAuthenticationException($e->getMessage(), [], 0, $e);
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['error' => strtr($exception->getMessageKey(), $exception->getMessageData())],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
