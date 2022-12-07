<?php

namespace Illuminate\Console\Process;

use ArrayAccess;
use InvalidArgumentException;

class Pool implements ArrayAccess
{
    /**
     * The process factory instance.
     *
     * @var \Illuminate\Console\Process\Factory
     */
    protected $factory;

    /**
     * The callback that resolves the pending processes.
     *
     * @var callable
     */
    protected $callback;

    /**
     * The array of pending processes.
     *
     * @var array
     */
    protected $pendingProcesses = [];

    /**
     * The array of invoked processes.
     *
     * @var array
     */
    protected $invokedProcesses = [];

    /**
     * Indicates if the process pool has been started.
     *
     * @var bool
     */
    protected $started = false;

    /**
     * Indicates if the process pool has been resolved.
     *
     * @var bool
     */
    protected $resolved = false;

    /**
     * The results of the processes.
     *
     * @var array
     */
    protected $results = [];

    /**
     * Create a new process pool.
     *
     * @param  \Illuminate\Console\Process\Factory  $factory
     * @param  callable  $callback
     * @return void
     */
    public function __construct(Factory $factory, $callback)
    {
        $this->factory = $factory;
        $this->callback = $callback;
    }

    /**
     * Add a process to the pool with a key.
     *
     * @param  string  $key
     * @return \Illuminate\Console\Process\PendingProcess
     */
    public function as(string $key)
    {
        return tap($this->factory->newPendingProcess(), function ($pendingProcess) use ($key) {
            $this->pendingProcesses[$key] = $pendingProcess;
        });
    }

    /**
     * Start all of the processes in the pool.
     *
     * @return $this
     */
    public function start()
    {
        if ($this->started) {
            return;
        }

        call_user_func($this->callback, $this);

        $this->invokedProcesses = collect($this->pendingProcesses)
            ->each(function ($pendingProcess) {
                if (! $pendingProcess instanceof PendingProcess) {
                    throw new InvalidArgumentException("Process pool must only contain pending processes.");
                }
            })->mapWithKeys(function ($pendingProcess, $key) {
                return [$key => $pendingProcess->start()];
            })->all();

        $this->started = true;

        return $this;
    }

    /**
     * Send a signal to each running process in the pool, returning the processes that were signalled.
     *
     * @param  int  $signal
     * @return \Illuminate\Support\Collection
     */
    public function signal(int $signal)
    {
        return $this->running()->each->signal($signal);
    }

    /**
     * Get the processes in the pool that are still currently running.
     *
     * @return \Illuminate\Support\Collection
     */
    public function running()
    {
        return collect($this->invokedProcesses)->filter->running()->values();
    }

    /**
     * Start and wait for the processes to finish.
     *
     * @param  callable|null  $output
     * @return array
     */
    public function wait($output = null)
    {
        if ($this->resolved) {
            return $this->results;
        }

        $this->start();

        return tap(collect($this->invokedProcesses)->map->wait()->all(), function ($results) {
            $this->results = $results;

            $this->resolved = true;
        });
    }

    /**
     * Determine if the given array offset exists.
     *
     * @param  int  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        $this->wait();

        return isset($this->results[$offset]);
    }

    /**
     * Get the result at the given offset.
     *
     * @param  int  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        $this->wait();

        return $this->results[$offset];
    }

    /**
     * Set the result at the given offset.
     *
     * @param  int  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->wait();

        $this->results[$offset] = $value;
    }

    /**
     * Unset the result at the given offset.
     *
     * @param  int  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $this->wait();

        unset($this->results[$offset]);
    }

    /**
     * Dynamically proxy methods calls to a new pending process.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Illuminate\Console\Process\PendingProcess
     */
    public function __call($method, $parameters)
    {
        return tap($this->factory->{$method}(...$parameters), function ($pendingProcess) {
            $this->pendingProcesses[] = $pendingProcess;
        });
    }
}
