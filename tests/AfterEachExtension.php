<?php

namespace Illuminate\Tests;

use Mockery;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class AfterEachExtension implements Extension
{
    public function bootstrap(Configuration $config, Facade $facade, ParameterCollection $params): void
    {
        $facade->registerSubscriber(new class implements FinishedSubscriber
        {
            public function notify(Finished $event): void
            {
                Mockery::close();
            }
        });
    }
}
