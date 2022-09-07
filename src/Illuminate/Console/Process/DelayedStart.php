<?php

namespace Illuminate\Console\Process;

/**
 * @mixin \Illuminate\Console\Contracts\ProcessResult
 */
class DelayedStart
{
    /**
     * The callback that will start the process.
     *
     * @var callable(): \Illuminate\Console\Contracts\ProcessResult
     */
    protected $callback;

    /**
     * Creates a new Process Result instance.
     *
     * @param  callable(): \Illuminate\Console\Contracts\ProcessResult  $callback
     * @return void
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /**
     * Starts the process, and returns the result.
     *
     * @return \Illuminate\Console\Contracts\ProcessResult
     */
    public function start()
    {
        return value($this->callback);
    }

    /**
     * Execute a method against the process's result instance.
     *
     * @param  string  $method
     * @param  iterable<array-key, string>  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->start()->{$method}(...$parameters);
    }
}
