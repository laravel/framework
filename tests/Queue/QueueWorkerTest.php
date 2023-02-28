<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as QueueJobContract;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobPopped;
use Illuminate\Queue\Events\JobPopping;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Carbon;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class QueueWorkerTest extends TestCase
{
    public $events;
    public $exceptionHandler;
    public $maintenanceFlags;

    protected function setUp(): void
    {
        $this->events = m::spy(Dispatcher::class);
        $this->exceptionHandler = m::spy(ExceptionHandler::class);

        Container::setInstance($container = new Container);

        $container->instance(Dispatcher::class, $this->events);
        $container->instance(ExceptionHandler::class, $this->exceptionHandler);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();

        Container::setInstance();
    }

    public function testJobCanBeFired()
    {
        $worker = $this->getWorker('default', ['queue' => [$job = new WorkerFakeJob]]);
        $worker->runNextJob('default', 'queue', new WorkerOptions);
        $this->assertTrue($job->fired);
        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobPopping::class))->once();
        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobPopped::class))->once();
        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobProcessing::class))->once();
        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobProcessed::class))->once();
    }

    public function testWorkerCanWorkUntilQueueIsEmpty()
    {
        $workerOptions = new WorkerOptions;
        $workerOptions->stopWhenEmpty = true;

        $worker = $this->getWorker('default', ['queue' => [
            $firstJob = new WorkerFakeJob,
            $secondJob = new WorkerFakeJob,
        ]]);

        $status = $worker->daemon('default', 'queue', $workerOptions);

        $this->assertTrue($secondJob->fired);

        $this->assertSame(0, $status);

        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobProcessing::class))->twice();

        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobProcessed::class))->twice();
    }

    public function testWorkerStopsWhenMemoryExceeded()
    {
        $workerOptions = new WorkerOptions;

        $worker = $this->getWorker('default', ['queue' => [
            $firstJob = new WorkerFakeJob,
            $secondJob = new WorkerFakeJob,
        ]]);
        $worker->stopOnMemoryExceeded = true;

        $status = $worker->daemon('default', 'queue', $workerOptions);

        $this->assertTrue($firstJob->fired);
        $this->assertFalse($secondJob->fired);
        $this->assertSame(12, $status);

        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobProcessing::class))->once();

        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobProcessed::class))->once();
    }

    public function testJobCanBeFiredBasedOnPriority()
    {
        $worker = $this->getWorker('default', [
            'high' => [$highJob = new WorkerFakeJob, $secondHighJob = new WorkerFakeJob], 'low' => [$lowJob = new WorkerFakeJob],
        ]);

        $worker->runNextJob('default', 'high,low', new WorkerOptions);
        $this->assertTrue($highJob->fired);
        $this->assertFalse($secondHighJob->fired);
        $this->assertFalse($lowJob->fired);

        $worker->runNextJob('default', 'high,low', new WorkerOptions);
        $this->assertTrue($secondHighJob->fired);
        $this->assertFalse($lowJob->fired);

        $worker->runNextJob('default', 'high,low', new WorkerOptions);
        $this->assertTrue($lowJob->fired);
    }

    public function testExceptionIsReportedIfConnectionThrowsExceptionOnJobPop()
    {
        $worker = new InsomniacWorker(
            new WorkerFakeManager('default', new BrokenQueueConnection('default', $e = new RuntimeException)),
            $this->events,
            $this->exceptionHandler,
            function () {
                return false;
            }
        );

        $worker->runNextJob('default', 'queue', $this->workerOptions());

        $this->exceptionHandler->shouldHaveReceived('report')->with($e);
    }

    public function testWorkerSleepsWhenQueueIsEmpty()
    {
        $worker = $this->getWorker('default', ['queue' => []]);
        $worker->runNextJob('default', 'queue', $this->workerOptions(['sleep' => 5]));
        $this->assertEquals(5, $worker->sleptFor);
    }

    public function testJobIsReleasedOnException()
    {
        $e = new RuntimeException;

        $job = new WorkerFakeJob(function () use ($e) {
            throw $e;
        });

        $worker = $this->getWorker('default', ['queue' => [$job]]);
        $worker->runNextJob('default', 'queue', $this->workerOptions(['backoff' => 10]));

        $this->assertEquals(10, $job->releaseAfter);
        $this->assertFalse($job->deleted);
        $this->exceptionHandler->shouldHaveReceived('report')->with($e);
        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobExceptionOccurred::class))->once();
        $this->events->shouldNotHaveReceived('dispatch', [m::type(JobProcessed::class)]);
    }

    public function testJobIsNotReleasedIfItHasExceededMaxAttempts()
    {
        $e = new RuntimeException;

        $job = new WorkerFakeJob(function ($job) use ($e) {
            // In normal use this would be incremented by being popped off the queue
            $job->attempts++;

            throw $e;
        });
        $job->attempts = 1;

        $worker = $this->getWorker('default', ['queue' => [$job]]);
        $worker->runNextJob('default', 'queue', $this->workerOptions(['maxTries' => 1]));

        $this->assertNull($job->releaseAfter);
        $this->assertTrue($job->deleted);
        $this->assertEquals($e, $job->failedWith);
        $this->exceptionHandler->shouldHaveReceived('report')->with($e);
        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobExceptionOccurred::class))->once();
        $this->events->shouldNotHaveReceived('dispatch', [m::type(JobProcessed::class)]);
    }

    public function testJobIsNotReleasedIfItHasExpired()
    {
        $e = new RuntimeException;

        $job = new WorkerFakeJob(function ($job) use ($e) {
            // In normal use this would be incremented by being popped off the queue
            $job->attempts++;

            throw $e;
        });

        $job->retryUntil = now()->addSeconds(1)->getTimestamp();

        $job->attempts = 0;

        Carbon::setTestNow(
            Carbon::now()->addSeconds(1)
        );

        $worker = $this->getWorker('default', ['queue' => [$job]]);
        $worker->runNextJob('default', 'queue', $this->workerOptions());

        $this->assertNull($job->releaseAfter);
        $this->assertTrue($job->deleted);
        $this->assertEquals($e, $job->failedWith);
        $this->exceptionHandler->shouldHaveReceived('report')->with($e);
        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobExceptionOccurred::class))->once();
        $this->events->shouldNotHaveReceived('dispatch', [m::type(JobProcessed::class)]);
    }

    public function testJobIsFailedIfItHasAlreadyExceededMaxAttempts()
    {
        $job = new WorkerFakeJob(function ($job) {
            $job->attempts++;
        });

        $job->attempts = 2;

        $worker = $this->getWorker('default', ['queue' => [$job]]);
        $worker->runNextJob('default', 'queue', $this->workerOptions(['maxTries' => 1]));

        $this->assertNull($job->releaseAfter);
        $this->assertTrue($job->deleted);
        $this->assertInstanceOf(MaxAttemptsExceededException::class, $job->failedWith);
        $this->exceptionHandler->shouldHaveReceived('report')->with(m::type(MaxAttemptsExceededException::class));
        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobExceptionOccurred::class))->once();
        $this->events->shouldNotHaveReceived('dispatch', [m::type(JobProcessed::class)]);
    }

    public function testJobIsFailedIfItHasAlreadyExpired()
    {
        $job = new WorkerFakeJob(function ($job) {
            $job->attempts++;
        });

        $job->retryUntil = Carbon::now()->addSeconds(2)->getTimestamp();

        $job->attempts = 1;

        Carbon::setTestNow(
            Carbon::now()->addSeconds(3)
        );

        $worker = $this->getWorker('default', ['queue' => [$job]]);
        $worker->runNextJob('default', 'queue', $this->workerOptions());

        $this->assertNull($job->releaseAfter);
        $this->assertTrue($job->deleted);
        $this->assertInstanceOf(MaxAttemptsExceededException::class, $job->failedWith);
        $this->exceptionHandler->shouldHaveReceived('report')->with(m::type(MaxAttemptsExceededException::class));
        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobExceptionOccurred::class))->once();
        $this->events->shouldNotHaveReceived('dispatch', [m::type(JobProcessed::class)]);
    }

    public function testJobBasedMaxRetries()
    {
        $job = new WorkerFakeJob(function ($job) {
            $job->attempts++;
        });
        $job->attempts = 2;

        $job->maxTries = 10;

        $worker = $this->getWorker('default', ['queue' => [$job]]);
        $worker->runNextJob('default', 'queue', $this->workerOptions(['maxTries' => 1]));

        $this->assertFalse($job->deleted);
        $this->assertNull($job->failedWith);
    }

    public function testJobBasedFailedDelay()
    {
        $job = new WorkerFakeJob(function ($job) {
            throw new Exception('Something went wrong.');
        });

        $job->attempts = 1;
        $job->backoff = 10;

        $worker = $this->getWorker('default', ['queue' => [$job]]);
        $worker->runNextJob('default', 'queue', $this->workerOptions(['backoff' => 3, 'maxTries' => 0]));

        $this->assertEquals(10, $job->releaseAfter);
    }

    public function testJobRunsIfAppIsNotInMaintenanceMode()
    {
        $firstJob = new WorkerFakeJob(function ($job) {
            $job->attempts++;
        });

        $secondJob = new WorkerFakeJob(function ($job) {
            $job->attempts++;
        });

        $this->maintenanceFlags = [false, true];

        $maintenanceModeChecker = function () {
            if ($this->maintenanceFlags) {
                return array_shift($this->maintenanceFlags);
            }

            throw new LoopBreakerException;
        };

        $worker = $this->getWorker('default', ['queue' => [$firstJob, $secondJob]], $maintenanceModeChecker);

        try {
            $worker->daemon('default', 'queue', $this->workerOptions());

            $this->fail('Expected LoopBreakerException to be thrown');
        } catch (LoopBreakerException) {
            $this->assertSame(1, $firstJob->attempts);

            $this->assertSame(0, $secondJob->attempts);
        }
    }

    public function testJobDoesNotFireIfDeleted()
    {
        $job = new WorkerFakeJob(function () {
            return true;
        });

        $worker = $this->getWorker('default', ['queue' => [$job]]);
        $job->delete();
        $worker->runNextJob('default', 'queue', $this->workerOptions());

        $this->events->shouldHaveReceived('dispatch')->with(m::type(JobProcessed::class))->once();
        $this->assertFalse($job->hasFailed());
        $this->assertFalse($job->isReleased());
        $this->assertTrue($job->isDeleted());
    }

    public function testWorkerPicksJobUsingCustomCallbacks()
    {
        $worker = $this->getWorker('default', [
            'default' => [$defaultJob = new WorkerFakeJob], 'custom' => [$customJob = new WorkerFakeJob],
        ]);

        $worker->runNextJob('default', 'default', new WorkerOptions);
        $worker->runNextJob('default', 'default', new WorkerOptions);

        $this->assertTrue($defaultJob->fired);
        $this->assertFalse($customJob->fired);

        $worker2 = $this->getWorker('default', [
            'default' => [$defaultJob = new WorkerFakeJob], 'custom' => [$customJob = new WorkerFakeJob],
        ]);

        $worker2->setName('myworker');

        Worker::popUsing('myworker', function ($pop) {
            return $pop('custom');
        });

        $worker2->runNextJob('default', 'default', new WorkerOptions);
        $worker2->runNextJob('default', 'default', new WorkerOptions);

        $this->assertFalse($defaultJob->fired);
        $this->assertTrue($customJob->fired);

        Worker::popUsing('myworker', null);
    }

    /**
     * Helpers...
     */
    private function getWorker($connectionName = 'default', $jobs = [], ?callable $isInMaintenanceMode = null)
    {
        return new InsomniacWorker(
            ...$this->workerDependencies($connectionName, $jobs, $isInMaintenanceMode)
        );
    }

    private function workerDependencies($connectionName = 'default', $jobs = [], ?callable $isInMaintenanceMode = null)
    {
        return [
            new WorkerFakeManager($connectionName, new WorkerFakeConnection($connectionName, $jobs)),
            $this->events,
            $this->exceptionHandler,
            $isInMaintenanceMode ?? function () {
                return false;
            },
        ];
    }

    private function workerOptions(array $overrides = [])
    {
        $options = new WorkerOptions;

        foreach ($overrides as $key => $value) {
            $options->{$key} = $value;
        }

        return $options;
    }
}

/**
 * Fakes.
 */
class InsomniacWorker extends Worker
{
    public $sleptFor;
    public $stopOnMemoryExceeded = false;

    public function sleep($seconds)
    {
        $this->sleptFor = $seconds;
    }

    public function stop($status = 0, $options = null)
    {
        return $status;
    }

    public function daemonShouldRun(WorkerOptions $options, $connectionName, $queue)
    {
        return ! ($this->isDownForMaintenance)();
    }

    public function memoryExceeded($memoryLimit)
    {
        return $this->stopOnMemoryExceeded;
    }
}

class WorkerFakeManager extends QueueManager
{
    public $connections = [];

    public function __construct($name, $connection)
    {
        $this->connections[$name] = $connection;
    }

    public function connection($name = null)
    {
        return $this->connections[$name];
    }
}

class WorkerFakeConnection
{
    public $connectionName;
    public $jobs = [];

    public function __construct($connectionName, $jobs)
    {
        $this->connectionName = $connectionName;
        $this->jobs = $jobs;
    }

    public function pop($queue)
    {
        return array_shift($this->jobs[$queue]);
    }

    public function getConnectionName()
    {
        return $this->connectionName;
    }
}

class BrokenQueueConnection
{
    public $connectionName;
    public $exception;

    public function __construct($connectionName, $exception)
    {
        $this->connectionName = $connectionName;
        $this->exception = $exception;
    }

    public function pop($queue)
    {
        throw $this->exception;
    }

    public function getConnectionName()
    {
        return $this->connectionName;
    }
}

class WorkerFakeJob implements QueueJobContract
{
    public $id = '';
    public $fired = false;
    public $callback;
    public $deleted = false;
    public $releaseAfter;
    public $released = false;
    public $maxTries;
    public $maxExceptions;
    public $shouldFailOnTimeout = false;
    public $uuid;
    public $backoff;
    public $retryUntil;
    public $attempts = 0;
    public $failedWith;
    public $failed = false;
    public $connectionName = '';
    public $queue = '';
    public $rawBody = '';

    public function __construct($callback = null)
    {
        $this->callback = $callback ?: function () {
            //
        };
    }

    public function getJobId()
    {
        return $this->id;
    }

    public function fire()
    {
        $this->fired = true;
        $this->callback->__invoke($this);
    }

    public function payload()
    {
        return [];
    }

    public function maxTries()
    {
        return $this->maxTries;
    }

    public function maxExceptions()
    {
        return $this->maxExceptions;
    }

    public function shouldFailOnTimeout()
    {
        return $this->shouldFailOnTimeout;
    }

    public function uuid()
    {
        return $this->uuid;
    }

    public function backoff()
    {
        return $this->backoff;
    }

    public function retryUntil()
    {
        return $this->retryUntil;
    }

    public function delete()
    {
        $this->deleted = true;
    }

    public function isDeleted()
    {
        return $this->deleted;
    }

    public function release($delay = 0)
    {
        $this->released = true;

        $this->releaseAfter = $delay;
    }

    public function isReleased()
    {
        return $this->released;
    }

    public function isDeletedOrReleased()
    {
        return $this->deleted || $this->released;
    }

    public function attempts()
    {
        return $this->attempts;
    }

    public function markAsFailed()
    {
        $this->failed = true;
    }

    public function fail($e = null)
    {
        $this->markAsFailed();

        $this->delete();

        $this->failedWith = $e;
    }

    public function hasFailed()
    {
        return $this->failed;
    }

    public function getName()
    {
        return 'WorkerFakeJob';
    }

    public function resolveName()
    {
        return $this->getName();
    }

    public function getConnectionName()
    {
        return $this->connectionName;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function getRawBody()
    {
        return $this->rawBody;
    }

    public function timeout()
    {
        return time() + 60;
    }
}

class LoopBreakerException extends RuntimeException
{
    //
}
