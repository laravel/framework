<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schedule;
use Orchestra\Testbench\TestCase;

class ScheduleAssertionsTest extends TestCase
{
    public function testAssertionsMayBeMadeThroughTheFacade()
    {
        Schedule::command(ScheduleAssertionsCommandStub::class)->dailyAt('03:00');

        Schedule::assertScheduled(ScheduleAssertionsCommandStub::class, '0 3 * * *')
            ->assertNotScheduled('missing:command');
    }

    public function testAssertNothingScheduled()
    {
        Schedule::assertNothingScheduled();
    }
}

class ScheduleAssertionsCommandStub extends Command
{
    protected $signature = 'schedule-assertions:stub';

    protected $description = 'A command used to test schedule assertions';
}
