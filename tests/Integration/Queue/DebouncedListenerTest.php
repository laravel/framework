<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('cache')]
#[WithMigration('queue')]
class DebouncedListenerTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('cache.default', 'database');
    }

    public function testSupersededDebouncedListenerIsSkipped()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['sync', 'beanstalkd']);

        DebouncedTestListener::$handledValues = [];

        Event::listen(DebouncedTestEvent::class, DebouncedTestListener::class);

        Event::dispatch(new DebouncedTestEvent('entity-1', 'first'));
        Event::dispatch(new DebouncedTestEvent('entity-1', 'second'));

        $this->travelTo(Carbon::now()->addSeconds(31));
        $this->runQueueWorkerCommand(['--once' => true], 2);

        $this->assertSame(['second'], DebouncedTestListener::$handledValues);
    }

    public function testMaxDebounceWaitStartsOverAfterTheDebouncedListenerRuns()
    {
        $this->markTestSkippedWhenUsingQueueDrivers(['sync', 'beanstalkd']);

        DebouncedWithMaxWaitListener::$handledValues = [];

        Event::listen(DebouncedTestEvent::class, DebouncedWithMaxWaitListener::class);

        Event::dispatch(new DebouncedTestEvent('entity-1', 'first'));

        $this->travelTo(Carbon::now()->addSeconds(31));
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertSame(['first'], DebouncedWithMaxWaitListener::$handledValues);

        // Dispatch again at t=92, long after the first listener stopped waiting.
        $this->travelTo(Carbon::now()->addSeconds(61));

        Event::dispatch(new DebouncedTestEvent('entity-1', 'second'));

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertSame(['first'], DebouncedWithMaxWaitListener::$handledValues);

        $this->travelTo(Carbon::now()->addSeconds(31));
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertSame(['first', 'second'], DebouncedWithMaxWaitListener::$handledValues);
    }
}

class DebouncedTestEvent
{
    public function __construct(public string $entityId, public string $value)
    {
    }
}

#[DebounceFor(30)]
class DebouncedTestListener implements ShouldQueue
{
    public static $handledValues = [];

    public function debounceId(DebouncedTestEvent $event): string
    {
        return $event->entityId;
    }

    public function handle(DebouncedTestEvent $event)
    {
        static::$handledValues[] = $event->value;
    }
}

#[DebounceFor(30, maxWait: 60)]
class DebouncedWithMaxWaitListener implements ShouldQueue
{
    public static $handledValues = [];

    public function debounceId(DebouncedTestEvent $event): string
    {
        return $event->entityId;
    }

    public function handle(DebouncedTestEvent $event)
    {
        static::$handledValues[] = $event->value;
    }
}
