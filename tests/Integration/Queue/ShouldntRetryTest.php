<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldntRetry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('queue')]
class ShouldntRetryTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('queue.default', 'database');
        $this->driver = 'database';
    }

    #[\Override]
    protected function tearDown(): void
    {
        ShouldntRetryTestJob::$attempts = 0;
        RetryableTestJob::$attempts = 0;

        parent::tearDown();
    }

    public function testJobImplementingShouldntRetryFailsImmediatelyWithoutBeingReleased()
    {
        ShouldntRetryTestJob::dispatch();

        // The job is given three tries, but it should fail on the first attempt and
        // skip the remaining retries because it implements the ShouldntRetry contract.
        $this->runQueueWorkerCommand(['--once' => true, '--tries' => 3]);

        $this->assertSame(1, ShouldntRetryTestJob::$attempts);
        $this->assertNull(DB::table('jobs')->first());
        $this->assertNotNull(DB::table('failed_jobs')->first());
    }

    public function testJobWithoutShouldntRetryIsReleasedBackOntoTheQueue()
    {
        RetryableTestJob::dispatch();

        $this->runQueueWorkerCommand(['--once' => true, '--tries' => 3]);

        $this->assertSame(1, RetryableTestJob::$attempts);
        $this->assertNotNull(DB::table('jobs')->first());
        $this->assertNull(DB::table('failed_jobs')->first());
    }
}

class ShouldntRetryTestJob implements ShouldQueue, ShouldntRetry
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public static int $attempts = 0;

    public function handle()
    {
        static::$attempts++;

        throw new \RuntimeException('Job failed.');
    }
}

class RetryableTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public static int $attempts = 0;

    public function handle()
    {
        static::$attempts++;

        throw new \RuntimeException('Job failed.');
    }
}
