<?php

declare(strict_types=1);

namespace Illuminate\Tests;

use Illuminate\Support\Carbon;
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
    }
}
