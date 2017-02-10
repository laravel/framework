<?php

namespace Illuminate\Tests\Events;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Events\Dispatcher;

class EventsDispatcherTest extends TestCase
{
    public function tearDown()
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
        $d->fire('foo', ['bar']);
        $this->assertEquals('bar', $_SERVER['__event.test']);
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
        $d->until('foo', ['bar']);
    }

    public function testContainerResolutionOfEventHandlers()
    {
        $d = new Dispatcher($container = m::mock('Illuminate\Container\Container'));
        $container->shouldReceive('make')->once()->with('FooHandler')->andReturn($handler = m::mock('StdClass'));
        $handler->shouldReceive('onFooEvent')->once()->with('foo', 'bar');
        $d->listen('foo', 'FooHandler@onFooEvent');
        $d->fire('foo', ['foo', 'bar']);
    }

    public function testContainerResolutionOfEventHandlersWithDefaultMethods()
    {
        $d = new Dispatcher($container = m::mock('Illuminate\Container\Container'));
        $container->shouldReceive('make')->once()->with('FooHandler')->andReturn($handler = m::mock('StdClass'));
        $handler->shouldReceive('handle')->once()->with('foo', 'bar');
        $d->listen('foo', 'FooHandler');
        $d->fire('foo', ['foo', 'bar']);
    }

    public function testQueuedEventsAreFired()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->push('update', ['name' => 'taylor']);
        $d->listen('update', function ($name) {
            $_SERVER['__event.test'] = $name;
        });

        $this->assertFalse(isset($_SERVER['__event.test']));
        $d->flush('update');
        $this->assertEquals('taylor', $_SERVER['__event.test']);
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
        $this->assertEquals('unset', $_SERVER['__event.test']);
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
        $d->fire('foo.bar');

        $this->assertEquals('wildcard', $_SERVER['__event.test']);
    }

    public function testListenersCanBeRemoved()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('foo', function () {
            $_SERVER['__event.test'] = 'foo';
        });
        $d->forget('foo');
        $d->fire('foo');

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
        $d->fire('foo.bar');

        $this->assertFalse(isset($_SERVER['__event.test']));
    }

    public function testListenersCanBeFound()
    {
        $d = new Dispatcher;
        $this->assertFalse($d->hasListeners('foo'));

        $d->listen('foo', function () {
        });
        $this->assertTrue($d->hasListeners('foo'));
    }

    public function testWildcardListenersCanBeFound()
    {
        $d = new Dispatcher;
        $this->assertFalse($d->hasListeners('foo.*'));

        $d->listen('foo.*', function () {
        });
        $this->assertTrue($d->hasListeners('foo.*'));
    }

    public function testEventPassedFirstToWildcards()
    {
        $d = new Dispatcher;
        $d->listen('foo.*', function ($event, $data) use ($d) {
            $this->assertEquals('foo.bar', $event);
            $this->assertEquals(['first', 'second'], $data);
        });
        $d->fire('foo.bar', ['first', 'second']);

        $d = new Dispatcher;
        $d->listen('foo.bar', function ($first, $second) use ($d) {
            $this->assertEquals('first', $first);
            $this->assertEquals('second', $second);
        });
        $d->fire('foo.bar', ['first', 'second']);
    }

    public function testQueuedEventHandlersAreQueued()
    {
        $d = new Dispatcher;
        $queue = m::mock('Illuminate\Contracts\Queue\Queue');

        $queue->shouldReceive('connection')->once()->with(null)->andReturnSelf();

        $queue->shouldReceive('pushOn')->once()->with(null, m::type('Illuminate\Events\CallQueuedListener'));

        $d->setQueueResolver(function () use ($queue) {
            return $queue;
        });

        $d->listen('some.event', 'Illuminate\Tests\Events\TestDispatcherQueuedHandler@someMethod');
        $d->fire('some.event', ['foo', 'bar']);
    }

    public function testClassesWork()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('Illuminate\Tests\Events\ExampleEvent', function () {
            $_SERVER['__event.test'] = 'baz';
        });
        $d->fire(new ExampleEvent);

        $this->assertSame('baz', $_SERVER['__event.test']);
    }

    public function testInterfacesWork()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('Illuminate\Tests\Events\SomeEventInterface', function () {
            $_SERVER['__event.test'] = 'bar';
        });
        $d->fire(new AnotherEvent);

        $this->assertSame('bar', $_SERVER['__event.test']);
    }

    public function testBothClassesAndInterfacesWork()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen('Illuminate\Tests\Events\AnotherEvent', function () {
            $_SERVER['__event.test1'] = 'fooo';
        });
        $d->listen('Illuminate\Tests\Events\SomeEventInterface', function () {
            $_SERVER['__event.test2'] = 'baar';
        });
        $d->fire(new AnotherEvent);

        $this->assertSame('fooo', $_SERVER['__event.test1']);
        $this->assertSame('baar', $_SERVER['__event.test2']);
    }
}

class TestDispatcherQueuedHandler implements \Illuminate\Contracts\Queue\ShouldQueue
{
    public function handle()
    {
    }
}

class TestDispatcherQueuedHandlerCustomQueue implements \Illuminate\Contracts\Queue\ShouldQueue
{
    public function handle()
    {
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

interface SomeEventInterface
{
    //
}

class AnotherEvent implements SomeEventInterface
{
    //
}
