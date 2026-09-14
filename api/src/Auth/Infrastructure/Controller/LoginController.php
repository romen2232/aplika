<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Controller;

use App\Auth\Application\Command\AuthenticateUser\AuthenticateUserCommand;
use App\Auth\Domain\Exception\InvalidCredentialsException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/api/auth/login', name: 'auth_login', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email'])) {
            return new JsonResponse(['error' => 'Email is required'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['password'])) {
            return new JsonResponse(['error' => 'Password is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new AuthenticateUserCommand($data['email'], $data['password']);
            $envelope = $this->messageBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            $token = $handledStamp->getResult();

            return new JsonResponse(['token' => $token], Response::HTTP_OK);
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
