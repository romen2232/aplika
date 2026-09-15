<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;

use App\Shared\Infrastructure\RateLimiting\RateLimitDecider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RateLimitDecider $decider,
        private readonly RateLimiterFactory $apiPublicLimiter,
        private readonly RateLimiterFactory $apiAuthenticatedLimiter,
        private readonly string $environment,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Only enable rate limiting in production environment
        if ('prod' !== $this->environment) {
            return;
        }

        $request = $event->getRequest();
        $decision = $this->decider->decide($request);

        $limiter = str_starts_with($decision['key'], 'user_')
            ? $this->apiAuthenticatedLimiter
            : $this->apiPublicLimiter;

        $limit = $limiter->create($decision['key'])->consume();

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter()->getTimestamp() - time();

            $response = new JsonResponse(
                ['error' => 'Too Many Requests'],
                JsonResponse::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => (string) $retryAfter]
            );

            $event->setResponse($response);
        }
    }
}
