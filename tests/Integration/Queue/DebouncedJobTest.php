<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
use Illuminate\Bus\DebounceLock;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Events\JobDebounced;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache as CacheFacade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Tests\App\Jobs\ChainReceiverJob;
use Illuminate\Tests\App\Jobs\ChildOfDebouncedTestJob;
use Illuminate\Tests\App\Jobs\DebouncedAndUniqueTestJob;
use Illuminate\Tests\App\Jobs\DebouncedTestFailJob;
use Illuminate\Tests\App\Jobs\DebouncedTestJob;
use Illuminate\Tests\App\Jobs\DebouncedWithCustomCacheJob;
use Illuminate\Tests\App\Jobs\DebouncedWithMaxWaitJob;
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
        $this->travelTo(Carbon::now()->addSeconds(31));
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
        $this->travelTo(Carbon::now()->addSeconds(31));

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
        $this->travelTo(Carbon::now()->addSeconds(31));
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

        $this->travelTo(Carbon::now()->addSeconds(31));
        $this->runQueueWorkerCommand(['--once' => true], 2);

        $this->assertEquals(1, $firedCount);
    }

    public function testDebouncedAndUniqueThrowsLogicException()
    {
        $this->expectExceptionObject(new LogicException('debounced job cannot also implement ShouldBeUnique'));

        DebouncedAndUniqueTestJob::dispatch('entity-1');
    }

    public function testDebounceOwnerSurvivesSerialization()
    {
        $job = new DebouncedTestJob('entity-1');
        $job->debounceOwner = 'test-owner-token-123';

        $restored = unserialize(serialize($job));

        $this->assertSame('test-owner-token-123', $restored->debounceOwner);
    }

    public function testDifferentDebounceIdsDoNotInterfere()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['sync', 'beanstalkd']);

        DebouncedTestJob::$handleCount = 0;

        dispatch(new DebouncedTestJob('entity-1'));
        dispatch(new DebouncedTestJob('entity-2'));

        $this->travelTo(Carbon::now()->addSeconds(31));
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

        $this->travelTo(Carbon::now()->addSeconds(31));
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

        $ownerA = $lock->acquire($jobA)['owner'];
        $ownerB = $lock->acquire($jobB)['owner'];

        // Releasing with A's owner should not wipe B's token.
        $lock->release($jobA, $ownerA);

        // B should still be the current owner.
        $this->assertSame($lock->getCurrentOwner($jobB), $ownerB);
    }

    public function testReleaseClearsMaxWaitTimestamp()
    {
        $cache = $this->app->get(Cache::class);
        $lock = new DebounceLock($cache);
        $job = new DebouncedWithMaxWaitJob('entity-1');

        $first = $lock->acquire($job);

        $this->assertFalse($first['maxWaitExceeded']);

        // Simulate rollback cleanup.
        $lock->release($job, $first['owner']);

        $this->assertNull($cache->get(DebounceLock::getKey($job).':first_dispatched_at'));

        // If timestamp cleanup worked, max wait should not appear exceeded.
        $this->travelTo(Carbon::now()->addSeconds(61));

        $second = $lock->acquire($job);

        $this->assertFalse($second['maxWaitExceeded']);
    }

    public function testSupersededDebouncedJobDoesNotDispatchChain()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['sync', 'beanstalkd']);

        DebouncedTestJob::$handleCount = 0;
        ChainReceiverJob::$handled = false;

        // First dispatch with a chain — will be superseded.
        dispatch(new DebouncedTestJob('entity-1'))->chain([new ChainReceiverJob]);

        // Second dispatch supersedes the first (no chain).
        dispatch(new DebouncedTestJob('entity-1'));

        $this->travelTo(Carbon::now()->addSeconds(31));
        $this->runQueueWorkerCommand(['--once' => true], 3);

        // Only the second dispatch should have executed.
        $this->assertEquals(1, DebouncedTestJob::$handleCount);

        // Chain from superseded job should NOT have been dispatched.
        $this->assertFalse(ChainReceiverJob::$handled);
    }

    public function testDebounceViaUsesCustomCacheStore()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['beanstalkd']);

        DebouncedWithCustomCacheJob::$handled = false;

        dispatch(new DebouncedWithCustomCacheJob('entity-1'));

        $key = DebounceLock::getKey(new DebouncedWithCustomCacheJob('entity-1'));

        // Token should exist in the custom 'array' store.
        $this->assertNotNull(CacheFacade::store('array')->get($key));

        // Token should NOT exist in the default 'database' store.
        $this->assertNull(CacheFacade::store('database')->get($key));
    }

    public function testMaxDebounceWaitForcesImmediateExecution()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['beanstalkd']);

        DebouncedWithMaxWaitJob::$handleCount = 0;

        // First dispatch at t=0.
        dispatch(new DebouncedWithMaxWaitJob('entity-1'));

        // Second dispatch at t=50 (within maxWait of 60s).
        $this->travelTo(Carbon::now()->addSeconds(50));
        dispatch(new DebouncedWithMaxWaitJob('entity-1'));

        // Third dispatch at t=61 — exceeds maxWait.
        $this->travelTo(Carbon::now()->addSeconds(11));
        $job = new DebouncedWithMaxWaitJob('entity-1');
        $pending = dispatch($job);
        unset($pending);

        // The job should be queued with delay=0 since max wait was exceeded.
        $this->assertEquals(0, $job->delay);
    }

    public function testDebounceWithoutMaxWaitAllowsIndefiniteDelay()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['beanstalkd']);

        // Regular debounced job (no maxWait) — delay should always be the debounce value.
        $job1 = new DebouncedTestJob('entity-1');
        $pending = dispatch($job1);
        unset($pending);

        $this->assertEquals(30, $job1->delay);

        // Dispatch again much later — still gets the full delay.
        $this->travelTo(Carbon::now()->addMinutes(10));
        $job2 = new DebouncedTestJob('entity-1');
        $pending2 = dispatch($job2);
        unset($pending2);

        $this->assertEquals(30, $job2->delay);
    }

    public function testChildDebouncedJobInheritsFromParent()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['sync', 'beanstalkd']);

        ChildOfDebouncedTestJob::$handleCount = 0;

        // Dispatch two jobs with the same debounce identity.
        // The second dispatch supersedes the first.
        dispatch(new ChildOfDebouncedTestJob('entity-1'));
        dispatch(new ChildOfDebouncedTestJob('entity-1'));

        // Advance time past the debounce window so jobs become available.
        $this->travelTo(Carbon::now()->addSeconds(31));

        // Process both jobs from the queue.
        $this->runQueueWorkerCommand(['--once' => true], 2);

        // Only the second (latest) dispatch should have executed.
        $this->assertEquals(1, ChildOfDebouncedTestJob::$handleCount);
    }
}
