<?php

declare(strict_types=1);

namespace Illuminate\Tests\Console\Fixtures;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Console\Scheduling\Schedulable;

final class SchedulableCommandToTestWithSchedule extends Command implements Schedulable
{
    protected $signature = 'schedulable:command';

    protected $description = 'A schedulable command for testing';

    public function handle(): void
    {
        //
    }

    public function schedule(Event $event): void
    {
        $event->hourly()->withoutOverlapping();
    }
}
