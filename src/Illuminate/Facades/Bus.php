<?php

namespace Illuminate\Facades;

/**
 * @see \Illuminate\Contracts\Bus\Dispatcher
 */
class Bus extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Illuminate\Contracts\Bus\Dispatcher';
    }
}
