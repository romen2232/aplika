<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Controller;

use App\Auth\Application\Command\AuthenticateUser\AuthenticateUserCommand;
use App\Auth\Domain\Exception\InvalidCredentialsException;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LoginController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly ValidatorInterface $validator,
        private readonly CookieHelper $cookieHelper,
    ) {
    }

    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Authenticate with email and password',
        security: [],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@aplika.test'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated. Sets host-only httpOnly access_token and refresh_token cookies.',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string', example: 'Authenticated')],
                ),
            ),
            new OA\Response(response: 400, description: 'Email or password missing.'),
            new OA\Response(response: 401, description: 'Invalid credentials.'),
        ],
    )]
    #[Route('/api/auth/login', name: 'auth_login', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $data = \is_array($data) ? $data : [];

        $command = new AuthenticateUserCommand($data['email'] ?? '', $data['password'] ?? '');

        $violations = $this->validator->validate($command);
        if ($violations->count() > 0) {
            return new JsonResponse(['error' => $violations->get(0)->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        try {
            $envelope = $this->messageBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            $result = $handledStamp->getResult();

            $response = new JsonResponse(['message' => 'Authenticated'], Response::HTTP_OK);
            $this->cookieHelper->setAuthCookies($response, $result['accessToken'], $result['refreshToken']);

            return $response;
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof InvalidCredentialsException) {
                return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
            }
            throw $e;
        } catch (InvalidCredentialsException $e) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }
    }
}
