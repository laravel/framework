<?php

namespace Illuminate\Console\Process;

/**
 * @mixin \Illuminate\Console\Process\PendingProcess
 */
class Pool
{
    /**
     * The process factory instance.
     *
     * @var \Illuminate\Console\Process\Factory
     */
    protected $factory;

    /**
     * Create a new Process Pool.
     *
     * @param  \Illuminate\Console\Process\Factory  $factory
     * @return void
     */
    public function __construct($factory)
    {
        $this->factory = $factory;
    }

    /**
     * Execute the given method against a new Async Pending Process instance.
     *
     * @param  string  $method
     * @param  iterable<int, mixed>  $parameters
     * @return \Illuminate\Console\Process\PendingProcess|\Illuminate\Console\Contracts\ProcessResult
     */
    public function __call($method, $parameters)
    {
        return $this->factory->async()->$method(...$parameters);
    }
}
