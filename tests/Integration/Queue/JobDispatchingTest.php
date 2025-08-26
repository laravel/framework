<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('queue')]
class JobDispatchingTest extends QueueTestCase
{
    protected function setUp(): void
    {
        $this->beforeApplicationDestroyed(function () {
            Job::$ran = false;
            Job::$value = null;
        });

        parent::setUp();
    }

    public function testJobCanUseCustomMethodsAfterDispatch()
    {
        Job::dispatch('test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(Job::$ran);
        $this->assertSame('new-test', Job::$value);
    }

    public function testDispatchesConditionallyWithBoolean()
    {
        Job::dispatchIf(false, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(Job::$ran);
        $this->assertNull(Job::$value);

        Job::dispatchIf(true, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(Job::$ran);
        $this->assertSame('new-test', Job::$value);
    }

    public function testDispatchesConditionallyWithClosure()
    {
        Job::dispatchIf(fn ($job) => $job instanceof Job ? 0 : 1, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(Job::$ran);

        Job::dispatchIf(fn ($job) => $job instanceof Job ? 1 : 0, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(Job::$ran);
    }

    public function testDoesNotDispatchConditionallyWithBoolean()
    {
        Job::dispatchUnless(true, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(Job::$ran);
        $this->assertNull(Job::$value);

        Job::dispatchUnless(false, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(Job::$ran);
        $this->assertSame('new-test', Job::$value);
    }

    public function testDoesNotDispatchConditionallyWithClosure()
    {
        Job::dispatchUnless(fn ($job) => $job instanceof Job ? 1 : 0, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(Job::$ran);

        Job::dispatchUnless(fn ($job) => $job instanceof Job ? 0 : 1, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(Job::$ran);
    }

    public function testUniqueJobLockIsReleasedForJobDispatchedAfterResponse()
    {
        // get initial terminatingCallbacks
        $terminatingCallbacksReflectionProperty = (new \ReflectionObject($this->app))->getProperty('terminatingCallbacks');
        $startTerminatingCallbacks = $terminatingCallbacksReflectionProperty->getValue($this->app);

        UniqueJob::dispatchAfterResponse('test');
        $this->assertFalse(
            $this->getJobLock(UniqueJob::class, 'test')
        );

        $this->app->terminate();
        $this->assertTrue(UniqueJob::$ran);

        $terminatingCallbacksReflectionProperty->setValue($this->app, $startTerminatingCallbacks);

        UniqueJob::$ran = false;
        UniqueJob::dispatch('test')->afterResponse();
        $this->app->terminate();
        $this->assertTrue(UniqueJob::$ran);

        // acquire job lock and confirm that job is not dispatched after response
        $this->assertTrue(
            $this->getJobLock(UniqueJob::class, 'test')
        );
        $terminatingCallbacksReflectionProperty->setValue($this->app, $startTerminatingCallbacks);
        UniqueJob::$ran = false;
        UniqueJob::dispatch('test')->afterResponse();
        $this->app->terminate();
        $this->assertFalse(UniqueJob::$ran);

        // confirm that dispatchAfterResponse also does not run
        UniqueJob::dispatchAfterResponse('test');
        $this->app->terminate();
        $this->assertFalse(UniqueJob::$ran);
    }

    public function testQueueMayBeNullForJobQueueingAndJobQueuedEvent()
    {
        Config::set('queue.default', 'database');
        $events = [];
        $this->app['events']->listen(function (JobQueueing $e) use (&$events) {
            $events[] = $e;
        });
        $this->app['events']->listen(function (JobQueued $e) use (&$events) {
            $events[] = $e;
        });

        MyTestDispatchableJob::dispatch();
        dispatch(function () {
            //
        });

        $this->assertCount(4, $events);
        $this->assertInstanceOf(JobQueueing::class, $events[0]);
        $this->assertNull($events[0]->queue);
        $this->assertInstanceOf(JobQueued::class, $events[1]);
        $this->assertNull($events[1]->queue);
        $this->assertInstanceOf(JobQueueing::class, $events[2]);
        $this->assertNull($events[2]->queue);
        $this->assertInstanceOf(JobQueued::class, $events[3]);
        $this->assertNull($events[3]->queue);
    }

    public function testQueuedClosureCanBeNamed()
    {
        Config::set('queue.default', 'database');
        $events = [];
        $this->app['events']->listen(function (JobQueued $e) use (&$events) {
            $events[] = $e;
        });

        dispatch(function () {
            //
        })->name('custom name');

        $this->assertCount(1, $events);
        $this->assertInstanceOf(JobQueued::class, $events[0]);
        $this->assertSame('custom name', $events[0]->job->name);
        $this->assertStringContainsString('custom name', $events[0]->job->displayName());
    }

    public function testCanDisableDispatchingAfterResponse()
    {
        Job::dispatchAfterResponse('test');

        $this->assertFalse(Job::$ran);

        $this->app->terminate();

        $this->assertTrue(Job::$ran);

        Bus::withoutDispatchingAfterResponses();

        Job::$ran = false;
        Job::dispatchAfterResponse('test');

        $this->assertTrue(Job::$ran);

        $this->app->terminate();

        Bus::withDispatchingAfterResponses();

        Job::$ran = false;
        Job::dispatchAfterResponse('test');

        $this->assertFalse(Job::$ran);

        $this->app->terminate();

        $this->assertTrue(Job::$ran);
    }

    /**
     * Helpers.
     */
    private function getJobLock($job, $value = null)
    {
        return $this->app->get(Repository::class)->lock('laravel_unique_job:'.$job.':'.$value, 10)->get();
    }
}

class Job implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;
    public static $usedQueue = null;
    public static $usedConnection = null;
    public static $value = null;

    public function __construct($value)
    {
        static::$value = $value;
    }

    public function handle()
    {
        static::$ran = true;
    }

    public function replaceValue($value)
    {
        static::$value = $value;
    }
}

class UniqueJob extends Job implements ShouldBeUnique
{
    use InteractsWithQueue;

    public function uniqueId()
    {
        return self::$value;
    }
}

class MyTestDispatchableJob implements ShouldQueue
{
    use Dispatchable;
}
