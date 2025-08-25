<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\PendingBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\PendingChain;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('queue')]
class JobChainingTest extends QueueTestCase
{
    use DatabaseMigrations;

    public static $catchCallbackRan = false;

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set([
            'queue.connections.sync1' => ['driver' => 'sync'],
            'queue.connections.sync2' => ['driver' => 'sync'],
        ]);
    }

    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            JobRunRecorder::reset();
        });

        $this->beforeApplicationDestroyed(function () {
            JobChainingTestFirstJob::$ran = false;
            JobChainingTestSecondJob::$ran = false;
            JobChainingTestThirdJob::$ran = false;
            static::$catchCallbackRan = false;
        });

        parent::setUp();
    }

    public function testJobsCanBeChainedOnSuccess()
    {
        JobChainingTestFirstJob::dispatch()->chain([
            new JobChainingTestSecondJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessUsingPendingChain()
    {
        JobChainingTestFirstJob::withChain([
            new JobChainingTestSecondJob,
        ])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessUsingBusFacade()
    {
        Bus::dispatchChain([
            new JobChainingTestFirstJob,
            new JobChainingTestSecondJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessUsingBusFacadeAsArguments()
    {
        Bus::dispatchChain(
            new JobChainingTestFirstJob,
            new JobChainingTestSecondJob
        );

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsChainedOnExplicitDelete()
    {
        JobChainingTestDeletingJob::dispatch()->chain([
            new JobChainingTestSecondJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingTestDeletingJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessWithSeveralJobs()
    {
        JobChainingTestFirstJob::dispatch()->chain([
            new JobChainingTestSecondJob,
            new JobChainingTestThirdJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
        $this->assertTrue(JobChainingTestThirdJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessUsingHelper()
    {
        dispatch(new JobChainingTestFirstJob)->chain([
            new JobChainingTestSecondJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsCanBeChainedViaQueue()
    {
        Queue::push((new JobChainingTestFirstJob)->chain([
            new JobChainingTestSecondJob,
        ]));

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testSecondJobIsNotFiredIfFirstFailed()
    {
        Queue::push((new JobChainingTestFailingJob)->chain([
            new JobChainingTestSecondJob,
        ]));

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(JobChainingTestSecondJob::$ran);
    }

    public function testSecondJobIsNotFiredIfFirstReleased()
    {
        Queue::push((new JobChainingTestReleasingJob)->chain([
            new JobChainingTestSecondJob,
        ]));

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(JobChainingTestSecondJob::$ran);
    }

    public function testThirdJobIsNotFiredIfSecondFails()
    {
        Queue::push((new JobChainingTestFirstJob)->chain([
            new JobChainingTestFailingJob,
            new JobChainingTestThirdJob,
        ]));

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertFalse(JobChainingTestThirdJob::$ran);
    }

    public function testCatchCallbackIsCalledOnFailure()
    {
        Bus::chain([
            new JobChainingTestFirstJob,
            new JobChainingTestFailingJob,
            new JobChainingTestSecondJob,
        ])->catch(static function () {
            self::$catchCallbackRan = true;
        })->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(static::$catchCallbackRan);
        $this->assertFalse(JobChainingTestSecondJob::$ran);
    }

    public function testChainJobsUseSameConfig()
    {
        JobChainingTestFirstJob::dispatch()->allOnQueue('some_queue')->allOnConnection('sync1')->chain([
            new JobChainingTestSecondJob,
            new JobChainingTestThirdJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertSame('some_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertSame('sync1', JobChainingTestFirstJob::$usedConnection);

        $this->assertSame('some_queue', JobChainingTestSecondJob::$usedQueue);
        $this->assertSame('sync1', JobChainingTestSecondJob::$usedConnection);

        $this->assertSame('some_queue', JobChainingTestThirdJob::$usedQueue);
        $this->assertSame('sync1', JobChainingTestThirdJob::$usedConnection);
    }

    public function testChainJobsUseOwnConfig()
    {
        JobChainingTestFirstJob::dispatch()->allOnQueue('some_queue')->allOnConnection('sync1')->chain([
            (new JobChainingTestSecondJob)->onQueue('another_queue')->onConnection('sync2'),
            new JobChainingTestThirdJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertSame('some_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertSame('sync1', JobChainingTestFirstJob::$usedConnection);

        $this->assertSame('another_queue', JobChainingTestSecondJob::$usedQueue);
        $this->assertSame('sync2', JobChainingTestSecondJob::$usedConnection);

        $this->assertSame('some_queue', JobChainingTestThirdJob::$usedQueue);
        $this->assertSame('sync1', JobChainingTestThirdJob::$usedConnection);
    }

    public function testChainJobsUseDefaultConfig()
    {
        JobChainingTestFirstJob::dispatch()->onQueue('some_queue')->onConnection('sync1')->chain([
            (new JobChainingTestSecondJob)->onQueue('another_queue')->onConnection('sync2'),
            new JobChainingTestThirdJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertSame('some_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertSame('sync1', JobChainingTestFirstJob::$usedConnection);

        $this->assertSame('another_queue', JobChainingTestSecondJob::$usedQueue);
        $this->assertSame('sync2', JobChainingTestSecondJob::$usedConnection);

        $this->assertNull(JobChainingTestThirdJob::$usedQueue);
        $this->assertNull(JobChainingTestThirdJob::$usedConnection);
    }

    public function testChainJobsCanBePrepended()
    {
        JobChainAddingPrependingJob::withChain([new JobChainAddingExistingJob])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertNotNull(JobChainAddingAddedJob::$ranAt);
        $this->assertNotNull(JobChainAddingExistingJob::$ranAt);
        $this->assertTrue(JobChainAddingAddedJob::$ranAt->isBefore(JobChainAddingExistingJob::$ranAt));
    }

    public function testChainJobsCanBePrependedWithoutExistingChain()
    {
        JobChainAddingPrependingJob::dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertNotNull(JobChainAddingAddedJob::$ranAt);
    }

    public function testChainJobsCanBeAppended()
    {
        JobChainAddingAppendingJob::withChain([new JobChainAddingExistingJob])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertNotNull(JobChainAddingAddedJob::$ranAt);
        $this->assertNotNull(JobChainAddingExistingJob::$ranAt);
        $this->assertTrue(JobChainAddingAddedJob::$ranAt->isAfter(JobChainAddingExistingJob::$ranAt));
    }

    public function testChainJobsCanBePrependedBatch()
    {
        Bus::chain([
            new JobChainAddingPrependedBatch('j1'),
            new JobChainingNamedTestJob('j2'),
        ])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertEquals(['j1', 'b1', 'b2', 'j2'], JobRunRecorder::$results);
    }

    public function testChainJobsCanBeAppendedBatch()
    {
        Bus::chain([
            new JobChainAddingAppendingBatch('j1'),
            new JobChainingNamedTestJob('j2'),
        ])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertEquals(['j1', 'j2', 'b1', 'b2'], JobRunRecorder::$results);
    }

    public function testChainJobsCanBeAppendedWithoutExistingChain()
    {
        JobChainAddingAppendingJob::dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertNotNull(JobChainAddingAddedJob::$ranAt);
    }

    public function testBatchCanBeAddedToChain()
    {
        Bus::chain([
            new JobChainingNamedTestJob('c1'),
            new JobChainingNamedTestJob('c2'),
            Bus::batch([
                new JobChainingTestBatchedJob('b1'),
                new JobChainingTestBatchedJob('b2'),
                new JobChainingTestBatchedJob('b3'),
                new JobChainingTestBatchedJob('b4'),
            ]),
            new JobChainingNamedTestJob('c3'),
        ])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertEquals(['c1', 'c2', 'b1', 'b2', 'b3', 'b4', 'c3'], JobRunRecorder::$results);
    }

    public function testDynamicBatchCanBeAddedToChain()
    {
        Bus::chain([
            new JobChainingNamedTestJob('c1'),
            new JobChainingNamedTestJob('c2'),
            Bus::batch([
                new JobChainingTestBatchedJob('b1'),
                new JobChainingTestBatchedJob('b2', times: 4),
                new JobChainingTestBatchedJob('b3'),
                new JobChainingTestBatchedJob('b4'),
            ]),
            new JobChainingNamedTestJob('c3'),
        ])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        if ($this->getQueueDriver() === 'sync') {
            $this->assertEquals(
                ['c1', 'c2', 'b1', 'b2-0', 'b2-1', 'b2-2', 'b2-3', 'b2', 'b3', 'b4', 'c3'], JobRunRecorder::$results
            );
        }

        $this->assertCount(11, JobRunRecorder::$results);
    }

    public function testChainBatchChain()
    {
        Bus::chain([
            new JobChainingNamedTestJob('c1'),
            new JobChainingNamedTestJob('c2'),
            Bus::batch([
                [
                    new JobChainingNamedTestJob('bc1'),
                    new JobChainingNamedTestJob('bc2'),
                ],
                new JobChainingTestBatchedJob('b1'),
                new JobChainingTestBatchedJob('b2', times: 4),
                new JobChainingTestBatchedJob('b3'),
                new JobChainingTestBatchedJob('b4'),
            ]),
            new JobChainingNamedTestJob('c3'),
        ])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        if ($this->getQueueDriver() === 'sync') {
            $this->assertEquals(
                ['c1', 'c2', 'bc1', 'bc2', 'b1', 'b2-0', 'b2-1', 'b2-2', 'b2-3', 'b2', 'b3', 'b4', 'c3'], JobRunRecorder::$results
            );
        }

        $this->assertCount(13, JobRunRecorder::$results);
    }

    public function testChainBatchChainBatch()
    {
        Bus::chain([
            new JobChainingNamedTestJob('c1'),
            new JobChainingNamedTestJob('c2'),
            Bus::batch([
                [
                    new JobChainingNamedTestJob('bc1'),
                    new JobChainingNamedTestJob('bc2'),
                    Bus::batch([
                        new JobChainingTestBatchedJob('bb1'),
                        new JobChainingTestBatchedJob('bb2'),
                    ]),
                ],
                new JobChainingTestBatchedJob('b1'),
                new JobChainingTestBatchedJob('b2', times: 4),
                new JobChainingTestBatchedJob('b3'),
                new JobChainingTestBatchedJob('b4'),
            ]),
            new JobChainingNamedTestJob('c3'),
        ])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        if ($this->getQueueDriver() === 'sync') {
            $this->assertEquals(
                ['c1', 'c2', 'bc1', 'bc2', 'bb1', 'bb2', 'b1', 'b2-0', 'b2-1', 'b2-2', 'b2-3', 'b2', 'b3', 'b4', 'c3'], JobRunRecorder::$results
            );
        }

        $this->assertCount(15, JobRunRecorder::$results);
    }

    public function testBatchCatchCallbacks()
    {
        Bus::chain([
            new JobChainingNamedTestJob('c1'),
            new JobChainingNamedTestJob('c2'),
            Bus::batch([
                new JobChainingTestFailingBatchedJob('fb1'),
            ])->catch(fn () => JobRunRecorder::recordFailure('batch failed')),
            new JobChainingNamedTestJob('c3'),
        ])->catch(fn () => JobRunRecorder::recordFailure('chain failed'))->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertEquals(['c1', 'c2'], JobRunRecorder::$results);
        $this->assertEquals(['batch failed', 'chain failed'], JobRunRecorder::$failures);
    }

    public function testChainBatchFailureAllowed()
    {
        Bus::chain([
            new JobChainingNamedTestJob('c1'),
            new JobChainingNamedTestJob('c2'),
            Bus::batch([
                new JobChainingTestBatchedJob('b1'),
                new JobChainingTestFailingBatchedJob('b2'),
                new JobChainingTestBatchedJob('b3'),
            ])->allowFailures()->catch(fn () => JobRunRecorder::recordFailure('batch failed')),
            new JobChainingNamedTestJob('c3'),
        ])->catch(fn () => JobRunRecorder::recordFailure('chain failed'))->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertEquals(['c1', 'c2', 'b1', 'b3', 'c3'], JobRunRecorder::$results);
        // Only the batch failed, but the chain should keep going since the batch allows failures
        $this->assertEquals(['batch failed'], JobRunRecorder::$failures);
    }

    public function testChainBatchFailureNotAllowed()
    {
        Bus::chain([
            new JobChainingNamedTestJob('c1'),
            new JobChainingNamedTestJob('c2'),
            Bus::batch([
                new JobChainingTestBatchedJob('b1'),
                new JobChainingTestFailingBatchedJob('b2'),
                new JobChainingTestBatchedJob('b3'),
            ])->allowFailures(false)->catch(fn () => JobRunRecorder::recordFailure('batch failed')),
            new JobChainingNamedTestJob('c3'),
        ])->catch(fn () => JobRunRecorder::recordFailure('chain failed'))->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertEquals(['c1', 'c2', 'b1', 'b3'], JobRunRecorder::$results);
        $this->assertEquals(['batch failed', 'chain failed'], JobRunRecorder::$failures);
    }

    public function testChainConditionable()
    {
        $chain = Bus::chain([])
            ->onConnection('sync1')
            ->when(true, function (PendingChain $chain) {
                $chain->onConnection('sync2');
            });

        $this->assertEquals('sync2', $chain->connection);

        $chain = Bus::chain([])
            ->onConnection('sync1')
            ->when(false, function (PendingChain $chain) {
                $chain->onConnection('sync2');
            });

        $this->assertEquals('sync1', $chain->connection);
    }

    public function testBatchConditionable()
    {
        $batch = Bus::batch([])
            ->onConnection('sync1')
            ->when(true, function (PendingBatch $batch) {
                $batch->onConnection('sync2');
            });

        $this->assertEquals('sync2', $batch->connection());
        $batch = Bus::batch([])
            ->onConnection('sync1')
            ->when(false, function (PendingBatch $batch) {
                $batch->onConnection('sync2');
            });

        $this->assertEquals('sync1', $batch->connection());
    }

    public function testJobsAreChainedWhenDispatchIfIsTrue()
    {
        JobChainingTestFirstJob::withChain([
            new JobChainingTestSecondJob,
        ])->dispatchIf(true);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsAreNotChainedWhenDispatchIfIsFalse()
    {
        JobChainingTestFirstJob::withChain([
            new JobChainingTestSecondJob,
        ])->dispatchIf(false);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(JobChainingTestFirstJob::$ran);
        $this->assertFalse(JobChainingTestSecondJob::$ran);
    }

    public function testJobsAreChainedWhenDispatchUnlessIsFalse()
    {
        JobChainingTestFirstJob::withChain([
            new JobChainingTestSecondJob,
        ])->dispatchUnless(false);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsAreNotChainedWhenDispatchUnlessIsTrue()
    {
        JobChainingTestFirstJob::withChain([
            new JobChainingTestSecondJob,
        ])->dispatchUnless(true);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(JobChainingTestFirstJob::$ran);
        $this->assertFalse(JobChainingTestSecondJob::$ran);
    }
}

class JobChainingTestFirstJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public static $usedQueue = null;

    public static $usedConnection = null;

    public function handle()
    {
        static::$ran = true;
        static::$usedQueue = $this->queue;
        static::$usedConnection = $this->connection;
    }
}

class JobChainingTestSecondJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public static $usedQueue = null;

    public static $usedConnection = null;

    public function handle()
    {
        static::$ran = true;
        static::$usedQueue = $this->queue;
        static::$usedConnection = $this->connection;
    }
}

class JobChainingTestThirdJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public static $usedQueue = null;

    public static $usedConnection = null;

    public function handle()
    {
        static::$ran = true;
        static::$usedQueue = $this->queue;
        static::$usedConnection = $this->connection;
    }
}

class JobChainingTestDeletingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
        $this->delete();
    }
}

class JobChainingTestReleasingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        $this->release(30);
    }
}

class JobChainingTestFailingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        $this->fail();
    }
}

class JobChainAddingPrependingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        $this->prependToChain(new JobChainAddingAddedJob);
    }
}

class JobChainAddingAppendingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        $this->appendToChain(new JobChainAddingAddedJob);
    }
}

class JobChainAddingAppendingBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function handle()
    {
        $this->appendToChain(Bus::batch([
            new JobChainingNamedTestJob('b1'),
            new JobChainingNamedTestJob('b2'),
        ]));

        JobRunRecorder::record($this->id);
    }
}

class JobChainAddingPrependedBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function handle()
    {
        $this->prependToChain(Bus::batch([
            new JobChainingNamedTestJob('b1'),
            new JobChainingNamedTestJob('b2'),
        ]));

        JobRunRecorder::record($this->id);
    }
}

class JobChainAddingExistingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var Carbon|null */
    public static $ranAt = null;

    public function handle()
    {
        static::$ranAt = now();
    }
}

class JobChainAddingAddedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var Carbon|null */
    public static $ranAt = null;

    public function handle()
    {
        static::$ranAt = now();
    }
}

class JobChainingTestThrowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        throw new \Exception();
    }
}

class JobChainingNamedTestJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public static $results = [];

    public string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function handle()
    {
        JobRunRecorder::record($this->id);
    }
}

class JobChainingTestBatchedJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public string $id;

    public int $times;

    public function __construct(string $id, int $times = 0)
    {
        $this->id = $id;
        $this->times = $times;
    }

    public function handle()
    {
        for ($i = 0; $i < $this->times; $i++) {
            $this->batch()->add(new JobChainingTestBatchedJob($this->id.'-'.$i));
        }
        JobRunRecorder::record($this->id);
    }
}

class JobChainingTestFailingBatchedJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        $this->fail();
    }
}

class JobRunRecorder
{
    public static $results = [];

    public static $failures = [];

    public static function record(string $id)
    {
        self::$results[] = $id;
    }

    public static function recordFailure(string $message)
    {
        self::$failures[] = $message;

        return $message;
    }

    public static function reset()
    {
        self::$results = [];
        self::$failures = [];
    }
}
