<?php

namespace Illuminate\Tests\Events;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class EventsDispatcherTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBasicEventExecution()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('foo', function ($foo) {
            $_SERVER['__event.test'] = $foo;
        });
        $response = $d->dispatch('foo', ['bar']);

        $this->assertEquals([null], $response);
        $this->assertSame('bar', $_SERVER['__event.test']);
    }

    public function testHaltingEventExecution()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('foo', function ($foo) {
            $this->assertTrue(true);

            return 'here';
        });
        $d->listen('foo', function ($foo) {
            throw new Exception('should not be called');
        });

        $response = $d->dispatch('foo', ['bar'], true);
        $this->assertEquals('here', $response);

        $response = $d->until('foo', ['bar']);
        $this->assertEquals('here', $response);
    }

    public function testResponseWhenNoListenersAreSet()
    {
        $d = new Dispatcher;
        $response = $d->dispatch('foo');

        $this->assertEquals([], $response);

        $response = $d->dispatch('foo', [], true);
        $this->assertNull($response);
    }

    public function testReturningFalseStopsPropagation()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('foo', function ($foo) {
            return $foo;
        });

        $d->listen('foo', function ($foo) {
            $_SERVER['__event.test'] = $foo;

            return false;
        });

        $d->listen('foo', function ($foo) {
            throw new Exception('should not be called');
        });

        $response = $d->dispatch('foo', ['bar']);

        $this->assertSame('bar', $_SERVER['__event.test']);
        $this->assertEquals(['bar'], $response);
    }

    public function testReturningFalsyValuesContinuesPropagation()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('foo', function () {
            return 0;
        });
        $d->listen('foo', function () {
            return [];
        });
        $d->listen('foo', function () {
            return '';
        });
        $d->listen('foo', function () {
        });

        $response = $d->dispatch('foo', ['bar']);

        $this->assertEquals([0, [], '', null], $response);
    }

    public function testContainerResolutionOfEventHandlers()
    {
        $d = new Dispatcher($container = m::mock(Container::class));
        $container->shouldReceive('make')->once()->with('FooHandler')->andReturn($handler = m::mock(stdClass::class));
        $handler->shouldReceive('onFooEvent')->once()->with('foo', 'bar')->andReturn('baz');
        $d->listen('foo', 'FooHandler@onFooEvent');
        $response = $d->dispatch('foo', ['foo', 'bar']);

        $this->assertEquals(['baz'], $response);
    }

    public function testContainerResolutionOfEventHandlersWithDefaultMethods()
    {
        $d = new Dispatcher($container = m::mock(Container::class));
        $container->shouldReceive('make')->once()->with('FooHandler')->andReturn($handler = m::mock(stdClass::class));
        $handler->shouldReceive('handle')->once()->with('foo', 'bar');
        $d->listen('foo', 'FooHandler');
        $d->dispatch('foo', ['foo', 'bar']);
    }

    public function testQueuedEventsAreFired()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('update', function ($name) {
            $_SERVER['__event.test'] = $name;
        });
        $d->push('update', ['name' => 'taylor']);
        $d->listen('update', function ($name) {
            $_SERVER['__event.test'] .= '_'.$name;
        });

        $this->assertFalse(isset($_SERVER['__event.test']));
        $d->flush('update');
        $d->listen('update', function ($name) {
            $_SERVER['__event.test'] .= $name;
        });
        $this->assertSame('taylor_taylor', $_SERVER['__event.test']);
    }

    public function testQueuedEventsCanBeForgotten()
    {
        $_SERVER['__event.test'] = 'unset';
        $d = new Dispatcher;
        $d->push('update', ['name' => 'taylor']);
        $d->listen('update', function ($name) {
            $_SERVER['__event.test'] = $name;
        });

        $d->forgetPushed();
        $d->flush('update');
        $this->assertSame('unset', $_SERVER['__event.test']);
    }

    public function testMultiplePushedEventsWillGetFlushed()
    {
        $_SERVER['__event.test'] = '';
        $d = new Dispatcher;
        $d->push('update', ['name' => 'taylor ']);
        $d->push('update', ['name' => 'otwell']);
        $d->listen('update', function ($name) {
            $_SERVER['__event.test'] .= $name;
        });

        $d->flush('update');
        $this->assertSame('taylor otwell', $_SERVER['__event.test']);
    }

    public function testWildcardListeners()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('foo.bar', function () {
            $_SERVER['__event.test'] = 'regular';
        });
        $d->listen('foo.*', function () {
            $_SERVER['__event.test'] = 'wildcard';
        });
        $d->listen('bar.*', function () {
            $_SERVER['__event.test'] = 'nope';
        });

        $response = $d->dispatch('foo.bar');

        $this->assertEquals([null, null], $response);
        $this->assertSame('wildcard', $_SERVER['__event.test']);
    }

    public function testWildcardListenersWithResponses()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('foo.bar', function () {
            return 'regular';
        });
        $d->listen('foo.*', function () {
            return 'wildcard';
        });
        $d->listen('bar.*', function () {
            return 'nope';
        });

        $response = $d->dispatch('foo.bar');

        $this->assertEquals(['regular', 'wildcard'], $response);
    }

    public function testWildcardListenersCacheFlushing()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('foo.*', function () {
            $_SERVER['__event.test'] = 'cached_wildcard';
        });
        $d->dispatch('foo.bar');
        $this->assertSame('cached_wildcard', $_SERVER['__event.test']);

        $d->listen('foo.*', function () {
            $_SERVER['__event.test'] = 'new_wildcard';
        });
        $d->dispatch('foo.bar');
        $this->assertSame('new_wildcard', $_SERVER['__event.test']);
    }

    public function testListenersCanBeRemoved()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('foo', function () {
            $_SERVER['__event.test'] = 'foo';
        });
        $d->forget('foo');
        $d->dispatch('foo');

        $this->assertFalse(isset($_SERVER['__event.test']));
    }

    public function testWildcardListenersCanBeRemoved()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('foo.*', function () {
            $_SERVER['__event.test'] = 'foo';
        });
        $d->forget('foo.*');
        $d->dispatch('foo.bar');

        $this->assertFalse(isset($_SERVER['__event.test']));
    }

    public function testWildcardCacheIsClearedWhenListenersAreRemoved()
    {
        unset($_SERVER['__event.test']);

        $d = new Dispatcher;
        $d->listen('foo*', function () {
            $_SERVER['__event.test'] = 'foo';
        });
        $d->dispatch('foo');

        $this->assertSame('foo', $_SERVER['__event.test']);

        unset($_SERVER['__event.test']);

        $d->forget('foo*');
        $d->dispatch('foo');

        $this->assertFalse(isset($_SERVER['__event.test']));
    }

    public function testListenersCanBeFound()
    {
        $d = new Dispatcher;
        $this->assertFalse($d->hasListeners('foo'));

        $d->listen('foo', function () {
            //
        });
        $this->assertTrue($d->hasListeners('foo'));
    }

    public function testWildcardListenersCanBeFound()
    {
        $d = new Dispatcher;
        $this->assertFalse($d->hasListeners('foo.*'));

        $d->listen('foo.*', function () {
            //
        });
        $this->assertTrue($d->hasListeners('foo.*'));
        $this->assertTrue($d->hasListeners('foo.bar'));
    }

    public function testEventPassedFirstToWildcards()
    {
        $d = new Dispatcher;
        $d->listen('foo.*', function ($event, $data) {
            $this->assertSame('foo.bar', $event);
            $this->assertEquals(['first', 'second'], $data);
        });
        $d->dispatch('foo.bar', ['first', 'second']);

        $d = new Dispatcher;
        $d->listen('foo.bar', function ($first, $second) {
            $this->assertSame('first', $first);
            $this->assertSame('second', $second);
        });
        $d->dispatch('foo.bar', ['first', 'second']);
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

    public function testClassesWork()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen(ExampleEvent::class, function () {
            $_SERVER['__event.test'] = 'baz';
        });
        $d->dispatch(new ExampleEvent);

        $this->assertSame('baz', $_SERVER['__event.test']);
    }

    public function testEventClassesArePayload()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen(ExampleEvent::class, function ($payload) {
            $_SERVER['__event.test'] = $payload;
        });
        $d->dispatch($e = new ExampleEvent, ['foo']);

        $this->assertSame($e, $_SERVER['__event.test']);
    }

    public function testInterfacesWork()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen(SomeEventInterface::class, function () {
            $_SERVER['__event.test'] = 'bar';
        });
        $d->dispatch(new AnotherEvent);

        $this->assertSame('bar', $_SERVER['__event.test']);
    }

    public function testBothClassesAndInterfacesWork()
    {
        unset($_SERVER['__event.test']);
        $_SERVER['__event.test'] = [];
        $d = new Dispatcher;
        $d->listen(AnotherEvent::class, function ($p) {
            $_SERVER['__event.test'][] = $p;
            $_SERVER['__event.test1'] = 'fooo';
        });
        $d->listen(SomeEventInterface::class, function ($p) {
            $_SERVER['__event.test'][] = $p;
            $_SERVER['__event.test2'] = 'baar';
        });
        $d->dispatch($e = new AnotherEvent, ['foo']);

        $this->assertSame($e, $_SERVER['__event.test'][0]);
        $this->assertSame($e, $_SERVER['__event.test'][1]);
        $this->assertSame('fooo', $_SERVER['__event.test1']);
        $this->assertSame('baar', $_SERVER['__event.test2']);

        unset($_SERVER['__event.test1']);
        unset($_SERVER['__event.test2']);
    }

    public function testShouldBroadcastSuccess()
    {
        $d = m::mock(Dispatcher::class);

        $d->makePartial()->shouldAllowMockingProtectedMethods();

        $event = new BroadcastEvent;

        $this->assertTrue($d->shouldBroadcast([$event]));

        $event = new AlwaysBroadcastEvent;

        $this->assertTrue($d->shouldBroadcast([$event]));
    }

    public function testShouldBroadcastAsQueuedAndCallNormalListeners()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher($container = m::mock(Container::class));
        $broadcast = m::mock(BroadcastFactory::class);
        $broadcast->shouldReceive('queue')->once();
        $container->shouldReceive('make')->once()->with(BroadcastFactory::class)->andReturn($broadcast);

        $d->listen(AlwaysBroadcastEvent::class, function ($payload) {
            $_SERVER['__event.test'] = $payload;
        });

        $d->dispatch($e = new AlwaysBroadcastEvent);

        $this->assertSame($e, $_SERVER['__event.test']);
    }

    public function testShouldBroadcastFail()
    {
        $d = m::mock(Dispatcher::class);

        $d->makePartial()->shouldAllowMockingProtectedMethods();

        $event = new BroadcastFalseCondition;

        $this->assertFalse($d->shouldBroadcast([$event]));

        $event = new ExampleEvent;

        $this->assertFalse($d->shouldBroadcast([$event]));
    }

    public function testEventSubscribers()
    {
        $d = new Dispatcher($container = m::mock(Container::class));
        $subs = m::mock(ExampleSubscriber::class);
        $subs->shouldReceive('subscribe')->once()->with($d);
        $container->shouldReceive('make')->once()->with(ExampleSubscriber::class)->andReturn($subs);

        $d->subscribe(ExampleSubscriber::class);
        $this->assertTrue(true);
    }

    public function testEventSubscribeCanAcceptObject()
    {
        $d = new Dispatcher();
        $subs = m::mock(ExampleSubscriber::class);
        $subs->shouldReceive('subscribe')->once()->with($d);

        $d->subscribe($subs);
        $this->assertTrue(true);
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

class TestDispatcherQueuedHandlerCustomQueue implements ShouldQueue
{
    public function handle()
    {
        //
    }

    public function queue($queue, $handler, array $payload)
    {
        $queue->push($handler, $payload);
    }
}

class ExampleEvent
{
    //
}

class ExampleSubscriber
{
    public function subscribe($e)
    {
        //
    }
}

interface SomeEventInterface
{
    //
}

class AnotherEvent implements SomeEventInterface
{
    //
}

class BroadcastEvent implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return ['test-channel'];
    }

    public function broadcastWhen()
    {
        return true;
    }
}

class AlwaysBroadcastEvent implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return ['test-channel'];
    }
}

class BroadcastFalseCondition extends BroadcastEvent
{
    public function broadcastWhen()
    {
        return false;
    }
}
