<?php

declare(strict_types=1);

namespace App\Tests\Behat\Auth;

use App\Auth\Domain\User;
use App\Tests\Behat\BehatState;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Step\Given;
use Behat\Step\When;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Auth domain context for user management and authentication steps.
 */
final class AuthContext implements Context
{
    public function __construct(private readonly BehatState $state)
    {
    }

    /**
     * @BeforeScenario
     */
    public function resetState(BeforeScenarioScope $scope): void
    {
        $this->state->reset();
        // Database reset is handled by FixtureContext::restoreSnapshot() via pg_restore
    }

    #[Given('there is a user with email :email and password :password')]
    public function thereIsAUserWithEmailAndPassword(string $email, string $password): void
    {
        // Use the registration endpoint to create the user
        $client = $this->getClient();
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));

        $response = $client->getResponse();
        if (201 !== $response->getStatusCode()) {
            throw new RuntimeException(\sprintf('Failed to create user "%s". Status: %d, Response: %s', $email, $response->getStatusCode(), $response->getContent()));
        }

        $data = json_decode($response->getContent(), true);

        $this->state->addUser($email, [
            'id' => $data['id'],
            'email' => $email,
            'password' => $password,
        ]);
    }

    #[Given('I am authenticated as :email')]
    public function iAmAuthenticatedAs(string $email): void
    {
        $user = $this->state->getUser($email);
        if (null === $user) {
            throw new RuntimeException(\sprintf('User "%s" does not exist.', $email));
        }

        $client = $this->getClient();
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $user['email'],
            'password' => $user['password'],
        ]));

        $response = $client->getResponse();
        if (200 !== $response->getStatusCode()) {
            throw new RuntimeException(\sprintf('Failed to authenticate as "%s". Status: %d, Response: %s', $email, $response->getStatusCode(), $response->getContent()));
        }

        $data = json_decode($response->getContent(), true);
        if (!isset($data['token'])) {
            throw new RuntimeException('Login response did not contain a token.');
        }

        $this->state->setCurrentToken($data['token']);
    }

    #[When('I register with email :email and password :password')]
    public function iRegisterWithEmailAndPassword(string $email, string $password): void
    {
        $client = $this->getClient();
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));
        $this->state->setResponse($client->getResponse());
    }

    #[When('I login with email :email and password :password')]
    public function iLoginWithEmailAndPassword(string $email, string $password): void
    {
        $client = $this->getClient();
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));
        $this->state->setResponse($client->getResponse());
    }

    private function getClient(): KernelBrowser
    {
        $client = $this->state->getClient();
        if (null === $client) {
            $client = $this->getKernel()->getContainer()->get('test.client');
            $this->state->setClient($client);
        }

        return $client;
    }

    private function getKernel(): KernelInterface
    {
        static $kernel = null;
        if (null === $kernel) {
            $kernel = new \App\Kernel('test', true);
            $kernel->boot();
        }

        return $kernel;
    }
}
