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

class ExampleSubscriber
{
    public function subscribe($e)
    {
        //
    }
}
