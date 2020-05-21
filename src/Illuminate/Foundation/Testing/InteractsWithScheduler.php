<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\App;
use Illuminate\Testing\Assert;

trait InteractsWithScheduler
{
    private function assertCommandIsScheduled(string $commandSignature): void
    {
        $event = $this->getScheduledCommand($commandSignature);

        if (is_null($event)) {
            Assert::fail("Command $commandSignature is not scheduled");
        }

        Assert::assertNotNull($event);
    }

    private function assertCommandIsNotScheduled(string $commandSignature): void
    {
        $event = $this->getScheduledCommand($commandSignature);

        if (!is_null($event)) {
            Assert::fail("Command $commandSignature is scheduled");
        }

        Assert::assertNull($event);
    }

    private function getScheduledCommand(string $commandSignature): ?Event
    {
        App::forgetInstance(Schedule::class);

        $schedule = app()->make(Schedule::class);

        return collect($schedule->events())->first(function ($event) use ($commandSignature) {
            return strpos($event->command, $commandSignature);
        });
    }
}
