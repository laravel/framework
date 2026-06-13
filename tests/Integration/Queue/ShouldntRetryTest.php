<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldntRetry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Attributes\WithMigration;
use RuntimeException;

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

        parent::tearDown();
    }

    public function testJobFailsImmediatelyWhenItThrowsAnExceptionThatShouldntRetry()
    {
        ShouldntRetryTestJob::$exception = new ShouldntRetryTestException;

        ShouldntRetryTestJob::dispatch();

        $this->runQueueWorkerCommand(['--once' => true, '--tries' => 3]);

        $this->assertSame(1, ShouldntRetryTestJob::$attempts);
        $this->assertNull(DB::table('jobs')->first());
        $this->assertNotNull(DB::table('failed_jobs')->first());
    }

    public function testJobIsRetriedWhenItThrowsAnExceptionThatDoesNotOptOutOfRetries()
    {
        ShouldntRetryTestJob::$exception = new RuntimeException('Transient failure.');

        ShouldntRetryTestJob::dispatch();

        $this->runQueueWorkerCommand(['--once' => true, '--tries' => 3]);

        $this->assertSame(1, ShouldntRetryTestJob::$attempts);
        $this->assertNotNull(DB::table('jobs')->first());
        $this->assertNull(DB::table('failed_jobs')->first());
    }
}

class ShouldntRetryTestException extends RuntimeException implements ShouldntRetry
{
    //
}

class ShouldntRetryTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public static int $attempts = 0;

    public static ?\Throwable $exception = null;

    public function handle()
    {
        static::$attempts++;

        throw static::$exception;
    }
}
