<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Behat\Step\When;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Shared HTTP context for making requests and asserting responses.
 *
 * Works with domain-specific contexts (e.g., AuthContext) via shared BehatState.
 */
final class FeatureContext implements Context
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly BehatState $state,
    ) {
    }

    #[Then('the application is running in the :environment environment')]
    public function theApplicationIsRunningInTheEnvironment(string $environment): void
    {
        if ($this->kernel->getEnvironment() !== $environment) {
            throw new RuntimeException(\sprintf('Expected the "%s" environment, got "%s".', $environment, $this->kernel->getEnvironment()));
        }
    }

    #[When('I request :method :path')]
    public function iRequest(string $method, string $path): void
    {
        $client = $this->getClient();

        $headers = [];
        $token = $this->state->getCurrentToken();
        if (null !== $token) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $client->request($method, $path, [], [], $headers);
        $this->state->setResponse($client->getResponse());
    }

    #[Then('the response status code should be :statusCode')]
    public function theResponseStatusCodeShouldBe(int $statusCode): void
    {
        $response = $this->state->getResponse();
        if (null === $response) {
            throw new RuntimeException('No response available. Make a request first.');
        }

        $actual = $response->getStatusCode();
        if ($actual !== $statusCode) {
            throw new RuntimeException(\sprintf('Expected status code %d, got %d. Response: %s', $statusCode, $actual, $response->getContent()));
        }
    }

    #[Then('the response should contain JSON:')]
    public function theResponseShouldContainJson(string $json): void
    {
        $response = $this->state->getResponse();
        if (null === $response) {
            throw new RuntimeException('No response available. Make a request first.');
        }

        $actual = json_decode($response->getContent(), true);
        if (null === $actual) {
            throw new RuntimeException('Response is not valid JSON.');
        }

        $expected = json_decode($json, true);
        if (null === $expected) {
            throw new RuntimeException('Expected JSON is not valid.');
        }

        foreach ($expected as $key => $value) {
            if (!\array_key_exists($key, $actual)) {
                throw new RuntimeException(\sprintf('Missing key "%s" in response.', $key));
            }

            if (\is_string($value) && str_starts_with($value, '@') && str_ends_with($value, '@')) {
                continue;
            }

            if ($actual[$key] !== $value) {
                throw new RuntimeException(\sprintf('Expected "%s" to be %s, got %s.', $key, json_encode($value), json_encode($actual[$key])));
            }
        }
    }

    #[Then('the response should have a cookie named :cookieName')]
    public function theResponseShouldHaveACookieNamed(string $cookieName): void
    {
        $response = $this->state->getResponse();
        if (null === $response) {
            throw new RuntimeException('No response available. Make a request first.');
        }

        $cookies = $response->headers->getCookies();
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $cookieName) {
                return;
            }
        }

        throw new RuntimeException(\sprintf('Response does not contain a cookie named "%s".', $cookieName));
    }

    #[Then('the response should not have a cookie named :cookieName with a value')]
    public function theResponseShouldNotHaveACookieNamedWithValue(string $cookieName): void
    {
        $response = $this->state->getResponse();
        if (null === $response) {
            throw new RuntimeException('No response available. Make a request first.');
        }

        $cookies = $response->headers->getCookies();
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $cookieName && !empty($cookie->getValue()) && $cookie->getExpiresTime() > time()) {
                throw new RuntimeException(\sprintf('Response has a cookie named "%s" with a non-empty, non-expired value.', $cookieName));
            }
        }
    }

    private function getClient(): KernelBrowser
    {
        $client = $this->state->getClient();
        if (null === $client) {
            $client = $this->kernel->getContainer()->get('test.client');
            $this->state->setClient($client);
        }

        return $client;
    }
}
