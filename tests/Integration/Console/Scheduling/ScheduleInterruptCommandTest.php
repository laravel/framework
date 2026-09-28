<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;

class ScheduleInterruptCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Schedule::$interruptible = true;

        parent::tearDown();
    }

    public function testInterruptsCommandsStartedBeforeIt()
    {
        $schedule = $this->app->make(Schedule::class);
        $startedAt = Carbon::now();

        Carbon::setTestNow($startedAt->copy()->addSecond());

        $this->artisan('schedule:interrupt');

        $this->assertTrue($schedule->interruptedSince($startedAt));
        $this->assertFalse($schedule->interruptedSince(Carbon::now()->addSecond()));
    }

    public function testDoesNotInterruptWhenInterruptingIsDisabled()
    {
        $this->artisan('schedule:interrupt');

        Schedule::$interruptible = false;

        $this->assertFalse($this->app->make(Schedule::class)->interruptedSince(Carbon::now()->subMinute()));
    }
}
