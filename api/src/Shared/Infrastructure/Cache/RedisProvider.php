<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

use Predis\Client;

class RedisProvider
{
    public static function create(string $dsn): Client
    {
        return new Client($dsn);
    }
}
