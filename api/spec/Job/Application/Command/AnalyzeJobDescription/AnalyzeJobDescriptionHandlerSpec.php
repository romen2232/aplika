<?php

declare(strict_types=1);

namespace spec\App\Job\Application\Command\AnalyzeJobDescription;

use App\Job\Application\Command\AnalyzeJobDescription\AnalyzeJobDescription;
use App\Job\Application\Command\AnalyzeJobDescription\AnalyzeJobDescriptionHandler;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;

class AnalyzeJobDescriptionHandlerSpec extends ObjectBehavior
{
    function let(LoggerInterface $logger): void
    {
        $this->beConstructedWith($logger);
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(AnalyzeJobDescriptionHandler::class);
    }

    function it_invokes_analyze_job_description_message(LoggerInterface $logger): void
    {
        $message = new AnalyzeJobDescription('job-123');

        $logger->info('AI analysis stub executed', ['jobId' => 'job-123'])->shouldBeCalled();

        $this->__invoke($message);
    }
}
