<?php

declare(strict_types=1);

namespace App\Job\Application\Command\AnalyzeJobDescription;

final readonly class AnalyzeJobDescription
{
    public function __construct(
        public string $jobId,
    ) {
    }
}
