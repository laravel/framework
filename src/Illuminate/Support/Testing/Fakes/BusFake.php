<?php

namespace Illuminate\Support\Testing\Fakes;

use Closure;
use Illuminate\Bus\PendingBatch;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ReflectsClosures;
use PHPUnit\Framework\Assert as PHPUnit;

class BusFake implements QueueingDispatcher
{
    use ReflectsClosures;

    /**
     * The original Bus dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Bus\QueueingDispatcher
     */
    protected $dispatcher;

    /**
     * The job types that should be intercepted instead of dispatched.
     *
     * @var array
     */
    protected $jobsToFake;

    /**
     * The commands that have been dispatched.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * The commands that have been dispatched synchronously.
     *
     * @var array
     */
    protected $commandsSync = [];

    /**
     * The commands that have been dispatched after the response has been sent.
     *
     * @var array
     */
    protected $commandsAfterResponse = [];

    /**
     * The batches that have been dispatched.
     *
     * @var array
     */
    protected $batches = [];

    /**
     * Create a new bus fake instance.
     *
     * @param  \Illuminate\Contracts\Bus\QueueingDispatcher  $dispatcher
     * @param  array|string  $jobsToFake
     * @return void
     */
    public function __construct(QueueingDispatcher $dispatcher, $jobsToFake = [])
    {
        $this->dispatcher = $dispatcher;

        $this->jobsToFake = Arr::wrap($jobsToFake);
    }

    /**
     * Assert if a job was dispatched based on a truth-test callback.
     *
     * @param  string|\Closure  $command
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertDispatched($command, $callback = null)
    {
        if ($command instanceof Closure) {
            [$command, $callback] = [$this->firstClosureParameterType($command), $command];
        }

        if (is_numeric($callback)) {
            return $this->assertDispatchedTimes($command, $callback);
        }

        PHPUnit::assertTrue(
            $this->dispatched($command, $callback)->count() > 0 ||
            $this->dispatchedAfterResponse($command, $callback)->count() > 0 ||
            $this->dispatchedSync($command, $callback)->count() > 0,
            "The expected [{$command}] job was not dispatched."
        );
    }

    /**
     * Assert if a job was pushed a number of times.
     *
     * @param  string  $command
     * @param  int  $times
     * @return void
     */
    public function assertDispatchedTimes($command, $times = 1)
    {
        $count = $this->dispatched($command)->count() +
                 $this->dispatchedAfterResponse($command)->count() +
                 $this->dispatchedSync($command)->count();

        PHPUnit::assertSame(
            $times, $count,
            "The expected [{$command}] job was pushed {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if a job was dispatched based on a truth-test callback.
     *
     * @param  string|\Closure  $command
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotDispatched($command, $callback = null)
    {
        if ($command instanceof Closure) {
            [$command, $callback] = [$this->firstClosureParameterType($command), $command];
        }

        PHPUnit::assertTrue(
            $this->dispatched($command, $callback)->count() === 0 &&
            $this->dispatchedAfterResponse($command, $callback)->count() === 0 &&
            $this->dispatchedSync($command, $callback)->count() === 0,
            "The unexpected [{$command}] job was dispatched."
        );
    }

    /**
     * Assert that no jobs were dispatched.
     *
     * @return void
     */
    public function assertNothingDispatched()
    {
        PHPUnit::assertEmpty($this->commands, 'Jobs were dispatched unexpectedly.');
    }

    /**
     * Assert if a job was explicitly dispatched synchronously based on a truth-test callback.
     *
     * @param  string|\Closure  $command
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertDispatchedSync($command, $callback = null)
    {
        if ($command instanceof Closure) {
            [$command, $callback] = [$this->firstClosureParameterType($command), $command];
        }

        if (is_numeric($callback)) {
            return $this->assertDispatchedSyncTimes($command, $callback);
        }

        PHPUnit::assertTrue(
            $this->dispatchedSync($command, $callback)->count() > 0,
            "The expected [{$command}] job was not dispatched synchronously."
        );
    }

    /**
     * Assert if a job was pushed synchronously a number of times.
     *
     * @param  string  $command
     * @param  int  $times
     * @return void
     */
    public function assertDispatchedSyncTimes($command, $times = 1)
    {
        $count = $this->dispatchedSync($command)->count();

        PHPUnit::assertSame(
            $times, $count,
            "The expected [{$command}] job was synchronously pushed {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if a job was dispatched based on a truth-test callback.
     *
     * @param  string|\Closure  $command
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotDispatchedSync($command, $callback = null)
    {
        if ($command instanceof Closure) {
            [$command, $callback] = [$this->firstClosureParameterType($command), $command];
        }

        PHPUnit::assertCount(
            0, $this->dispatchedSync($command, $callback),
            "The unexpected [{$command}] job was dispatched synchronously."
        );
    }

    /**
     * Assert if a job was dispatched after the response was sent based on a truth-test callback.
     *
     * @param  string|\Closure  $command
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertDispatchedAfterResponse($command, $callback = null)
    {
        if ($command instanceof Closure) {
            [$command, $callback] = [$this->firstClosureParameterType($command), $command];
        }

        if (is_numeric($callback)) {
            return $this->assertDispatchedAfterResponseTimes($command, $callback);
        }

        PHPUnit::assertTrue(
            $this->dispatchedAfterResponse($command, $callback)->count() > 0,
            "The expected [{$command}] job was not dispatched for after sending the response."
        );
    }

    /**
     * Assert if a job was pushed after the response was sent a number of times.
     *
     * @param  string  $command
     * @param  int  $times
     * @return void
     */
    public function assertDispatchedAfterResponseTimes($command, $times = 1)
    {
        $count = $this->dispatchedAfterResponse($command)->count();

        PHPUnit::assertSame(
            $times, $count,
            "The expected [{$command}] job was pushed {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if a job was dispatched based on a truth-test callback.
     *
     * @param  string|\Closure  $command
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotDispatchedAfterResponse($command, $callback = null)
    {
        if ($command instanceof Closure) {
            [$command, $callback] = [$this->firstClosureParameterType($command), $command];
        }

        PHPUnit::assertCount(
            0, $this->dispatchedAfterResponse($command, $callback),
            "The unexpected [{$command}] job was dispatched for after sending the response."
        );
    }

    /**
     * Assert if a chain of jobs was dispatched.
     *
     * @param  array  $expectedChain
     * @return void
     */
    public function assertChained(array $expectedChain)
    {
        $command = $expectedChain[0];

        $expectedChain = array_slice($expectedChain, 1);

        $callback = null;

        if ($command instanceof Closure) {
            [$command, $callback] = [$this->firstClosureParameterType($command), $command];
        } elseif (! is_string($command)) {
            $instance = $command;

            $command = get_class($instance);

            $callback = function ($job) use ($instance) {
                return serialize($this->resetChainPropertiesToDefaults($job)) === serialize($instance);
            };
        }

        PHPUnit::assertTrue(
            $this->dispatched($command, $callback)->isNotEmpty(),
            "The expected [{$command}] job was not dispatched."
        );

        PHPUnit::assertTrue(
            collect($expectedChain)->isNotEmpty(),
            'The expected chain can not be empty.'
        );

        $this->isChainOfObjects($expectedChain)
            ? $this->assertDispatchedWithChainOfObjects($command, $expectedChain, $callback)
            : $this->assertDispatchedWithChainOfClasses($command, $expectedChain, $callback);
    }

    /**
     * Reset the chain properties to their default values on the job.
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected function resetChainPropertiesToDefaults($job)
    {
        return tap(clone $job, function ($job) {
            $job->chainConnection = null;
            $job->chainQueue = null;
            $job->chainCatchCallbacks = null;
            $job->chained = [];
        });
    }

    /**
     * Assert if a job was dispatched with an empty chain based on a truth-test callback.
     *
     * @param  string|\Closure  $command
     * @param  callable|null  $callback
     * @return void
     */
    public function assertDispatchedWithoutChain($command, $callback = null)
    {
        if ($command instanceof Closure) {
            [$command, $callback] = [$this->firstClosureParameterType($command), $command];
        }

        PHPUnit::assertTrue(
            $this->dispatched($command, $callback)->isNotEmpty(),
            "The expected [{$command}] job was not dispatched."
        );

        $this->assertDispatchedWithChainOfClasses($command, [], $callback);
    }

    /**
     * Assert if a job was dispatched with chained jobs based on a truth-test callback.
     *
     * @param  string  $command
     * @param  array  $expectedChain
     * @param  callable|null  $callback
     * @return void
     */
    protected function assertDispatchedWithChainOfObjects($command, $expectedChain, $callback)
    {
        $chain = collect($expectedChain)->map(function ($job) {
            return serialize($job);
        })->all();

        PHPUnit::assertTrue(
            $this->dispatched($command, $callback)->filter(function ($job) use ($chain) {
                return $job->chained == $chain;
            })->isNotEmpty(),
            'The expected chain was not dispatched.'
        );
    }

    /**
     * Assert if a job was dispatched with chained jobs based on a truth-test callback.
     *
     * @param  string  $command
     * @param  array  $expectedChain
     * @param  callable|null  $callback
     * @return void
     */
    protected function assertDispatchedWithChainOfClasses($command, $expectedChain, $callback)
    {
        $matching = $this->dispatched($command, $callback)->map->chained->map(function ($chain) {
            return collect($chain)->map(function ($job) {
                return get_class(unserialize($job));
            });
        })->filter(function ($chain) use ($expectedChain) {
            return $chain->all() === $expectedChain;
        });

        PHPUnit::assertTrue(
            $matching->isNotEmpty(), 'The expected chain was not dispatched.'
        );
    }

    /**
     * Determine if the given chain is entirely composed of objects.
     *
     * @param  array  $chain
     * @return bool
     */
    protected function isChainOfObjects($chain)
    {
        return ! collect($chain)->contains(function ($job) {
            return ! is_object($job);
        });
    }

    /**
     * Assert if a batch was dispatched based on a truth-test callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public function assertBatched(callable $callback)
    {
        PHPUnit::assertTrue(
            $this->batched($callback)->count() > 0,
            'The expected batch was not dispatched.'
        );
    }

    /**
     * Assert the number of batches that have been dispatched.
     *
     * @param  int  $count
     * @return void
     */
    public function assertBatchCount($count)
    {
        PHPUnit::assertCount(
            $count, $this->batches,
        );
    }

    /**
     * Get all of the jobs matching a truth-test callback.
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function dispatched($command, $callback = null)
    {
        if (! $this->hasDispatched($command)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return collect($this->commands[$command])->filter(function ($command) use ($callback) {
            return $callback($command);
        });
    }

    /**
     * Get all of the jobs dispatched synchronously matching a truth-test callback.
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function dispatchedSync(string $command, $callback = null)
    {
        if (! $this->hasDispatchedSync($command)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return collect($this->commandsSync[$command])->filter(function ($command) use ($callback) {
            return $callback($command);
        });
    }

    /**
     * Get all of the jobs dispatched after the response was sent matching a truth-test callback.
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function dispatchedAfterResponse(string $command, $callback = null)
    {
        if (! $this->hasDispatchedAfterResponse($command)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return collect($this->commandsAfterResponse[$command])->filter(function ($command) use ($callback) {
            return $callback($command);
        });
    }

    /**
     * Get all of the pending batches matching a truth-test callback.
     *
     * @param  callable  $callback
     * @return \Illuminate\Support\Collection
     */
    public function batched(callable $callback)
    {
        if (empty($this->batches)) {
            return collect();
        }

        return collect($this->batches)->filter(function ($batch) use ($callback) {
            return $callback($batch);
        });
    }

    /**
     * Determine if there are any stored commands for a given class.
     *
     * @param  string  $command
     * @return bool
     */
    public function hasDispatched($command)
    {
        return isset($this->commands[$command]) && ! empty($this->commands[$command]);
    }

    /**
     * Determine if there are any stored commands for a given class.
     *
     * @param  string  $command
     * @return bool
     */
    public function hasDispatchedSync($command)
    {
        return isset($this->commandsSync[$command]) && ! empty($this->commandsSync[$command]);
    }

    /**
     * Determine if there are any stored commands for a given class.
     *
     * @param  string  $command
     * @return bool
     */
    public function hasDispatchedAfterResponse($command)
    {
        return isset($this->commandsAfterResponse[$command]) && ! empty($this->commandsAfterResponse[$command]);
    }

    /**
     * Dispatch a command to its appropriate handler.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch($command)
    {
        if ($this->shouldFakeJob($command)) {
            $this->commands[get_class($command)][] = $command;
        } else {
            return $this->dispatcher->dispatch($command);
        }
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * Queueable jobs will be dispatched to the "sync" queue.
     *
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchSync($command, $handler = null)
    {
        if ($this->shouldFakeJob($command)) {
            $this->commandsSync[get_class($command)][] = $command;
        } else {
            return $this->dispatcher->dispatchSync($command, $handler);
        }
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchNow($command, $handler = null)
    {
        if ($this->shouldFakeJob($command)) {
            $this->commands[get_class($command)][] = $command;
        } else {
            return $this->dispatcher->dispatchNow($command, $handler);
        }
    }

    /**
     * Dispatch a command to its appropriate handler behind a queue.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatchToQueue($command)
    {
        if ($this->shouldFakeJob($command)) {
            $this->commands[get_class($command)][] = $command;
        } else {
            return $this->dispatcher->dispatchToQueue($command);
        }
    }

    /**
     * Dispatch a command to its appropriate handler.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatchAfterResponse($command)
    {
        if ($this->shouldFakeJob($command)) {
            $this->commandsAfterResponse[get_class($command)][] = $command;
        } else {
            return $this->dispatcher->dispatch($command);
        }
    }

    /**
     * Create a new chain of queueable jobs.
     *
     * @param  \Illuminate\Support\Collection|array  $jobs
     * @return \Illuminate\Foundation\Bus\PendingChain
     */
    public function chain($jobs)
    {
        $jobs = Collection::wrap($jobs);

        return new PendingChainFake($this, $jobs->shift(), $jobs->toArray());
    }

    /**
     * Attempt to find the batch with the given ID.
     *
     * @param  string  $batchId
     * @return \Illuminate\Bus\Batch|null
     */
    public function findBatch(string $batchId)
    {
        //
    }

    /**
     * Create a new batch of queueable jobs.
     *
     * @param  \Illuminate\Support\Collection|array  $jobs
     * @return \Illuminate\Bus\PendingBatch
     */
    public function batch($jobs)
    {
        return new PendingBatchFake($this, Collection::wrap($jobs));
    }

    /**
     * Record the fake pending batch dispatch.
     *
     * @param  \Illuminate\Bus\PendingBatch  $pendingBatch
     * @return \Illuminate\Bus\Batch
     */
    public function recordPendingBatch(PendingBatch $pendingBatch)
    {
        $this->batches[] = $pendingBatch;

        return (new BatchRepositoryFake)->store($pendingBatch);
    }

    /**
     * Determine if a command should be faked or actually dispatched.
     *
     * @param  mixed  $command
     * @return bool
     */
    protected function shouldFakeJob($command)
    {
        if (empty($this->jobsToFake)) {
            return true;
        }

        return collect($this->jobsToFake)
            ->filter(function ($job) use ($command) {
                return $job instanceof Closure
                            ? $job($command)
                            : $job === get_class($command);
            })->isNotEmpty();
    }

    /**
     * Set the pipes commands should be piped through before dispatching.
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes)
    {
        $this->dispatcher->pipeThrough($pipes);

        return $this;
    }

    /**
     * Determine if the given command has a handler.
     *
     * @param  mixed  $command
     * @return bool
     */
    public function hasCommandHandler($command)
    {
        return $this->dispatcher->hasCommandHandler($command);
    }

    /**
     * Retrieve the handler for a command.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function getCommandHandler($command)
    {
        return $this->dispatcher->getCommandHandler($command);
    }

    /**
     * Map a command to a handler.
     *
     * @param  array  $map
     * @return $this
     */
    public function map(array $map)
    {
        $this->dispatcher->map($map);

        return $this;
    }
}
