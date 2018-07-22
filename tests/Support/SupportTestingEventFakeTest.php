<?php

namespace Illuminate\Tests\Support;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Testing\Fakes\EventFake;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Constraint\ExceptionMessage;

class SupportTestingEventFakeTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->fake = new EventFake(m::mock(Dispatcher::class));
    }

    public function testAssertDispatched()
    {
        try {
            $this->fake->assertDispatched(EventStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\EventStub] event was not dispatched.'));
        }

        $this->fake->dispatch(EventStub::class);

        $this->fake->assertDispatched(EventStub::class);
    }

    public function testAssertDispatchedWithCallbackInt()
    {
        $this->fake->dispatch(EventStub::class);
        $this->fake->dispatch(EventStub::class);

        try {
            $this->fake->assertDispatched(EventStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\EventStub] event was dispatched 2 times instead of 1 times.'));
        }

        $this->fake->assertDispatched(EventStub::class, 2);
    }

    public function testAssertDispatchedTimes()
    {
        $this->fake->dispatch(EventStub::class);
        $this->fake->dispatch(EventStub::class);

        try {
            $this->fake->assertDispatchedTimes(EventStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\EventStub] event was dispatched 2 times instead of 1 times.'));
        }

        $this->fake->assertDispatchedTimes(EventStub::class, 2);
    }

    public function testAssertNotDispatched()
    {
        $this->fake->assertNotDispatched(EventStub::class);

        $this->fake->dispatch(EventStub::class);

        try {
            $this->fake->assertNotDispatched(EventStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\EventStub] event was dispatched.'));
        }
    }
}

class EventStub
{
}
