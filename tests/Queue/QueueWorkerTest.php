<?php

namespace Illuminate\Tests\Queue;

use Mockery;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\MaxAttemptsExceededException;

class QueueWorkerTest extends TestCase
{
    public $events;
    public $exceptionHandler;

    public function setUp()
    {
        $this->events = Mockery::spy(Dispatcher::class);
        $this->exceptionHandler = Mockery::spy(ExceptionHandler::class);

        Container::setInstance($container = new Container);

        $container->instance(Dispatcher::class, $this->events);
        $container->instance(ExceptionHandler::class, $this->exceptionHandler);
    }

    public function tearDown()
    {
        Container::setInstance();
    }

    public function test_job_can_be_fired()
    {
        $worker = $this->getWorker('default', ['queue' => [$job = new WorkerFakeJob]]);
        $worker->runNextJob('default', 'queue', new WorkerOptions);
        $this->assertTrue($job->fired);
        $this->events->shouldHaveReceived('fire')->with(Mockery::type(JobProcessing::class))->once();
        $this->events->shouldHaveReceived('fire')->with(Mockery::type(JobProcessed::class))->once();
    }

    public function test_job_can_be_fired_based_on_priority()
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

    public function test_exception_is_reported_if_connection_throws_exception_on_job_pop()
    {
        $worker = new InsomniacWorker(
            new WorkerFakeManager('default', new BrokenQueueConnection($e = new RuntimeException)),
            $this->events,
            $this->exceptionHandler
        );

        $worker->runNextJob('default', 'queue', $this->workerOptions());

        $this->exceptionHandler->shouldHaveReceived('report')->with($e);
    }

    public function test_worker_sleeps_when_queue_is_empty()
    {
        $worker = $this->getWorker('default', ['queue' => []]);
        $worker->runNextJob('default', 'queue', $this->workerOptions(['sleep' => 5]));
        $this->assertEquals(5, $worker->sleptFor);
    }

    public function test_job_is_released_on_exception()
    {
        $e = new RuntimeException;

        $job = new WorkerFakeJob(function () use ($e) {
            throw $e;
        });

        $worker = $this->getWorker('default', ['queue' => [$job]]);
        $worker->runNextJob('default', 'queue', $this->workerOptions(['delay' => 10]));

        $this->assertEquals(10, $job->releaseAfter);
        $this->assertFalse($job->deleted);
        $this->exceptionHandler->shouldHaveReceived('report')->with($e);
        $this->events->shouldHaveReceived('fire')->with(Mockery::type(JobExceptionOccurred::class))->once();
        $this->events->shouldNotHaveReceived('fire', [Mockery::type(JobProcessed::class)]);
    }

    public function test_job_is_not_released_if_it_has_exceeded_max_attempts()
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
        $this->events->shouldHaveReceived('fire')->with(Mockery::type(JobExceptionOccurred::class))->once();
        $this->events->shouldHaveReceived('fire')->with(Mockery::type(JobFailed::class))->once();
        $this->events->shouldNotHaveReceived('fire', [Mockery::type(JobProcessed::class)]);
    }

    public function test_job_is_failed_if_it_has_already_exceeded_max_attempts()
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
        $this->exceptionHandler->shouldHaveReceived('report')->with(Mockery::type(MaxAttemptsExceededException::class));
        $this->events->shouldHaveReceived('fire')->with(Mockery::type(JobExceptionOccurred::class))->once();
        $this->events->shouldHaveReceived('fire')->with(Mockery::type(JobFailed::class))->once();
        $this->events->shouldNotHaveReceived('fire', [Mockery::type(JobProcessed::class)]);
    }

    public function test_job_based_max_retries()
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

    /**
     * Helpers...
     */
    private function getWorker($connectionName = 'default', $jobs = [])
    {
        return new InsomniacWorker(
            ...$this->workerDependencies($connectionName, $jobs)
        );
    }

    private function workerDependencies($connectionName = 'default', $jobs = [])
    {
        return [
            new WorkerFakeManager($connectionName, new WorkerFakeConnection($jobs)),
            $this->events,
            $this->exceptionHandler,
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
class InsomniacWorker extends \Illuminate\Queue\Worker
{
    public $sleptFor;

    public function sleep($seconds)
    {
        $this->sleptFor = $seconds;
    }
}

class WorkerFakeManager extends \Illuminate\Queue\QueueManager
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
    public $jobs = [];

    public function __construct($jobs)
    {
        $this->jobs = $jobs;
    }

    public function pop($queue)
    {
        return array_shift($this->jobs[$queue]);
    }
}

class BrokenQueueConnection
{
    public $exception;

    public function __construct($exception)
    {
        $this->exception = $exception;
    }

    public function pop($queue)
    {
        throw $this->exception;
    }
}

class WorkerFakeJob
{
    public $fired = false;
    public $callback;
    public $deleted = false;
    public $releaseAfter;
    public $released = false;
    public $maxTries;
    public $attempts = 0;
    public $failedWith;
    public $failed = false;
    public $connectionName;

    public function __construct($callback = null)
    {
        $this->callback = $callback ?: function () {
        };
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

    public function delete()
    {
        $this->deleted = true;
    }

    public function isDeleted()
    {
        return $this->deleted;
    }

    public function release($delay)
    {
        $this->released = true;

        $this->releaseAfter = $delay;
    }

    public function isReleased()
    {
        return $this->released;
    }

    public function attempts()
    {
        return $this->attempts;
    }

    public function markAsFailed()
    {
        $this->failed = true;
    }

    public function failed($e)
    {
        $this->markAsFailed();

        $this->failedWith = $e;
    }

    public function hasFailed()
    {
        return $this->failed;
    }

    public function setConnectionName($name)
    {
        $this->connectionName = $name;
    }
}
