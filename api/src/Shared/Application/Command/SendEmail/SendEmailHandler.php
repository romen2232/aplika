<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\SendEmail;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendEmailHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendEmail $message): void
    {
        $this->logger->info('Email stub sent', [
            'to' => $message->to,
            'subject' => $message->subject,
        ]);
    }
}
