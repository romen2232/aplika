<?php

declare(strict_types=1);

namespace App\Tests\Behat\Auth;

use App\Tests\Behat\BehatState;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use RuntimeException;

/**
 * Auth domain context for user management and authentication steps.
 */
final class AuthContext implements Context
{
    public function __construct(private readonly BehatState $state)
    {
    }

    #[Given('there is a user with email :email and password :password')]
    public function thereIsAUserWithEmailAndPassword(string $email, string $password): void
    {
        // TODO: Implement when User entity and repository are ready
        // This should:
        // 1. Create a User entity with the given email and hashed password
        // 2. Persist it to the test database
        // 3. Store the user data in state for later authentication

        $this->state->addUser($email, [
            'email' => $email,
            'password' => $password,
        ]);

        throw new RuntimeException(
            'User creation not yet implemented. ' .
            'Requires: User entity, UserRepository, and test database setup.'
        );
    }

    #[Given('I am authenticated as :email')]
    public function iAmAuthenticatedAs(string $email): void
    {
        $user = $this->state->getUser($email);
        if ($user === null) {
            throw new RuntimeException(sprintf('User "%s" does not exist.', $email));
        }

        // TODO: Implement when login endpoint is ready
        // This should:
        // 1. POST to /api/auth/login with email and password
        // 2. Extract the JWT token from the response
        // 3. Store it in state for use in subsequent requests

        throw new RuntimeException(
            'Authentication not yet implemented. ' .
            'Requires: POST /api/auth/login endpoint.   '
        );
    }
}
