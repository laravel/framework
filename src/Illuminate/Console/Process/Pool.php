<?php

namespace Illuminate\Console\Process;

/**
 * @mixin \Illuminate\Console\Process\Factory
 */
class Pool
{
    /**
     * The factory instance.
     *
     * @var \Illuminate\Console\Process\Factory
     */
    protected $factory;

    /**
     * Create a new requests pool.
     *
     * @param  \Illuminate\Console\Process\Factory  $factory
     * @return void
     */
    public function __construct($factory)
    {
        $this->factory = $factory;
    }

    /**
     * Add a process to the pool with a numeric index.
     *
     * @param  string  $method
     * @param  iterable<int, mixed>  $parameters
     * @return \Illuminate\Console\Process\PendingProcess
     */
    public function __call($method, $parameters)
    {
        return $this->factory->delayStart()->$method(...$parameters);
    }
}
