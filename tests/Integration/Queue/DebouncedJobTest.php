<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
use Illuminate\Bus\DebounceLock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeDebounced;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Events\JobDebounced;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use LogicException;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('cache')]
#[WithMigration('queue')]
class DebouncedJobTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('cache.default', 'database');
    }

    public function testDebouncedJobDispatchesAndExecutes()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['beanstalkd']);

        DebouncedTestJob::$handled = false;

        dispatch(new DebouncedTestJob('entity-1'));
        $this->travelTo(now()->addSeconds(31));
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue(DebouncedTestJob::$handled);
    }

    public function testSupersededDebouncedJobIsSkipped()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['sync', 'beanstalkd']);

        DebouncedTestJob::$handleCount = 0;

        // Dispatch two jobs with the same debounce identity.
        // The second dispatch supersedes the first.
        dispatch(new DebouncedTestJob('entity-1'));
        dispatch(new DebouncedTestJob('entity-1'));

        // Advance time past the debounce window so jobs become available.
        $this->travelTo(now()->addSeconds(31));

        // Process both jobs from the queue.
        $this->runQueueWorkerCommand(['--once' => true], 2);

        // Only the second (latest) dispatch should have executed.
        $this->assertEquals(1, DebouncedTestJob::$handleCount);
    }

    public function testTokenPersistsAfterSuccessfulExecution()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['beanstalkd']);

        DebouncedTestJob::$handled = false;

        dispatch($job = new DebouncedTestJob('entity-1'));
        $this->travelTo(now()->addSeconds(31));
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);

        // Debounce token persists after execution (cleaned up by GC TTL)
        // to prevent a race where a superseded job sees an empty cache
        // and executes via fail-open.
        $this->assertNotNull(
            $this->app->get(Cache::class)->get(DebounceLock::getKey($job))
        );
    }

    public function testFailedDebouncedJobStillCallsHandler()
    {
        DebouncedTestFailJob::$handled = false;

        $this->expectException(Exception::class);

        try {
            dispatch_sync(new DebouncedTestFailJob('entity-1'));
        } finally {
            $this->assertTrue(DebouncedTestFailJob::$handled);
        }
    }

    public function testJobDebouncedEventFiresForSupersededJob()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['sync', 'beanstalkd']);

        $firedCount = 0;

        Event::listen(JobDebounced::class, function () use (&$firedCount) {
            $firedCount++;
        });

        dispatch(new DebouncedTestJob('entity-1'));
        dispatch(new DebouncedTestJob('entity-1'));

        $this->travelTo(now()->addSeconds(31));
        $this->runQueueWorkerCommand(['--once' => true], 2);

        $this->assertEquals(1, $firedCount);
    }

    public function testDebouncedAndUniqueThrowsLogicException()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('ShouldBeDebounced and ShouldBeUnique');

        DebouncedAndUniqueTestJob::dispatch('entity-1');
    }

    public function testDebounceOwnerSurvivesSerialization()
    {
        $job = new DebouncedTestJob('entity-1');
        $job->debounceOwner = 'test-owner-token-123';

        $restored = unserialize(serialize($job));

        $this->assertEquals('test-owner-token-123', $restored->debounceOwner);
    }

    public function testDifferentDebounceIdsDoNotInterfere()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['sync', 'beanstalkd']);

        DebouncedTestJob::$handleCount = 0;

        dispatch(new DebouncedTestJob('entity-1'));
        dispatch(new DebouncedTestJob('entity-2'));

        $this->travelTo(now()->addSeconds(31));
        $this->runQueueWorkerCommand(['--once' => true], 2);

        // Both should execute — different identities.
        $this->assertEquals(2, DebouncedTestJob::$handleCount);
    }

    public function testDebounceLockKeyFormat()
    {
        $job = new DebouncedTestJob('entity-1');

        $key = DebounceLock::getKey($job);

        $this->assertStringStartsWith('laravel_debounced_job:', $key);
        $this->assertStringEndsWith(':entity-1', $key);
    }

    public function testQueueFakeCapturesDebouncedJob()
    {
        Queue::fake();

        DebouncedTestJob::dispatch('entity-1');

        Queue::assertPushed(DebouncedTestJob::class);
    }

    public function testJobExecutesWhenCacheTokenIsEvicted()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['beanstalkd']);

        DebouncedTestJob::$handled = false;

        dispatch($job = new DebouncedTestJob('entity-1'));

        // Simulate cache eviction by manually removing the debounce token.
        $this->app->get(Cache::class)->forget(DebounceLock::getKey($job));

        $this->travelTo(now()->addSeconds(31));
        $this->runQueueWorkerCommand(['--once' => true]);

        // Job should execute (fail-open) even though token was evicted.
        $this->assertTrue(DebouncedTestJob::$handled);
    }

    public function testOwnerAwareReleaseDoesNotWipeNewerLock()
    {
        $cache = $this->app->get(Cache::class);
        $lock = new DebounceLock($cache);

        $jobA = new DebouncedTestJob('entity-1');
        $jobB = new DebouncedTestJob('entity-1');

        $ownerA = $lock->acquire($jobA);
        $ownerB = $lock->acquire($jobB);

        // Releasing with A's owner should not wipe B's token.
        $lock->release($jobA, $ownerA);

        // B should still be the current owner.
        $this->assertTrue($lock->isCurrentOwner($jobB, $ownerB));
    }
}

class DebouncedTestJob implements ShouldQueue, ShouldBeDebounced
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public static $handled = false;

    public static $handleCount = 0;

    public string $debounceOwner = '';

    public function __construct(public string $entityId)
    {
    }

    public function debounceId(): string
    {
        return $this->entityId;
    }

    public function debounceFor(): int
    {
        return 30;
    }

    public function handle()
    {
        static::$handled = true;
        static::$handleCount++;
    }
}

class DebouncedTestFailJob implements ShouldQueue, ShouldBeDebounced
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public $tries = 1;

    public static $handled = false;

    public string $debounceOwner = '';

    public function __construct(public string $entityId)
    {
    }

    public function debounceId(): string
    {
        return $this->entityId;
    }

    public function debounceFor(): int
    {
        return 30;
    }

    public function handle()
    {
        static::$handled = true;

        throw new Exception;
    }
}

class DebouncedAndUniqueTestJob implements ShouldQueue, ShouldBeDebounced, ShouldBeUnique
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public string $debounceOwner = '';

    public function __construct(public string $entityId)
    {
    }

    public function debounceId(): string
    {
        return $this->entityId;
    }

    public function debounceFor(): int
    {
        return 30;
    }

    public function handle()
    {
    }
}
