<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\SendEmail;

final readonly class SendEmail
{
    public function __construct(
        public string $from,
        public string $to,
        public string $subject,
        public string $body,
    ) {
    }
}
