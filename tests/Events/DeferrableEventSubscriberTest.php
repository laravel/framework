<?php

namespace Illuminate\Tests\Events;

use Illuminate\Contracts\Events\DeferrableSubscriber;
use Illuminate\Events\Dispatcher;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DeferrableEventSubscriberTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testDeferrableSubscriber()
    {
        $d = new Dispatcher();
        $d->subscribe(LazySubscriber::class);
        $d->subscribe(LazySubscriber2::class);

        // make sure the "subscribe" method is NOT called on the "lazySubscriber" after subscription.
        $this->assertEquals(0, LazySubscriber::$counter);
        $this->assertEquals(0, LazySubscriber2::$counter);

        $this->assertTrue($d->hasListeners('myEvent1'));
        $this->assertTrue($d->hasListeners('myEvent2'));

        $d->dispatch('myEvent3');
        // we only subscribe relevant subscriber for "myEvent3", not all of them.
        $this->assertEquals(1, LazySubscriber::$counter);
        $this->assertEquals(0, LazySubscriber2::$counter);

        $d->dispatch('myEvent1');
        $this->assertSame('L1_', LazySubscriber::$string);
        $this->assertSame('L1_', LazySubscriber2::$string);

        // make sure the "subscribe" method IS called on the "lazySubscriber" after event happens
        $this->assertEquals(1, LazySubscriber::$counter);
        $this->assertEquals(1, LazySubscriber2::$counter);

        // Firing an event twice does not cause an strange behaviour.
        $d->dispatch('myEvent1');
        $this->assertSame('L1_L1_', LazySubscriber::$string);

        // Firing the second event
        $d->dispatch('myEvent2');
        $this->assertSame('L1_L1_L2_', LazySubscriber::$string);
        $this->assertSame('L1_L1_L2_', LazySubscriber2::$string);

        // Object-events are also ok.
        $d->dispatch(new ClassyEvent());
        $this->assertSame('L1_L1_L2_', LazySubscriber::$string);
        $this->assertSame('L1_L1_L2_L2_', LazySubscriber2::$string);

        // since we internally empty out the waitedEvents property after firing each event
        // we make sure the optimization does not affect the hasListeners functionality.
        $this->assertTrue($d->hasListeners('myEvent1'));
        $this->assertTrue($d->hasListeners('myEvent2'));

        // makes sure the subscribe method is called only once.
        $this->assertEquals(1, LazySubscriber::$counter);
        $this->assertEquals(1, LazySubscriber::$counter);
    }
}

class LazySubscriber implements DeferrableSubscriber
{
    public static $string = '';

    public static $counter = 0;

    public function subscribe()
    {
        self::$counter++;

        return [
            'myEvent1' => [
                self::class.'@listener1',
            ],
            'myEvent2' => [
                self::class.'@listener2',
            ],
            'myEvent3' => [
                self::class.'@listener3',
            ],
        ];
    }

    public function listener1()
    {
        self::$string .= 'L1_';
    }

    public function listener2()
    {
        self::$string .= 'L2_';
    }

    public function listener3()
    {
        //
    }

    public function listensTo()
    {
        return ['myEvent1', 'myEvent2', 'myEvent3'];
    }
}

class LazySubscriber2 implements DeferrableSubscriber
{
    public static $string = '';

    public static $counter = 0;

    public function subscribe()
    {
        self::$counter++;

        return [
            'myEvent1' => [
                self::class.'@listener1',
            ],
            'myEvent2' => [
                self::class.'@listener2',
            ],
            ClassyEvent::class => [
                self::class.'@listener2',
            ],
        ];
    }

    public function listener1()
    {
        self::$string .= 'L1_';
    }

    public function listener2()
    {
        self::$string .= 'L2_';
    }

    public function listensTo()
    {
        return ['myEvent1', 'myEvent2', ClassyEvent::class];
    }
}

class ClassyEvent
{
    //
}
