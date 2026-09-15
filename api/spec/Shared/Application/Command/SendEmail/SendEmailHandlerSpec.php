<?php

declare(strict_types=1);

namespace spec\App\Shared\Application\Command\SendEmail;

use App\Shared\Application\Command\SendEmail\SendEmail;
use App\Shared\Application\Command\SendEmail\SendEmailHandler;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SendEmailHandlerSpec extends ObjectBehavior
{
    function let(MailerInterface $mailer): void
    {
        $this->beConstructedWith($mailer);
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(SendEmailHandler::class);
    }

    function it_sends_email_via_mailer(MailerInterface $mailer): void
    {
        $message = new SendEmail('noreply@aplika.com', 'user@example.com', 'Welcome', 'Hello!');

        $mailer->send(\Prophecy\Argument::that(function (Email $email) {
            return $email->getFrom()[0]->getAddress() === 'noreply@aplika.com'
                && $email->getTo()[0]->getAddress() === 'user@example.com'
                && $email->getSubject() === 'Welcome'
                && $email->getTextBody() === 'Hello!';
        }))->shouldBeCalled();

        $this->__invoke($message);
    }
}
