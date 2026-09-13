<?php

declare(strict_types=1);

namespace App\Job\Infrastructure\Controller;

use App\Job\Application\Command\AnalyzeJobDescription\AnalyzeJobDescription;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AnalyzeJobController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/api/jobs/{jobId}/analyze', name: 'job_analyze', methods: ['POST'])]
    public function __invoke(string $jobId, Request $request): Response
    {
        $this->messageBus->dispatch(new AnalyzeJobDescription($jobId));

        return new JsonResponse(null, Response::HTTP_ACCEPTED);
    }
}
