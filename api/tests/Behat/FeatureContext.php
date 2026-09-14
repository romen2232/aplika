<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use RuntimeException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Shared acceptance context for the Joblog API.
 *
 * Business-facing behaviours described in `features/*.feature` are implemented
 * here or in focused contexts placed alongside this one.
 */
final class FeatureContext implements Context
{
    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    #[Then('the application is running in the :environment environment')]
    public function theApplicationIsRunningInTheEnvironment(string $environment): void
    {
        if ($this->kernel->getEnvironment() !== $environment) {
            throw new RuntimeException(\sprintf('Expected the "%s" environment, got "%s".', $environment, $this->kernel->getEnvironment()));
        }
    }
}
