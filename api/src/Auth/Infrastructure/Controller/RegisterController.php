<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Controller;

use App\Auth\Application\Command\AuthenticateUser\AuthenticateUserCommand;
use App\Auth\Application\Command\RegisterUser\RegisterUserCommand;
use App\Auth\Domain\Exception\DuplicateEmailException;
use App\Auth\Domain\Exception\InvalidEmailException;
use App\Auth\Domain\Exception\WeakPasswordException;
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

class RegisterController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly ValidatorInterface $validator,
        private readonly CookieHelper $cookieHelper,
    ) {
    }

    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Create an account and log the user in',
        security: [],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'fullName', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'new-user@aplika.test'),
                    new OA\Property(property: 'fullName', type: 'string', example: 'Jane Doe'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'password123'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Account created and authenticated. Sets host-only httpOnly cookies.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Invalid email format or weak password.'),
            new OA\Response(response: 409, description: 'Email already registered.'),
        ],
    )]
    #[Route('/api/auth/register', name: 'auth_register', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $data = \is_array($data) ? $data : [];

        $command = new RegisterUserCommand($data['email'] ?? '', $data['fullName'] ?? '', $data['password'] ?? '');

        $violations = $this->validator->validate($command);
        if ($violations->count() > 0) {
            return new JsonResponse(['error' => $violations->get(0)->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        try {
            $envelope = $this->messageBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            $user = $handledStamp->getResult();

            // Auto-login after registration
            $loginCommand = new AuthenticateUserCommand($data['email'], $data['password']);
            $loginEnvelope = $this->messageBus->dispatch($loginCommand);
            $loginStamp = $loginEnvelope->last(HandledStamp::class);
            $tokens = $loginStamp->getResult();

            $response = new JsonResponse(
                ['id' => $user->id(), 'email' => $user->email(), 'fullName' => $user->fullName()],
                Response::HTTP_CREATED,
            );
            $this->cookieHelper->setAuthCookies($response, $tokens['accessToken'], $tokens['refreshToken']);

            return $response;
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof InvalidEmailException) {
                return new JsonResponse(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
            }
            if ($previous instanceof WeakPasswordException) {
                return new JsonResponse(['error' => $previous->getMessage()], Response::HTTP_BAD_REQUEST);
            }
            if ($previous instanceof DuplicateEmailException) {
                return new JsonResponse(['error' => 'Email already registered'], Response::HTTP_CONFLICT);
            }

            throw $e;
        } catch (InvalidEmailException $e) {
            return new JsonResponse(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        } catch (WeakPasswordException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (DuplicateEmailException $e) {
            return new JsonResponse(['error' => 'Email already registered'], Response::HTTP_CONFLICT);
        }
    }
}
