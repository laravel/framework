<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Queue\Events\UniqueJobSkipped;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Tests\App\Jobs\OwnerlessUniqueUntilProcessingRetryJob;
use Illuminate\Tests\App\Jobs\UniqueIdTestJob;
use Illuminate\Tests\App\Jobs\UniqueIdTestJobWithDisplayName;
use Illuminate\Tests\App\Jobs\UniqueTestAfterCommitJob;
use Illuminate\Tests\App\Jobs\UniqueTestFailJob;
use Illuminate\Tests\App\Jobs\UniqueTestJob;
use Illuminate\Tests\App\Jobs\UniqueTestJobWithDisplayName;
use Illuminate\Tests\App\Jobs\UniqueTestReleasedJob;
use Illuminate\Tests\App\Jobs\UniqueTestRetryJob;
use Illuminate\Tests\App\Jobs\UniqueTestSerializesModelsJob;
use Illuminate\Tests\App\Jobs\UniqueUntilProcessingRetryJob;
use Illuminate\Tests\App\Jobs\UniqueUntilStartTestJob;
use Illuminate\Tests\App\Jobs\UniqueViaJob;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;

#[WithMigration]
#[WithMigration('cache')]
#[WithMigration('queue')]
class UniqueJobTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('cache.default', 'database');
    }

    public function testUniqueJobsAreNotDispatched()
    {
        Bus::fake();

        UniqueTestJob::dispatch();
        $this->runQueueWorkerCommand(['--once' => true]);
        Bus::assertDispatched(UniqueTestJob::class);

        $this->assertFalse(
            $this->app->get(Cache::class)->lock($this->getLockKey(UniqueTestJob::class), 10)->get()
        );

        Bus::assertDispatchedTimes(UniqueTestJob::class);
        UniqueTestJob::dispatch();
        $this->runQueueWorkerCommand(['--once' => true]);
        Bus::assertDispatchedTimes(UniqueTestJob::class);

        $this->assertFalse(
            $this->app->get(Cache::class)->lock($this->getLockKey(UniqueTestJob::class), 10)->get()
        );
    }

    public function testUniqueJobEmitsUniqueJobSkippedEventWhenAlreadyAcquired()
    {
        Bus::fake();

        $skipped = [];

        Event::listen(UniqueJobSkipped::class, function ($event) use (&$skipped) {
            $skipped[] = $event->job;
        });

        UniqueTestJob::dispatch();

        $this->assertSame([], $skipped);

        UniqueTestJob::dispatch();

        $this->assertCount(1, $skipped);
        $this->assertInstanceOf(UniqueTestJob::class, $skipped[0]);
    }

    public function testUniqueJobWithViaDispatched()
    {
        Bus::fake();

        UniqueViaJob::dispatch();
        Bus::assertDispatched(UniqueViaJob::class);
    }

    public function testLockIsReleasedForSuccessfulJobs()
    {
        UniqueTestJob::$handled = false;
        dispatch($job = new UniqueTestJob);
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);
        $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
    }

    public function testLockIsReleasedForFailedJobs()
    {
        UniqueTestFailJob::$handled = false;

        $this->expectException(Exception::class);

        try {
            dispatch_sync($job = new UniqueTestFailJob);
        } finally {
            $this->assertTrue($job::$handled);
            $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
        }
    }

    public function testLockIsNotReleasedForJobRetries()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        UniqueTestRetryJob::$handled = false;

        dispatch($job = new UniqueTestRetryJob);

        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);
        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());

        UniqueTestRetryJob::$handled = false;
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);
        $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
    }

    public function testLockIsNotReleasedForJobReleases()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        UniqueTestReleasedJob::$handled = false;
        dispatch($job = new UniqueTestReleasedJob);

        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);
        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());

        UniqueTestReleasedJob::$handled = false;
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertFalse($job::$handled);
        $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
    }

    public function testLockCanBeReleasedBeforeProcessing()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        UniqueUntilStartTestJob::$handled = false;

        dispatch($job = new UniqueUntilStartTestJob);

        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);
        $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
    }

    public function testRetryOfUniqueUntilProcessingJobDoesNotReleaseSubsequentLock()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        dispatch($job = new UniqueUntilProcessingRetryJob);

        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);
        $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 60)->get());

        UniqueUntilProcessingRetryJob::$handled = false;
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);
        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
    }

    public function testRetryOfOwnerlessUniqueUntilProcessingJobDoesNotReleaseSubsequentLock()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        dispatch($job = new OwnerlessUniqueUntilProcessingRetryJob);

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 60)->get());

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
    }

    public function testLockIsReleasedOnModelNotFoundException()
    {
        UniqueTestSerializesModelsJob::$handled = false;

        /** @var \Illuminate\Foundation\Auth\User */
        $user = UserFactory::new()->create();
        $job = new UniqueTestSerializesModelsJob($user);

        $this->expectException(ModelNotFoundException::class);

        try {
            $user->delete();
            dispatch($job);
            $this->runQueueWorkerCommand(['--once' => true]);
            unserialize(serialize($job));
        } finally {
            $this->assertFalse($job::$handled);
            $this->assertModelMissing($user);
            $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
        }
    }

    public function testModelNotFoundExceptionDoesNotReleaseSubsequentLock()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        /** @var \Illuminate\Foundation\Auth\User */
        $user = UserFactory::new()->create();
        $job = new UniqueTestSerializesModelsJob($user);
        $cache = $this->app->get(Cache::class);
        $lock = new UniqueLock($cache);

        dispatch($job);

        $lock->release($job);

        $replacement = new UniqueTestSerializesModelsJob($user);
        $this->assertTrue($lock->acquire($replacement));

        $user->delete();
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertFalse($cache->lock($this->getLockKey($job), 10)->get());

        $lock->release($replacement);
    }

    public function testQueueFakeReleasesUniqueJobLocksBetweenFakes()
    {
        Queue::fake();

        UniqueTestJob::dispatch();
        Queue::assertPushed(UniqueTestJob::class);

        Queue::fake();

        UniqueTestJob::dispatch();
        Queue::assertPushed(UniqueTestJob::class);
    }

    public function testQueueFakePreservesUniqueJobLockWithinTest()
    {
        Queue::fake();

        UniqueTestJob::dispatch();
        UniqueTestJob::dispatch();

        Queue::assertPushedTimes(UniqueTestJob::class, 1);
    }

    public function testRolledBackPushDoesNotReleaseAnotherDispatchesUniqueLock()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        dispatch($job = new UniqueTestAfterCommitJob);

        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());

        try {
            DB::transaction(function () {
                Queue::push(new UniqueTestAfterCommitJob);

                throw new Exception('Rollback.');
            });
        } catch (Exception) {
            //
        }

        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
    }

    protected function getLockKey($job)
    {
        return 'laravel_unique_job:'.(is_string($job) ? $job : get_class($job)).':';
    }

    public function testLockUsesDisplayNameWhenAvailable()
    {
        Bus::fake();

        $lockKey = 'laravel_unique_job:'.hash('xxh128', 'App\\Actions\\UniqueTestAction').':';

        dispatch(new UniqueTestJobWithDisplayName);
        $this->runQueueWorkerCommand(['--once' => true]);
        Bus::assertDispatched(UniqueTestJobWithDisplayName::class);

        $this->assertFalse(
            $this->app->get(Cache::class)->lock($lockKey, 10)->get()
        );

        Bus::assertDispatchedTimes(UniqueTestJobWithDisplayName::class);
        dispatch(new UniqueTestJobWithDisplayName);
        $this->runQueueWorkerCommand(['--once' => true]);
        Bus::assertDispatchedTimes(UniqueTestJobWithDisplayName::class);

        $this->assertFalse(
            $this->app->get(Cache::class)->lock($lockKey, 10)->get()
        );
    }

    public function testUniqueLockCreatesKeyWithClassName()
    {
        $this->assertSame(
            'laravel_unique_job:'.UniqueTestJob::class.':',
            UniqueLock::getKey(new UniqueTestJob)
        );
    }

    public function testUniqueLockCreatesKeyWithIdAndClassName()
    {
        $this->assertSame(
            'laravel_unique_job:'.UniqueIdTestJob::class.':unique-id-1',
            UniqueLock::getKey(new UniqueIdTestJob)
        );
    }

    public function testUniqueLockCreatesKeyWithDisplayNameWhenAvailable()
    {
        $this->assertSame(
            'laravel_unique_job:'.hash('xxh128', 'App\\Actions\\UniqueTestAction').':unique-id-2',
            UniqueLock::getKey(new UniqueIdTestJobWithDisplayName)
        );
    }

    public function testUniqueLockCreatesKeyWithIdAndDisplayNameWhenAvailable()
    {
        $this->assertSame(
            'laravel_unique_job:'.hash('xxh128', 'App\\Actions\\UniqueTestAction').':unique-id-2',
            UniqueLock::getKey(new UniqueIdTestJobWithDisplayName)
        );
    }
}
