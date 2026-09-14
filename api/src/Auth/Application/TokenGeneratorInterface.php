<?php

declare(strict_types=1);

namespace App\Auth\Application;

use App\Auth\Domain\User;

interface TokenGeneratorInterface
{
    public function generate(User $user): string;
}
