<?php

declare(strict_types=1);

namespace spec\App\Auth\Application\Command\AuthenticateUser;

use App\Auth\Application\Command\AuthenticateUser\AuthenticateUserCommand;
use App\Auth\Application\Command\AuthenticateUser\AuthenticateUserHandler;
use App\Auth\Application\PasswordHasherAdapter;
use App\Auth\Domain\Exception\InvalidCredentialsException;
use App\Auth\Application\TokenGeneratorInterface;
use App\Auth\Domain\RefreshToken;
use App\Auth\Domain\RefreshTokenRepository;
use App\Auth\Domain\User;
use App\Auth\Domain\UserRepository;
use PhpSpec\ObjectBehavior;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticateUserHandlerSpec extends ObjectBehavior
{
    function let(
        UserRepository $repository,
        UserPasswordHasherInterface $passwordHasher,
        TokenGeneratorInterface $tokenGenerator,
        RefreshTokenRepository $refreshTokenRepository,
    ) {
        $this->beConstructedWith($repository, $passwordHasher, $tokenGenerator, $refreshTokenRepository);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(AuthenticateUserHandler::class);
    }

    function it_authenticates_user_with_valid_credentials(
        UserRepository $repository,
        UserPasswordHasherInterface $passwordHasher,
        TokenGeneratorInterface $tokenGenerator,
        RefreshTokenRepository $refreshTokenRepository,
    ) {
        $command = new AuthenticateUserCommand('user@example.com', 'SecurePass123');

        $user = User::register('user-id', 'user@example.com', '$2y$13$hashedpassword');
        $repository->findByEmail('user@example.com')->willReturn($user);
        $passwordHasher->isPasswordValid(
            \Prophecy\Argument::type(PasswordHasherAdapter::class),
            'SecurePass123'
        )->willReturn(true);
        $tokenGenerator->generate($user)->willReturn('jwt-token-string');
        $refreshTokenRepository->save(\Prophecy\Argument::type(RefreshToken::class))->shouldBeCalled();

        $result = $this->__invoke($command);

        $result->shouldBeArray();
        $result['accessToken']->shouldBe('jwt-token-string');
        $result['refreshToken']->shouldBeString();
    }

    function it_rejects_invalid_password(
        UserRepository $repository,
        UserPasswordHasherInterface $passwordHasher,
        TokenGeneratorInterface $tokenGenerator,
        RefreshTokenRepository $refreshTokenRepository,
    ) {
        $command = new AuthenticateUserCommand('user@example.com', 'WrongPass');

        $user = User::register('user-id', 'user@example.com', '$2y$13$hashedpassword');
        $repository->findByEmail('user@example.com')->willReturn($user);
        $passwordHasher->isPasswordValid(
            \Prophecy\Argument::type(PasswordHasherAdapter::class),
            'WrongPass'
        )->willReturn(false);

        $this->shouldThrow(InvalidCredentialsException::class)->during('__invoke', [$command]);

        $tokenGenerator->generate(\Prophecy\Argument::any())->shouldNotHaveBeenCalled();
        $refreshTokenRepository->save(\Prophecy\Argument::any())->shouldNotHaveBeenCalled();
    }

    function it_rejects_non_existent_user(
        UserRepository $repository,
        UserPasswordHasherInterface $passwordHasher,
        TokenGeneratorInterface $tokenGenerator,
        RefreshTokenRepository $refreshTokenRepository,
    ) {
        $command = new AuthenticateUserCommand('missing@example.com', 'AnyPass123');

        $repository->findByEmail('missing@example.com')->willReturn(null);

        $this->shouldThrow(InvalidCredentialsException::class)->during('__invoke', [$command]);

        $passwordHasher->isPasswordValid(\Prophecy\Argument::cetera())->shouldNotHaveBeenCalled();
        $tokenGenerator->generate(\Prophecy\Argument::any())->shouldNotHaveBeenCalled();
        $refreshTokenRepository->save(\Prophecy\Argument::any())->shouldNotHaveBeenCalled();
    }
}
