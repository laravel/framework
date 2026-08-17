<?php

declare(strict_types=1);

namespace Illuminate\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Lottery;
use Illuminate\Support\Once;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;

final class AfterEachTestSubscriber implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        if (class_exists(\Mockery::class)) {
            \Mockery::close();
        }

        Date::useDefault();
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        date_default_timezone_set('UTC');

        Str::resetFactoryState();
        Sleep::fake(false);
        Once::flush();
        Lottery::determineResultNormally();

        Container::setInstance(null);
        Application::setInstance(null);
        Facade::setFacadeApplication(null);
        Facade::clearResolvedInstances();
    }
}
