<?php

declare(strict_types=1);

namespace spec\App\Auth\Application\Command\RegisterUser;

use App\Auth\Application\Command\RegisterUser\RegisterUserCommand;
use App\Auth\Application\Command\RegisterUser\RegisterUserHandler;
use App\Auth\Application\PasswordHasherAdapter;
use App\Auth\Domain\Exception\DuplicateEmailException;
use App\Auth\Domain\Exception\WeakPasswordException;
use App\Auth\Domain\User;
use App\Auth\Domain\UserRepository;
use PhpSpec\ObjectBehavior;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterUserHandlerSpec extends ObjectBehavior
{
    function let(
        UserRepository $repository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->beConstructedWith($repository, $passwordHasher);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(RegisterUserHandler::class);
    }

    function it_registers_user_with_valid_data(
        UserRepository $repository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $command = new RegisterUserCommand('user@example.com', 'SecurePass123');
        
        $repository->findByEmail('user@example.com')->willReturn(null);
        $passwordHasher->hashPassword(
            \Prophecy\Argument::type(PasswordHasherAdapter::class),
            'SecurePass123'
        )->willReturn('$2y$13$hashedpassword');
        $repository->save(\Prophecy\Argument::type(User::class))->shouldBeCalled();

        $user = $this->__invoke($command);
        
        $user->shouldBeAnInstanceOf(User::class);
        $user->email()->shouldReturn('user@example.com');
    }

    function it_rejects_duplicate_email(
        UserRepository $repository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $command = new RegisterUserCommand('user@example.com', 'SecurePass123');
        
        $existingUser = User::register('existing-id', 'user@example.com', 'hashed');
        $repository->findByEmail('user@example.com')->willReturn($existingUser);

        $this->shouldThrow(DuplicateEmailException::class)->during('__invoke', [$command]);
        
        $passwordHasher->hashPassword(
            \Prophecy\Argument::cetera()
        )->shouldNotHaveBeenCalled();
        $repository->save(\Prophecy\Argument::any())->shouldNotHaveBeenCalled();
    }

    function it_rejects_weak_password(
        UserRepository $repository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $command = new RegisterUserCommand('user@example.com', 'short');
        
        $repository->findByEmail('user@example.com')->willReturn(null);

        $this->shouldThrow(WeakPasswordException::class)->during('__invoke', [$command]);
        
        $passwordHasher->hashPassword(
            \Prophecy\Argument::cetera()
        )->shouldNotHaveBeenCalled();
        $repository->save(\Prophecy\Argument::any())->shouldNotHaveBeenCalled();
    }
}
