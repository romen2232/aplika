<?php

declare(strict_types=1);

namespace App\Tests\Behat;

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

    public function reset(): void
    {
        $this->users = [];
        $this->currentToken = null;
    }
}
