<?php

declare(strict_types=1);

namespace spec\App\Shared\Application\Command\SendEmail;

use App\Shared\Application\Command\SendEmail\SendEmail;
use App\Shared\Application\Command\SendEmail\SendEmailHandler;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;

class SendEmailHandlerSpec extends ObjectBehavior
{
    function let(LoggerInterface $logger): void
    {
        $this->beConstructedWith($logger);
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(SendEmailHandler::class);
    }

    function it_invokes_send_email_message(LoggerInterface $logger): void
    {
        $message = new SendEmail('user@example.com', 'Welcome', 'Hello!');

        $logger->info('Email stub sent', [
            'to' => 'user@example.com',
            'subject' => 'Welcome',
        ])->shouldBeCalled();

        $this->__invoke($message);
    }
}
