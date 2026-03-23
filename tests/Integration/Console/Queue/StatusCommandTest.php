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

    public function testDisplaysRunningJson()
    {
        Cache::put('illuminate:queue:paused:sync:default', false);

        $this->artisan('queue:status default --json')
            ->assertSuccessful()
            ->expectsOutputToContain(json_encode([
                'connection' => 'sync',
                'queue' => 'default',
                'status' => 'running',
            ]));
    }

    public function testDisplaysPausedJson()
    {
        Cache::put('illuminate:queue:paused:sync:default', true);

        $this->artisan('queue:status default --json')
            ->assertFailed()
            ->expectsOutputToContain(json_encode([
                'connection' => 'sync',
                'queue' => 'default',
                'status' => 'paused',
            ]));
    }
}
