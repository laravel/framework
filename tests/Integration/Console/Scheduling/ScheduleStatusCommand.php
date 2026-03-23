<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;

class ScheduleStatusCommand extends TestCase
{
    public function testDisplaysRunning()
    {
        Cache::put('illuminate:schedule:paused', false);

        $this->artisan('schedule:status')
            ->assertSuccessful()
            ->expectsOutputToContain('Scheduler is currently running.');
    }

    public function testDisplaysPaused()
    {
        Cache::put('illuminate:schedule:paused', true);

        $this->artisan('schedule:status')
            ->assertFailed()
            ->expectsOutputToContain('Scheduler is currently paused.');
    }
}
