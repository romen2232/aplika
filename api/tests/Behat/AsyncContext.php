<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Step\When;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Context for async message processing in Behat scenarios.
 *
 * Provides step definitions to consume messages from the in-memory transport
 * synchronously during test execution.
 */
final class AsyncContext implements Context
{
    public function __construct(
        private readonly TransportInterface $asyncTransport,
    ) {
    }

    #[When('the worker processes pending messages')]
    public function theWorkerProcessesPendingMessages(): void
    {
        while (null !== ($envelope = $this->asyncTransport->get()[0] ?? null)) {
            $this->asyncTransport->ack($envelope);
        }
    }
}
