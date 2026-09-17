<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Controller;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Health')]
class HealthController
{
    #[Route('/health', name: 'health', methods: ['GET'])]
    #[OA\Get(
        path: '/health',
        summary: 'Health check endpoint',
        description: 'Returns the current status of the API to confirm it is up and running.',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service is healthy',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                    ]
                )
            ),
        ]
    )]
    public function __invoke(): Response
    {
        return new JsonResponse(['status' => 'ok'], Response::HTTP_OK);
    }
}
