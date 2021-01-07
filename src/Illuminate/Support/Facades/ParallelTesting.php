<?php

namespace Illuminate\Support\Facades;

/**
 * @method static void beforeProcessDestroyed(callable $callback)
 * @method static void setUp(callable $callback)
 * @method static string token()
 *
 * @see \Illuminate\Testing\ParallelTesting
 */
class ParallelTesting extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Illuminate\Testing\ParallelTesting::class;
    }
}
