<?php

namespace Illuminate\Tests\Support;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Testing\Fakes\BusFake;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Constraint\ExceptionMessage;

class SupportTestingBusFakeTest extends TestCase
{
    /** @var \Illuminate\Support\Testing\Fakes\BusFake */
    protected $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new BusFake(m::mock(Dispatcher::class));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testAssertDispatched()
    {
        try {
            $this->fake->assertDispatched(BusJobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\BusJobStub] job was not dispatched.'));
        }

        $this->fake->dispatch(new BusJobStub);

        $this->fake->assertDispatched(BusJobStub::class);
    }

    public function testAssertDispatchedNow()
    {
        $this->fake->dispatchNow(new BusJobStub);

        $this->fake->assertDispatched(BusJobStub::class);
    }

    public function testAssertDispatchedWithCallbackInt()
    {
        $this->fake->dispatch(new BusJobStub);
        $this->fake->dispatchNow(new BusJobStub);

        try {
            $this->fake->assertDispatched(BusJobStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\BusJobStub] job was pushed 2 times instead of 1 times.'));
        }

        $this->fake->assertDispatched(BusJobStub::class, 2);
    }

    public function testAssertDispatchedWithCallbackFunction()
    {
        $this->fake->dispatch(new AnotherBusJobStub);
        $this->fake->dispatchNow(new AnotherBusJobStub(1));

        try {
            $this->fake->assertDispatched(AnotherBusJobStub::class, function ($job) {
                return $job->id === 0;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\AnotherBusJobStub] job was not dispatched.'));
        }

        $this->fake->assertDispatched(AnotherBusJobStub::class, function ($job) {
            return $job->id === null;
        });

        $this->fake->assertDispatched(AnotherBusJobStub::class, function ($job) {
            return $job->id === 1;
        });
    }

    public function testAssertDispatchedTimes()
    {
        $this->fake->dispatch(new BusJobStub);
        $this->fake->dispatchNow(new BusJobStub);

        try {
            $this->fake->assertDispatchedTimes(BusJobStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\BusJobStub] job was pushed 2 times instead of 1 times.'));
        }

        $this->fake->assertDispatchedTimes(BusJobStub::class, 2);
    }

    public function testAssertNotDispatched()
    {
        $this->fake->assertNotDispatched(BusJobStub::class);

        $this->fake->dispatch(new BusJobStub);
        $this->fake->dispatchNow(new BusJobStub);

        try {
            $this->fake->assertNotDispatched(BusJobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\BusJobStub] job was dispatched.'));
        }
    }

    public function testAssertDispatchedWithIgnoreClass()
    {
        $dispatcher = m::mock(Dispatcher::class);

        $BusJobStub = new BusJobStub;
        $dispatcher->shouldReceive('dispatch')->once()->with($BusJobStub);
        $dispatcher->shouldReceive('dispatchNow')->once()->with($BusJobStub, null);

        $AnotherBusJobStub = new AnotherBusJobStub;
        $dispatcher->shouldReceive('dispatch')->never()->with($AnotherBusJobStub);
        $dispatcher->shouldReceive('dispatchNow')->never()->with($AnotherBusJobStub, null);

        $fake = new BusFake($dispatcher, AnotherBusJobStub::class);

        $fake->dispatch($BusJobStub);
        $fake->dispatchNow($BusJobStub);

        $fake->dispatch($AnotherBusJobStub);
        $fake->dispatchNow($AnotherBusJobStub);

        $fake->assertNotDispatched(BusJobStub::class);
        $fake->assertDispatchedTimes(AnotherBusJobStub::class, 2);
    }

    public function testAssertDispatchedWithIgnoreCallback()
    {
        $dispatcher = m::mock(Dispatcher::class);

        $BusJobStub = new BusJobStub;
        $dispatcher->shouldReceive('dispatch')->once()->with($BusJobStub);
        $dispatcher->shouldReceive('dispatchNow')->once()->with($BusJobStub, null);

        $AnotherBusJobStub = new AnotherBusJobStub;
        $dispatcher->shouldReceive('dispatch')->once()->with($AnotherBusJobStub);
        $dispatcher->shouldReceive('dispatchNow')->once()->with($AnotherBusJobStub, null);

        $anotherJobStub = new AnotherBusJobStub(1);
        $dispatcher->shouldReceive('dispatch')->never()->with($anotherJobStub);
        $dispatcher->shouldReceive('dispatchNow')->never()->with($anotherJobStub, null);

        $fake = new BusFake($dispatcher, [
            function ($command) {
                return $command instanceof AnotherBusJobStub && $command->id === 1;
            },
        ]);

        $fake->dispatch($BusJobStub);
        $fake->dispatchNow($BusJobStub);

        $fake->dispatch($AnotherBusJobStub);
        $fake->dispatchNow($AnotherBusJobStub);

        $fake->dispatch($anotherJobStub);
        $fake->dispatchNow($anotherJobStub);

        $fake->assertNotDispatched(BusJobStub::class);
        $fake->assertDispatchedTimes(AnotherBusJobStub::class, 2);
        $fake->assertNotDispatched(AnotherBusJobStub::class, function ($job) {
            return $job->id === null;
        });
        $fake->assertDispatched(AnotherBusJobStub::class, function ($job) {
            return $job->id === 1;
        });
    }
}

class BusJobStub
{
    //
}

class AnotherBusJobStub
{
    public $id;

    public function __construct($id = null)
    {
        $this->id = $id;
    }
}
