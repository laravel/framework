<?php

namespace Illuminate\Tests\Events;

use Error;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
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

        // we can still add listeners after the event has fired
        $d->listen('foo', function ($foo) {
            $_SERVER['__event.test'] .= $foo;
        });

        $d->dispatch('foo', ['bar']);
        $this->assertSame('barbar', $_SERVER['__event.test']);
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
        $this->assertSame('here', $response);

        $response = $d->until('foo', ['bar']);
        $this->assertSame('here', $response);
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
        $container->shouldReceive('make')->once()->with(TestEventListener::class)->andReturn(new TestEventListener);
        $d->listen('foo', TestEventListener::class.'@onFooEvent');
        $response = $d->dispatch('foo', ['foo', 'bar']);

        $this->assertEquals(['baz'], $response);
    }

    public function testContainerResolutionOfEventHandlersWithDefaultMethods()
    {
        $d = new Dispatcher(new Container);
        $d->listen('foo', TestEventListener::class);
        $response = $d->dispatch('foo', ['foo', 'bar']);
        $this->assertEquals(['baz'], $response);
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

    public function testPushMethodCanAcceptObjectAsPayload()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->push(ExampleEvent::class, $e = new ExampleEvent);
        $d->listen(ExampleEvent::class, function ($payload) {
            $_SERVER['__event.test'] = $payload;
        });

        $d->flush(ExampleEvent::class);

        $this->assertSame($e, $_SERVER['__event.test']);
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

    public function testHasWildcardListeners()
    {
        $d = new Dispatcher;
        $d->listen('foo', 'listener1');
        $this->assertFalse($d->hasWildcardListeners('foo'));

        $d->listen('foo*', 'listener1');
        $this->assertTrue($d->hasWildcardListeners('foo'));
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

    public function testClassesWorkWithAnonymousListeners()
    {
        unset($_SERVER['__event.test']);
        $d = new Dispatcher;
        $d->listen(function (ExampleEvent $event) {
            $_SERVER['__event.test'] = 'qux';
        });
        $d->dispatch(new ExampleEvent);

        $this->assertSame('qux', $_SERVER['__event.test']);
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

        unset($_SERVER['__event.test1'], $_SERVER['__event.test2']);
    }

    public function testNestedEvent()
    {
        $_SERVER['__event.test'] = [];
        $d = new Dispatcher;

        $d->listen('event', function () use ($d) {
            $d->listen('event', function () {
                $_SERVER['__event.test'][] = 'fired 1';
            });
            $d->listen('event', function () {
                $_SERVER['__event.test'][] = 'fired 2';
            });
        });

        $d->dispatch('event');
        $this->assertSame([], $_SERVER['__event.test']);
        $d->dispatch('event');
        $this->assertEquals(['fired 1', 'fired 2'], $_SERVER['__event.test']);
    }

    public function testDuplicateListenersWillFire()
    {
        $d = new Dispatcher;
        $d->listen('event', TestListener::class);
        $d->listen('event', TestListener::class);
        $d->listen('event', TestListener::class.'@handle');
        $d->listen('event', TestListener::class.'@handle');
        $d->dispatch('event');

        $this->assertEquals(4, TestListener::$counter);
        TestListener::$counter = 0;
    }

    public function testGetListeners()
    {
        $d = new Dispatcher;
        $d->listen(ExampleEvent::class, 'Listener1');
        $d->listen(ExampleEvent::class, 'Listener2');
        $listeners = $d->getListeners(ExampleEvent::class);
        $this->assertCount(2, $listeners);

        $d->listen(ExampleEvent::class, 'Listener3');
        $listeners = $d->getListeners(ExampleEvent::class);
        $this->assertCount(3, $listeners);
    }

    public function testListenersObjectsCreationOrder()
    {
        $_SERVER['__event.test'] = [];
        $d = new Dispatcher;
        $d->listen(TestEvent::class, TestListener1::class);
        $d->listen(TestEvent::class, TestListener2::class);
        $d->listen(TestEvent::class, TestListener3::class);

        // Attaching events does not make any objects.
        $this->assertEquals([], $_SERVER['__event.test']);

        $d->dispatch(TestEvent::class);

        // Dispatching event does not make an object of the event class.
        $this->assertEquals([
            'cons-1',
            'handle-1',
            'cons-2',
            'handle-2',
            'cons-3',
            'handle-3',
        ], $_SERVER['__event.test']);

        $d->dispatch(TestEvent::class);

        // Event Objects are re-resolved on each dispatch. (No memoization)
        $this->assertEquals([
            'cons-1',
            'handle-1',
            'cons-2',
            'handle-2',
            'cons-3',
            'handle-3',
            'cons-1',
            'handle-1',
            'cons-2',
            'handle-2',
            'cons-3',
            'handle-3',
        ], $_SERVER['__event.test']);

        unset($_SERVER['__event.test']);
    }

    public function test_Listener_object_creation_is_lazy()
    {
        $d = new Dispatcher;
        $d->listen(TestEvent::class, TestListener1::class);
        $d->listen(TestEvent::class, TestListener2Falser::class);
        $d->listen(TestEvent::class, TestListener3::class);
        $d->listen(ExampleEvent::class, TestListener2::class);

        $_SERVER['__event.test'] = [];
        $d->dispatch(ExampleEvent::class);

        // It only resolves relevant listeners not all.
        $this->assertEquals(['cons-2', 'handle-2'], $_SERVER['__event.test']);

        $_SERVER['__event.test'] = [];
        $d->dispatch(TestEvent::class);

        $this->assertEquals([
            'cons-1',
            'handle-1',
            'cons-2-falser',
            'handle-2-falser',
        ], $_SERVER['__event.test']);

        unset($_SERVER['__event.test']);

        $d = new Dispatcher;
        $d->listen(TestEvent::class, TestListener1::class);
        $d->listen(TestEvent::class, TestListener2Falser::class);
        $d->listen(TestEvent::class, TestListener3::class);

        $_SERVER['__event.test'] = [];
        $d->dispatch(TestEvent::class, halt: true);

        $this->assertEquals([
            'cons-1',
            'handle-1',
        ], $_SERVER['__event.test']);

        unset($_SERVER['__event.test']);
    }

    public function testInvokeIsCalled()
    {
        // Only "handle" is called when both "handle" and "__invoke" exist on listener.
        $_SERVER['__event.test'] = [];
        $d = new Dispatcher;
        $d->listen('myEvent', TestListenerInvokeyHandler::class);
        $d->dispatch('myEvent');
        $this->assertEquals(['__construct', 'handle'], $_SERVER['__event.test']);

        // "__invoke" is called when there is no handle.
        $_SERVER['__event.test'] = [];
        $d = new Dispatcher;
        $d->listen('myEvent', TestListenerInvokey::class);
        $d->listen('myEvent', TestListenerInvokeyHandler::class);
        $d->dispatch('myEvent', 'somePayload');
        $this->assertEquals(['__construct', '__invoke_somePayload'], $_SERVER['__event.test']);

        // It falls back to __invoke if the referenced method is not found.
        $_SERVER['__event.test'] = [];
        $d = new Dispatcher;
        $d->listen('myEvent', [TestListenerInvokey::class, 'someAbsentMethod']);
        $d->dispatch('myEvent', 'somePayload');
        $this->assertEquals(['__construct', '__invoke_somePayload'], $_SERVER['__event.test']);

        // It throws an "Error" when there is no method to be called.
        $d = new Dispatcher;
        $d->listen('myEvent', TestListenerLean::class);
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Call to undefined method '.TestListenerLean::class.'::__invoke()');
        $d->dispatch('myEvent', 'somePayload');

        unset($_SERVER['__event.test']);
    }
}

class TestListenerLean
{
    //
}

class TestListenerInvokeyHandler
{
    public function __construct()
    {
        $_SERVER['__event.test'][] = '__construct';
    }

    public function __invoke()
    {
        $_SERVER['__event.test'][] = '__invoke';
    }

    public function handle()
    {
        $_SERVER['__event.test'][] = 'handle';
    }
}

class TestListenerInvokey
{
    public function __construct()
    {
        $_SERVER['__event.test'][] = '__construct';
    }

    public function __invoke($payload)
    {
        $_SERVER['__event.test'][] = '__invoke_'.$payload;

        return false;
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

class TestEventListener
{
    public function handle($foo, $bar)
    {
        return 'baz';
    }

    public function onFooEvent($foo, $bar)
    {
        return 'baz';
    }
}

class TestListener
{
    public static $counter = 0;

    public function handle()
    {
        self::$counter++;
    }
}

class TestEvent
{
    public function __construct()
    {
        $_SERVER['__event.test'][] = 'cons-event-1';
    }
}

class TestListener1
{
    public function __construct()
    {
        $_SERVER['__event.test'][] = 'cons-1';
    }

    public function handle()
    {
        $_SERVER['__event.test'][] = 'handle-1';

        return 'resp-1';
    }
}

class TestListener2
{
    public function __construct()
    {
        $_SERVER['__event.test'][] = 'cons-2';
    }

    public function handle()
    {
        $_SERVER['__event.test'][] = 'handle-2';

        return 'resp-2';
    }
}

class TestListener2Falser
{
    public function __construct()
    {
        $_SERVER['__event.test'][] = 'cons-2-falser';
    }

    public function handle()
    {
        $_SERVER['__event.test'][] = 'handle-2-falser';

        return false;
    }
}

class TestListener3
{
    public function __construct()
    {
        $_SERVER['__event.test'][] = 'cons-3';
    }

    public function handle()
    {
        $_SERVER['__event.test'][] = 'handle-3';
    }
}
