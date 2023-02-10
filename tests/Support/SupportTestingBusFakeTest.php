<?php

namespace Illuminate\Tests\Support;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Support\Testing\Fakes\BatchRepositoryFake;
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

    public function testItUsesCustomBusRepository()
    {
        $busRepository = new BatchRepositoryFake;

        $fake = new BusFake(m::mock(QueueingDispatcher::class), [], $busRepository);

        $this->assertNull($fake->findBatch('non-existent-batch'));

        $batch = $fake->batch([])->dispatch();

        $this->assertSame($batch, $fake->findBatch($batch->id));
        $this->assertSame($batch, $busRepository->find($batch->id));
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

    public function testAssertDispatchedAfterResponseTimesWithCallbackFunction()
    {
        $this->fake->dispatchAfterResponse(new OtherBusJobStub(0));
        $this->fake->dispatchAfterResponse(new OtherBusJobStub(1));
        $this->fake->dispatchAfterResponse(new OtherBusJobStub(1));

        try {
            $this->fake->assertDispatchedAfterResponseTimes(function (OtherBusJobStub $job) {
                return $job->id === 0;
            }, 2);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\OtherBusJobStub] job was pushed 1 times instead of 2 times.'));
        }

        $this->fake->assertDispatchedAfterResponseTimes(function (OtherBusJobStub $job) {
            return $job->id === 0;
        });

        $this->fake->assertDispatchedAfterResponseTimes(function (OtherBusJobStub $job) {
            return $job->id === 1;
        }, 2);
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

    public function testAssertDispatchedTimesWithCallbackFunction()
    {
        $this->fake->dispatch(new OtherBusJobStub(0));
        $this->fake->dispatchNow(new OtherBusJobStub(1));
        $this->fake->dispatchAfterResponse(new OtherBusJobStub(1));

        try {
            $this->fake->assertDispatchedTimes(function (OtherBusJobStub $job) {
                return $job->id === 0;
            }, 2);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\OtherBusJobStub] job was pushed 1 times instead of 2 times.'));
        }

        $this->fake->assertDispatchedTimes(function (OtherBusJobStub $job) {
            return $job->id === 0;
        });

        $this->fake->assertDispatchedTimes(function (OtherBusJobStub $job) {
            return $job->id === 1;
        }, 2);
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

    public function testAssertDispatchedSyncTimesWithCallbackFunction()
    {
        $this->fake->dispatchSync(new OtherBusJobStub(0));
        $this->fake->dispatchSync(new OtherBusJobStub(1));
        $this->fake->dispatchSync(new OtherBusJobStub(1));

        try {
            $this->fake->assertDispatchedSyncTimes(function (OtherBusJobStub $job) {
                return $job->id === 0;
            }, 2);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\OtherBusJobStub] job was synchronously pushed 1 times instead of 2 times.'));
        }

        $this->fake->assertDispatchedSyncTimes(function (OtherBusJobStub $job) {
            return $job->id === 0;
        });

        $this->fake->assertDispatchedSyncTimes(function (OtherBusJobStub $job) {
            return $job->id === 1;
        }, 2);
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

    public function testAssertChained()
    {
        $this->fake->chain([
            new ChainedJobStub,
            new OtherBusJobStub,
        ])->dispatch();

        $this->fake->assertChained([
            ChainedJobStub::class,
            OtherBusJobStub::class,
        ]);
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

    public function testDispatchedFakingOnlyGivenJobs()
    {
        $dispatcher = m::mock(QueueingDispatcher::class);

        $job = new BusJobStub;
        $dispatcher->shouldReceive('dispatch')->never()->with($job);
        $dispatcher->shouldReceive('dispatchNow')->never()->with($job, null);

        $otherJob = new OtherBusJobStub;
        $dispatcher->shouldReceive('dispatch')->once()->with($otherJob);
        $dispatcher->shouldReceive('dispatchNow')->once()->with($otherJob, null);

        $thirdJob = new ThirdJob;
        $dispatcher->shouldReceive('dispatch')->never()->with($thirdJob);
        $dispatcher->shouldReceive('dispatchNow')->never()->with($thirdJob, null);

        $fake = (new BusFake($dispatcher))->except(OtherBusJobStub::class);

        $fake->dispatch($job);
        $fake->dispatchNow($job);

        $fake->dispatch($otherJob);
        $fake->dispatchNow($otherJob);

        $fake->dispatch($thirdJob);
        $fake->dispatchNow($thirdJob);

        $fake->assertNotDispatched(OtherBusJobStub::class);
        $fake->assertDispatchedTimes(BusJobStub::class, 2);
        $fake->assertDispatchedTimes(ThirdJob::class, 2);
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

    public function testAssertNothingBatched()
    {
        $this->fake->assertNothingBatched();

        $this->fake->batch([])->dispatch();

        try {
            $this->fake->assertNothingBatched();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Batched jobs were dispatched unexpectedly.'));
        }
    }

    public function testFindBatch()
    {
        $this->assertNull($this->fake->findBatch('non-existent-batch'));

        $batch = $this->fake->batch([])->dispatch();

        $this->assertSame($batch, $this->fake->findBatch($batch->id));
    }

    public function testBatchesCanBeCancelled()
    {
        $batch = $this->fake->batch([])->dispatch();

        $this->assertFalse($batch->cancelled());

        $batch->cancel();

        $this->assertTrue($batch->cancelled());
    }

    public function testDispatchFakeBatch()
    {
        $this->fake->assertNothingBatched();

        $batch = $this->fake->dispatchFakeBatch('my fake job batch');

        $this->fake->assertBatchCount(1);
        $this->assertInstanceOf(Batch::class, $batch);
        $this->assertSame('my fake job batch', $batch->name);
        $this->assertSame(0, $batch->totalJobs);

        $batch = $this->fake->dispatchFakeBatch();

        $this->fake->assertBatchCount(2);
        $this->assertInstanceOf(Batch::class, $batch);
        $this->assertSame('', $batch->name);
        $this->assertSame(0, $batch->totalJobs);
    }

    public function testIncrementFailedJobsInFakeBatch()
    {
        $this->fake->assertNothingBatched();
        $batch = $this->fake->dispatchFakeBatch('my fake job batch');

        $this->fake->assertBatchCount(1);
        $this->assertInstanceOf(Batch::class, $batch);
        $this->assertSame('my fake job batch', $batch->name);
        $this->assertSame(0, $batch->totalJobs);

        $batch->incrementFailedJobs($batch->id);

        $this->assertSame(0, $batch->failedJobs);
        $this->assertSame(0, $batch->pendingJobs);
    }

    public function testDecrementPendingJobsInFakeBatch()
    {
        $this->fake->assertNothingBatched();
        $batch = $this->fake->dispatchFakeBatch('my fake job batch');

        $this->fake->assertBatchCount(1);
        $this->assertInstanceOf(Batch::class, $batch);
        $this->assertSame('my fake job batch', $batch->name);
        $this->assertSame(0, $batch->totalJobs);

        $batch->decrementPendingJobs($batch->id);

        $this->assertSame(0, $batch->failedJobs);
        $this->assertSame(0, $batch->pendingJobs);
    }
}

class BusJobStub
{
    //
}

class ChainedJobStub
{
    use Queueable;
}

class OtherBusJobStub
{
    public $id;

    public function __construct($id = null)
    {
        $this->id = $id;
    }
}

class ThirdJob
{
    //
}
