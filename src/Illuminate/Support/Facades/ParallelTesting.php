<?php

namespace Illuminate\Support\Facades;

/**
 * @method static void setupProcess(callable $callback)
 * @method static void setupTestCase(callable $callback)
 * @method static void setupTestDatabase(callable $callback)
 * @method static void tearDownProcess(callable $callback)
 * @method static void tearDownTestCase(callable $callback)
 * @method static int|false token()
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
