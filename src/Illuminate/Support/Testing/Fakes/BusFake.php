<?php

namespace Illuminate\Support\Testing\Fakes;

use Closure;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Assert as PHPUnit;

class BusFake implements Dispatcher
{
    /**
     * The original Bus dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Bus\Dispatcher
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
     * Create a new bus fake instance.
     *
     * @param  \Illuminate\Contracts\Bus\Dispatcher  $dispatcher
     * @param  array|string  $jobsToFake
     * @return void
     */
    public function __construct(Dispatcher $dispatcher, $jobsToFake = [])
    {
        $this->dispatcher = $dispatcher;

        $this->jobsToFake = Arr::wrap($jobsToFake);
    }

    /**
     * Assert if a job was dispatched based on a truth-test callback.
     *
     * @param  string  $command
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertDispatched($command, $callback = null)
    {
        if (is_numeric($callback)) {
            return $this->assertDispatchedTimes($command, $callback);
        }

        PHPUnit::assertTrue(
            $this->dispatched($command, $callback)->count() > 0,
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
        PHPUnit::assertTrue(
            ($count = $this->dispatched($command)->count()) === $times,
            "The expected [{$command}] job was pushed {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if a job was dispatched based on a truth-test callback.
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotDispatched($command, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->dispatched($command, $callback)->count() === 0,
            "The unexpected [{$command}] job was dispatched."
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
     * Determine if an command should be faked or actually dispatched.
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
