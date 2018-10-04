<?php

namespace Illuminate\Tests\Support;

use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Testing\Fakes\EventFake;

class SupportFacadesEventTest extends TestCase
{
    private $events;

    protected function setUp()
    {
        parent::setUp();

        $this->events = Mockery::spy(Dispatcher::class);

        $container = new Container;
        $container->instance('events', $this->events);

        Facade::setFacadeApplication($container);
    }

    public function tearDown()
    {
        Event::clearResolvedInstances();

        Mockery::close();
    }

    public function testFakeFor()
    {
        Event::fakeFor(function () {
            (new FakeForStub())->dispatch();

            Event::assertDispatched(EventStub::class);
        });

        $this->events->shouldReceive('dispatch')->once();

        (new FakeForStub())->dispatch();
    }

    public function testFakeForSwapsDispatchers()
    {
        Event::fakeFor(function () {
            $this->assertInstanceOf(EventFake::class, Event::getFacadeRoot());
            $this->assertInstanceOf(EventFake::class, Model::getEventDispatcher());
        });

        $this->assertSame($this->events, Event::getFacadeRoot());
        $this->assertSame($this->events, Model::getEventDispatcher());
    }
}

class FakeForStub
{
    public function dispatch()
    {
        Event::dispatch(EventStub::class);
    }
}
