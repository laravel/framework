<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Queue\Attributes\Queue as QueueAttribute;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Tests\App\Jobs\DispatchableJob;
use Illuminate\Tests\App\Jobs\JobWithClassQueueAttributeOverridingTrait;
use Illuminate\Tests\App\Jobs\JobWithEnumQueueAttribute;
use Illuminate\Tests\App\Jobs\JobWithTraitQueueAttribute;
use Illuminate\Tests\App\Jobs\MyTestDispatchableJob;
use Illuminate\Tests\App\Jobs\UniqueJob;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('queue')]
class JobDispatchingTest extends QueueTestCase
{
    protected function setUp(): void
    {
        $this->beforeApplicationDestroyed(function () {
            DispatchableJob::$ran = false;
            DispatchableJob::$value = null;
        });

        parent::setUp();
    }

    public function testJobCanUseCustomMethodsAfterDispatch()
    {
        DispatchableJob::dispatch('test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(DispatchableJob::$ran);
        $this->assertSame('new-test', DispatchableJob::$value);
    }

    public function testDispatchesConditionallyWithBoolean()
    {
        DispatchableJob::dispatchIf(false, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(DispatchableJob::$ran);
        $this->assertNull(DispatchableJob::$value);

        DispatchableJob::dispatchIf(true, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(DispatchableJob::$ran);
        $this->assertSame('new-test', DispatchableJob::$value);
    }

    public function testDispatchesConditionallyWithClosure()
    {
        DispatchableJob::dispatchIf(fn ($job) => $job instanceof DispatchableJob ? 0 : 1, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(DispatchableJob::$ran);

        DispatchableJob::dispatchIf(fn ($job) => $job instanceof DispatchableJob ? 1 : 0, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(DispatchableJob::$ran);
    }

    public function testDoesNotDispatchConditionallyWithBoolean()
    {
        DispatchableJob::dispatchUnless(true, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(DispatchableJob::$ran);
        $this->assertNull(DispatchableJob::$value);

        DispatchableJob::dispatchUnless(false, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(DispatchableJob::$ran);
        $this->assertSame('new-test', DispatchableJob::$value);
    }

    public function testDoesNotDispatchConditionallyWithClosure()
    {
        DispatchableJob::dispatchUnless(fn ($job) => $job instanceof DispatchableJob ? 1 : 0, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(DispatchableJob::$ran);

        DispatchableJob::dispatchUnless(fn ($job) => $job instanceof DispatchableJob ? 0 : 1, 'test')->replaceValue('new-test');

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(DispatchableJob::$ran);
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
        DispatchableJob::dispatchAfterResponse('test');

        $this->assertFalse(DispatchableJob::$ran);

        $this->app->terminate();

        $this->assertTrue(DispatchableJob::$ran);

        Bus::withoutDispatchingAfterResponses();

        DispatchableJob::$ran = false;
        DispatchableJob::dispatchAfterResponse('test');

        $this->assertTrue(DispatchableJob::$ran);

        $this->app->terminate();

        Bus::withDispatchingAfterResponses();

        DispatchableJob::$ran = false;
        DispatchableJob::dispatchAfterResponse('test');

        $this->assertFalse(DispatchableJob::$ran);

        $this->app->terminate();

        $this->assertTrue(DispatchableJob::$ran);
    }

    public function testQueueAttributeWithEnumNormalizesToStringInJobQueuedEvent()
    {
        Config::set('queue.default', 'database');
        $events = [];
        $this->app['events']->listen(function (JobQueueing $e) use (&$events) {
            $events[] = $e;
        });
        $this->app['events']->listen(function (JobQueued $e) use (&$events) {
            $events[] = $e;
        });

        JobWithEnumQueueAttribute::dispatch();

        $this->assertCount(2, $events);
        $this->assertInstanceOf(JobQueueing::class, $events[0]);
        $this->assertSame('default', $events[0]->queue);
        $this->assertInstanceOf(JobQueued::class, $events[1]);
        $this->assertSame('default', $events[1]->queue);
    }

    public function testQueueAttributeOnTraitIsResolved()
    {
        Config::set('queue.default', 'database');
        $events = [];
        $this->app['events']->listen(function (JobQueued $e) use (&$events) {
            $events[] = $e;
        });

        JobWithTraitQueueAttribute::dispatch();

        $this->assertCount(1, $events);
        $this->assertSame('notifications', $events[0]->queue);
    }

    public function testClassQueueAttributeTakesPrecedenceOverTrait()
    {
        Config::set('queue.default', 'database');
        $events = [];
        $this->app['events']->listen(function (JobQueued $e) use (&$events) {
            $events[] = $e;
        });

        JobWithClassQueueAttributeOverridingTrait::dispatch();

        $this->assertCount(1, $events);
        $this->assertSame('high', $events[0]->queue);
    }

    /**
     * Helpers.
     */
    private function getJobLock($job, $value = null)
    {
        return $this->app->get(Repository::class)->lock('laravel_unique_job:'.$job.':'.$value, 10)->get();
    }
}

enum JobDispatchingTestQueueEnum: string
{
    case DEFAULT = 'default';
}

#[QueueAttribute('notifications')]
trait JobDispatchingTestQueueTrait
{
}
