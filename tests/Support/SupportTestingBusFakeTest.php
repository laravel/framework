<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Support\Testing\Fakes\BusFake;
use Mockery as m;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SupportTestingBusFakeTest extends TestCase
{
    /** @var \Illuminate\Support\Testing\Fakes\BusFake */
    protected $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new BusFake(m::mock(QueueingDispatcher::class));
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

    public function testAssertDispatchedWithClosure()
    {
        $this->fake->dispatch(new BusJobStub);

        $this->fake->assertDispatched(function (BusJobStub $job) {
            return true;
        });
    }

    public function testAssertDispatchedAfterResponse()
    {
        try {
            $this->fake->assertDispatchedAfterResponse(BusJobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\BusJobStub] job was not dispatched after sending the response.'));
        }

        $this->fake->dispatchAfterResponse(new BusJobStub);

        $this->fake->assertDispatchedAfterResponse(BusJobStub::class);
    }

    public function testAssertDispatchedAfterResponseClosure()
    {
        try {
            $this->fake->assertDispatchedAfterResponse(function (BusJobStub $job) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\BusJobStub] job was not dispatched after sending the response.'));
        }
    }

    public function testAssertDispatchedSync()
    {
        try {
            $this->fake->assertDispatchedSync(BusJobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\BusJobStub] job was not dispatched synchronously.'));
        }

        $this->fake->dispatch(new BusJobStub);

        try {
            $this->fake->assertDispatchedSync(BusJobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\BusJobStub] job was not dispatched synchronously.'));
        }

        $this->fake->dispatchSync(new BusJobStub);

        $this->fake->assertDispatchedSync(BusJobStub::class);
    }

    public function testAssertDispatchedSyncClosure()
    {
        try {
            $this->fake->assertDispatchedSync(function (BusJobStub $job) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\BusJobStub] job was not dispatched synchronously.'));
        }
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

    public function testAssertDispatchedAfterResponseWithCallbackInt()
    {
        $this->fake->dispatchAfterResponse(new BusJobStub);
        $this->fake->dispatchAfterResponse(new BusJobStub);

        try {
            $this->fake->assertDispatchedAfterResponse(BusJobStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\BusJobStub] job was pushed 2 times instead of 1 times.'));
        }

        $this->fake->assertDispatchedAfterResponse(BusJobStub::class, 2);
    }

    public function testAssertDispatchedSyncWithCallbackInt()
    {
        $this->fake->dispatchSync(new BusJobStub);
        $this->fake->dispatchSync(new BusJobStub);

        try {
            $this->fake->assertDispatchedSync(BusJobStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\BusJobStub] job was synchronously pushed 2 times instead of 1 times.'));
        }

        $this->fake->assertDispatchedSync(BusJobStub::class, 2);
    }

    public function testAssertDispatchedWithCallbackFunction()
    {
        $this->fake->dispatch(new OtherBusJobStub);
        $this->fake->dispatchNow(new OtherBusJobStub(1));

        try {
            $this->fake->assertDispatched(OtherBusJobStub::class, function ($job) {
                return $job->id === 0;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\OtherBusJobStub] job was not dispatched.'));
        }

        $this->fake->assertDispatched(OtherBusJobStub::class, function ($job) {
            return $job->id === null;
        });

        $this->fake->assertDispatched(OtherBusJobStub::class, function ($job) {
            return $job->id === 1;
        });
    }

    public function testAssertDispatchedAfterResponseWithCallbackFunction()
    {
        $this->fake->dispatchAfterResponse(new OtherBusJobStub);
        $this->fake->dispatchAfterResponse(new OtherBusJobStub(1));

        try {
            $this->fake->assertDispatchedAfterResponse(OtherBusJobStub::class, function ($job) {
                return $job->id === 0;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\OtherBusJobStub] job was not dispatched after sending the response.'));
        }

        $this->fake->assertDispatchedAfterResponse(OtherBusJobStub::class, function ($job) {
            return $job->id === null;
        });

        $this->fake->assertDispatchedAfterResponse(OtherBusJobStub::class, function ($job) {
            return $job->id === 1;
        });
    }

    public function testAssertDispatchedSyncWithCallbackFunction()
    {
        $this->fake->dispatchSync(new OtherBusJobStub);
        $this->fake->dispatchSync(new OtherBusJobStub(1));

        try {
            $this->fake->assertDispatchedSync(OtherBusJobStub::class, function ($job) {
                return $job->id === 0;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\OtherBusJobStub] job was not dispatched synchronously.'));
        }

        $this->fake->assertDispatchedSync(OtherBusJobStub::class, function ($job) {
            return $job->id === null;
        });

        $this->fake->assertDispatchedSync(OtherBusJobStub::class, function ($job) {
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

    public function testAssertDispatchedAfterResponseTimes()
    {
        $this->fake->dispatchAfterResponse(new BusJobStub);
        $this->fake->dispatchAfterResponse(new BusJobStub);

        try {
            $this->fake->assertDispatchedAfterResponseTimes(BusJobStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\BusJobStub] job was pushed 2 times instead of 1 times.'));
        }

        $this->fake->assertDispatchedAfterResponseTimes(BusJobStub::class, 2);
    }

    public function testAssertDispatchedSyncTimes()
    {
        $this->fake->dispatchSync(new BusJobStub);
        $this->fake->dispatchSync(new BusJobStub);

        try {
            $this->fake->assertDispatchedSyncTimes(BusJobStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\BusJobStub] job was synchronously pushed 2 times instead of 1 times.'));
        }

        $this->fake->assertDispatchedSyncTimes(BusJobStub::class, 2);
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

    public function testAssertNotDispatchedWithClosure()
    {
        $this->fake->dispatch(new BusJobStub);
        $this->fake->dispatchNow(new BusJobStub);

        try {
            $this->fake->assertNotDispatched(function (BusJobStub $job) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\BusJobStub] job was dispatched.'));
        }
    }

    public function testAssertNotDispatchedAfterResponse()
    {
        $this->fake->assertNotDispatchedAfterResponse(BusJobStub::class);

        $this->fake->dispatchAfterResponse(new BusJobStub);

        try {
            $this->fake->assertNotDispatchedAfterResponse(BusJobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\BusJobStub] job was dispatched after sending the response.'));
        }
    }

    public function testAssertNotDispatchedAfterResponseClosure()
    {
        $this->fake->dispatchAfterResponse(new BusJobStub);

        try {
            $this->fake->assertNotDispatchedAfterResponse(function (BusJobStub $job) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\BusJobStub] job was dispatched after sending the response.'));
        }
    }

    public function testAssertNotDispatchedSync()
    {
        $this->fake->assertNotDispatchedSync(BusJobStub::class);

        $this->fake->dispatchSync(new BusJobStub);

        try {
            $this->fake->assertNotDispatchedSync(BusJobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\BusJobStub] job was dispatched synchronously.'));
        }
    }

    public function testAssertNotDispatchedSyncClosure()
    {
        $this->fake->dispatchSync(new BusJobStub);

        try {
            $this->fake->assertNotDispatchedSync(function (BusJobStub $job) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\BusJobStub] job was dispatched synchronously.'));
        }
    }

    public function testAssertNothingDispatched()
    {
        $this->fake->assertNothingDispatched();

        $this->fake->dispatch(new BusJobStub);

        try {
            $this->fake->assertNothingDispatched();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Jobs were dispatched unexpectedly.'));
        }
    }

    public function testAssertDispatchedWithIgnoreClass()
    {
        $dispatcher = m::mock(QueueingDispatcher::class);

        $job = new BusJobStub;
        $dispatcher->shouldReceive('dispatch')->once()->with($job);
        $dispatcher->shouldReceive('dispatchNow')->once()->with($job, null);

        $otherJob = new OtherBusJobStub;
        $dispatcher->shouldReceive('dispatch')->never()->with($otherJob);
        $dispatcher->shouldReceive('dispatchNow')->never()->with($otherJob, null);

        $fake = new BusFake($dispatcher, OtherBusJobStub::class);

        $fake->dispatch($job);
        $fake->dispatchNow($job);

        $fake->dispatch($otherJob);
        $fake->dispatchNow($otherJob);

        $fake->assertNotDispatched(BusJobStub::class);
        $fake->assertDispatchedTimes(OtherBusJobStub::class, 2);
    }

    public function testAssertDispatchedWithIgnoreCallback()
    {
        $dispatcher = m::mock(QueueingDispatcher::class);

        $job = new BusJobStub;
        $dispatcher->shouldReceive('dispatch')->once()->with($job);
        $dispatcher->shouldReceive('dispatchNow')->once()->with($job, null);

        $otherJob = new OtherBusJobStub;
        $dispatcher->shouldReceive('dispatch')->once()->with($otherJob);
        $dispatcher->shouldReceive('dispatchNow')->once()->with($otherJob, null);

        $anotherJob = new OtherBusJobStub(1);
        $dispatcher->shouldReceive('dispatch')->never()->with($anotherJob);
        $dispatcher->shouldReceive('dispatchNow')->never()->with($anotherJob, null);

        $fake = new BusFake($dispatcher, [
            function ($command) {
                return $command instanceof OtherBusJobStub && $command->id === 1;
            },
        ]);

        $fake->dispatch($job);
        $fake->dispatchNow($job);

        $fake->dispatch($otherJob);
        $fake->dispatchNow($otherJob);

        $fake->dispatch($anotherJob);
        $fake->dispatchNow($anotherJob);

        $fake->assertNotDispatched(BusJobStub::class);
        $fake->assertDispatchedTimes(OtherBusJobStub::class, 2);
        $fake->assertNotDispatched(OtherBusJobStub::class, function ($job) {
            return $job->id === null;
        });
        $fake->assertDispatched(OtherBusJobStub::class, function ($job) {
            return $job->id === 1;
        });
    }
}

class BusJobStub
{
    //
}

class OtherBusJobStub
{
    public $id;

    public function __construct($id = null)
    {
        $this->id = $id;
    }
}
