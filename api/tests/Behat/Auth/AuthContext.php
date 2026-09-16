<?php

declare(strict_types=1);

namespace App\Tests\Behat\Auth;

use App\Tests\Behat\BehatState;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Step\Given;
use Behat\Step\When;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Auth domain context for user management and authentication steps.
 */
final class AuthContext implements Context
{
    private ?string $savedRefreshToken = null;

    public function __construct(private readonly BehatState $state)
    {
    }

    /**
     * @BeforeScenario
     */
    public function resetState(BeforeScenarioScope $scope): void
    {
        $this->state->reset();
    }

    #[Given('there is a user with email :email and password :password')]
    public function thereIsAUserWithEmailAndPassword(string $email, string $password): void
    {
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

        // Clear cookies so the registration cookies don't leak into subsequent requests
        $client->getCookieJar()->clear();
    }

    #[Given('I am authenticated as :email')]
    public function iAmAuthenticatedAs(string $email): void
    {
        $user = $this->state->getUser($email);
        $password = $user['password'] ?? 'password123';

        $client = $this->getClient();
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));

        $response = $client->getResponse();
        if (200 !== $response->getStatusCode()) {
            throw new RuntimeException(\sprintf('Failed to authenticate as "%s". Status: %d, Response: %s', $email, $response->getStatusCode(), $response->getContent()));
        }

        // Cookies are automatically stored in the KernelBrowser cookie jar.
        // Capture the refresh token cookie for later reuse-detection tests.
        $refreshCookie = $client->getCookieJar()->get('refresh_token');
        if (null !== $refreshCookie) {
            $this->state->setLastRefreshToken($refreshCookie->getValue());
        }
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
        $response = $client->getResponse();
        $this->state->setResponse($response);

        if (200 === $response->getStatusCode()) {
            $refreshCookie = $client->getCookieJar()->get('refresh_token');
            if (null !== $refreshCookie) {
                $this->state->setLastRefreshToken($refreshCookie->getValue());
            }
        }
    }

    #[When('I login with email :email and no password')]
    public function iLoginWithEmailAndNoPassword(string $email): void
    {
        $client = $this->getClient();
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
        ]));
        $this->state->setResponse($client->getResponse());
    }

    #[When('I refresh my token')]
    public function iRefreshMyToken(): void
    {
        $client = $this->getClient();
        $client->request('POST', '/api/auth/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);
        $response = $client->getResponse();
        $this->state->setResponse($response);

        if (200 === $response->getStatusCode()) {
            $refreshCookie = $client->getCookieJar()->get('refresh_token');
            if (null !== $refreshCookie) {
                $this->state->setLastRefreshToken($refreshCookie->getValue());
            }
        }
    }

    #[When('I refresh with the previous refresh token cookie :tokenValue')]
    public function iRefreshWithPreviousRefreshTokenCookie(string $tokenValue): void
    {
        $client = $this->getClient();

        // Set the old refresh token cookie manually
        $cookie = new Cookie('refresh_token', $tokenValue, null, '/', '', false, false);
        $client->getCookieJar()->set($cookie);

        $client->request('POST', '/api/auth/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);
        $this->state->setResponse($client->getResponse());
    }

    #[When('I logout')]
    public function iLogout(): void
    {
        $client = $this->getClient();
        $client->request('POST', '/api/auth/logout', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);
        $this->state->setResponse($client->getResponse());
    }

    #[When('I save the current refresh token cookie')]
    public function iSaveTheCurrentRefreshTokenCookie(): void
    {
        $client = $this->getClient();
        $cookie = $client->getCookieJar()->get('refresh_token');
        if (null !== $cookie) {
            $this->savedRefreshToken = $cookie->getValue();
        }
    }

    #[When('I refresh with the saved refresh token')]
    public function iRefreshWithTheSavedRefreshToken(): void
    {
        if (null === $this->savedRefreshToken) {
            throw new RuntimeException('No saved refresh token. Call "I save the current refresh token cookie" first.');
        }

        $client = $this->getClient();

        // Clear existing cookies and set the old refresh token
        $client->getCookieJar()->clear();
        $cookie = new Cookie('refresh_token', $this->savedRefreshToken, null, '/', '', false, false);
        $client->getCookieJar()->set($cookie);

        $client->request('POST', '/api/auth/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);
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
