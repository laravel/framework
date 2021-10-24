<?php

namespace Illuminate\Tests\Events;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class EventsSubscriberTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testEventSubscribers()
    {
        $this->expectNotToPerformAssertions();

        $d = new Dispatcher($container = m::mock(Container::class));
        $subs = m::mock(ExampleSubscriber::class);
        $subs->shouldReceive('subscribe')->once()->with($d);
        $container->shouldReceive('make')->once()->with(ExampleSubscriber::class)->andReturn($subs);

        $d->subscribe(ExampleSubscriber::class);
    }

    public function testEventSubscribeCanAcceptObject()
    {
        $this->expectNotToPerformAssertions();

        $d = new Dispatcher;
        $subs = m::mock(ExampleSubscriber::class);
        $subs->shouldReceive('subscribe')->once()->with($d);

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
