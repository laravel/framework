<?php

declare(strict_types=1);

namespace Illuminate\Tests\Console\Fixtures;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Console\Scheduling\Schedulable;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SchedulableJob implements ShouldQueue, Schedulable
{
    public function schedule(Event $event): void
    {
        $event->daily()->at('10:00')->withoutOverlapping();
    }
}
