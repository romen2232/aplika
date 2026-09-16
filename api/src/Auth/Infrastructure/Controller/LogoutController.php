<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Controller;

use App\Auth\Application\Command\RevokeRefreshToken\RevokeRefreshTokenCommand;
use App\Auth\Infrastructure\Cookie\CookieHelper;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class LogoutController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly CookieHelper $cookieHelper,
    ) {
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Revoke the refresh token and clear auth cookies',
        description: 'Idempotent: returns 200 even when no valid session exists.',
        security: [],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Refresh token revoked and both auth cookies expired.',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string', example: 'Logged out')],
                ),
            ),
        ],
    )]
    #[Route('/api/auth/logout', name: 'auth_logout', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $refreshToken = $request->cookies->get($this->cookieHelper->getRefreshCookieName());

        if (!empty($refreshToken)) {
            try {
                $command = new RevokeRefreshTokenCommand($refreshToken);
                $this->messageBus->dispatch($command);
            } catch (Throwable) {
                // Idempotent: ignore revocation failures
            }
        }

        $response = new JsonResponse(['message' => 'Logged out'], Response::HTTP_OK);
        $this->cookieHelper->clearAuthCookies($response);

        return $response;
    }
}
