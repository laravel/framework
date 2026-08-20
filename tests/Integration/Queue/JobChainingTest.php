<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Bus\PendingChain;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Tests\App\Jobs\JobChainAddingAddedJob;
use Illuminate\Tests\App\Jobs\JobChainAddingAppendingBatch;
use Illuminate\Tests\App\Jobs\JobChainAddingAppendingJob;
use Illuminate\Tests\App\Jobs\JobChainAddingExistingJob;
use Illuminate\Tests\App\Jobs\JobChainAddingPrependedBatch;
use Illuminate\Tests\App\Jobs\JobChainAddingPrependingJob;
use Illuminate\Tests\App\Jobs\JobChainingBatchedJob;
use Illuminate\Tests\App\Jobs\JobChainingDeletingJob;
use Illuminate\Tests\App\Jobs\JobChainingFailingBatchedJob;
use Illuminate\Tests\App\Jobs\JobChainingFailingJob;
use Illuminate\Tests\App\Jobs\JobChainingFirstJob;
use Illuminate\Tests\App\Jobs\JobChainingNamedTestJob;
use Illuminate\Tests\App\Jobs\JobChainingReleasingJob;
use Illuminate\Tests\App\Jobs\JobChainingSecondJob;
use Illuminate\Tests\App\Jobs\JobChainingThirdJob;
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
            JobChainingFirstJob::$ran = false;
            JobChainingSecondJob::$ran = false;
            JobChainingThirdJob::$ran = false;
            static::$catchCallbackRan = false;
        });

        parent::setUp();
    }

    public function testJobsCanBeChainedOnSuccess()
    {
        JobChainingFirstJob::dispatch()->chain([
            new JobChainingSecondJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingFirstJob::$ran);
        $this->assertTrue(JobChainingSecondJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessUsingPendingChain()
    {
        JobChainingFirstJob::withChain([
            new JobChainingSecondJob,
        ])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingFirstJob::$ran);
        $this->assertTrue(JobChainingSecondJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessUsingBusFacade()
    {
        Bus::dispatchChain([
            new JobChainingFirstJob,
            new JobChainingSecondJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingFirstJob::$ran);
        $this->assertTrue(JobChainingSecondJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessUsingBusFacadeAsArguments()
    {
        Bus::dispatchChain(
            new JobChainingFirstJob,
            new JobChainingSecondJob
        );

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingFirstJob::$ran);
        $this->assertTrue(JobChainingSecondJob::$ran);
    }

    public function testJobsChainedOnExplicitDelete()
    {
        JobChainingDeletingJob::dispatch()->chain([
            new JobChainingSecondJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingDeletingJob::$ran);
        $this->assertTrue(JobChainingSecondJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessWithSeveralJobs()
    {
        JobChainingFirstJob::dispatch()->chain([
            new JobChainingSecondJob,
            new JobChainingThirdJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingFirstJob::$ran);
        $this->assertTrue(JobChainingSecondJob::$ran);
        $this->assertTrue(JobChainingThirdJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessUsingHelper()
    {
        dispatch(new JobChainingFirstJob)->chain([
            new JobChainingSecondJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingFirstJob::$ran);
        $this->assertTrue(JobChainingSecondJob::$ran);
    }

    public function testJobsCanBeChainedViaQueue()
    {
        Queue::push((new JobChainingFirstJob)->chain([
            new JobChainingSecondJob,
        ]));

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingFirstJob::$ran);
        $this->assertTrue(JobChainingSecondJob::$ran);
    }

    public function testSecondJobIsNotFiredIfFirstFailed()
    {
        Queue::push((new JobChainingFailingJob)->chain([
            new JobChainingSecondJob,
        ]));

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(JobChainingSecondJob::$ran);
    }

    public function testSecondJobIsNotFiredIfFirstReleased()
    {
        Queue::push((new JobChainingReleasingJob)->chain([
            new JobChainingSecondJob,
        ]));

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(JobChainingSecondJob::$ran);
    }

    public function testThirdJobIsNotFiredIfSecondFails()
    {
        Queue::push((new JobChainingFirstJob)->chain([
            new JobChainingFailingJob,
            new JobChainingThirdJob,
        ]));

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingFirstJob::$ran);
        $this->assertFalse(JobChainingThirdJob::$ran);
    }

    public function testCatchCallbackIsCalledOnFailure()
    {
        Bus::chain([
            new JobChainingFirstJob,
            new JobChainingFailingJob,
            new JobChainingSecondJob,
        ])->catch(static function () {
            self::$catchCallbackRan = true;
        })->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingFirstJob::$ran);
        $this->assertTrue(static::$catchCallbackRan);
        $this->assertFalse(JobChainingSecondJob::$ran);
    }

    public function testChainJobsUseSameConfig()
    {
        JobChainingFirstJob::dispatch()->allOnQueue('some_queue')->allOnConnection('sync1')->chain([
            new JobChainingSecondJob,
            new JobChainingThirdJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertSame('some_queue', JobChainingFirstJob::$usedQueue);
        $this->assertSame('sync1', JobChainingFirstJob::$usedConnection);

        $this->assertSame('some_queue', JobChainingSecondJob::$usedQueue);
        $this->assertSame('sync1', JobChainingSecondJob::$usedConnection);

        $this->assertSame('some_queue', JobChainingThirdJob::$usedQueue);
        $this->assertSame('sync1', JobChainingThirdJob::$usedConnection);
    }

    public function testChainJobsUseOwnConfig()
    {
        JobChainingFirstJob::dispatch()->allOnQueue('some_queue')->allOnConnection('sync1')->chain([
            (new JobChainingSecondJob)->onQueue('another_queue')->onConnection('sync2'),
            new JobChainingThirdJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertSame('some_queue', JobChainingFirstJob::$usedQueue);
        $this->assertSame('sync1', JobChainingFirstJob::$usedConnection);

        $this->assertSame('another_queue', JobChainingSecondJob::$usedQueue);
        $this->assertSame('sync2', JobChainingSecondJob::$usedConnection);

        $this->assertSame('some_queue', JobChainingThirdJob::$usedQueue);
        $this->assertSame('sync1', JobChainingThirdJob::$usedConnection);
    }

    public function testChainJobsUseDefaultConfig()
    {
        JobChainingFirstJob::dispatch()->onQueue('some_queue')->onConnection('sync1')->chain([
            (new JobChainingSecondJob)->onQueue('another_queue')->onConnection('sync2'),
            new JobChainingThirdJob,
        ]);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertSame('some_queue', JobChainingFirstJob::$usedQueue);
        $this->assertSame('sync1', JobChainingFirstJob::$usedConnection);

        $this->assertSame('another_queue', JobChainingSecondJob::$usedQueue);
        $this->assertSame('sync2', JobChainingSecondJob::$usedConnection);

        $this->assertNull(JobChainingThirdJob::$usedQueue);
        $this->assertNull(JobChainingThirdJob::$usedConnection);
    }

    public function testChainJobRemovesFalsy()
    {
        $job = (new JobChainingFirstJob)->chain([
            new JobChainingSecondJob,
            null,
            '',
            0,
            [],
        ]);

        $this->assertCount(1, $job->chained);

        Queue::push($job);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingFirstJob::$ran);
        $this->assertTrue(JobChainingSecondJob::$ran);
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

    public function testChainCanBeAppended()
    {
        $chain = Bus::chain();

        $chain->append($firstJob = new JobChainingNamedTestJob('j1'));
        $chain->append($secondJob = new JobChainingNamedTestJob('j2'));
        $chain->append($thirdJob = new JobChainingNamedTestJob('j3'));

        $this->assertEquals($firstJob, $chain->job);
        $this->assertEquals([$secondJob, $thirdJob], $chain->chain);
    }

    public function testChainCanBeAppendedWithInitialJob()
    {
        $chain = Bus::chain([
            $firstJob = new JobChainingNamedTestJob('j1'),
        ]);

        $chain->append([
            $secondJob = new JobChainingNamedTestJob('j2'),
            $thirdJob = new JobChainingNamedTestJob('j3'),
        ]);

        $this->assertEquals($firstJob, $chain->job);
        $this->assertEquals([$secondJob, $thirdJob], $chain->chain);
    }

    public function testChainRemovesFalsy()
    {
        $chain = Bus::chain([
            $firstJob = new JobChainingFirstJob,
            $secondJob = new JobChainingSecondJob,
            null,
            '',
            0,
            [],
        ]);

        $this->assertEquals($firstJob, $chain->job);
        $this->assertEquals([$secondJob], $chain->chain);
    }

    public function testChainAppendRemovesFalsy()
    {
        $chain = Bus::chain([
            $firstJob = new JobChainingNamedTestJob('j1'),
        ]);

        $chain->append([
            $secondJob = new JobChainingNamedTestJob('j2'),
            $thirdJob = new JobChainingNamedTestJob('j3'),
            null,
            '',
            0,
            [],
        ]);

        $this->assertEquals($firstJob, $chain->job);
        $this->assertEquals([$secondJob, $thirdJob], $chain->chain);
    }

    public function testChainCanBePrepended()
    {
        $chain = Bus::chain();

        $chain->prepend($firstJob = new JobChainingNamedTestJob('j1'));
        $chain->prepend($secondJob = new JobChainingNamedTestJob('j2'));
        $chain->prepend($thirdJob = new JobChainingNamedTestJob('j3'));

        $this->assertEquals($thirdJob, $chain->job);
        $this->assertEquals([$secondJob, $firstJob], $chain->chain);
    }

    public function testChainCanBePrependedWithInitialJob()
    {
        $chain = Bus::chain([
            $firstJob = new JobChainingNamedTestJob('j4'),
        ]);

        $chain->prepend([
            $secondJob = new JobChainingNamedTestJob('j1'),
            $thirdJob = new JobChainingNamedTestJob('j2'),
            $fourthJob = new JobChainingNamedTestJob('j3'),
        ]);

        $this->assertEquals($secondJob, $chain->job);
        $this->assertEquals([$thirdJob, $fourthJob, $firstJob], $chain->chain);
    }

    public function testChainPrependRemovesFalsy()
    {
        $chain = Bus::chain([
            $firstJob = new JobChainingNamedTestJob('j4'),
        ]);

        $chain->prepend([
            $secondJob = new JobChainingNamedTestJob('j1'),
            $thirdJob = new JobChainingNamedTestJob('j2'),
            $fourthJob = new JobChainingNamedTestJob('j3'),
            null,
            '',
            0,
            [],
        ]);

        $this->assertEquals($secondJob, $chain->job);
        $this->assertEquals([$thirdJob, $fourthJob, $firstJob], $chain->chain);
    }

    public function testBatchCanBeAddedToChain()
    {
        Bus::chain([
            new JobChainingNamedTestJob('c1'),
            new JobChainingNamedTestJob('c2'),
            Bus::batch([
                new JobChainingBatchedJob('b1'),
                new JobChainingBatchedJob('b2'),
                new JobChainingBatchedJob('b3'),
                new JobChainingBatchedJob('b4'),
            ]),
            new JobChainingNamedTestJob('c3'),
        ])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertEquals(['c1', 'c2', 'b1', 'b2', 'b3', 'b4', 'c3'], JobRunRecorder::$results);
    }

    public function testBatchInChainUsesCorrectQueue()
    {
        $otherQueue = $this->getQueueDriver() === 'redis' ? '{other}' : 'other';
        Bus::chain([
            (new JobChainingNamedTestJob('c1'))->onQueue($otherQueue),
            (new JobChainingNamedTestJob('c2'))->onQueue($otherQueue),
            Bus::batch([
                new JobChainingBatchedJob('b1'),
                new JobChainingBatchedJob('b2'),
                new JobChainingBatchedJob('b3'),
                new JobChainingBatchedJob('b4'),
            ])->onQueue($otherQueue),
            (new JobChainingNamedTestJob('c3'))->onQueue($otherQueue),
        ])->dispatch();

        $this->runQueueWorkerCommand(['--queue' => $otherQueue, '--stop-when-empty' => true]);

        $this->assertEquals(['c1', 'c2', 'b1', 'b2', 'b3', 'b4', 'c3'], JobRunRecorder::$results);
    }

    public function testDynamicBatchCanBeAddedToChain()
    {
        Bus::chain([
            new JobChainingNamedTestJob('c1'),
            new JobChainingNamedTestJob('c2'),
            Bus::batch([
                new JobChainingBatchedJob('b1'),
                new JobChainingBatchedJob('b2', times: 4),
                new JobChainingBatchedJob('b3'),
                new JobChainingBatchedJob('b4'),
            ]),
            new JobChainingNamedTestJob('c3'),
        ])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        if ($this->getQueueDriver() === 'sync') {
            $this->assertEquals(
                ['c1', 'c2', 'b1', 'b2-0', 'b2-1', 'b2-2', 'b2-3', 'b2', 'b3', 'b4', 'c3'], JobRunRecorder::$results
            );
        } else {
            $this->assertEquals(
                ['c1', 'c2', 'b1', 'b2', 'b3', 'b4', 'b2-0', 'b2-1', 'b2-2', 'b2-3', 'c3'], JobRunRecorder::$results
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
                new JobChainingBatchedJob('b1'),
                new JobChainingBatchedJob('b2', times: 4),
                new JobChainingBatchedJob('b3'),
                new JobChainingBatchedJob('b4'),
            ]),
            new JobChainingNamedTestJob('c3'),
        ])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        if ($this->getQueueDriver() === 'sync') {
            $this->assertEquals(
                ['c1', 'c2', 'bc1', 'bc2', 'b1', 'b2-0', 'b2-1', 'b2-2', 'b2-3', 'b2', 'b3', 'b4', 'c3'], JobRunRecorder::$results
            );
        } else {
            $this->assertEquals(
                ['c1', 'c2', 'bc1', 'b1', 'b2', 'b3', 'b4', 'bc2', 'b2-0', 'b2-1', 'b2-2', 'b2-3', 'c3'], JobRunRecorder::$results
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
                        new JobChainingBatchedJob('bb1'),
                        new JobChainingBatchedJob('bb2'),
                    ]),
                ],
                new JobChainingBatchedJob('b1'),
                new JobChainingBatchedJob('b2', times: 4),
                new JobChainingBatchedJob('b3'),
                new JobChainingBatchedJob('b4'),
            ]),
            new JobChainingNamedTestJob('c3'),
        ])->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        if ($this->getQueueDriver() === 'sync') {
            $this->assertEquals(
                ['c1', 'c2', 'bc1', 'bc2', 'bb1', 'bb2', 'b1', 'b2-0', 'b2-1', 'b2-2', 'b2-3', 'b2', 'b3', 'b4', 'c3'], JobRunRecorder::$results
            );
        } else {
            $this->assertEquals(
                ['c1', 'c2', 'bc1', 'b1', 'b2', 'b3', 'b4', 'bc2', 'b2-0', 'b2-1', 'b2-2', 'b2-3', 'bb1', 'bb2', 'c3'], JobRunRecorder::$results
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
                new JobChainingFailingBatchedJob('fb1'),
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
                new JobChainingBatchedJob('b1'),
                new JobChainingFailingBatchedJob('b2'),
                new JobChainingBatchedJob('b3'),
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
                new JobChainingBatchedJob('b1'),
                new JobChainingFailingBatchedJob('b2'),
                new JobChainingBatchedJob('b3'),
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

        $this->assertSame('sync2', $chain->connection);

        $chain = Bus::chain([])
            ->onConnection('sync1')
            ->when(false, function (PendingChain $chain) {
                $chain->onConnection('sync2');
            });

        $this->assertSame('sync1', $chain->connection);
    }

    public function testBatchConditionable()
    {
        $batch = Bus::batch([])
            ->onConnection('sync1')
            ->when(true, function (PendingBatch $batch) {
                $batch->onConnection('sync2');
            });

        $this->assertSame('sync2', $batch->connection());
        $batch = Bus::batch([])
            ->onConnection('sync1')
            ->when(false, function (PendingBatch $batch) {
                $batch->onConnection('sync2');
            });

        $this->assertSame('sync1', $batch->connection());
    }

    public function testJobsAreChainedWhenDispatchIfIsTrue()
    {
        JobChainingFirstJob::withChain([
            new JobChainingSecondJob,
        ])->dispatchIf(true);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingFirstJob::$ran);
        $this->assertTrue(JobChainingSecondJob::$ran);
    }

    public function testJobsAreNotChainedWhenDispatchIfIsFalse()
    {
        JobChainingFirstJob::withChain([
            new JobChainingSecondJob,
        ])->dispatchIf(false);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(JobChainingFirstJob::$ran);
        $this->assertFalse(JobChainingSecondJob::$ran);
    }

    public function testJobsAreChainedWhenDispatchUnlessIsFalse()
    {
        JobChainingFirstJob::withChain([
            new JobChainingSecondJob,
        ])->dispatchUnless(false);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertTrue(JobChainingFirstJob::$ran);
        $this->assertTrue(JobChainingSecondJob::$ran);
    }

    public function testJobsAreNotChainedWhenDispatchUnlessIsTrue()
    {
        JobChainingFirstJob::withChain([
            new JobChainingSecondJob,
        ])->dispatchUnless(true);

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertFalse(JobChainingFirstJob::$ran);
        $this->assertFalse(JobChainingSecondJob::$ran);
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
