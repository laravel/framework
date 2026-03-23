<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;

class StatusCommandTest extends TestCase
{
    public function testDisplaysRunning()
    {
        Cache::put('illuminate:queue:paused:sync:default', false);

        $this->artisan('queue:status default')
            ->assertSuccessful()
            ->expectsOutputToContain('Queue [sync:default] is currently running.');
    }

    public function testDisplaysPaused()
    {
        Cache::put('illuminate:queue:paused:sync:default', true);

        $this->artisan('queue:status default')
            ->assertFailed()
            ->expectsOutputToContain('Queue [sync:default] is currently paused.');
    }
}
