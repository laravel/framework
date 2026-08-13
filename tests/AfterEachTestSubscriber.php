<?php

declare(strict_types=1);

namespace Illuminate\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
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

        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        date_default_timezone_set('UTC');

        Str::resetFactoryState();
        Sleep::fake(false);
        Once::flush();
        Lottery::determineResultNormally();
    }
}
