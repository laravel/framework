<?php

namespace Illuminate\Tests\Events;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Mockery;
use PHPUnit\Framework\TestCase;

class EventsSubscriberTest extends TestCase
{
    public function testEventSubscribers()
    {
        $this->expectNotToPerformAssertions();

        $container = Mockery::mock(Container::class);
        $d = new Dispatcher($container);
        $subs = Mockery::mock(ExampleSubscriber::class);
        $subs->expects('subscribe')->with($d);
        $container->expects('make')->with(ExampleSubscriber::class)->andReturn($subs);

        $d->subscribe(ExampleSubscriber::class);
    }

    public function testEventSubscribeCanAcceptObject()
    {
        $this->expectNotToPerformAssertions();

        $d = new Dispatcher;
        $subs = Mockery::mock(ExampleSubscriber::class);
        $subs->expects('subscribe')->with($d);

        $d->subscribe($subs);
    }

    public function testEventSubscribeCanReturnMappings()
    {
        $d = new Dispatcher;
        $d->subscribe(DeclarativeSubscriber::class);

        $d->dispatch('myEvent1');
        $this->assertSame('L1_L2_', DeclarativeSubscriber::$string);

        $d->dispatch('myEvent2');
        $this->assertSame('L1_L2_L3', DeclarativeSubscriber::$string);
    }
}

class ExampleSubscriber
{
    public function subscribe($e)
    {
        // There would be no error if a non-array is returned.
        return '(O_o)';
    }
}

class DeclarativeSubscriber
{
    public static $string = '';

    public function subscribe()
    {
        return [
            'myEvent1' => [
                self::class.'@listener1',
                self::class.'@listener2',
            ],
            'myEvent2' => [
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
        self::$string .= 'L3';
    }
}
