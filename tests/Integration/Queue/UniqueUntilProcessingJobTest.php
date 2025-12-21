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
