<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Queue\Failed\NullFailedJobProvider;
use Orchestra\Testbench\TestCase;

class QueueListFailedCommandTest extends TestCase
{
    public function testListFailedJobsAsJsonWhenEmpty()
    {
        $this->app->instance('queue.failer', new NullFailedJobProvider);

        $this->artisan('queue:failed', ['--json' => true])
            ->expectsOutput(json_encode([
                'failed_jobs' => [],
                'count' => 0,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
            ->assertExitCode(0);
    }

    public function testListFailedJobsAsTableWhenEmpty()
    {
        $this->app->instance('queue.failer', new NullFailedJobProvider);

        $this->artisan('queue:failed')
            ->expectsOutputToContain('No failed jobs found')
            ->assertExitCode(0);
    }
}
