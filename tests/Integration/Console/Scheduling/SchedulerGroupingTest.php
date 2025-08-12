<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class SchedulerGroupingTest extends TestCase
{
    public function testSchedulerGroupsEventsProperly()
    {
        $schedule = new Schedule();

        $schedule->command('foo')->name('foo')->withoutOverlapping();
        $schedule->command('bar')->name('bar')->withoutOverlapping();
        $schedule->command('baz')->name('baz')->withoutOverlapping();

        $groups = $schedule->eventsGroupedByMutex();

        // Each event has its own mutex lock, so each should be in a separate group
        $this->assertCount(3, $groups);
    }
}
