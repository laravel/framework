<?php

namespace Illuminate\Support\Testing\Fakes;

use BadMethodCallException;
use Closure;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ReflectsClosures;
use PHPUnit\Framework\Assert as PHPUnit;

class QueueFake extends QueueManager implements Fake, Queue
{
    use ReflectsClosures;

    /**
     * The original queue manager.
     *
     * @var \Illuminate\Contracts\Queue\Queue
     */
    public $queue;

    /**
     * The job types that should be intercepted instead of pushed to the queue.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $jobsToFake;

    /**
     * The job types that should be pushed to the queue and not intercepted.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $jobsToBeQueued;

    /**
     * All of the jobs that have been pushed.
     *
     * @var array
     */
    protected $jobs = [];

    /**
     * Indicates if items should be serialized and restored when pushed to the queue.
     *
     * @var bool
     */
    protected bool $serializeAndRestore = false;

    /**
     * Create a new fake queue instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  array  $jobsToFake
     * @param  \Illuminate\Queue\QueueManager|null  $queue
     * @return void
     */
    public function __construct($app, $jobsToFake = [], $queue = null)
    {
        parent::__construct($app);

        $this->jobsToFake = Collection::wrap($jobsToFake);
        $this->jobsToBeQueued = Collection::make();
        $this->queue = $queue;
    }

    /**
     * Specify the jobs that should be queued instead of faked.
     *
     * @param  array|string  $jobsToBeQueued
     * @return $this
     */
    public function except($jobsToBeQueued)
    {
        $this->jobsToBeQueued = Collection::wrap($jobsToBeQueued)->merge($this->jobsToBeQueued);

        return $this;
    }

    /**
     * Assert if a job was pushed based on a truth-test callback.
     *
     * @param  string|\Closure  $job
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertPushed($job, $callback = null)
    {
        if ($job instanceof Closure) {
            [$job, $callback] = [$this->firstClosureParameterType($job), $job];
        }

        if (is_numeric($callback)) {
            return $this->assertPushedTimes($job, $callback);
        }

        PHPUnit::assertTrue(
            $this->pushed($job, $callback)->count() > 0,
            "The expected [{$job}] job was not pushed."
        );
    }

    /**
     * Assert if a job was pushed a number of times.
     *
     * @param  string  $job
     * @param  int  $times
     * @return void
     */
    protected function assertPushedTimes($job, $times = 1)
    {
        $count = $this->pushed($job)->count();

        PHPUnit::assertSame(
            $times, $count,
            "The expected [{$job}] job was pushed {$count} times instead of {$times} times."
        );
    }

    /**
     * Assert if a job was pushed based on a truth-test callback.
     *
     * @param  string  $queue
     * @param  string|\Closure  $job
     * @param  callable|null  $callback
     * @return void
     */
    public function assertPushedOn($queue, $job, $callback = null)
    {
        if ($job instanceof Closure) {
            [$job, $callback] = [$this->firstClosureParameterType($job), $job];
        }

        $this->assertPushed($job, function ($job, $pushedQueue) use ($callback, $queue) {
            if ($pushedQueue !== $queue) {
                return false;
            }

            return $callback ? $callback(...func_get_args()) : true;
        });
    }

    /**
     * Assert if a job was pushed with chained jobs based on a truth-test callback.
     *
     * @param  string  $job
     * @param  array  $expectedChain
     * @param  callable|null  $callback
     * @return void
     */
    public function assertPushedWithChain($job, $expectedChain = [], $callback = null)
    {
        PHPUnit::assertTrue(
            $this->pushed($job, $callback)->isNotEmpty(),
            "The expected [{$job}] job was not pushed."
        );

        PHPUnit::assertTrue(
            collect($expectedChain)->isNotEmpty(),
            'The expected chain can not be empty.'
        );

        $this->isChainOfObjects($expectedChain)
                ? $this->assertPushedWithChainOfObjects($job, $expectedChain, $callback)
                : $this->assertPushedWithChainOfClasses($job, $expectedChain, $callback);
    }

    /**
     * Assert if a job was pushed with an empty chain based on a truth-test callback.
     *
     * @param  string  $job
     * @param  callable|null  $callback
     * @return void
     */
    public function assertPushedWithoutChain($job, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->pushed($job, $callback)->isNotEmpty(),
            "The expected [{$job}] job was not pushed."
        );

        $this->assertPushedWithChainOfClasses($job, [], $callback);
    }

    /**
     * Assert if a job was pushed with chained jobs based on a truth-test callback.
     *
     * @param  string  $job
     * @param  array  $expectedChain
     * @param  callable|null  $callback
     * @return void
     */
    protected function assertPushedWithChainOfObjects($job, $expectedChain, $callback)
    {
        $chain = collect($expectedChain)->map(fn ($job) => serialize($job))->all();

        PHPUnit::assertTrue(
            $this->pushed($job, $callback)->filter(fn ($job) => $job->chained == $chain)->isNotEmpty(),
            'The expected chain was not pushed.'
        );
    }

    /**
     * Assert if a job was pushed with chained jobs based on a truth-test callback.
     *
     * @param  string  $job
     * @param  array  $expectedChain
     * @param  callable|null  $callback
     * @return void
     */
    protected function assertPushedWithChainOfClasses($job, $expectedChain, $callback)
    {
        $matching = $this->pushed($job, $callback)->map->chained->map(function ($chain) {
            return collect($chain)->map(function ($job) {
                return get_class(unserialize($job));
            });
        })->filter(function ($chain) use ($expectedChain) {
            return $chain->all() === $expectedChain;
        });

        PHPUnit::assertTrue(
            $matching->isNotEmpty(), 'The expected chain was not pushed.'
        );
    }

    /**
     * Assert if a closure was pushed based on a truth-test callback.
     *
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertClosurePushed($callback = null)
    {
        $this->assertPushed(CallQueuedClosure::class, $callback);
    }

    /**
     * Assert that a closure was not pushed based on a truth-test callback.
     *
     * @param  callable|null  $callback
     * @return void
     */
    public function assertClosureNotPushed($callback = null)
    {
        $this->assertNotPushed(CallQueuedClosure::class, $callback);
    }

    /**
     * Determine if the given chain is entirely composed of objects.
     *
     * @param  array  $chain
     * @return bool
     */
    protected function isChainOfObjects($chain)
    {
        return ! collect($chain)->contains(fn ($job) => ! is_object($job));
    }

    /**
     * Determine if a job was pushed based on a truth-test callback.
     *
     * @param  string|\Closure  $job
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotPushed($job, $callback = null)
    {
        if ($job instanceof Closure) {
            [$job, $callback] = [$this->firstClosureParameterType($job), $job];
        }

        PHPUnit::assertCount(
            0, $this->pushed($job, $callback),
            "The unexpected [{$job}] job was pushed."
        );
    }

    /**
     * Assert that no jobs were pushed.
     *
     * @return void
     */
    public function assertNothingPushed()
    {
        PHPUnit::assertEmpty($this->jobs, 'Jobs were pushed unexpectedly.');
    }

    /**
     * Get all of the jobs matching a truth-test callback.
     *
     * @param  string  $job
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function pushed($job, $callback = null)
    {
        if (! $this->hasPushed($job)) {
            return collect();
        }

        $callback = $callback ?: fn () => true;

        return collect($this->jobs[$job])->filter(
            fn ($data) => $callback($data['job'], $data['queue'], $data['data'])
        )->pluck('job');
    }

    /**
     * Determine if there are any stored jobs for a given class.
     *
     * @param  string  $job
     * @return bool
     */
    public function hasPushed($job)
    {
        return isset($this->jobs[$job]) && ! empty($this->jobs[$job]);
    }

    /**
     * Resolve a queue connection instance.
     *
     * @param  mixed  $value
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connection($value = null)
    {
        return $this;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return collect($this->jobs)->flatten(1)->filter(
            fn ($job) => $job['queue'] === $queue
        )->count();
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        if ($this->shouldFakeJob($job)) {
            if ($job instanceof Closure) {
                $job = CallQueuedClosure::create($job);
            }

            $this->jobs[is_object($job) ? get_class($job) : $job][] = [
                'job' =>  $this->serializeAndRestore ? $this->serializeAndRestoreJob($job) : $job,
                'queue' => $queue,
                'data' => $data,
            ];
        } else {
            is_object($job) && isset($job->connection)
                ? $this->queue->connection($job->connection)->push($job, $data, $queue)
                : $this->queue->push($job, $data, $queue);
        }
    }

    /**
     * Determine if a job should be faked or actually dispatched.
     *
     * @param  object  $job
     * @return bool
     */
    public function shouldFakeJob($job)
    {
        if ($this->shouldDispatchJob($job)) {
            return false;
        }

        if ($this->jobsToFake->isEmpty()) {
            return true;
        }

        return $this->jobsToFake->contains(
            fn ($jobToFake) => $job instanceof ((string) $jobToFake)
        );
    }

    /**
     * Determine if a job should be pushed to the queue instead of faked.
     *
     * @param  object  $job
     * @return bool
     */
    protected function shouldDispatchJob($job)
    {
        if ($this->jobsToBeQueued->isEmpty()) {
            return false;
        }

        return $this->jobsToBeQueued->contains(
            fn ($jobToQueue) => $job instanceof ((string) $jobToQueue)
        );
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        //
    }

    /**
     * Push a new job onto the queue after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $queue
     * @param  string|object  $job
     * @param  mixed  $data
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '')
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto a specific queue after (n) seconds.
     *
     * @param  string  $queue
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|object  $job
     * @param  mixed  $data
     * @return mixed
     */
    public function laterOn($queue, $delay, $job, $data = '')
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        //
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ($jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    /**
     * Get the jobs that have been pushed.
     *
     * @return array
     */
    public function pushedJobs()
    {
        return $this->jobs;
    }

    /**
     * Specify if jobs should be serialized and restored when being "pushed" to the queue.
     *
     * @param  bool  $serializeAndRestore
     * @return $this
     */
    public function serializeAndRestore(bool $serializeAndRestore = true)
    {
        $this->serializeAndRestore = $serializeAndRestore;

        return $this;
    }

    /**
     * Serialize and unserialize the job to simulate the queueing process.
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected function serializeAndRestoreJob($job)
    {
        return unserialize(serialize($job));
    }

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName()
    {
        //
    }

    /**
     * Set the connection name for the queue.
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName($name)
    {
        return $this;
    }

    /**
     * Override the QueueManager to prevent circular dependency.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}
