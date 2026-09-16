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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LoginController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly ValidatorInterface $validator,
    ) {
    }

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
