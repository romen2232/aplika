<?php

declare(strict_types=1);

namespace spec\App\Auth\Application\Command\RefreshToken;

use App\Auth\Application\Command\RefreshToken\RefreshTokenCommand;
use App\Auth\Application\Command\RefreshToken\RefreshTokenHandler;
use App\Auth\Application\TokenGeneratorInterface;
use App\Auth\Domain\Exception\RefreshTokenExpiredException;
use App\Auth\Domain\Exception\RefreshTokenReuseException;
use App\Auth\Domain\RefreshToken;
use App\Auth\Domain\RefreshTokenRepository;
use App\Auth\Domain\User;
use App\Auth\Domain\UserRepository;
use DateTimeImmutable;
use PhpSpec\ObjectBehavior;

class RefreshTokenHandlerSpec extends ObjectBehavior
{
    function let(
        RefreshTokenRepository $refreshTokenRepository,
        UserRepository $userRepository,
        TokenGeneratorInterface $tokenGenerator,
    ) {
        $this->beConstructedWith($refreshTokenRepository, $userRepository, $tokenGenerator);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(RefreshTokenHandler::class);
    }

    function it_rotates_tokens_on_valid_refresh(
        RefreshTokenRepository $refreshTokenRepository,
        UserRepository $userRepository,
        TokenGeneratorInterface $tokenGenerator,
    ) {
        $refreshPlaintext = 'valid-refresh-token-plaintext';
        $tokenHash = hash('sha256', $refreshPlaintext);
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $familyId = '550e8400-e29b-41d4-a716-446655440002';

        $existingToken = RefreshToken::create(
            'old-token-id',
            $userId,
            $tokenHash,
            $familyId,
            new DateTimeImmutable('+7 days'),
        );

        $user = User::register($userId, 'user@example.com', 'Jane Doe', '$2y$13$hashedpassword');

        $refreshTokenRepository->findByTokenHash($tokenHash)->willReturn($existingToken);
        $userRepository->findById($userId)->willReturn($user);
        $refreshTokenRepository->save(\Prophecy\Argument::type(RefreshToken::class))->shouldBeCalled();
        $tokenGenerator->generate($user)->willReturn('new-jwt-access-token');

        $command = new RefreshTokenCommand($refreshPlaintext);
        $result = $this->__invoke($command);

        $result->shouldBeArray();
        $result['accessToken']->shouldBe('new-jwt-access-token');
        $result['refreshToken']->shouldBeString();
        $result['refreshToken']->shouldNotBe($refreshPlaintext);
    }

    function it_detects_reuse_and_revokes_family_when_token_already_revoked(
        RefreshTokenRepository $refreshTokenRepository,
        UserRepository $userRepository,
        TokenGeneratorInterface $tokenGenerator,
    ) {
        $refreshPlaintext = 'reused-refresh-token';
        $tokenHash = hash('sha256', $refreshPlaintext);
        $familyId = '550e8400-e29b-41d4-a716-446655440002';

        $revokedToken = RefreshToken::create(
            'old-token-id',
            '550e8400-e29b-41d4-a716-446655440000',
            $tokenHash,
            $familyId,
            new DateTimeImmutable('+7 days'),
        );
        $revokedToken->revoke();

        $refreshTokenRepository->findByTokenHash($tokenHash)->willReturn($revokedToken);
        $refreshTokenRepository->revokeFamily($familyId)->shouldBeCalled();

        $command = new RefreshTokenCommand($refreshPlaintext);
        $this->shouldThrow(RefreshTokenReuseException::class)->during('__invoke', [$command]);

        $tokenGenerator->generate(\Prophecy\Argument::any())->shouldNotHaveBeenCalled();
    }

    function it_rejects_expired_refresh_token(
        RefreshTokenRepository $refreshTokenRepository,
        TokenGeneratorInterface $tokenGenerator,
    ) {
        $refreshPlaintext = 'expired-refresh-token';
        $tokenHash = hash('sha256', $refreshPlaintext);

        $expiredToken = RefreshToken::create(
            'old-token-id',
            '550e8400-e29b-41d4-a716-446655440000',
            $tokenHash,
            '550e8400-e29b-41d4-a716-446655440002',
            new DateTimeImmutable('-1 second'),
        );

        $refreshTokenRepository->findByTokenHash($tokenHash)->willReturn($expiredToken);

        $command = new RefreshTokenCommand($refreshPlaintext);
        $this->shouldThrow(RefreshTokenExpiredException::class)->during('__invoke', [$command]);

        $tokenGenerator->generate(\Prophecy\Argument::any())->shouldNotHaveBeenCalled();
    }

    function it_rejects_unknown_refresh_token(
        RefreshTokenRepository $refreshTokenRepository,
        TokenGeneratorInterface $tokenGenerator,
    ) {
        $refreshPlaintext = 'unknown-token';
        $tokenHash = hash('sha256', $refreshPlaintext);

        $refreshTokenRepository->findByTokenHash($tokenHash)->willReturn(null);

        $command = new RefreshTokenCommand($refreshPlaintext);
        $this->shouldThrow(\App\Auth\Domain\Exception\InvalidTokenException::class)->during('__invoke', [$command]);

        $tokenGenerator->generate(\Prophecy\Argument::any())->shouldNotHaveBeenCalled();
    }
}
