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

    public function testAssertDispatchedWithCallbackInt()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Illuminate\\Tests\\Support\\EventStub] event was dispatched 2 times instead of 1 times.');

        $this->fake->dispatch(EventStub::class);
        $this->fake->dispatch(EventStub::class);

        $this->fake->assertDispatched(EventStub::class, 2);
        $this->fake->assertDispatched(EventStub::class, 1);
    }

    public function testAssertDispatchedTimes()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Illuminate\\Tests\\Support\\EventStub] event was dispatched 2 times instead of 1 times.');

        $this->fake->dispatch(EventStub::class);
        $this->fake->dispatch(EventStub::class);

        $this->fake->assertDispatchedTimes(EventStub::class, 2);
        $this->fake->assertDispatchedTimes(EventStub::class, 1);
    }

    public function testAssertNotDispatched()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The unexpected [Illuminate\\Tests\\Support\\EventStub] event was dispatched.');

        $this->fake->dispatch(EventStub::class);

        $this->fake->assertNotDispatched(EventStub::class);
    }
}

class EventStub
{
}
