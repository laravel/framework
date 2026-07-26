<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
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

    public function testShouldBeUniqueUntilProcessingReleasesLockOnTheAttemptThatIsProcessed()
    {
        UniqueUntilProcessingJobThatReleasesOnce::dispatch();

        $this->assertNotNull(DB::table('cache_locks')->first());

        // Attempt 1: a middleware releases the job before it is handled, so the
        // lock must be retained -- the job is still pending on the queue...
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertFalse(UniqueUntilProcessingJobThatReleasesOnce::$handled);
        $this->assertNotNull(DB::table('cache_locks')->first());

        // Attempt 2: the middleware lets the job through, so the lock must be
        // released before the job is handled...
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue(UniqueUntilProcessingJobThatReleasesOnce::$handled);
        $this->assertNull(DB::table('cache_locks')->first());
    }

    public function testShouldBeUniqueUntilProcessingReleasesLockFromFinallyOnALaterAttempt()
    {
        UniqueUntilProcessingJobThatThrowsFromMiddleware::dispatch();

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertNotNull(DB::table('cache_locks')->first());

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertFalse(UniqueUntilProcessingJobThatThrowsFromMiddleware::$handled);
        $this->assertNull(DB::table('cache_locks')->first());
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

class UniqueUntilProcessingJobThatReleasesOnce extends UniqueTestJobThatDoesNotRelease
{
    public $tries = 3;

    public function middleware()
    {
        return [
            function ($job, $next) {
                if ($job->attempts() === 1) {
                    static::$released = true;

                    return $job->release();
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

class UniqueUntilProcessingJobThatThrowsFromMiddleware extends UniqueTestJobThatDoesNotRelease
{
    public $tries = 3;

    public function middleware()
    {
        return [
            function ($job) {
                if ($job->attempts() === 1) {
                    static::$released = true;

                    return $job->release();
                }

                throw new Exception('Middleware failure.');
            },
        ];
    }

    public function uniqueId()
    {
        return 300;
    }
}
