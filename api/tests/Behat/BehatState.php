<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared state container for Behat contexts.
 *
 * Allows domain-specific contexts (e.g., AuthContext) to share state
 * with the shared HTTP context (FeatureContext).
 */
final class BehatState
{
    private array $users = [];
    private ?string $currentToken = null;
    private ?KernelBrowser $client = null;
    private ?Response $response = null;
    private bool $fixturesLoaded = false;

    public function addUser(string $email, array $userData): void
    {
        $this->users[$email] = $userData;
    }

    public function getUser(string $email): ?array
    {
        return $this->users[$email] ?? null;
    }

    public function setCurrentToken(?string $token): void
    {
        $this->currentToken = $token;
    }

    public function getCurrentToken(): ?string
    {
        return $this->currentToken;
    }

    public function setClient(?KernelBrowser $client): void
    {
        $this->client = $client;
    }

    public function getClient(): ?KernelBrowser
    {
        return $this->client;
    }

    public function setResponse(?Response $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function areFixturesLoaded(): bool
    {
        return $this->fixturesLoaded;
    }

    public function markFixturesLoaded(): void
    {
        $this->fixturesLoaded = true;
    }

    public function reset(): void
    {
        $this->users = [];
        $this->currentToken = null;
        $this->client = null;
        $this->response = null;
        // Note: fixturesLoaded is intentionally NOT reset here.
        // Fixtures are loaded once per suite (BeforeSuite), not per scenario.
    }
}
