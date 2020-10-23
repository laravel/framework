<?php

namespace Illuminate\Tests\Events;

use Illuminate\Events\Dispatcher;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class EventsDispatcherMutingTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testMutingAllListeners()
    {
        unset($_SERVER['__event.test']);
        $_SERVER['__event.test'] = 'big bang';

        $d = new Dispatcher;
        $d->listen('foo', function () {
            $_SERVER['__event.test'] .= 'first';
        });
        $d->listen('foo', function () {
            $_SERVER['__event.test'] .= 'second';
        });

        $d->mute('foo');

        $response = $d->dispatch('foo');

        $this->assertEquals([], $response);
        $this->assertSame('big bang', $_SERVER['__event.test']);

        // makes sure it is muted forever, not only for the first event firing.
        $d->dispatch('foo', ['bar']);

        $this->assertSame('big bang', $_SERVER['__event.test']);
    }

    public function testBasicWildcardEventMuting()
    {
        unset($_SERVER['__event.test']);

        $d = new Dispatcher;
        $d->listen('fo*', function ($foo) {
            $_SERVER['__event.test'] = $foo;
        });

        $response = $d->dispatch('foo', ['bar']);

        $d->mute('fo*');

        $_SERVER['__event.test'] = 'big bang';
        $response = $d->dispatch('foo', ['bar']);
        $this->assertSame('big bang', $_SERVER['__event.test']);
        $this->assertEquals([], $response);

        $d->listen('ba*', TestyListener::class);
        $d->mute('ba*', TestyListener::class);
        TestyListener::$string = '';
        $response = $d->dispatch('ba*', ['p1', 'p2']);
        $this->assertSame('', TestyListener::$string);
        $this->assertEquals([], $response);
    }

    public function testWildcardEventMuting()
    {
        unset($_SERVER['__event.test']);
        $_SERVER['__event.test'] = 'big bang';

        $d = new Dispatcher;
        $d->listen('foo', function ($foo) {
            $_SERVER['__event.test'] = $foo;
        });

        $d->listen('boo', function ($foo) {
            $_SERVER['__event.test'] = $foo;

            return 'I was Fired lol';
        });

        $d->mute('fo*');

        $response = $d->dispatch('foo', ['bar']);

        $this->assertEquals([], $response);
        $this->assertSame('big bang', $_SERVER['__event.test']);

        // makes sure other event/listener are not affected, accidentally.
        $response = $d->dispatch('boo', ['boo']);
        $this->assertEquals(['I was Fired lol'], $response);
        $this->assertSame('boo', $_SERVER['__event.test']);
    }

    public function testMutingForSpecificEventListener()
    {
        unset($_SERVER['__event.test']);
        $_SERVER['__event.test'] = 'big bang';

        $d = new Dispatcher;
        $d->listen('foo', TestyListener::class);

        $response = $d->dispatch('foo', ['p1', 'p2']);

        $this->assertEquals(['baz'], $response);
        $this->assertSame('handle', TestyListener::$string);

        $d->listen('foo', function ($p1) {
            $_SERVER['__event.test'] = $p1;
        });
        // now we mute the listener and fire again
        $d->mute('foo', TestyListener::class);

        TestyListener::$string = '(-_-) zzz';
        $response = $d->dispatch('foo', ['p1', '']);
        $this->assertSame('(-_-) zzz', TestyListener::$string);

        // other listeners still work just fine.
        $this->assertSame('p1', $_SERVER['__event.test']);
        $this->assertEquals([null], $response);

        $d->mute('foo', 'non_existing_listener');
        $d->dispatch('foo', ['bar']);
    }

    public function testItRemovesOnlyOneListenerEachTime()
    {
        $d = new Dispatcher;
        $d->listen('foo', TestyListener::class);
        $d->listen('foo', TestyListener::class);

        $d->mute('foo', TestyListener::class);

        TestyListener::$string = '';
        $response = $d->dispatch('foo', ['p1', 'p2']);

        // check only one listener was removed.
        $this->assertEquals('handle', TestyListener::$string);
        $this->assertEquals(['baz'], $response);
    }

    public function testWildcardMutingForSpecificEventListener()
    {
        unset($_SERVER['__event.test']);
        $_SERVER['__event.test'] = 'big bang';

        $d = new Dispatcher;
        $d->listen('foo', TestyListener::class);
        $d->listen('foo', TestyListener::class.'@method');
        $d->listen('foo', function ($p1) {
            $_SERVER['__event.test'] = $p1;
        });
        // now we mute the listener and fire again
        $d->mute('fo*', TestyListener::class);

        TestyListener::$string = '';
        $response = $d->dispatch('foo', ['p1', '']);
        // the second listener was fired but not the first.
        $this->assertSame('method', TestyListener::$string);

        // other listeners still work
        $this->assertSame('p1', $_SERVER['__event.test']);
        $this->assertEquals(['baz', null], $response);
    }

    public function testMutingClassAtMethodEventListener()
    {
        unset($_SERVER['__event.test']);
        $_SERVER['__event.test'] = 'big bang';
        TestyListener::$string = '';

        $d = new Dispatcher;
        $d->listen('foo', TestyListener::class.'@method');

        $response = $d->dispatch('foo', ['p1', 'p2']);

        $this->assertEquals(['baz'], $response);
        $this->assertSame('method', TestyListener::$string);

        // now we mute and fire again
        $d->mute('foo', TestyListener::class.'@method');
        TestyListener::$string = '(-_-) zzz';
        $response = $d->dispatch('foo', ['p1', 'p2']);
        $this->assertSame('(-_-) zzz', TestyListener::$string);

        $this->assertEquals([], $response);
    }
}

class TestyListener
{
    public static $string = '';

    public function handle($foo, $bar)
    {
        self::$string .= 'handle';

        return 'baz';
    }

    public function method($foo, $bar)
    {
        self::$string .= 'method';

        return 'baz';
    }
}
