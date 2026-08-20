<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Support\Facades\DB;
use Illuminate\Tests\App\Jobs\UniqueTestJobThatDoesNotRelease;
use Illuminate\Tests\App\Jobs\UniqueUntilProcessingJobThatReleases;
use Illuminate\Tests\App\Jobs\UniqueUntilProcessingJobThatReleasesOnce;
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

    public function testShouldBeUniqueUntilProcessingReleasesLockWhenLaterAttemptIsProcessed()
    {
        UniqueUntilProcessingJobThatReleasesOnce::dispatch();

        $this->assertNotNull(DB::table('cache_locks')->first());

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertFalse(UniqueUntilProcessingJobThatReleasesOnce::$handled);
        $this->assertNotNull(DB::table('cache_locks')->first());

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue(UniqueUntilProcessingJobThatReleasesOnce::$handled);
        $this->assertNull(DB::table('cache_locks')->first());
    }
}
