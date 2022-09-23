<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Foundation\Benchmark\PendingBenchmark repeat(int $times)
 * @method static never measure(iterable|\Closure $callables)
 *
 * @see \Illuminate\Foundation\Benchmark\Factory
 */
class Benchmark extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Illuminate\Foundation\Benchmark\Factory::class;
    }
}
