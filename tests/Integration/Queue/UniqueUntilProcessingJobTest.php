<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('cache')]
#[WithMigration('queue')]
class UniqueUntilProcessingJobTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);
        $app['config']->set('queue.default', 'database');
        $app['config']->set('cache.default', 'database');
        $this->driver = 'database';
    }

    public function testShouldBeUniqueUntilProcessingReleasesLockWhenJobIsReleasedByAMiddleware()
    {
        // Job that does not release and gets processed
        UniqueTestJobThatDoesNotRelease::dispatch();
        $lockKey = DB::table('cache_locks')->orderBy('id')->first()->key;
        $this->assertNotNull($lockKey);
        $this->runQueueWorkerCommand(['--once' => true]);
        $this->assertFalse(UniqueTestJobThatDoesNotRelease::$released);
        $lockKey = DB::table('cache_locks')->first()->key ?? null;
        $this->assertNull($lockKey);
        $this->assertDatabaseCount('jobs', 0);

        // Job that releases and does not get processed
        UniqueUntilProcessingJobThatReleases::dispatch();
        $lockKey = DB::table('cache_locks')->first()->key;
        $this->assertNotNull($lockKey);
        $this->runQueueWorkerCommand(['--once' => true]);
        $this->assertFalse(UniqueUntilProcessingJobThatReleases::$handled);
        $this->assertTrue(UniqueUntilProcessingJobThatReleases::$released);
        $lockKey = DB::table('cache_locks')->orderBy('id')->first()->key ?? null;
        $this->assertNotNull($lockKey);

        UniqueUntilProcessingJobThatReleases::dispatch();
        $this->assertDatabaseCount('jobs', 1);
    }

    public function testShouldBeUniqueUntilProcessingReleasesLockWhenRetryAfterBeingReleasedByMiddlewareSucceeds()
    {
        UniqueUntilProcessingJobThatReleasesOnlyOnFirstAttempt::dispatch();
        $this->assertNotNull(DB::table('cache_locks')->first());

        // Attempt #1: middleware releases the job back to the queue without processing it.
        $this->runQueueWorkerCommand(['--once' => true, '--tries' => 2]);
        $this->assertFalse(UniqueUntilProcessingJobThatReleasesOnlyOnFirstAttempt::$handled);
        $this->assertNotNull(DB::table('cache_locks')->first());

        // Attempt #2: middleware lets it through this time, and the job actually runs.
        $this->runQueueWorkerCommand(['--once' => true, '--tries' => 2]);
        $this->assertTrue(UniqueUntilProcessingJobThatReleasesOnlyOnFirstAttempt::$handled);
        $this->assertNull(DB::table('cache_locks')->first());

        // The lock should be free again for a fresh dispatch.
        UniqueUntilProcessingJobThatReleasesOnlyOnFirstAttempt::dispatch();
        $this->assertDatabaseCount('jobs', 1);
    }
}

class UniqueTestJobThatDoesNotRelease implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public static $handled = false;
    public static $released = false;

    public function __construct()
    {
        static::$handled = false;
        static::$released = false;
    }

    public function handle()
    {
        static::$handled = true;
    }
}

class UniqueUntilProcessingJobThatReleases extends UniqueTestJobThatDoesNotRelease
{
    public function middleware()
    {
        return [
            function ($job) {
                static::$released = true;

                return $job->release(30);
            },
        ];
    }

    public function uniqueId()
    {
        return 100;
    }
}

class UniqueUntilProcessingJobThatReleasesOnlyOnFirstAttempt extends UniqueTestJobThatDoesNotRelease
{
    public function middleware()
    {
        return [
            function ($job, $next) {
                if ($job->attempts() === 1) {
                    return $job->release(0);
                }

                return $next($job);
            },
        ];
    }

    public function uniqueId()
    {
        return 200;
    }
}
