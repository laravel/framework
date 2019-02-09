<?php

namespace Illuminate\Tests\Support;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Auth\AuthManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Testing\Fakes\EventFake;

class SupportFacadesEventTest extends TestCase
{
    private $events;

    private $auth;

    private $guard;

    protected function setUp()
    {
        parent::setUp();

        $this->events = m::spy(Dispatcher::class);
        $this->auth = m::spy(AuthManager::class);

        $container = new Container;
        $container->instance('events', $this->events);
        $container->instance('auth', $this->auth);
        $container->rebinding('events', function ($app, $dispatcher) {
            $guard = $app['auth']->guard();

            if (method_exists($guard, 'setDispatcher')) {
                $guard->setDispatcher($dispatcher);
            }
        });

        Facade::setFacadeApplication($container);
    }

    public function tearDown()
    {
        Event::clearResolvedInstances();

        m::close();
    }

    public function testFakeFor()
    {
        Event::fakeFor(function () {
            (new FakeForStub)->dispatch();

            Event::assertDispatched(EventStub::class);
        });

        $this->events->shouldReceive('dispatch')->once();

        (new FakeForStub)->dispatch();
    }

    public function testFakeForSwapsDispatchers()
    {
        $this->guard = new SessionGuardStub;
        $this->auth->shouldReceive('guard')->andReturn($this->guard);

        Event::fakeFor(function () {
            $this->assertInstanceOf(EventFake::class, Event::getFacadeRoot());
            $this->assertInstanceOf(EventFake::class, Model::getEventDispatcher());
            $this->assertInstanceOf(EventFake::class, $this->guard->getDispatcher());
        });

        $this->assertSame($this->events, Event::getFacadeRoot());
        $this->assertSame($this->events, Model::getEventDispatcher());
        $this->assertSame($this->events, $this->guard->getDispatcher());
    }

    public function testFakeForSwapsDispatchersHasNoProblemWithStateLessGuards()
    {
        $this->auth->shouldReceive('guard')->once()->andReturn(new TokenGuardStub);

        Event::fake();

        $this->assertInstanceOf(EventFake::class, Model::getEventDispatcher());
        $this->assertInstanceOf(EventFake::class, Event::getFacadeRoot());
    }
}

class FakeForStub
{
    public function dispatch()
    {
        Event::dispatch(EventStub::class);
    }
}

class SessionGuardStub
{
    private $events;

    public function setDispatcher($events)
    {
        $this->events = $events;
    }

    public function getDispatcher()
    {
        return $this->events;
    }
}

class TokenGuardStub
{
}
