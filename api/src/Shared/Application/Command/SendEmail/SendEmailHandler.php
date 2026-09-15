<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\SendEmail;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final readonly class SendEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendEmail $message): void
    {
        $email = (new Email())
            ->from($message->from)
            ->to($message->to)
            ->subject($message->subject)
            ->text($message->body);

        $this->mailer->send($email);
    }
}
