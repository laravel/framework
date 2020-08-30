<?php

namespace Illuminate\Tests\Events;

use Illuminate\Container\Container;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Events\Dispatcher;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BroadcastedEventsTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
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
