<?php

namespace Illuminate\Support\Facades;


/**
 * @method static \Illuminate\Support\Timebox make(callable $callback, int $microseconds): mixed
 *
 * @see \Illuminate\Support\Timebox
 */
class Timebox extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Illuminate\Support\Timebox::class;
    }
}
