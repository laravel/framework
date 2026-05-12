<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\UniqueLock;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
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

    public function testRetryOfUniqueUntilProcessingJobDoesNotForceReleaseSubsequentLock()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        dispatch($job = new UniqueUntilProcessingRetryJob);

        // Lock acquired at dispatch time.
        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());

        $this->runQueueWorkerCommand(['--once' => true]); // attempt 1: releases lock, then fails

        $this->assertTrue($job::$handled);

        // Lock was correctly released before attempt 1 ran. Simulate a subsequent external dispatch
        // acquiring it (asserts it was free and holds it for the rest of the test).
        $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 60)->get());

        // Attempt 2 (the retry) must not force-release the lock it did not acquire.
        UniqueUntilProcessingRetryJob::$handled = false;
        $this->runQueueWorkerCommand(['--once' => true]); // attempt 2

        $this->assertTrue($job::$handled);
        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
    }

    public function testUniqueUntilProcessingLockIsReleasedAfterMiddlewareReleasedRetry()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        UniqueUntilProcessingWithoutOverlapJob::$handled = 0;

        $cache = $this->app->get(Cache::class);

        // Pre-acquire the WithoutOverlapping lock so attempt 1 will be released by
        // the middleware before reaching handle(). This simulates the original
        // user-reported scenario where another instance of the job is already
        // processing while this one is dequeued.
        $overlapKey = 'laravel-queue-overlap:'.UniqueUntilProcessingWithoutOverlapJob::class.':';
        $overlapLock = $cache->lock($overlapKey, 60);
        $this->assertTrue($overlapLock->get());

        dispatch($job = new UniqueUntilProcessingWithoutOverlapJob);

        // SBUUP lock acquired at dispatch time.
        $this->assertFalse($cache->lock($this->getLockKey($job), 10)->get());

        // Attempt 1: WithoutOverlapping releases the job because the overlap lock
        // is already held. handle() must not run, and the SBUUP lock must remain
        // held for the upcoming retry.
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertSame(0, UniqueUntilProcessingWithoutOverlapJob::$handled);
        $this->assertFalse($cache->lock($this->getLockKey($job), 10)->get());

        // Free the overlap lock so attempt 2 can run handle().
        $overlapLock->release();

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertSame(1, UniqueUntilProcessingWithoutOverlapJob::$handled);

        // Regression: the SBUUP lock must be released after the successful
        // attempt 2, even though attempts() > 1. Otherwise future dispatches for
        // the same key are silently dropped forever.
        $this->assertTrue($cache->lock($this->getLockKey($job), 10)->get());
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

class UniqueTestJob implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }
}

class UniqueTestFailJob implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public $tries = 1;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;

        throw new Exception;
    }
}

class UniqueTestReleasedJob extends UniqueTestFailJob
{
    public $tries = 1;

    public function handle()
    {
        static::$handled = true;

        $this->release();
    }
}

class UniqueTestRetryJob extends UniqueTestFailJob
{
    public $tries = 2;
}

class UniqueUntilStartTestJob extends UniqueTestJob implements ShouldBeUniqueUntilProcessing
{
    public $tries = 2;
}

class UniqueUntilProcessingRetryJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public $tries = 2;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;

        if ($this->attempts() === 1) {
            throw new Exception('First attempt failure.');
        }
    }
}

class UniqueUntilProcessingWithoutOverlapJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public $tries = 3;

    public static int $handled = 0;

    public function middleware()
    {
        return [new WithoutOverlapping];
    }

    public function handle()
    {
        static::$handled++;
    }
}

class UniqueTestSerializesModelsJob extends UniqueTestJob
{
    use SerializesModels;

    public $deleteWhenMissingModels = true;

    public function __construct(public User $user)
    {
    }
}

class UniqueViaJob extends UniqueTestJob
{
    public function uniqueVia(): Cache
    {
        return Container::getInstance()->make(Cache::class);
    }
}

class UniqueIdTestJob extends UniqueTestJob
{
    public function uniqueId(): string
    {
        return 'unique-id-1';
    }
}

class UniqueTestJobWithDisplayName extends UniqueTestJob
{
    public function displayName(): string
    {
        return 'App\\Actions\\UniqueTestAction';
    }
}

class UniqueIdTestJobWithDisplayName extends UniqueTestJob
{
    public function uniqueId(): string
    {
        return 'unique-id-2';
    }

    public function displayName(): string
    {
        return 'App\\Actions\\UniqueTestAction';
    }
}
