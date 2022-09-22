<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Support\Benchmark repeat(int $times)
 * @method static \Illuminate\Support\Benchmark measure(iterable|callable $callables)
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
