<?php

declare(strict_types=1);

namespace App\Job\Application\Command\AnalyzeJobDescription;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AnalyzeJobDescriptionHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(AnalyzeJobDescription $message): void
    {
        $this->logger->info('AI analysis stub executed', ['jobId' => $message->jobId]);
    }
}
