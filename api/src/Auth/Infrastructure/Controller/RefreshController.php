<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Controller;

use App\Auth\Application\Command\RefreshToken\RefreshTokenCommand;
use App\Auth\Domain\Exception\InvalidTokenException;
use App\Auth\Domain\Exception\RefreshTokenExpiredException;
use App\Auth\Domain\Exception\RefreshTokenReuseException;
use App\Auth\Infrastructure\Cookie\CookieHelper;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class RefreshController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly CookieHelper $cookieHelper,
    ) {
    }

    #[OA\Post(
        path: '/api/auth/refresh',
        summary: 'Rotate the refresh token and issue a new access token',
        description: 'Reads the refresh_token cookie, rotates it (the previous token is revoked) and reissues both cookies. Reusing an already rotated token revokes the whole token family.',
        security: [],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tokens rotated. Sets new host-only httpOnly access_token and refresh_token cookies.',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string', example: 'Tokens refreshed')],
                ),
            ),
            new OA\Response(response: 401, description: 'Missing, expired, invalid or already-rotated refresh token.'),
        ],
    )]
    #[Route('/api/auth/refresh', name: 'auth_refresh', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $refreshToken = $request->cookies->get($this->cookieHelper->getRefreshCookieName());

        if (empty($refreshToken)) {
            return new JsonResponse(['error' => 'No refresh token'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $command = new RefreshTokenCommand($refreshToken);
            $envelope = $this->messageBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            $result = $handledStamp->getResult();

            $response = new JsonResponse(['message' => 'Tokens refreshed'], Response::HTTP_OK);
            $this->cookieHelper->setAuthCookies($response, $result['accessToken'], $result['refreshToken']);

            return $response;
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof RefreshTokenReuseException) {
                $response = new JsonResponse(['error' => 'Token reuse detected'], Response::HTTP_UNAUTHORIZED);
                $this->cookieHelper->clearAuthCookies($response);

                return $response;
            }
            if ($previous instanceof RefreshTokenExpiredException || $previous instanceof InvalidTokenException) {
                $response = new JsonResponse(['error' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
                $this->cookieHelper->clearAuthCookies($response);

                return $response;
            }

            throw $e;
        } catch (RefreshTokenReuseException $e) {
            $response = new JsonResponse(['error' => 'Token reuse detected'], Response::HTTP_UNAUTHORIZED);
            $this->cookieHelper->clearAuthCookies($response);

            return $response;
        } catch (RefreshTokenExpiredException|InvalidTokenException $e) {
            $response = new JsonResponse(['error' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
            $this->cookieHelper->clearAuthCookies($response);

            return $response;
        }
    }
}
