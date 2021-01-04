<?php

namespace Illuminate\Support\Facades;

/**
 * @method static string addTokenIfNeeded(string $string)
 * @method static void whenRunningInParallel(callable $callback)
 *
 * @see \Illuminate\Testing\Testing
 */
class Testing extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Illuminate\Testing\Testing::class;
    }
}
