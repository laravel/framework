<?php

namespace Illuminate\Tests\Events;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueuedEventsTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testQueuedEventHandlersAreQueued()
    {
        $d = new Dispatcher;
        $queue = m::mock(Queue::class);

        $queue->shouldReceive('connection')->once()->with(null)->andReturnSelf();

        $queue->shouldReceive('pushOn')->once()->with(null, m::type(CallQueuedListener::class));

        $d->setQueueResolver(function () use ($queue) {
            return $queue;
        });

        $d->listen('some.event', TestDispatcherQueuedHandler::class.'@someMethod');
        $d->dispatch('some.event', ['foo', 'bar']);
    }

    public function testCustomizedQueuedEventHandlersAreQueued()
    {
        $d = new Dispatcher;

        $fakeQueue = new QueueFake(new Container());

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherConnectionQueuedHandler::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushedOn('my_queue', CallQueuedListener::class);
    }

    public function testQueueIsSetByGetQueue()
    {
        $d = new Dispatcher;

        $fakeQueue = new QueueFake(new Container());

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherGetQueue::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushedOn('some_other_queue', CallQueuedListener::class);
    }

    public function testQueueOptionsWereTakenFromConstructor()
    {
        $dispatcher = new Dispatcher();

        $fakeQueue = new QueueFake(new Container());

        $dispatcher->setQueueResolver(function () use ($fakeQueue): QueueFake {
            return $fakeQueue;
        });

        $dispatcher->listen('some.event', TestEventListenerWithQueueOptionsDeclaredInTheConstructor::class);
        $dispatcher->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushedOn('constructor_queue', CallQueuedListener::class);
        $fakeQueue->assertQueuedListenerConnectionWas('constructor_connection');
        $fakeQueue->assertQueuedListenerDelayWas(3600 * 200);

        $dispatcher->listen('some.event', TestEventListenerWithQueueOptionsDeclaredWithinProperties::class);
        $dispatcher->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushedOn('property_queue', CallQueuedListener::class);
        $fakeQueue->assertQueuedListenerConnectionWas('property_connection');
        $fakeQueue->assertQueuedListenerDelayWas(3600 * 80);
    }
}

class TestDispatcherQueuedHandler implements ShouldQueue
{
    public function handle()
    {
        //
    }
}

class TestDispatcherConnectionQueuedHandler implements ShouldQueue
{
    public $connection = 'redis';

    public $delay = 10;

    public $queue = 'my_queue';

    public function handle()
    {
        //
    }
}

class TestDispatcherGetQueue implements ShouldQueue
{
    public $queue = 'my_queue';

    public function handle()
    {
        //
    }

    public function viaQueue()
    {
        return 'some_other_queue';
    }
}

class TestEventListenerWithQueueOptionsDeclaredInTheConstructor implements ShouldQueue
{
    public $queue = 'property_queue';
    public $connection = 'property_connection';
    public $delay = 3600 * 80;

    public function __construct()
    {
        $this->queue = 'constructor_queue';
        $this->connection = 'constructor_connection';
        $this->delay = 3600 * 200;
    }

    public function handle()
    {
        //
    }
}

class TestEventListenerWithQueueOptionsDeclaredWithinProperties implements ShouldQueue
{
    public $queue = 'property_queue';
    public $connection = 'property_connection';
    public $delay = 3600 * 80;

    public function handle()
    {
        //
    }
}
