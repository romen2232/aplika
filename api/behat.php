<?php

declare(strict_types=1);

use App\Tests\Behat\FeatureContext;
use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withExtension(new Extension(SymfonyExtension::class, [
                'bootstrap' => 'config/bootstrap.php',
                'kernel' => [
                    'class' => 'App\Kernel',
                    'environment' => 'test',
                    'debug' => true,
                ],
            ]))
            ->withSuite(
                (new Suite('default'))
                    ->withContexts(FeatureContext::class)
            )
    );
