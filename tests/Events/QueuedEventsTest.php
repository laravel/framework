<?php

namespace Illuminate\Tests\Events;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcherContract;
use Illuminate\Bus\Dispatcher as Bus;

class QueuedEventsTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testQueuedEventHandlersAreQueued()
    {
        $bus = m::mock(BusDispatcherContract::class);
        $bus->shouldReceive('dispatch')->once()->with(m::type(CallQueuedListener::class));

        $container = new Container();
        $container->bind(BusDispatcherContract::class, fn () => $bus);

        $d = new Dispatcher($container);

        $d->listen('some.event', TestDispatcherQueuedHandler::class.'@someMethod');
        $d->dispatch('some.event', ['foo', 'bar']);
    }

    public function testCustomizedQueuedEventHandlersAreQueued()
    {
        $container = new Container();

        $fakeQueue = new QueueFake($container);
        $bus = new Bus($container, fn () => $fakeQueue);
        $container->bind(BusDispatcherContract::class, fn () => $bus);

        $d = new Dispatcher($container);

        $d->listen('some.event', TestDispatcherConnectionQueuedHandler::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushedOn('my_queue', CallQueuedListener::class);
    }

    public function testQueueIsSetByGetQueue()
    {
        $container = new Container();

        $fakeQueue = new QueueFake($container);
        $bus = new Bus($container, fn () => $fakeQueue);
        $container->bind(BusDispatcherContract::class, fn () => $bus);

        $d = new Dispatcher($container);

        $d->listen('some.event', TestDispatcherGetQueue::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushedOn('some_other_queue', CallQueuedListener::class);
    }

    public function testQueueIsSetByGetConnection()
    {
        $container = new Container();

        $fakeQueue = new QueueFake($container);
        $bus = new Bus($container, fn () => $fakeQueue);
        $container->bind(BusDispatcherContract::class, fn () => $bus);

        $d = new Dispatcher($container);

        $d->listen('some.event', TestDispatcherGetConnection::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);
    }

    public function testQueuePropagateRetryUntilAndMaxExceptions()
    {
        $container = new Container();

        $fakeQueue = new QueueFake($container);
        $bus = new Bus($container, fn () => $fakeQueue);
        $container->bind(BusDispatcherContract::class, fn () => $bus);

        $d = new Dispatcher($container);

        $d->listen('some.event', TestDispatcherOptions::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class, function ($job) {
            return $job->maxExceptions === 1 && $job->retryUntil !== null;
        });
    }

    public function testQueuePropagateMiddleware()
    {
        $container = new Container();

        $fakeQueue = new QueueFake($container);
        $bus = new Bus($container, fn () => $fakeQueue);
        $container->bind(BusDispatcherContract::class, fn () => $bus);

        $d = new Dispatcher($container);

        $d->listen('some.event', TestDispatcherMiddleware::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class, function ($job) {
            return count($job->middleware) === 1
                && $job->middleware[0] instanceof TestMiddleware
                && $job->middleware[0]->a === 'foo'
                && $job->middleware[0]->b === 'bar';
        });
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

class TestDispatcherGetConnection implements ShouldQueue
{
    public $connection = 'my_connection';

    public function handle()
    {
        //
    }

    public function viaConnection()
    {
        return 'some_other_connection';
    }
}

class TestDispatcherOptions implements ShouldQueue
{
    public $maxExceptions = 1;

    public function retryUntil()
    {
        return now()->addHour(1);
    }

    public function handle()
    {
        //
    }
}

class TestDispatcherMiddleware implements ShouldQueue
{
    public function middleware($a, $b)
    {
        return [new TestMiddleware($a, $b)];
    }

    public function handle($a, $b)
    {
        //
    }
}

class TestMiddleware
{
    public $a;
    public $b;

    public function __construct($a, $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

    public function handle($job, $next)
    {
        $next($job);
    }
}
