<?php

namespace Illuminate\Tests\Support;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Testing\Fakes\EventFake;

class SupportTestingEventFakeTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->fake = new EventFake(m::mock(Dispatcher::class));
    }

    public function testAssertDispacthed()
    {
        $this->fake->dispatch(EventStub::class);

        $this->fake->assertDispatched(EventStub::class);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The expected [Illuminate\Tests\Support\EventStub] event was dispatched 2 times instead of 1 times.
     */
    public function testAssertDispatchedWithCallbackInt()
    {
        $this->fake->dispatch(EventStub::class);
        $this->fake->dispatch(EventStub::class);

        $this->fake->assertDispatched(EventStub::class, 2);
        $this->fake->assertDispatched(EventStub::class, 1);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The expected [Illuminate\Tests\Support\EventStub] event was dispatched 2 times instead of 1 times.
     */
    public function testAssertDispatchedTimes()
    {
        $this->fake->dispatch(EventStub::class);
        $this->fake->dispatch(EventStub::class);

        $this->fake->assertDispatchedTimes(EventStub::class, 2);
        $this->fake->assertDispatchedTimes(EventStub::class, 1);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The unexpected [Illuminate\Tests\Support\EventStub] event was dispatched.
     */
    public function testAssertNotDispatched()
    {
        $this->fake->dispatch(EventStub::class);

        $this->fake->assertNotDispatched(EventStub::class);
    }
}

class EventStub
{
}
